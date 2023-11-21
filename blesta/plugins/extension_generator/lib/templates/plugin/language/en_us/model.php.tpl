{{array:tables}}<?php

// Errors{{array:tables.columns}}
$lang['{{tables.class_name}}.!error.{{tables.columns.name}}.valid'] = 'Invalid {{tables.columns.name}}';{{array:tables.columns}}
{{array:tables.columns}}{{if:tables.columns.type:ENUM}}
// Get {{tables.columns.uc_name}} Values{{array:tables.columns.values}}
$lang['{{tables.class_name}}.get_{{tables.columns.name}}_values.{{tables.columns.values.value}}'] = '{{tables.columns.values.value}}';{{array:tables.columns.values}}
    {{endif:tables.columns.type}}{{array:tables.columns}}
{{array:tables}}