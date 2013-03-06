<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

if (!function_exists('json_encode')) {
    include_once('simplejson.php');
}
if (!function_exists('curl_init')) {
    include_once('mycurl.php');
}

function debug($label, $value) {
    echo("<p>$label<br /><pre>".htmlentities(print_r($value, 1))."</pre></p>");
}
// phpinfo();
// debug('server', $_SERVER);

define('GITHUBGET_CONFIG_PATH', 'config/');
define('GITHUBGET_CONFIG_FILE', GITHUBGET_CONFIG_PATH.'config.json');
define('GITHUBGET_CONTENT_FILE', GITHUBGET_CONFIG_PATH.'content.json');

// the following constants are useful for setup. they should all be set to false in production
define('GITHUBGET_STORE_NODOWNLOADLIMIT', true); // for setup purposes only
// the following constants are useful for testing. they should all be set to false in production
define('GITHUBGET_STORE_NODOWNLOAD', false); // for setup or debugging purposes only
define('GITHUBGET_GITHUB_NOREQUEST', false); // for debugging purposes only
define('GITHUBGET_FORCE_UPDATE', false); // for debugging purposes only
define('GITHUBGET_STORE_NOUPDATE', false); // for debugging purposes only

if (is_file(GITHUBGET_CONFIG_FILE)) {
    $config = json_decode(stripslashes(file_get_contents(GITHUBGET_CONFIG_FILE)), 1);
} else {
    header('Location: '.pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_DIRNAME).'/'.'install.php');
}
// debug('config', $config);

define('GITHUBGET_DATA_PATH', $config['data_path']);


if (!is_writable($config['data_path'])) {
    echo('<p class="warning">'.$config['data_path'].' is not writable.</p>');
}

?>
<html>
<head>
<title>Update from <?= $config['github_repository'] ?></title>
<style>
    .warning {background-color:yellow;}
</style>
</head>
<body>
<h1>Update from <?= $config['github_repository'] ?></h1>
<?php
if (file_exists('install.php')) {
    echo('<p class="warning">You should remove the <a href="install.php">install file</a>.</p>');
}

function get_content_from_github($url) {
    // debug('url', $url);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    // curl_setopt($ch, CURLOPT_HEADER, true);
    // curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
    // curl_setopt($ch, CURLOPT_VERBOSE, true);
    $content = curl_exec($ch);
    // debug('curl getinfo', curl_getinfo($ch));
    curl_close($ch);
    return $content;
}

$directory = array();
function ensure_directory($path) {
    global $directory;
    $result = true;
    $string = '';
    // debug('path', $path);
    if (!array_key_exists((string) $path, $directory)) {
        foreach (explode('/', $path) as $item) {
            $string .= $item.'/';
            if (!array_key_exists($string, $directory)) {
                $result = is_dir(GITHUBGET_DATA_PATH.$string);
                if (!$result) {
                    $result = mkdir(GITHUBGET_DATA_PATH.$string);
                }
                $directory[$string] = $result;
            }
            
        }
    }
    return $result;
} // ensure_directory()

/**
 * @param array $config
 * @param array $content
 * @param string $path
 * @param int $changed
 * @return the number of changed element
 */
function read_content($config, & $content, & $list_github, $path = "", $changed = 0) {

    // debug('config', $config);
    // debug('path', $path);

    if (!GITHUBGET_GITHUB_NOREQUEST) {
        $content_github = get_content_from_github($config['github_url'].($path == "" ? "" : "/".$path));
        file_put_contents(GITHUBGET_CONFIG_PATH."content_github".($path == "" ? "" : "_".strtr($path, '/', '_')).".json", $content_github);
    } else {
        echo('<p class="warning">Requests are from the cache: queries to GitHub are disabled.</p>');
        $content_github = file_get_contents(GITHUBGET_CONFIG_PATH."content_github".($path == "" ? "" : "_".$path).".json");
    }
    $content_github = json_decode($content_github, true);
    // debug('content_github', $content_github);

    $github_path_length = strlen($config['github_path']);

    if (is_array($content_github)) {
        $changed = 0;
        // debug('list_current', $list_current);
        // for removing $config['github_path']
        foreach ($content_github as $item) {
            if ($item['type'] == 'file') {
                $id = substr($item['path'], $github_path_length);
                $list_github[] = $id;
                // debug('id', $id);
                if (!array_key_exists($id, $content)) {
                    $content[$id] = array (
                        // remove $config['github_path']
                        'path_github' => $item['path'],
                        'path_data' => substr($item['path'], $github_path_length),
                        'name' => $item['name'],
                        'id' => $id,
                        'raw_url' => $config['github_url_raw'].$item['path'],
                        'sha' => '',
                    );
                }
                $content_item = $content[$id];
                // debug('content_item', $content_item);
                // debug('path_data', $content_item['path_data']);
                if (ensure_directory(dirname($content_item['path_data']))) {
                    // debug('item', $item);
                    if ($item['sha'] != $content_item['sha']) {
                        $changed++;
                        $downloaded = false;
                        if (($config['max_items'] == 0) || ($changed <= $config['max_items']) || GITHUBGET_STORE_NODOWNLOADLIMIT) {
                            if (!GITHUBGET_STORE_NODOWNLOAD) {
                                $file = get_content_from_github($content_item['raw_url']);
                                // if file contains a timeout error or another github message, ignore it
                                if (strpos($file, 'Hello future GitHubber!') === false) {
                                    if (!file_exists(GITHUBGET_DATA_PATH.$content_item['path_data']) || is_writable(GITHUBGET_DATA_PATH.$content_item['path_data'])) {
                                        file_put_contents(GITHUBGET_DATA_PATH.$content_item['path_data'], $file);
                                        $downloaded = true;
                                    }
                                }
                                // debug('file', $file);
                            }
                            if ($downloaded) {
                                $content_item['sha'] = $item['sha'];
                                $content[$id]['sha'] = $item['sha'];
                            }
                        }
                    }
                }
            } elseif ($item['type'] == 'dir') {
                $dirname = substr($item['path'], $github_path_length);
                if (ensure_directory($dirname)) {
                    $changed  = read_content($config, $content, $list_github, $dirname, $changed);
                }
            } // if file
        } // foreach
    } // is_array($content_github)

    return $changed;
} // read_content()

$content = null;
if (!array_key_exists('force', $_REQUEST) && !GITHUBGET_FORCE_UPDATE) {
    if (file_exists(GITHUBGET_CONTENT_FILE)) {
        $content = file_get_contents(GITHUBGET_CONTENT_FILE);
        $content = json_decode($content, 1);
    }
    if (!is_array($content)) {
        $content = array();
    }
}
// debug('content', $content);
if (empty($content)) {
    echo('<p class="warning">There is no previous content.</p>');
}

$list_data = array_keys($content);

$list_github = array();

$changed = read_content($config, $content, $list_github);

// debug('list_data', $list_data);
// debug('list_github', $list_github);

$list_deleted = array_diff($list_data, $list_github);
if (!empty($list_deleted)) {
    // debug('list_deleted', $list_deleted);
    foreach($list_deleted as $item) {
        // debug('content[deleted]', $content[$item]);
        if (array_key_exists($item, $content)) {
            if (!GITHUBGET_STORE_NODOWNLOAD ) {
                unlink(GITHUBGET_DATA_PATH.$content[$item]['path_data']);
            } else {
                echo('<p class="warning">'.$item.' has not been removed.</p>');
            }
            unset($content[$item]);
        }
    }
}

// debug('content', $content);
if (($config['max_items'] > 0) && ($changed > $config['max_items']) && !GITHUBGET_STORE_NODOWNLOADLIMIT) {
    echo('<p class="warning">Updated '.$config['max_items'].' items; '.($changed - $config['max_items']).' items still need an update.</p>');
} else {
    echo('<p>Updated '.$changed.' items.</p>');
}
if (!GITHUBGET_STORE_NOUPDATE) {
    if (!file_exists(GITHUBGET_CONTENT_FILE) || is_writable(GITHUBGET_CONTENT_FILE)) {
        $content_json = json_encode($content);
        // debug('content_json', $content_json);
        file_put_contents(GITHUBGET_CONTENT_FILE, json_encode($content));
    } else {
        echo('<p class="warning">Could not store content.json</p>');
    }
} else {
    echo("<p class=\"warning\">I'm not storing the content.json</p>\n");
}
// debug('directory', $directory);
foreach ($directory as $key => $value) {
    if (!$value) {
        echo("<p class=\"warning\">".GITHUBGET_DATA_PATH.$key." is not writable</p>\n");
    }
}

$rate_limit = json_decode(get_content_from_github("https://api.github.com/rate_limit"));
// debug('rate_limit', $rate_limit);

echo("<p>".$rate_limit->rate->remaining." hits remaining out of ".$rate_limit->rate->limit." for the next hour.</p>");

?>
<form method="post">
<input type="checkbox" name="force" value="yes" id="force_update" /> <label for="force_update">Force</label>
<input type="submit" value="&raquo;" />
</form>
</body>
</html>
