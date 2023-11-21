<?php
// Success messages
$lang['AdminCompanyLookandfeel.!success.template_updated'] = 'The templates were successfully updated.';
$lang['AdminCompanyLookandfeel.!success.layout_updated'] = 'The layout was successfully updated.';
$lang['AdminCompanyLookandfeel.!success.navigation_updated'] = 'The navigation was successfully updated.';
$lang['AdminCompanyLookandfeel.!success.action_created'] = 'The action was successfully created and can be added under Look and Feel > Navigation.';
$lang['AdminCompanyLookandfeel.!success.action_updated'] = 'The action was successfully updated.';
$lang['AdminCompanyLookandfeel.!success.action_deleted'] = 'The action was successfully deleted.';
$lang['AdminCompanyLookandfeel.!success.logo_updated'] = 'The custom logo was successfully updated.';


// Tooltips
$lang['AdminCompanyLookandfeel.!tooltip.action_name'] = 'To create multi-lingual actions you can specify a language definition in the _global.php language file. Rename _global-example.php to _global.php if necessary. These files can be found at %1$s and each other language directory.  Example code for definition:'; // %1$s is the path to the language files
$lang['AdminCompanyLookandfeel.!tooltip.action_name_language'] = 'Link Text';
$lang['AdminCompanyLookandfeel.!tooltip.action_url'] = 'This path can be relative (e.g. clients/add) or absolute (e.g. %1$sclients/add/ or https://blesta.com). Relative paths will be based on the interface it is displayed.  For example clients/add will link to %1$sclients/add'; // %1$s is the admin uri for this company
$lang['AdminCompanyLookandfeel.!tooltip.disabled_location'] = 'The location of an action cannot be altered post-creation.';
$lang['AdminCompanyLookandfeel.!tooltip.client_view_override'] = 'Allow the client to override the template from the URL by defining the "bltemplate" parameter where the value is the template directory name, e.g. "/?bltemplate=bootstrap".';


// Template
$lang['AdminCompanyLookandfeel.template.page_title'] = 'Settings > Company > Look and Feel > Template';
$lang['AdminCompanyLookandfeel.template.box_title'] = 'Template';
$lang['AdminCompanyLookandfeel.template.text_client_view_dir'] = 'Client Template';
$lang['AdminCompanyLookandfeel.template.text_admin_view_dir'] = 'Admin Template';
$lang['AdminCompanyLookandfeel.template.text_client_view_override'] = 'Allow to override the client template from the URL';
$lang['AdminCompanyLookandfeel.template.text_submit'] = 'Save';


// Layout
$lang['AdminCompanyLookandfeel.layout.page_title'] = 'Settings > Company > Look and Feel > Layout';
$lang['AdminCompanyLookandfeel.layout.box_title'] = 'Layout';
$lang['AdminCompanyLookandfeel.layout.field.text_color'] = 'Text Color';
$lang['AdminCompanyLookandfeel.layout.field.background_url'] = 'Background URL';
$lang['AdminCompanyLookandfeel.layout.text_html_generated'] = 'HTML Generated';
$lang['AdminCompanyLookandfeel.layout.text_cards_note'] = 'Drag the cards to change the order, click them to change the color, and click the check box to enable or disable a card. The color of the cards generated by HTML cannot be changed.';
$lang['AdminCompanyLookandfeel.layout.text_widget_preview_note'] = 'For security reasons the preview of this widget was not generated.';
$lang['AdminCompanyLookandfeel.layout.text_submit'] = 'Save';


// Customize
$lang['AdminCompanyLookandfeel.customize.page_title'] = 'Settings > Company > Look and Feel > Customize';
$lang['AdminCompanyLookandfeel.customize.box_title'] = 'Customize';
$lang['AdminCompanyLookandfeel.customize.heading_custom_logo'] = 'Custom Logo';
$lang['AdminCompanyLookandfeel.customize.field.admin_type_logo'] = 'Upload Logo';
$lang['AdminCompanyLookandfeel.customize.field.admin_type_url'] = 'Set Logo URL';
$lang['AdminCompanyLookandfeel.customize.field.admin_logo'] = 'Header Logo';
$lang['AdminCompanyLookandfeel.customize.field.admin_url'] = 'Header Logo';
$lang['AdminCompanyLookandfeel.customize.field.client_type_logo'] = 'Upload Logo';
$lang['AdminCompanyLookandfeel.customize.field.client_type_url'] = 'Set Logo URL';
$lang['AdminCompanyLookandfeel.customize.field.client_logo'] = 'Header Logo';
$lang['AdminCompanyLookandfeel.customize.field.client_url'] = 'Header Logo';
$lang['AdminCompanyLookandfeel.customize.text_submit'] = 'Save';


// Navigation
$lang['AdminCompanyLookandfeel.navigation.page_title'] = 'Settings > Company > Look and Feel > Navigation';
$lang['AdminCompanyLookandfeel.navigation.boxtitle_navigation'] = 'Navigation';

$lang['AdminCompanyLookandfeel.navigation.category_nav_staff'] = 'Staff Navigation';
$lang['AdminCompanyLookandfeel.navigation.category_nav_client'] = 'Client Navigation';
$lang['AdminCompanyLookandfeel.navigation.category_nav_public'] = 'Public Navigation';

$lang['AdminCompanyLookandfeel.navigation.heading_navigation']  = 'Navigation Menu';
$lang['AdminCompanyLookandfeel.navigation.heading_action']  = 'Action';
$lang['AdminCompanyLookandfeel.navigation.heading_options']  = 'Options';

$lang['AdminCompanyLookandfeel.actions.option_add'] = 'Add';
$lang['AdminCompanyLookandfeel.actions.option_remove'] = 'Remove';
$lang['AdminCompanyLookandfeel.actions.option_add_action'] = 'Add Custom Action';


$lang['AdminCompanyLookandfeel.navigation.field_subitem'] = 'Subitem';

$lang['AdminCompanyLookandfeel.navigation.text_link'] = 'link';
$lang['AdminCompanyLookandfeel.navigation.no_results_actions'] = 'There are no actions for this location.';

$lang['AdminCompanyLookandfeel.navigation.text_submit'] = 'Save Navigation';


// Custom Actions
$lang['AdminCompanyLookandfeel.actions.page_title'] = 'Settings > Company > Look and Feel > Custom Actions';
$lang['AdminCompanyLookandfeel.actions.boxtitle_actions'] = 'Custom Actions';

$lang['AdminCompanyLookandfeel.actions.category_nav_staff'] = 'Staff Navigation';
$lang['AdminCompanyLookandfeel.actions.category_nav_client'] = 'Client Navigation';
$lang['AdminCompanyLookandfeel.actions.category_nav_public'] = 'Public Navigation';
$lang['AdminCompanyLookandfeel.actions.categorylink_addaction'] = 'Add Action';

$lang['AdminCompanyLookandfeel.actions.heading_name']  = 'Name';
$lang['AdminCompanyLookandfeel.actions.heading_url']  = 'URL';
$lang['AdminCompanyLookandfeel.actions.heading_options']  = 'Options';

$lang['AdminCompanyLookandfeel.actions.no_results'] = 'There are no actions for this location.';

$lang['AdminCompanyLookandfeel.actions.option_edit'] = 'Edit';
$lang['AdminCompanyLookandfeel.actions.option_delete'] = 'Delete';
$lang['AdminCompanyLookandfeel.actions.confirm_delete'] = 'Are you sure you want to delete this action?';


// Add Action
$lang['AdminCompanyLookandfeel.addaction.page_title'] = 'Settings > Company > Look and Feel > Add Action';
$lang['AdminCompanyLookandfeel.addaction.boxtitle_addaction'] = 'Add Action';

$lang['AdminCompanyLookandfeel.addaction.field_location'] = 'Location';
$lang['AdminCompanyLookandfeel.addaction.field_name'] = 'Name';
$lang['AdminCompanyLookandfeel.addaction.field_url'] = 'Link URL';

$lang['AdminCompanyLookandfeel.addaction.placeholder_name'] = 'Package Name';
$lang['AdminCompanyLookandfeel.addaction.placeholder_url'] = 'Status';

$lang['AdminCompanyLookandfeel.addaction.field_addsubmit'] = 'Add Action';


// Edit Action
$lang['AdminCompanyLookandfeel.editaction.page_title'] = 'Settings > Company > Look and Feel > Edit Action';
$lang['AdminCompanyLookandfeel.editaction.boxtitle_editaction'] = 'Edit Action';
$lang['AdminCompanyLookandfeel.editaction.field_location'] = 'Location';
$lang['AdminCompanyLookandfeel.editaction.field_name'] = 'Name';
$lang['AdminCompanyLookandfeel.editaction.field_url'] = 'Link URL';

$lang['AdminCompanyLookandfeel.editaction.placeholder_name'] = 'Package Name';
$lang['AdminCompanyLookandfeel.editaction.placeholder_url'] = 'Status';

$lang['AdminCompanyLookandfeel.editaction.field_addsubmit'] = 'Edit Action';
