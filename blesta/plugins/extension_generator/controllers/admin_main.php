<?php
require_once PLUGINDIR . 'extension_generator' . DS . 'lib' . DS . 'extension_file_generator.php';

/**
 * Extension Generator admin main controller
 *
 * @package blesta
 * @subpackage blesta.plugins.extension_generator
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminMain extends ExtensionGeneratorController
{
    /**
     * Setup
     */
    public function preAction()
    {
        parent::preAction();

        $this->structure->set('page_title', Language::_('AdminMain.index.page_title', true));
    }

    /**
     * Returns the view for a list of extensions
     */
    public function index()
    {
        // Set current page of results
        $page = (isset($this->get[1]) ? (int) $this->get[1] : 1);
        $sort = (isset($this->get['sort']) ? $this->get['sort'] : 'date_updated');
        $order = (isset($this->get['order']) ? $this->get['order'] : 'desc');

        $extensions = $this->ExtensionGeneratorExtensions->getList(
            Configure::get('Blesta.company_id'),
            $page, [$sort => $order]
        );
        $total_results = $this->ExtensionGeneratorExtensions->getListCount(Configure::get('Blesta.company_id'));

        $this->set('types', $this->ExtensionGeneratorExtensions->getTypes());
        $this->set('form_types', $this->ExtensionGeneratorExtensions->getFormTypes());
        $this->set('extensions', $extensions);
        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination'),
            [
                'total_results' => $total_results,
                'uri' => $this->base_uri . 'plugin/extension_generator/admin_main/index/[p]/',
                'params' => ['sort' => $sort, 'order' => $order],
            ]
        );
        $this->setPagination($this->get, $settings);

        return $this->renderAjaxWidgetIfAsync(isset($this->get[0]) || isset($this->get['sort']));
    }

    /**
     * Delete extension
     */
    public function delete()
    {
        // Redirect if invalid extension ID given
        if (!isset($this->post['id'])
            || !($extension = $this->ExtensionGeneratorExtensions->get((int) $this->post['id']))
            || ($extension->company_id != $this->company_id)
        ) {
            $this->redirect($this->base_uri . 'plugin/extension_generator/admin_main/');
        }

        // Attempt to delete the extension
        $this->ExtensionGeneratorExtensions->delete($extension->id);

        if (($errors = $this->ExtensionGeneratorExtensions->errors())) {
            // Error
            $this->flashMessage('error', $errors);
        } else {
            // Success
            $this->flashMessage('message', Language::_('AdminMain.!success.package_deleted', true));
        }

        $this->redirect($this->base_uri . 'plugin/extension_generator/admin_main/');
    }

    /**
     * Returns the view to be rendered when configuring the general settings for an extension
     */
    public function general()
    {
        // Ensure any submitted extension ID is valid
        if (isset($this->get[0])
            && (!($extension = $this->ExtensionGeneratorExtensions->get($this->get[0]))
                || $extension->company_id != $this->company_id)
        ) {
            $this->redirect($this->base_uri . 'plugin/extension_generator/admin_main/');
        }

        // Add/update the extension
        if (!empty($this->post))
        {
            // Set unset checkbox
            if (!isset($this->post['code_examples'])) {
                $this->post['code_examples'] = 0;
            }

            // Make sure that the extension directory will not conflict with an existing one
            $form_rules = new FormRules();
            if (
                $this->checkNameTaken(
                    $this->post['name'] ?? '',
                    $this->post['type'] ?? '',
                    isset($extension) ? $extension->id : null
                )
            ) {
                $errors = Language::_('AdminMain.!error.name_taken', true);
            } elseif (!$form_rules->validate('general', $this->post)) {
                $errors = $form_rules->errors();
            }

            if (!isset($errors) && isset($extension)) {
                // Unset settings when the extension type is changed
                if ($extension->type != $this->post['type']) {
                    $this->post['data'] = [];
                }

                // Edit the extension
                $extension_id = $extension->id;
                $this->ExtensionGeneratorExtensions->edit($extension_id, $this->post);
            } elseif (!isset($errors)) {
                // Add a new extension
                $this->post['company_id'] = Configure::get('Blesta.company_id');
                $extension_id = $this->ExtensionGeneratorExtensions->add($this->post);
            }

            if (isset($errors) || ($errors = $this->ExtensionGeneratorExtensions->errors())) {
                $this->setMessage('error', $errors, false, null, false);

                $vars = (object) $this->post;
            } else {
                // Redirect to the next step in the configuration process
                $extension = $this->ExtensionGeneratorExtensions->get($extension_id);
                $this->redirect(
                    $this->base_uri
                    . 'plugin/extension_generator/admin_' . $extension->type . '/basic/' . $extension->id
                );
            }
        } else {
            $vars = isset($extension) ? $extension : null;
        }

        // Set the view to render for all actions under this controller
        $this->set(
            'type_warning',
            $this->setMessage('notice', Language::_('AdminMain.!notice.type_warning', true), true, null, false)
        );
        $this->set('extension_types', $this->ExtensionGeneratorExtensions->getTypes());
        $this->set('form_types', $this->ExtensionGeneratorExtensions->getFormTypes());
        $this->set('vars', $vars);

        // Set the node progress bar
        $nodes = $this->getNodes(isset($extension) ? $extension : null);
        $page_step = array_search('main/general', array_keys($nodes));
        $this->set(
            'progress_bar',
            $this->partial(
                'partial_progress_bar',
                ['nodes' => $nodes, 'page_step' => $page_step, 'extension' => isset($extension) ? $extension : null]
            )
        );
    }

    /**
     * Checks if the given extension name conflicts with an existing extension directory
     *
     * @param string $name The extension name to change
     * @param int $extension_id The ID of an extension to exlude from the conflict search
     * @return bool True if the given name conflicts with an existing extension directory
     */
    private function checkNameTaken($name, $extension_type, $extension_id = null)
    {
        // Make a list of extension names
        $extension_names = [];
        $extension_directories = $this->getExtensionDirectories();
        $extension_directory = $extension_directories[$extension_type] ?? '';
        foreach (scandir($extension_directory) as $file) {
            if (is_dir($extension_directory . $file) && !in_array($file, ['.', '..'])) {
                $extension_names[$file] = true;
            }
        }

        // Make an exception for the submitted extension ID
        if ($extension_id && ($extension = $this->ExtensionGeneratorExtensions->get($extension_id))) {
            unset($extension_names[str_replace(' ', '_', strtolower($extension->name))]);
        }

        return array_key_exists(str_replace(' ', '_', strtolower($name)), $extension_names);
    }

    /**
     * Returns the view to be rendered when confirming extension generation
     */
    public function confirm()
    {
        $this->uses(['PluginManager']);

        // Ensure extension exists
        if (!isset($this->get[0])
            || !($extension = $this->ExtensionGeneratorExtensions->get($this->get[0]))
            || $extension->company_id != $this->company_id
        ) {
            $this->redirect($this->base_uri . 'plugin/extension_generator/admin_main/');
        }

        // Set a warning if this extension already exists in the file system
        if ($this->checkNameTaken($extension->name, $extension->type)) {
            $this->setMessage('notice', Language::_('AdminMain.!notice.file_overwrite', true), false, null, false);
        }

        // Set a warning if this extension is installed
        if ($this->PluginManager->getByDir(str_replace(' ', '_', strtolower($extension->name)))) {
            $this->setMessage(
                'error',
                Language::_('AdminMain.!notice.updating_installed_extension', true),
                false,
                null,
                false
            );
        }

        // Update the extension
        if (!empty($this->post['location']))
        {
            $directories = $this->getExtensionDirectories();

            $directory = '';
            switch ($this->post['location']) {
                case 'custom':
                    $directory = isset($this->post['custom_path'])
                        ? $this->post['custom_path']
                        : (isset($directories[$extension->type])
                            ? $directories[$extension->type]
                            : $directories['module']);
                    break;
                case 'upload':
                    $this->uses(['Companies']);
                    $this->components(['SettingsCollection']);
                    $temp = $this->SettingsCollection->fetchSetting(
                        $this->Companies,
                        Configure::get('Blesta.company_id'),
                        'uploads_dir'
                    );
                    $directory = $temp['value'];
                    break;
                default:
                    $directory = isset($directories[$extension->type])
                        ? $directories[$extension->type]
                        : $directories['module'];
                    break;
            }
            $directory = rtrim($directory, DS) . DS;

            try {
                // Load the extension generator
                $generator = new ExtensionFileGenerator($extension->type, (array)$extension);
                $generator->setOutputDir($directory);

                // Generate the extension files.  This is where the magic happens.
                $generator->parseAndOutput();

                $this->flashMessage(
                    'message',
                    Language::_(
                        'AdminMain.!success.' . $extension->type . '_created',
                        true,
                        $directory . str_replace(' ', '_', strtolower($extension->name))
                    ),
                    null,
                    false
                );

                // Redirect to the list page
                $this->redirect($this->base_uri . 'plugin/extension_generator/admin_main/');
            } catch (Exception $ex) {
                $this->setMessage(
                    'error',
                    Language::_('AdminMain.!error.generation_failed', true, $ex->getMessage()),
                    false,
                    null,
                    false
                );
            }
        }

        $this->set('locations', $this->getFileLocations($extension->type));

        // Set the node progress bar
        $nodes = $this->getNodes($extension);
        $page_step = array_search('main/confirm', array_keys($nodes));
        $this->set(
            'progress_bar',
            $this->partial(
                'partial_progress_bar',
                ['nodes' => $nodes, 'page_step' => $page_step, 'extension' => $extension]
            )
        );
    }

    /**
     * Get a list of extension directories
     *
     * @return array A list of extension types and their directory
     */
    private function getExtensionDirectories()
    {
        return [
            'module' => COMPONENTDIR . 'modules' . DS,
            'plugin' => PLUGINDIR,
            'merchant' => COMPONENTDIR . 'gateways' . DS . 'merchant' . DS,
            'nonmerchant' => COMPONENTDIR . 'gateways' . DS . 'nonmerchant' . DS,
        ];
    }

    /**
     * Gets a list of file generation locations and their languages
     *
     * @param string $extention_type The type of extension for which to load file locations
     * @return A list of file generation locations and their languages
     */
    private function getFileLocations($extention_type)
    {
        return [
            'extension' => Language::_('AdminMain.getfilelocations.' . $extention_type, true),
            'upload' => Language::_('AdminMain.getfilelocations.upload', true),
            'custom' => Language::_('AdminMain.getfilelocations.custom', true)
        ];
    }
}
