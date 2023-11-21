<?php
/**
 * en_us language for the {{name}} plugin.
 */
// Basics
$lang['{{class_name}}Plugin.name'] = '{{name}}';
$lang['{{class_name}}Plugin.description'] = '{{description}}';
{{array:service_tabs}}

// {{service_tabs.label}}
$lang['{{class_name}}.{{service_tabs.snake_case_name}}'] = '{{service_tabs.label}}';
$lang['{{class_name}}.{{service_tabs.snake_case_name}}.header'] = '{{service_tabs.label}}';
$lang['{{class_name}}.{{service_tabs.snake_case_name}}.submit'] = 'Submit';{{array:service_tabs}}{{function:getCronTasks}}

// Cron Tasks{{array:cron_tasks}}
$lang['{{class_name}}Plugin.getCronTasks.{{cron_tasks.name}}'] = '{{cron_tasks.label}}';
$lang['{{class_name}}Plugin.getCronTasks.{{cron_tasks.name}}_description'] = '{{cron_tasks.description}}';{{array:cron_tasks}}{{function:getCronTasks}}{{function:getActions}}

// Plugin Actions{{array:actions}}
$lang['{{class_name}}Plugin.{{actions.location}}.{{actions.action}}'] = '{{actions.name}}';{{array:actions}}{{function:getActions}}{{function:getCards}}

// Plugin Cards{{array:cards}}
$lang['{{class_name}}Plugin.card_{{cards.level}}.{{cards.callback}}'] = '{{cards.label}}';{{array:cards}}{{function:getCards}}