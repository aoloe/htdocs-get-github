<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

function debug($label, $value) {
    echo("<p>$label<br /><pre>".htmlentities(print_r($value, 1))."</pre></p>");
}
// phpinfo();
// debug('server', $_SERVER);

define('BLOG_CONFIG_PATH', 'config.json');
define('BLOG_HTTP_URL', sprintf('http://%s%s', $_SERVER['SERVER_NAME'], pathinfo($_SERVER['REQUEST_URI'], PATHINFO_DIRNAME)));

define('BLOG_MODREWRITE_ENABLED', true);
define('BLOG_GITHUB_NOREQUEST', true); // for debugging purposes only
define('BLOG_FORCE_UPDATE', true); // for debugging purposes only
define('BLOG_STORE_NOUPDATE', false); // for debugging purposes only

if (is_file(BLOG_CONFIG_PATH)) {
    $config = json_decode(file_get_contents(BLOG_CONFIG_PATH), 1);
} else {
    header('Location: '.pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_DIRNAME).'/'.'install.php');
}
debug('config', $config);
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

define('BLOG_CACHE_PATH', $config['data_path'].'cache/');

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
    foreach (explode('/', $path) as $item) {
        $string .= $item.'/';
        if (!array_key_exists($string, $directory)) {
            $result = is_dir(BLOG_CACHE_PATH.$string);
            $directory[$string] = $result;
        }
        
    }
    return $result;
} // ensure_directory()

$rate_limit = json_decode(get_content_from_github("https://api.github.com/rate_limit"));
// debug('rate_limit', $rate_limit);

echo("<p>".$rate_limit->rate->remaining." hits remaining out of ".$rate_limit->rate->limit." for the next hour.</p>");

if (!BLOG_GITHUB_NOREQUEST) {
    $content_github = get_content_from_github($config['github_url']);
    file_put_contents("content_github.json", $content_github);
} else {
    echo('<p class="warning">Requests are from the cache: queries to GitHub are disabled.</p>');
    $content_github = file_get_contents("content_github.json");
}
$content_github = json_decode($content_github);
debug('content_github', $content_github);

$content = array();
if (!array_key_exists('force', $_REQUEST) && !BLOG_FORCE_UPDATE) {
    if (file_exists(BLOG_CONTENT_PATH)) {
        $content = file_get_contents(BLOG_CONTENT_PATH);
        $content = json_decode($content, 1);
    }
    if (!is_array($content)) {
        $content = array();
    }
}
debug('content', $content);


$list = array();

if (is_array($content_github)) {
    $changed = 0;
    foreach ($content_github as $item) {
        if ($item->type == 'file') {
            $dirname = pathinfo($item->path, PATHINFO_DIRNAME); // TODO: remove 'content/' which is $config['github_path']
            $id = $item->path;
            if (ensure_directory($dirname)) {
                if (!array_key_exists($id, $content)) {
                    $content[$id] = array (
                        'path' => $item->path,
                        'name' => $item->name,
                        'id' => $id,
                        'raw_url' => $config['github_url_raw'].$item->path,
                        'sha' => '',
                    );

                }
            }
        } // if file
    } // foreach
} // is_array($content_github)
?>
<form method="post">
<input type="checkbox" name="force" value="yes" id="force_update" /> <label for="force_update">Force</label>
<input type="submit" value="&raquo;" />
</form>
<p>You can now <a href="index.php">view your site</a>.</p>
</body>
</html>
