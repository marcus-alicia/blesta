<?php
// Load the core config
$init = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'init.php';
include $init;

$error = null;
if (defined('HTACCESS') && HTACCESS) {
    // Error, "HTACCESS" is expected, but mod rewrite is not working
    $error = 'Mod rewrite is not enabled, or htaccess is not supported by this server.
    You must disable pretty URL support in Blesta by removing the .htaccess file.';
} else {
    header('Location: index.php/install/');
    exit();
}

$view_dir = rtrim(str_replace('install.php', '', WEBDIR), '/');
?><!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8" http-equiv="Content-Type" content="text/html;" />
        <link rel="shortcut icon" href="<?php echo $view_dir;?>/app/views/default/images/favicon.ico" />
        <link rel="stylesheet" href="<?php echo $view_dir;?>/app/views/default/css/application.min.css" />
        <title>Install Error</title>
    </head>
    <body>
        <div class="container">
            <div class="panel panel-default panel-simple">
                <div class="panel-heading">
                    <h3>Error</h3>
                </div>
                <div class="panel-body">
                    <div class="alert alert-danger">
                        <p><?php echo $error;?></p>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
