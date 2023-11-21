<?php
/**
 * Language definitions for the PluginManager model
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Plugin errors
$lang['PluginManager.!error.dir.empty'] = 'Please enter the plugin directory.';
$lang['PluginManager.!error.dir.length'] = 'The plugin directory length may not exceed 64 characters.';
$lang['PluginManager.!error.company_id.exists'] = 'Invalid company ID.';
$lang['PluginManager.!error.name.empty'] = 'Please enter a plugin name.';
$lang['PluginManager.!error.author.empty'] = 'Please enter the plugin author.';
$lang['PluginManager.!error.version.empty'] = 'Please enter the plugin version.';
$lang['PluginManager.!error.version.length'] = 'The plugin version length may not exceed 16 characters.';

// Plugin event errors
$lang['PluginManager.!error.event.empty'] = 'Please enter an event.';
$lang['PluginManager.!error.event.length'] = 'Event length may not exceed 128 characters.';
$lang['PluginManager.!error.event.exists'] = 'The plugin event could not be found.';
$lang['PluginManager.!error.callback.empty'] = 'Please enter a callback.';

// Plugin card errors
$lang['PluginManager.!error.level.valid'] = 'The level must be set to "client" or "staff".';
$lang['PluginManager.!error.callback.exists'] = 'The plugin card callback could not be found.';
$lang['PluginManager.!error.callback.unique'] = 'The callback must be unique for this plugin card.';
$lang['PluginManager.!error.callback_type.valid'] = 'The callback type must be set to "value" or "html".';
$lang['PluginManager.!error.label.empty'] = 'Please enter a label.';
$lang['PluginManager.!error.link.empty'] = 'Please enter a link.';
$lang['PluginManager.!error.text_color.empty'] = 'Please enter a text color.';
$lang['PluginManager.!error.background.empty'] = 'Please enter a background color or a image URL.';
$lang['PluginManager.!error.background.valid'] = 'The background must be a valid URL for an image.';
$lang['PluginManager.!error.background_type.valid'] = 'The background type must be set to "color" or "image".';
