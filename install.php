<?php

ini_set('display_errors', '1');
error_reporting(E_ALL);
// phpinfo();

if (!function_exists('json_encode')) {
    include_once('simplejson.php');
}

function debug($label, $value) {
    echo("<p>$label<br /><pre>".htmlentities(print_r($value, 1))."</pre></p>");
}

$field = array(
    'github_user' => 'GitHub user',
    'github_repository' => 'GitHub repository',
    'github_path' => 'GitHub path',
    'data_path' => 'Data path',
    'max_items' => 'Maximum number of items per batch',
);

$config = array_fill_keys(array_keys($field), '');
$config['data_path'] = 'data/';

define('GITHUBGET_CONFIG_PATH', 'config/');
define('GITHUBGET_CONFIG_FILE', GITHUBGET_CONFIG_PATH.'config.json');
define('GITHUBGET_CONTENT_FILE', GITHUBGET_CONFIG_PATH.'content.json');

// debug('_REQUEST', $_REQUEST);
if (array_key_exists('install', $_REQUEST)) {

    $error = array();

    foreach ($config as $key => $value) {
        if (array_key_exists($key, $_REQUEST)) {
            $config[$key] = $_REQUEST[$key];
        }
    }
    if ($config['data_path'] == '') {
        $config['data_path'] = 'data/';
    }
    $config['data_path'] = rtrim($config['data_path'], '/').'/';
    $config['github_repository'] = rtrim($config['github_repository'], '/');
    $config['github_path'] = rtrim($config['github_path'], '/');
    // GET /repos/:owner/:repo/contents/:path
    // https://api.github.com/repos/aoloe/htdocs-blog-xox/contents/text
    $config['github_url'] = sprintf("https://api.github.com/repos/%s/%s/contents/%s", $config['github_user'], $config['github_repository'], $config['github_path']);
    $config['github_url_raw'] = sprintf("https://raw.github.com/%s/%s/master/", $config['github_user'], $config['github_repository']);

    // debug('config', $config);

    foreach (array('github_user', 'github_repository') as $item) {
        if ($config[$item] == '') {
            $error[] = $field[$item].' is mandatory';
        }
    }

    if (empty($error)) {
        foreach (array(GITHUBGET_CONFIG_PATH, $config['data_path']) as $item) {
            if (!is_dir($item)) {
                if (!@mkdir($item, 0777)) {
                    $error[] = "the ".$item." directory does not exist and could not be created";
                }
            } elseif (!is_writable($item)) {
                $error[] = "the ".$item." directory is not writable";
            }
        }
        foreach (array(GITHUBGET_CONTENT_FILE, GITHUBGET_CONFIG_FILE) as $item) {
            if (!is_file($item)) {
                if (!@touch($item)) {
                    $error[] = "the ".$item." file does not exist and could not be created";
                }
            } else if (!is_writable($item)) {
                $error[] = "the ".$item." file is not writable";
            }
        }
    }
    if (empty($error)) {
        file_put_contents(GITHUBGET_CONFIG_FILE, json_encode($config));
    }
} else {
    if (is_readable(GITHUBGET_CONFIG_FILE)) {
        $config = json_decode(file_get_contents(GITHUBGET_CONFIG_FILE), 1);
    }
}

?>
<html>
<head>
<title>Install</title>
<style>
body {font-family:sans;}
label {display:block;}
input.input {width:400px;}
.error {color:darkred;}
</style>
</head>
<body>
<h1>Install</h1>
<?php
if (!empty($error)) {
    foreach ($error as $item) {
        echo('<p class="error">'.$item."</p>\n");
    }
}
?>
<?php if (empty($error) && $config['github_user'] != '') : ?>
<p>When you're finished with the configuration, you can <a href="update.php">pull the pages from your repository</a></p>
<?php endif; ?>
<form method="post">
<?php foreach($field as $key => $value) : ?>
<p><label for="<?= $key ?>"><?= $value ?></label><input type="text" name="<?= $key ?>" <?= !array_key_exists($key, $config) || $config[$key] == '' ? '' : ' value="'.$config[$key].'"' ?> id="<?= $key ?>" class="input" /></p>
<?php endforeach; ?>
<p><input type="submit" name="install" value="install" /></p>
</form>
</body>
</html>
