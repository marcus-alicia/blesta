<?php
/**
 * All routes may be defined here.  Routes have the following syntax:
 * Router::route($orig_uri, $mapped_uri);
 *
 * For example:
 * Router::route("foo/bar", "bar/foo");
 *
 * The above route maps the "foo" controller and "bar" method to the "bar"
 * controller and "foo" method.
 *
 * Each parenthesized regular expression in the first parameter can be used in
 * the second parameter by calling the $[numeric value] of that statement. For
 * example: $1, $2, ... $n.
 *
 * @package blesta.config
 */

/**
 * Admin panel directory name
 */
Configure::set('Route.admin', 'admin');
/**
 * Client panel directory name
 */
Configure::set('Route.client', 'client');

$admin_loc = Configure::get('Route.admin');
$client_loc = Configure::get('Route.client');

// Backward compatible non-merchant gateway callback URL (from Blesta 2.x). Requires .htaccess be enabled
Router::route('^callback.php', 'callback');

// Route all except the following to the CMS plugin (if the plugin exists)
if (file_exists(PLUGINDIR . 'cms')) {
    Router::route('^(?!' . $admin_loc . '|api|feed|callback|cron|dialog|404|uploads|download|' . $client_loc . '|install|order|plugin|widget|app)', '/cms/main/index/$1');
}

if (file_exists(PLUGINDIR . 'download_manager')) {
    Router::route('^download/(.+)', 'download_manager/client_main/static/$1');
}

// Admin routes
Router::route('^' . $admin_loc . '/(widget|plugin)/(.+)', '$2');
Router::route('^' . $admin_loc . '/settings/company/(.+)', 'admin_company_$1');
Router::route('^' . $admin_loc . '/settings/system/(.+)', 'admin_system_$1');
Router::route('^' . $admin_loc . '/tools/logs/(.+)', 'admin_tools/log$1');
Router::route('^' . $admin_loc . '/theme/(.+)$', 'admin_theme'); // Theme CSS controller
Router::route('^' . $admin_loc . '/(.+)', 'admin_$1');
Router::route('^' . $admin_loc . '/?$', 'admin_main'); // Default admin controller

// Client routes
Router::route('^' . $client_loc . '/(widget|plugin)/(.+)', '$2');
Router::route('^' . $client_loc . '/theme/(.+)$', 'client_theme'); // Theme CSS controller
Router::route('^' . $client_loc . '/(.+)', 'client_$1');
Router::route('^' . $client_loc . '/?$', 'client_main'); // Default client controller

// Send all API requests to Api::index
Router::route('^api/(.+)', 'api/index/$1');

// Send all Data Feed requests to Feed::index
Router::route('^feed/(.+)', 'feed/index/$1');

// Send all direct widget/plugin requests
Router::route('^(widget|plugin)/(.+)', '$2');

unset($admin_loc);
unset($client_loc);
