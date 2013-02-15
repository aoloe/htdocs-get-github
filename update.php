<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

function debug($label, $value) {
    echo("<p>$label<br /><pre>".htmlentities(print_r($value, 1))."</pre></p>");
}
// phpinfo();
// debug('server', $_SERVER);

define('GITHUBGET_CONFIG_PATH', 'config.json');


define('GITHUBGET_MODREWRITE_ENABLED', true);
// the following constants are useful for setup. they should all be set to false in production
define('GITHUBGET_STORE_NODOWNLOAD', true); // for setup purposes only
// the following constants are useful for testing. they should all be set to false in production
define('GITHUBGET_GITHUB_NOREQUEST', true); // for debugging purposes only
define('GITHUBGET_FORCE_UPDATE', true); // for debugging purposes only
define('GITHUBGET_STORE_NOUPDATE', false); // for debugging purposes only

if (is_file(GITHUBGET_CONFIG_PATH)) {
    $config = json_decode(file_get_contents(GITHUBGET_CONFIG_PATH), 1);
} else {
    header('Location: '.pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_DIRNAME).'/'.'install.php');
}
// debug('config', $config);

define('GITHUBGET_CONTENT_PATH', $config['data_path'].'content.json');
define('GITHUBGET_CACHE_PATH', $config['data_path'].'cache/');

if (!is_writable($config['data_path'])) {
    echo('<p class="warning">'.$config['data_path'].' is not writable.</p>');
}

?>
<html>
<head>
<title><?= $config['title'] ?></title>
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
    if (!array_key_exists($path, $directory)) {
        foreach (explode('/', $path) as $item) {
            $string .= $item.'/';
            if (!array_key_exists($string, $directory)) {
                $result = is_dir(GITHUBGET_CACHE_PATH.$string);
                $directory[$string] = $result;
            }
            
        }
    }
    return $result;
} // ensure_directory()

if (!GITHUBGET_GITHUB_NOREQUEST) {
    $content_github = get_content_from_github($config['github_url']);
    file_put_contents("content_github.json", $content_github);
} else {
    echo('<p class="warning">Requests are from the cache: queries to GitHub are disabled.</p>');
    $content_github = file_get_contents("content_github.json");
}
$content_github = json_decode($content_github, true);
// debug('content_github', $content_github);

$content = array();
if (!array_key_exists('force', $_REQUEST) && !GITHUBGET_FORCE_UPDATE) {
    if (file_exists(GITHUBGET_CONTENT_PATH)) {
        $content = file_get_contents(GITHUBGET_CONTENT_PATH);
        $content = json_decode($content, 1);
    }
    if (!is_array($content)) {
        $content = array();
    }
}
// debug('content', $content);


$list = array();

if (is_array($content_github)) {
    $changed = 0;
    foreach ($content_github as $item) {
        if ($item['type'] == 'file') {
            $id = $item['path'];
            if (!array_key_exists($id, $content)) {
                $content[$id] = array (
                    'path' => $item['path'],
                    'name' => $item['name'],
                    'id' => $id,
                    'raw_url' => $config['github_url_raw'].$item['path'],
                    'sha' => '',
                );
            }
            $dirname = pathinfo($item['path'], PATHINFO_DIRNAME); // TODO: remove 'content/' which is $config['github_path']
            if (ensure_directory($dirname)) {
                $content_item = $content[$id];
                // debug('content_item', $content_item);
                // debug('item', $item);
                if ($item['sha'] != $content_item['sha']) {
                    $changed++;
                    if (($config['max_items'] == 0) || ($changed <= $config['max_items'])) {
                        if (!GITHUBGET_STORE_NODOWNLOAD) {
                            $file = get_content_from_github($content_item['raw_url']);
                            file_put_contents(GITHUBGET_CACHE_PATH.$content_item['path'], $file);
                        }
                        // debug('file', $file);
                        $content_item['sha'] = $item['sha'];
                        $content[$id]['sha'] = $item['sha'];
                    }
                }
            }
        } // if file
    } // foreach
} // is_array($content_github)
// debug('content', $content);
if (!GITHUBGET_STORE_NOUPDATE) {
    // debug('GITHUBGET_CONTENT_PATH', GITHUBGET_CONTENT_PATH);
    if (!file_exists(GITHUBGET_CONTENT_PATH) || is_writable(GITHUBGET_CONTENT_PATH)) {
        $content_json = json_encode($content);
        // debug('content_json', $content_json);
        file_put_contents(GITHUBGET_CONTENT_PATH, json_encode($content));
    } else {
        echo('<p class="warning">Could not store content.json</p>');
    }
} else {
    echo("<p class=\"warning\">I'm not storing the content.json</p>\n");
}
// debug('directory', $directory);
foreach ($directory as $key => $value) {
    if (!$value) {
        echo("<p class=\"warning\">".GITHUBGET_CACHE_PATH.$key." is not writable</p>\n");
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
<p>You can now <a href="index.php">view your site</a>.</p>
</body>
</html>
