<?php
// Controller get functions

$lang['AdminPlugin.getactionlocations.nav_primary_client'] = 'Primary Nav (Client)';
$lang['AdminPlugin.getactionlocations.nav_primary_staff'] = 'Primary Nav (Staff)';
$lang['AdminPlugin.getactionlocations.nav_secondary_staff'] = 'Sub-Menu Nav (Staff)';
$lang['AdminPlugin.getactionlocations.action_staff_client'] = 'Client Profile Sidebar (Staff)';
$lang['AdminPlugin.getactionlocations.widget_staff_home'] = 'Widget - Dashboard (Staff)';
$lang['AdminPlugin.getactionlocations.widget_staff_client'] = 'Widget - Client Profile (Staff)';
$lang['AdminPlugin.getactionlocations.widget_client_home'] = 'Widget - Client Profile (Client)';
$lang['AdminPlugin.getactionlocations.widget_staff_billing'] = 'Widget - Billing Overview (Staff)';

$lang['AdminPlugin.getcardlevels.client'] = 'Client';
$lang['AdminPlugin.getcardlevels.staff'] = 'Staff';

$lang['AdminPlugin.getoptionalfunctions.tooltip_upgrade'] = 'Performs migration of data from $current_version (the current installed version) to the given file set version. Sets Input errors on failure, preventing the module from being upgraded.';
$lang['AdminPlugin.getoptionalfunctions.tooltip_getPermissions'] = 'Returns all permissions to be configured for this plugin (invoked after install(), upgrade(), and uninstall(), overwrites all existing permissions)';
$lang['AdminPlugin.getoptionalfunctions.tooltip_getPermissionGroups'] = 'Returns all permission groups to be configured for this plugin (invoked after install(), upgrade(), and uninstall(), overwrites all existing permission groups)';


// Page title
$lang['AdminPlugin.index.page_title'] = 'Extension Generator - %1$s'; // %1$s is the plugin name

$lang['AdminPlugin.index.boxtitle_extension_generator'] = 'Extension Generator - Plugin';


// Basic info page
$lang['AdminPlugin.basic.heading_basic'] = 'Basic Information';
$lang['AdminPlugin.basic.heading_authors'] = 'Authors';

$lang['AdminPlugin.basic.description'] = 'Description';
$lang['AdminPlugin.basic.logo'] = 'Logo';
$lang['AdminPlugin.basic.author_name'] = 'Author Name';
$lang['AdminPlugin.basic.author_url'] = 'Author URL';
$lang['AdminPlugin.basic.text_options'] = 'Options';
$lang['AdminPlugin.basic.author_row_add'] = 'Add Author';
$lang['AdminPlugin.basic.text_remove'] = 'Remove';

$lang['AdminPlugin.basic.tooltip_description'] = 'The description shown in the plugin listing';
$lang['AdminPlugin.basic.tooltip_logo'] = 'The logo displayed in the plugin listing. Images are not resized, so the ideal dimensions are 150x70';

$lang['AdminPlugin.basic.placeholder_author_name'] = 'e.g. Blesta';
$lang['AdminPlugin.basic.placeholder_author_url'] = 'e.g. https://blesta.com/';

$lang['AdminPlugin.basic.database'] = 'Next - Database Info';
$lang['AdminPlugin.basic.confirm'] = 'Next - Confirmation';


// Database info page
$lang['AdminPlugin.database.heading_database'] = 'Database Tables';
$lang['AdminPlugin.database.heading_more_info'] = 'More Info';

$lang['AdminPlugin.database.table_row_add'] = 'Add Database Table';
$lang['AdminPlugin.database.column_row_add'] = 'Add Column';

$lang['AdminPlugin.database.table_name'] = 'Table Name';
$lang['AdminPlugin.database.tooltip_table_name'] = 'The name of the table in the database (e.g. extention_table_name)';
$lang['AdminPlugin.database.placeholder_table_name'] = 'e.g. extention_table_name';

$lang['AdminPlugin.database.column_name'] = 'Column Name';
$lang['AdminPlugin.database.type'] = 'Type';
$lang['AdminPlugin.database.length'] = 'Length/Values';
$lang['AdminPlugin.database.default'] = 'Default';
$lang['AdminPlugin.database.nullable'] = 'Nullable';
$lang['AdminPlugin.database.primary'] = 'Primary Key';

$lang['AdminPlugin.database.tooltip_column_name'] = 'The name of the column in the database (e.g. column_name).';
$lang['AdminPlugin.database.tooltip_type'] = 'The type of the column in the database.';
$lang['AdminPlugin.database.tooltip_length'] = 'Sets the max number of characters in a field. If column type is "enum", please enter the values using this format: \'a\',\'b\',\'c\'...';
$lang['AdminPlugin.database.tooltip_default'] = 'The default value to insert for this column. Nullable fields with an empty default will default to NULL.';
$lang['AdminPlugin.database.tooltip_nullable'] = 'Whether or not to allow a null value for this column.';
$lang['AdminPlugin.database.tooltip_primary'] = 'Marks this column as the primary key for the column. Composite keys are not currently supportedm. If you wish to have a composite key or no primary key, see the install() method of the generated ***_plugin.php file.';
$lang['AdminPlugin.database.placeholder_column_name'] = 'e.g. column_name';
$lang['AdminPlugin.database.placeholder_length'] = "e.g. 64 or 'a','b','c'";

$lang['AdminPlugin.database.text_more_info'] = 'This page is used to create a schema for the database tables generated and managed by this plugin. Code will be generated for adding and removing these tables on install and uninstall. In addition, model files will be create for these tables with some basic functions.';
$lang['AdminPlugin.database.text_options'] = 'Options';
$lang['AdminPlugin.database.text_remove'] = 'Remove';

$lang['AdminPlugin.database.integrations'] = 'Next - Core Integrations';


// Core integration page
$lang['AdminPlugin.integrations.heading_integrations'] = 'Core Integrations';

$lang['AdminPlugin.integrations.action_row_add'] = 'Add Action';
$lang['AdminPlugin.integrations.event_row_add'] = 'Add Event Handler';
$lang['AdminPlugin.integrations.card_row_add'] = 'Add Client Card';

$lang['AdminPlugin.integrations.heading_actions'] = 'Actions';
$lang['AdminPlugin.integrations.heading_events'] = 'Event Handlers';
$lang['AdminPlugin.integrations.heading_cards'] = 'Client Cards';
$lang['AdminPlugin.integrations.heading_more_info'] = 'More Info';

$lang['AdminPlugin.integrations.location'] = 'Location';
$lang['AdminPlugin.integrations.controller'] = 'Controller';
$lang['AdminPlugin.integrations.action'] = 'Action';
$lang['AdminPlugin.integrations.name'] = 'Name';
$lang['AdminPlugin.integrations.event'] = 'Event';
$lang['AdminPlugin.integrations.event_callback'] = 'Callback Method';
$lang['AdminPlugin.integrations.label'] = 'Label';
$lang['AdminPlugin.integrations.level'] = 'Level';
$lang['AdminPlugin.integrations.card_callback'] = 'Callback Method';
$lang['AdminPlugin.integrations.link'] = 'Link';

$lang['AdminPlugin.integrations.text_actions_more_info'] = '"Actions" represent plugin pages that are accesible through the core interface. This includes navigation links in the admin and client areas, widgets in the admin area, and links on the client profile sidebar. The controllers and actions entered here will be used to generate simple controller files. In addition, sample view files will be created for each action.';
$lang['AdminPlugin.integrations.text_events_more_info'] = 'This is the hook system in Blesta.  Events are registered either by the core or by a plugin, then plugins listen for these events and define handler methods for the event.  Since any plugins can register events, there can be any number of them, however the list of core events defined by blesta can be found here https://docs.blesta.com/display/dev/Event+Handlers.';
$lang['AdminPlugin.integrations.text_cards_more_info'] = 'Client cards are small boxes shown on the client profile (in the client or admin area) and are primarily used for displaying various statistic (e.g number of service, tickets, or orders). They consist of a value, a label, a background, and a link.  The label is displayed below the value which is pulled in from a callback function.';
$lang['AdminPlugin.integrations.text_options'] = 'Options';
$lang['AdminPlugin.integrations.text_remove'] = 'Remove';

$lang['AdminPlugin.integrations.tooltip_location'] = 'The location in the interface to display the action';
$lang['AdminPlugin.integrations.tooltip_controller'] = 'The controller for the URI from which to pull in the content for the action (e.g. admin_main)';
$lang['AdminPlugin.integrations.tooltip_action'] = 'The action for the URI from which to pull in the content for the action (e.g. index)';
$lang['AdminPlugin.integrations.tooltip_name'] = 'The display name this action (nav text, widget header, or link text depending on the action location)';
$lang['AdminPlugin.integrations.tooltip_event'] = 'The event for which to add a handler (e.g. Clients.add). For a list of core events registered by blesta see https://docs.blesta.com/display/dev/Event+Handlers';
$lang['AdminPlugin.integrations.tooltip_event_callback'] = 'The name of the event handler method to create in the main plugin class';
$lang['AdminPlugin.integrations.tooltip_label'] = 'A string appearing under the value as a label';
$lang['AdminPlugin.integrations.tooltip_level'] = 'The level of interface at which to display the client card';
$lang['AdminPlugin.integrations.tooltip_card_callback'] = 'The name of the method to create in the main plugin class for fetching the card value';
$lang['AdminPlugin.integrations.tooltip_link'] = 'The URI to link to when the client card is click';

$lang['AdminPlugin.integrations.placeholder_controller'] = 'e.g. admin_main';
$lang['AdminPlugin.integrations.placeholder_action'] = 'e.g. index';
$lang['AdminPlugin.integrations.placeholder_name'] = 'e.g. Extension Generator';
$lang['AdminPlugin.integrations.placeholder_event'] = 'e.g. Clients.Add';
$lang['AdminPlugin.integrations.placeholder_event_callback'] = 'e.g. myClientAddHandlerMethod';
$lang['AdminPlugin.integrations.placeholder_card_callback'] = 'e.g. getClientTickets';
$lang['AdminPlugin.integrations.placeholder_label'] = 'e.g. Tickets';
$lang['AdminPlugin.integrations.placeholder_link'] = 'e.g. plugin/support_manager/client_tickets/';

$lang['AdminPlugin.integrations.features'] = 'Next - Additional Features';


// Additional features page
$lang['AdminPlugin.features.heading_features'] = 'Additional Features';
$lang['AdminPlugin.features.heading_service_tabs'] = 'Service Management Tabs';
$lang['AdminPlugin.features.heading_cron_tasks'] = 'Cron Tasks';
$lang['AdminPlugin.features.heading_optional_functions'] = 'Optional Functions';

$lang['AdminPlugin.features.method_name'] = 'Method Name';
$lang['AdminPlugin.features.label'] = 'Label';
$lang['AdminPlugin.features.level'] = 'Level';
$lang['AdminPlugin.features.name'] = 'Name';
$lang['AdminPlugin.features.description'] = 'Description';
$lang['AdminPlugin.features.type'] = 'Type';
$lang['AdminPlugin.features.time'] = 'Start Time/Interval';

$lang['AdminPlugin.features.tooltip_method_name'] = 'The name of the method to be generated for this tab in the code base (in the form camelCaseMethodName)';
$lang['AdminPlugin.features.tooltip_tab_label'] = 'The display name of the service tab';
$lang['AdminPlugin.features.tooltip_level'] = 'The level of interface on which to display the tab (staff or client)';
$lang['AdminPlugin.features.tooltip_name'] = 'The name of the cron task in the code base in snake case (i.e. cron_task_example)';
$lang['AdminPlugin.features.tooltip_cron_label'] = 'The display name of the cron task';
$lang['AdminPlugin.features.tooltip_description'] = 'The description shown on the cron task list page';
$lang['AdminPlugin.features.tooltip_type'] = 'The type of timing to use for the cron task (time or interval)';
$lang['AdminPlugin.features.tooltip_time'] = 'The daily 24-hour time that this task should run (e.g. "14:25") OR The interval, in minutes, that this cron task should run';

$lang['AdminPlugin.features.placeholder_method_name'] = 'e.g. tabChangePassword';
$lang['AdminPlugin.features.placeholder_tab_label'] = 'e.g. Change Password';
$lang['AdminPlugin.features.placeholder_name'] = 'e.g. cron_task_example';
$lang['AdminPlugin.features.placeholder_cron_label'] = 'e.g. Cron Task Example';
$lang['AdminPlugin.features.placeholder_time'] = 'e.g. 14:25 or 60';

$lang['AdminPlugin.features.service_tab_row_add'] = 'Add Service Management Tab';
$lang['AdminPlugin.features.cron_task_row_add'] = 'Add Cron Task';

$lang['AdminPlugin.features.text_options'] = 'Options';
$lang['AdminPlugin.features.text_remove'] = 'Remove';

$lang['AdminPlugin.features.confirm'] = 'Next - Confirmation';