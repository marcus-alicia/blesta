<?php
/**
 * Extension File Generator
 *
 * @package blesta
 * @subpackage blesta.plugins.extension_generator.lib
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ExtensionFileGenerator
{
    /**
     * @var string The type of extension for which to parse files
     */
    private $extension_type;

    /**
     * @var array $options A list of options for generating files including:
     *
     *  - name The name of the extension to generate
     *  - comment_code True to include commented out code examples
     *  - language_code The language code for which to create language files
     *  - replacements A list of tag names and their replacements
     *  - optional_functions A list method names to include
     */
    private $options;

    /**
     * @var string $output_dir The directory under which to generate files
     */
    private $output_dir;

    private $code_comment_marker = '\/\/\/\/';
    private $tag_start = '{{';
    private $tag_end = '}}';

    /**
     * @param string $extension_type The type of extension for which to generate files
     * @param array $options A list of options for generating files including:
     *
     *  - name The name of the extension to generate
     *  - comment_code True to include commented out code examples
     *  - language_code The language code for which to create language files
     *  - replacements A list of tag names and their replacements
     *  - optional_functions A list method names to include
     */
    public function __construct($extension_type, array $options)
    {
        $this->setExtensionType($extension_type);
        $this->setOptions($options);
    }

    /**
     * Sets the extension type for which to parse and generate files
     *
     * @param string $extension_type The type of extension for which to generate files
     */
    public function setExtensionType($extension_type)
    {
        $this->extension_type = $extension_type;
    }

    /**
     * Sets the options for parsing and generating files
     *
     * @param array $options A list of options for generating files including:
     *
     *  - name The name of the extension to generate
     *  - code_examples True to include commented out code examples
     *  - language_code The language code for which to create language files
     *  - data A list of tag names and their replacements
     *  - optional_functions A list method names to include
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * Sets the directory under which to generate files
     *
     * @param string $output_dir The directory under which to generate files
     */
    public function setOutputDir($output_dir)
    {
        $this->output_dir = $output_dir;
    }

    /**
     * Parse and generate the extension files. This is where the science happens.
     */
    public function parseAndOutput()
    {
        $data = $this->options['data'];

        // Set extension name variables
        $data['name'] = $this->options['name'];
        $data['snake_case_name'] = str_replace(' ', '_', strtolower($data['name']));
        $data['class_name'] = Loader::toCamelCase($data['snake_case_name']);

        // Parse TLDs
        if (isset($data['tlds'])) {
            $data['tlds'] = explode(',', trim($data['tlds'])) ?? [];
            foreach ($data['tlds'] as &$tld) {
                $tld = ['tld' => trim($tld)];
            }
        }
        // Get the directory in which to search for template files
        $template_directory = $this->getTemplateDirectory();
        // Get a list of template files to parse and output
        $file_paths = $this->getFileList($data['snake_case_name']);

        // Set a data flag for whether code examples are being included
        $data['code_examples'] = $this->options['code_examples'];

        // Clear and remake the directory for this extension
        $extension_directory = $this->output_dir . $data['snake_case_name'];
        self::deleteDir($extension_directory . DS);
        mkdir($extension_directory);

        // Set new optional function values based on the given data
        $data['optional_functions'] = array_merge(
            isset($data['optional_functions']) ? $data['optional_functions'] : [],
            $this->getOptionalFunctionValues($data)
        );

        // Parse and output template files
        foreach ($file_paths as $file_settings) {
            // If the given file is required by optional functions and none are being used, skip the file
            if (isset($file_settings['required_by'])
                && isset($data['optional_functions'])
                && ($required_by =
                    array_intersect_key($data['optional_functions'], array_flip($file_settings['required_by']))
                )
                && array_search('true', $required_by) === false
            ) {
                continue;
            }

            // Create any necessary directories before creating this file
            $file_path = $file_settings['path'];
            $file_path_parts = explode(DS, $file_path);
            $temp_path = '';
            for ($i = 0; $i < count($file_path_parts) - 1; $i++) {
                $temp_path .= DS . $file_path_parts[$i];
                if (!is_dir($extension_directory . DS . $temp_path)) {
                    mkdir($extension_directory . DS . $temp_path);
                }
            }

            // Get the template file contents
            $content = file_get_contents($template_directory . $file_path);

            // Parse and replace the template file contents

            // Remove code examples if set to do so
            if (!$this->options['code_examples']) {
                $content = preg_replace('/' . $this->code_comment_marker . '.*[\r\n|\n]/', '', $content);
            }

            // Accommodate 'foreach' files by setting the appropriate value for the particular view being generated
            $temp_data = $data;
            if (isset($file_settings['foreach'])) {
                foreach ($file_settings['foreach'] as $field_label => $name_key) {
                    $temp_data[$field_label] = $file_settings['page_value'];
                }
            }

            // Replace content tags
            $content = $this->replaceTags($content, $temp_data);

            // Remove any remaining array tags
            $content = preg_replace(
                '/' . $this->tag_start . 'array\:.*?' . $this->tag_end
                    . '[\d\D]*?'
                    . $this->tag_start . 'array\:.*?' . $this->tag_end . '/',
                '',
                $content
            );

            // Remove any remaining conditional tags
            $content = preg_replace(
                '/' . $this->tag_start . 'if\:.*?' . $this->tag_end
                    . '[\d\D]*?'
                    . $this->tag_start . 'endif\:.*?' . $this->tag_end . '/',
                '',
                $content
            );

            // Filter optional functions
            if (!isset($data['optional_functions'])) {
                $data['optional_functions'] = [];
            }

            // Replace content tags
            $content = $this->filterOptionalFunctions($content, $data['optional_functions']);

            // Remove any remaining tags
            $content = preg_replace('/' . $this->tag_start . '\S*' . $this->tag_end . '/', '', $content);

            if (isset($file_settings['name'])) {
                $file_path_parts[count($file_path_parts) - 1] = $file_settings['name'];
                $file_path = implode(DS, $file_path_parts);
            }

            // Output the parsed contents
            file_put_contents($extension_directory . DS . str_replace('.tpl', '', $file_path), $content);
        }

        // Update the logo with the uploaded image
        if (isset($data['logo_path'])) {
            $path_parts = ['views', 'default','images'];
            $temp_path = $extension_directory;
            foreach ($path_parts as $path_part) {
                $temp_path .= DS . $path_part;
                if (!is_dir($temp_path)) {
                    mkdir($temp_path);
                }
            }

            copy($data['logo_path'], $temp_path . DS . 'logo.png');
        }
    }

    /**
     * Replaces tags in the given content
     *
     * @param string $content The content in which to replace tags
     * @param array $replacement_tags A list of tags and values to search for and replace
     * @param string $parent_tag The parent tag with which to prefix replacement tags (optional)
     * @return string The content with tags replaced
     */
    private function replaceTags($content, array $replacement_tags, $parent_tag = '')
    {
        // Get a list of array tags and remove them from the primary list
        // Wrap keys in the set  tag delimiters
        $array_tags = [];
        $tag_start = $this->tag_start;
        $tag_end = $this->tag_end;
        foreach ($replacement_tags as $replacement_tag => $replacement_value) {
            if (is_array($replacement_value)) {
                $array_tags[$replacement_tag] = $replacement_value;
            } else {
                $replacement_tags[$tag_start . $parent_tag . $replacement_tag . $tag_end] = str_replace('\'', '\\\'', $replacement_value);
            }

            unset($replacement_tags[$replacement_tag]);
        }

        // Parse content condtitionally based on the value of tags
        $content = $this->replaceConditionalTags($content, $replacement_tags);

        // Replace all tags for scalar values
        $content = str_replace(
            array_keys($replacement_tags),
            array_values($replacement_tags),
            $content
        );

        foreach ($array_tags as $array_tag => $array_values) {
            $matches = [];
            $wrapped_array_tag = $tag_start . 'array:' . $parent_tag . $array_tag . $tag_end;
            $pattern = '/' . $wrapped_array_tag . '([\d\D]*?)' . $wrapped_array_tag . '/';

            // Find all instances of the array tag
            preg_match_all($pattern, $content, $matches);

            // No matches for this tag, move on
            if (!isset($matches[1])) {
                continue;
            }

            // Process each match
            foreach ($matches[0] as $match) {
                $matched_content = '';

                // For each item in the array, copy the text within the array tag and perform tag replacement on it
                foreach ($array_values as $key => $value) {
                    if (is_array($value)) {
                        // Examine the matched content for each subtag and perform the tag replacement
                        $matched_content .= $this->replaceTags($match, $value, $parent_tag . $array_tag . '.');
                    } else {
                        // Perform single tag replacement on the content of the array tag
                        $matched_content .= $this->replaceTags($match, $array_values, $parent_tag . $array_tag . '.');
                        break;
                    }
                }

                // Remove the array tag from around the content
                $matched_content = rtrim(str_replace($wrapped_array_tag, '', $matched_content), ',');

                // Replace the matched content with the new, tag replaced content
                $content = str_replace($match, $matched_content, $content);
            }
        }

        return $content;
    }

    /**
     * Replaces conditional tags in the given content
     *
     * @param string $content The content in which to replace tags
     * @param array $replacement_tags A list of tags and values to search for and replace
     * @return string The content with tags replaced
     */
    private function replaceConditionalTags($content, array $replacement_tags)
    {
        // Created a list of strings that match the conditional pattern, and the value to replace them with
        $match_replacements = [];
        foreach ($replacement_tags as $replacement_tag => $replacement_value) {
            // Remove any tag delimiters
            $trimmed_tag = rtrim(ltrim($replacement_tag, $this->tag_start), $this->tag_end);

            // {{if:tag:value}}true_text{{else:tag}}false_text{{if:tag}}
            $pattern = '/' . $this->tag_start . 'if:' . $trimmed_tag . ':(.*?)' . $this->tag_end
                . '([\d\D]*?)(' . $this->tag_start . 'else:' . $trimmed_tag . $this->tag_end
                . '([\d\D]*?))?' . $this->tag_start . 'endif:' . $trimmed_tag . $this->tag_end . '/';

            if (preg_match_all($pattern, $content, $matches) && count($matches) !== 0) {
                foreach ($matches[0] as $index => $match) {
                    // Set variables to make the role of each match more clear
                    $comparaison_value = $matches[1][$index];
                    $true_text = $matches[2][$index];
                    $false_text = $matches[3][$index];

                    // If the tag value equals the parsed comparison value, use the parse text for the true case,
                    // else use the parsed text for the false calse
                    $match_replacements[$match] = $replacement_value == $comparaison_value
                        ? $replacement_tags[$trimmed_tag] = $true_text
                        : $replacement_tags[$trimmed_tag] = $false_text;
                }
            }

            unset($replacement_tags[$replacement_tag]);
        }

        return str_replace(array_keys($match_replacements), array_values($match_replacements), $content);
    }

    /**
     * Remove optional functions that are set to false
     *
     * @param string $content The content from which to remove/keep optional functions
     * @param array $optional_functions A list of optional functions and their status, false to remove, true to keep
     * @return string The content with optional functions removed
     */
    private function filterOptionalFunctions($content, array $optional_functions)
    {
        // Filter optional functions from the content
        foreach ($optional_functions as $optional_function => $include)
        {
            if ($include == 'false') {
                // Remove the optional function
                $content = preg_replace(
                    '/' . $this->tag_start . 'function:' . $optional_function . $this->tag_end
                        . '[\d\D]*?'
                        . $this->tag_start . 'function:' . $optional_function . $this->tag_end . '/',
                    '',
                    $content
                );
            } else {
                // Keep the optional function but remove the surrounding tags
                $content = str_replace(
                    $this->tag_start . 'function:' . $optional_function . $this->tag_end,
                    '',
                    $content
                );
            }
        }

        return $content;
    }

    /**
     * Gets a list of files to parse and generate based on the set extension type
     *
     * @param string $extension_name The name of the extension being generated
     * @return array A list of arrays for files to parse and generate, each containing:
     *
     *  - path The path to the template file to parse, relative to the template directory
     *  - required_by A list of optional functions that require the given file
     */
    private function getFileList($extension_name)
    {
        $file_path_list = [
            'module' => [
                ['path' => 'module.php', 'name' => $extension_name . '.php'],
                ['path' => 'config.json'],
                ['path' => 'language' . DS . 'en_us' . DS . 'module.php', 'name' => $extension_name . '.php'],
                ['path' => 'views' . DS . 'default' . DS . 'images' . DS . 'logo.png'],
                ['path' => 'views' . DS . 'default' . DS . 'manage.pdt'],
                [
                    'path' => 'views' . DS . 'default' . DS . 'client_service_info.pdt',
                    'required_by' => ['getClientServiceInfo']
                ],
                [
                    'path' => 'views' . DS . 'default' . DS . 'admin_service_info.pdt',
                    'required_by' => ['getAdminServiceInfo']
                ],
                ['path' => 'views' . DS . 'default' . DS . 'edit_row.pdt', 'required_by' => ['module_rows']],
                ['path' => 'views' . DS . 'default' . DS . 'add_row.pdt', 'required_by' => ['module_rows']],
                ['path' => 'views' . DS . 'default' . DS . 'edit_row.pdt', 'required_by' => ['module_rows']],
                ['path' => 'views' . DS . 'default' . DS . 'tab.pdt', 'foreach' => ['service_tabs' => 'method_name']],
                [
                    'path' => 'config' . DS . 'module.php',
                    'name' => $extension_name . '.php',
                ],
                [
                    'path' => 'apis' . DS . 'module_api.php',
                    'name' => $extension_name . '_api.php',
                    'required_by' => ['code_examples']
                ],
                [
                    'path' => 'apis' . DS . 'module_response.php',
                    'name' => $extension_name . '_response.php',
                    'required_by' => ['code_examples']
                ],
                ['path' => 'README.md'],
                ['path' => 'composer.json', 'required_by' => ['code_examples']],
            ],
            'plugin' => [
                ['path' => 'plugin.php', 'name' => $extension_name . '_plugin.php'],
                ['path' => 'controller.php', 'name' => $extension_name . '_controller.php'],
                ['path' => 'model.php', 'name' => $extension_name . '_model.php'],
                ['path' => 'config.json'],
                [
                    'path' => 'config' . DS . 'plugin.php',
                    'name' => $extension_name . '.php',
                    'required_by' => ['code_examples']
                ],
                ['path' => 'language' . DS . 'en_us' . DS . 'plugin.php', 'name' => $extension_name . '_plugin.php'],
                [
                    'path' => 'language' . DS . 'en_us' . DS . 'parent_controller.php',
                    'name' => $extension_name . '_controller.php'
                ],
                [
                    'path' => 'language' . DS . 'en_us' . DS . 'controller.php',
                    'foreach' => ['controllers' => 'snake_case_name'],
                    'extension' => 'php'
                ],
                [
                    'path' => 'language' . DS . 'en_us' . DS . 'model.php',
                    'foreach' => ['tables' => 'name'],
                    'extension' => 'php'
                ],
                [
                    'path' => 'controllers' . DS . 'controller.php',
                    'foreach' => ['controllers' => 'snake_case_name'],
                    'extension' => 'php'
                ],
                [
                    'path' => 'models' . DS . 'model.php',
                    'foreach' => ['tables' => 'name'],
                    'extension' => 'php'
                ],
                ['path' => 'views' . DS . 'default' . DS . 'images' . DS . 'logo.png'],
                [
                    'path' => 'views' . DS . 'default' . DS . 'tab.pdt',
                    'foreach' => ['service_tabs' => 'snake_case_name']
                ],
                [
                    'path' => 'views' . DS . 'default' . DS . 'action.pdt',
                    'foreach' => ['actions' => 'controller_action']
                ],
                ['path' => 'README.md'],
                ['path' => 'composer.json', 'required_by' => ['code_examples']],
            ],
            'merchant' => [
                ['path' => 'gateway.php', 'name' => $extension_name . '.php'],
                ['path' => 'language' . DS . 'en_us' . DS . 'gateway.php', 'name' => $extension_name . '.php'],
                ['path' => 'views' . DS . 'default' . DS . 'images' . DS . 'logo.png'],
                ['path' => 'views' . DS . 'default' . DS . 'settings.pdt'],
                [
                    'path' => 'views' . DS . 'default' . DS . 'cc_form.pdt',
                    'required_by' => ['buildCcForm']
                ],
                [
                    'path' => 'views' . DS . 'default' . DS . 'payment_confirmation.pdt',
                    'required_by' => ['buildPaymentConfirmation']
                ],
                [
                    'path' => 'apis' . DS . 'gateway_api.php',
                    'name' => $extension_name . '_api.php',
                    'required_by' => ['code_examples']
                ],
                [
                    'path' => 'apis' . DS . 'gateway_response.php',
                    'name' => $extension_name . '_response.php',
                    'required_by' => ['code_examples']
                ],
                ['path' => 'config.json'],
                ['path' => 'README.md'],
                ['path' => 'composer.json', 'required_by' => ['code_examples']],
            ],
            'nonmerchant' => [
                ['path' => 'gateway.php', 'name' => $extension_name . '.php'],
                ['path' => 'language' . DS . 'en_us' . DS . 'gateway.php', 'name' => $extension_name . '.php'],
                ['path' => 'views' . DS . 'default' . DS . 'images' . DS . 'logo.png'],
                ['path' => 'views' . DS . 'default' . DS . 'settings.pdt'],
                ['path' => 'views' . DS . 'default' . DS . 'process.pdt'],
                [
                    'path' => 'apis' . DS . 'gateway_api.php',
                    'name' => $extension_name . '_api.php',
                    'required_by' => ['code_examples']
                ],
                [
                    'path' => 'apis' . DS . 'gateway_response.php',
                    'name' => $extension_name . '_response.php',
                    'required_by' => ['code_examples']
                ],
                ['path' => 'config.json'],
                ['path' => 'README.md'],
                ['path' => 'composer.json', 'required_by' => ['code_examples']],
            ],
        ];

        // Duplicate a view file for each of the values in the defined 'foreach' field
        $return_list = $file_path_list[$this->extension_type];
        $appended_list = [];
        foreach ($return_list as $index => &$return_file) {
            $return_file['path'] .= '.tpl';
            if (isset($return_file['foreach'])) {
                // Search the options for the defined 'foreach' field
                foreach ($return_file['foreach'] as $field_label => $name_key) {
                    if (isset($this->options['data'][$field_label])) {
                        foreach ($this->options['data'][$field_label] as $field) {
                            // Copy the value from the 'foreach' field, use that as a new
                            // file name, and insert a new file
                            if (isset($field[$name_key]) && !in_array($field[$name_key], $return_list)) {
                                $return_file['name'] = $field[$name_key]
                                    . (isset($return_file['extension']) ? '.' . $return_file['extension'] : '.pdt');
                                $return_file['page_value'] = $field;
                                $appended_list[] = $return_file;
                            }
                        }
                    }
                }
                unset($return_list[$index]);
            }
        }

        return array_merge($return_list, $appended_list);
    }

    /**
     * Gets the template directory containing the files to parse based on the set extension type
     *
     * @return string The directory containing template files
     */
    private function getTemplateDirectory()
    {
        $directories = [
            'module' => PLUGINDIR . 'extension_generator' . DS . 'lib' . DS . 'templates' . DS . 'module' . DS,
            'plugin' => PLUGINDIR . 'extension_generator' . DS . 'lib' . DS . 'templates' . DS . 'plugin' . DS,
            'merchant' => PLUGINDIR . 'extension_generator' . DS . 'lib' . DS . 'templates' . DS . 'merchant' . DS,
            'nonmerchant' => PLUGINDIR . 'extension_generator'
                . DS . 'lib' . DS . 'templates' . DS . 'nonmerchant' . DS,
        ];

        return $directories[$this->extension_type];
    }

    private function getOptionalFunctionValues($data)
    {
        $function_dependencies = [
            'module' => [
                'addCronTasks' => 'cron_tasks',
                'getCronTasks' => 'cron_tasks',
                'getPackageFields' => 'package_fields',
                'getAdminAddFields' => 'service_fields',
                'getAdminEditFields' => 'service_fields',
                'getClientAddFields' => 'service_fields',
                'getAdminTabs' => 'service_tabs',
                'getClientTabs' => 'service_tabs',
                'code_examples' => 'code_examples', // Set this fake optional function to exclude certain files
            ],
            'plugin' => [
                'addCronTasks' => 'cron_tasks',
                'getCronTasks' => 'cron_tasks',
                'getActions' => 'actions',
                'getEvents' => 'events',
                'getCards' => 'cards',
                'allowsServiceTabs' => 'service_tabs',
                'getAdminServiceTabs' => 'service_tabs',
                'getClientServiceTabs' => 'service_tabs',
                'installTables' => 'tables',
                'code_examples' => 'code_examples', // Set this fake optional function to exclude certain files
            ],
            'merchant' => [],
            'nonmerchant' => [],
        ];

        $optional_functions = [];
        foreach ($function_dependencies[$this->extension_type] as $function => $dependency) {
            $optional_functions[$function] = empty($data[$dependency]) ? 'false' : 'true';
        }

        return $optional_functions;
    }

    /**
     * Recursively delete the directory and it's contents
     *
     * @param string $directory The directory to clear and delete
     */
    public static function deleteDir($directory) {
        $directory = rtrim($directory, DS) . DS;
        if (!is_dir($directory)) {
            return;
        }

        $files = glob($directory . '*', GLOB_MARK);
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }

            if (is_dir($file)) {
                self::deleteDir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($directory);
    }

    /**
     * Recursively search the given directory for files with the given old name and rename them
     *
     * @param string $directory The directory to search
     * @param string $old_name The name of the files for which to search
     * @param string $new_name The new name to give the files
     */
    public static function renameFiles($directory, $old_name, $new_name) {
        $directory = rtrim($directory, DS) . DS;

        $files = glob($directory . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                self::renameFiles($file, $old_name, $new_name);
            }
        }

        if (is_file($directory . $old_name) && !is_dir($directory . $old_name)) {
            rename($directory . $old_name, $directory . $new_name);
        }
    }
}
