<?php
/**
 * This files pipes email messages into the system. This is configured to process
 * messages for only a single company ID. Clone this file and change the
 * $company_id variable below to add pipe support for additional companies.
 */
$company_id = 1;

try {
    include(
        dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
        . '..' . DIRECTORY_SEPARATOR . 'lib/init.php'
    );

    $_SERVER['REQUEST_URI'] = null;
    $argv = [
        '',
        'plugin/support_manager/ticket_pipe/index/' . $company_id
    ];
    unset($company_id);

    // Dispatch the Web request
    if (!empty($_SERVER['REQUEST_URI'])) {
        Dispatcher::dispatch($_SERVER['REQUEST_URI']);
    } else {
        // Dispatch the CLI request
        Dispatcher::dispatchCli($argv);
    }
} catch (Exception $e) {
    try {
        // Attempt to raise any error, gracefully
        Dispatcher::raiseError($e);
    } catch (Exception $e) {
        if (Configure::get('System.debug')) {
            echo $e->getMessage() . ' on line <strong>' . $e->getLine() .
                '</strong> in <strong>' . $e->getFile() . "</strong>\n" .
                '<br />Printing Stack Trace:<br />' . nl2br($e->getTraceAsString());
        } else {
            echo $e->getMessage();
        }
    }
}
