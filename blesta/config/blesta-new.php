<?php
/**
 * Blesta configuration settings
 *
 * @package blesta
 * @subpackage blesta.config
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

////////////////////////////////////////////////////////////////////////////////
// Debugging
////////////////////////////////////////////////////////////////////////////////
// PHP error_reporting. 0 to disable error reporting, -1 to to show all errors
// Consult php's documentation for additional options
Configure::errorReporting(0);
// Override minPHP's debugging setting. true to enable debugging, false to disable it
Configure::set('System.debug', false);

////////////////////////////////////////////////////////////////////////////////
// Database
////////////////////////////////////////////////////////////////////////////////
// Database connection information
Configure::set('Blesta.database_info', [
        'driver' => 'mysql',
        'host' => '{database_host}',
        'port' => '{database_port}',
        'database' => '{database_name}',
        'user' => '{database_user}',
        'pass' => '{database_password}',
        'persistent' => false,
        'charset_query' => "SET NAMES 'utf8'",
        'sqlmode_query' => "SET sql_mode='TRADITIONAL'",
        'options' => []
    ]
);
// Deadlocked transactions will reattempt at most this many times
Configure::set('Blesta.transaction_deadlock_reattempts', 5);
// The maximum number of records to fetch from the database (default 2147483647 or 2^31 - 1).
// This should match the max record count for tables in your storage engine
Configure::set('Blesta.max_records', 2147483647);

////////////////////////////////////////////////////////////////////////////////
// Pagination
////////////////////////////////////////////////////////////////////////////////
// Number of results to display per page
Configure::set('Blesta.results_per_page', 20);
// Set pagination settings
Configure::set('Blesta.pagination', [
    'show' => 'if_needed',
    'total_results' => 0,
    'pages_to_show' => 5,
    'results_per_page' => Configure::get('Blesta.results_per_page'),
    'uri' => WEBDIR,
    'uri_labels' => [
        'page' => 'p',
        'per_page' => 'pp'
    ],
    'navigation' => [
        'current' => [
            'link' => true,
            'attributes' => ['class' => 'active']
        ],
        'first' => [
            'show' => 'always'
        ],
        'prev' => [
            'show' => 'always'
        ],
        'next' => [
            'show' => 'always'
        ],
        'last' => [
            'show' => 'always',
            'attributes' => ['class' => 'next']
        ]
    ],
    'params' => []
]);
// Set pagination settings
Configure::set('Blesta.pagination_client', [
    'show' => 'if_needed',
    'total_results' => 0,
    'pages_to_show' => 5,
    'results_per_page' => Configure::get('Blesta.results_per_page'),
    'uri' => WEBDIR,
    'uri_labels' => [
        'page' => 'p',
        'per_page' => 'pp'
    ],
    'navigation' => [
        'surround' => [
            'attributes' => [
                'class' => 'pagination pagination-sm'
            ]
        ],
        'current' => [
            'link' => true,
            'attributes' => ['class' => 'page-item active']
        ],
        'first' => [
            'show' => 'always',
            'attributes' => ['class' => 'page-item']
        ],
        'prev' => [
            'show' => 'always',
            'attributes' => ['class' => 'page-item']
        ],
        'next' => [
            'show' => 'always',
            'attributes' => ['class' => 'page-item']
        ],
        'last' => [
            'show' => 'always',
            'attributes' => ['class' => 'page-item next']
        ],
        'numerical' => [
            'attributes' => ['class' => 'page-item']
        ]
    ],
    'params' => []
]);
// Configurations to override on pagination to help enabled AJAX
Configure::set('Blesta.pagination_ajax', [
    'merge_get' => false,
    'navigation' => [
        'current' => [
            'link_attributes' => ['class' => 'page-link ajax']
        ],
        'first' => [
            'link_attributes' => ['class' => 'page-link ajax']
        ],
        'prev' => [
            'link_attributes' => ['class' => 'page-link ajax']
        ],
        'next' => [
            'link_attributes' => ['class' => 'page-link ajax']
        ],
        'last' => [
            'link_attributes' => ['class' => 'page-link ajax']
        ],
        'numerical' => [
            'link_attributes' => ['class' => 'page-link ajax']
        ]
    ]
]);

////////////////////////////////////////////////////////////////////////////////
// Cron
////////////////////////////////////////////////////////////////////////////////
// Sets the memory limit during cron execution, null will not override memory limit
// Acceptable values are those allowed by init_set() for 'memory_limit' (e.g. "512M" = 512 MB)
Configure::set('Blesta.cron_memory_limit', null);
// The minimum time period (in minutes) after which a cron task may be rerun.
// THIS SHOULD BE SET TO NO LESS THAN THE SMALLEST INTERVAL CONFIGURED FOR AUTOMATION TASKS IN BLESTA
Configure::set('Blesta.cron_minimum_run_interval', 5);
// The time period (in minutes) after which an unfinished cron task will be considered stalled and the cron task may
// be run again
Configure::set('Blesta.cron_task_restart_limit', 360);

////////////////////////////////////////////////////////////////////////////////
// Session
////////////////////////////////////////////////////////////////////////////////
// Length of time (in seconds) that a session will be valid for
Configure::set('Blesta.session_ttl', 1800); // 30 minutes
// Length of time (in seconds) that a cookie will be valid for
Configure::set('Blesta.cookie_ttl', 604800); // 7 days
// Name to give the blesta session ID
Configure::set('Blesta.session_name', 'blesta_sid');
// Name to give the blesta cookie session ID
Configure::set('Blesta.cookie_name', 'blesta_csid');

////////////////////////////////////////////////////////////////////////////////
// Misc
////////////////////////////////////////////////////////////////////////////////
// Maximum number of js logic evaluations to make.  This is to prevent an infinite loop.
Configure::set('Blesta.max_config_option_logic_evaluations', 500);
// Number of minutes between intervals of the fullcalendar time
Configure::set('Blesta.calendar_time_interval', 15);
// Number of sticky notes to show before viewing more
Configure::set('Blesta.sticky_notes_to_show', 2);
// Maximum number of sticky notes to show
Configure::set('Blesta.sticky_notes_max', 10);
// Maximum number of days to allow invoice days before renewal to be set
Configure::set('Blesta.invoice_renewal_max_days', 60);
// Maximum number of days to allow auto debit days before due date to be set
Configure::set('Blesta.autodebit_before_due_max_days', 60);
// Maximum number of days to allow services to be unpaid and overdue before suspension
Configure::set('Blesta.suspend_services_after_due_max_days', 60);
// Maximum number of days to allow payment notices/reminders to be set
Configure::set('Blesta.payment_notices_max_days', 120);
// Maximum number of days that a quotation is valid by default
Configure::set('Blesta.quotation_valid_max_days', 60);
// Number of days in the past to retain cron logs
Configure::set('Blesta.cron_log_retention_days', 10);
// Whether or not to delete account access logs according to the cron log retention policy
Configure::set('Blesta.auto_delete_accountaccess_logs', false);
// Whether or not to delete client setting logs according to the cron log retention policy
Configure::set('Blesta.auto_delete_client_setting_logs', false);
// Whether or not to delete contact logs according to the cron log retention policy
Configure::set('Blesta.auto_delete_contact_logs', false);
// Whether or not to delete email logs according to the cron log retention policy
Configure::set('Blesta.auto_delete_email_logs', false);
// Whether or not to delete messenger logs according to the cron log retention policy
Configure::set('Blesta.auto_delete_messenger_logs', false);
// Whether or not to delete gateway logs according to the cron log retention policy
Configure::set('Blesta.auto_delete_gateway_logs', true);
// Whether or not to delete module logs according to the cron log retention policy
Configure::set('Blesta.auto_delete_module_logs', true);
// Whether or not to delete service logs according to the cron log retention policy
Configure::set('Blesta.auto_delete_service_logs', false);
// Whether or not to delete transaction logs according to the cron log retention policy
Configure::set('Blesta.auto_delete_transaction_logs', false);
// Whether or not to delete user logs according to the cron log retention policy
Configure::set('Blesta.auto_delete_user_logs', false);
// Length of time that a cached page will be served before being built again
Configure::set('Blesta.cache_length', '2 hours');
// Length of time that a reset password request will be valid for
Configure::set('Blesta.reset_password_ttl', '4 hours');
// The URL that gateway callback requests should be directed to
Configure::set('Blesta.gw_callback_url', 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 's' : '') . '://' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost') . WEBDIR . 'callback/gw/');
Configure::set('Blesta.mgw_callback_url', 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 's' : '') . '://' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost') . WEBDIR . 'callback/mgw/');
// The URL to the marketplace
Configure::set('Blesta.marketplace_url', 'http://marketplace.blesta.com/');
// Enables/Disables demo mode. Demo mode disables certain features
Configure::set('Blesta.demo_mode', false);
// Default password reset value. Set to true for improved security, false for more accurate error reporting
Configure::set('Blesta.default_password_reset_value', true);
// Default forgot username value. Set to true for improved security, false for more accurate error reporting
Configure::set('Blesta.default_forgot_username_value', true);
// Sets parser options DO NOT MODIFY
Configure::set('Blesta.parser_options', [
    'VARIABLE_START' => '{',
    'VARIABLE_END' => '}',
]);
// Sets various tags used for ID code replacement values throughout the app DO NOT MODIFY
Configure::set('Blesta.replacement_keys', [
    'clients' => ['ID_VALUE_TAG' => '{num}'],
    'invoices' => ['ID_VALUE_TAG' => '{num}'],
    'quotations' => ['ID_VALUE_TAG' => '{num}'],
    'packages' => ['ID_VALUE_TAG' => '{num}'],
    'services' => ['ID_VALUE_TAG' => '{num}']
]);
// When attempting to sort by an "id_code" pseudo field, will instead sort by the given array
// of values in the given order. If null, will sort "id_code" as a string by itself
Configure::set('Blesta.id_code_sort_mode', ['id_format', 'id_value']);
// The default view template for the admin interface
Configure::set('Blesta.default_admin_view_template', 'default');
// The default view template for the client interface
Configure::set('Blesta.default_client_view_template', 'bootstrap');

////////////////////////////////////////////////////////////////////////////////
// Email
////////////////////////////////////////////////////////////////////////////////
// The maximum number of messages to send before disconnecting/reconnecting to the mail server
Configure::set('Blesta.email_messages_per_connection', 100);
// The number of seconds to wait before reconnecting to the mail server
Configure::set('Blesta.email_reconnect_sleep', 5);

////////////////////////////////////////////////////////////////////////////////
// Encryption
////////////////////////////////////////////////////////////////////////////////
// Work-factor for password hashing algorithms (between 4 and 31)
Configure::set('Blesta.hash_work', 12);
// The maximum number of failed login attempts to permit from a given IP per hour
Configure::set('Blesta.max_failed_login_attempts', 10);
// Set to true to enable support for legacy passwords (plain md5). Set to false for improved security
Configure::set('Blesta.auth_legacy_passwords', false);
// The legacy password algorithm to use if legacy passwords are enabled
Configure::set('Blesta.auth_legacy_passwords_algo', 'md5');
// Enable/disable automatic CSRF token verification
Configure::set('Blesta.verify_csrf_token', true);
// Enable/disable curl SSL verification
Configure::set('Blesta.curl_verify_ssl', false);
// Bypasses automatic CSRF checking for a set of controllers and actions (eg. array('client_login::index'))
// CSRF checking is a security feature, BE SURE YOU KNOW WHAT YOU ARE DOING BEFORE SETTING THIS VALUE
Configure::set('Blesta.csrf_bypass', []);
// The value used to generate the 256-bit AES key using HMAC SHA-256
// NEVER MODIFY THIS VALUE OR ALL ENCRYPTED DATA WILL BE LOST!
Configure::set('Blesta.system_key', '{system_key}');
