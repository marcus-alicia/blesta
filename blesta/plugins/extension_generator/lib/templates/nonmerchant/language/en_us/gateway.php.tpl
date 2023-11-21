<?php
/**
 * en_us language for the {{name}} gateway.
 */
// Basics
$lang['{{class_name}}.name'] = '{{name}}';
$lang['{{class_name}}.description'] = '{{description}}';


//Errors{{array:fields}}
$lang['{{class_name}}.!error.{{fields.name}}.valid'] = '{{fields.label}} invalid.';{{array:fields}}


// Settings{{array:fields}}
$lang['{{class_name}}.meta.{{fields.name}}'] = '{{fields.label}}';{{array:fields}}

{{array:fields}}{{if:fields.tooltip:}}{{else:fields.tooltip}}
$lang['{{class_name}}.meta.tooltip_{{fields.name}}'] = '{{fields.tooltip}}';{{endif:fields.tooltip}}{{array:fields}}

$lang['{{class_name}}.buildprocess.submit'] = 'Submit Payment';
