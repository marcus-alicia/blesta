<?php

/**
 * Invoice template manager. Handles fetches invoice templates
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class InvoiceTemplateManager extends AppModel
{
    /**
     * Initialize InvoiceTemplateManager
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['invoice_template_manager']);
    }

    /**
     * Lists all available invoice templates
     *
     * @return array An array of stdClass objects representing available invoice templates
     */
    public function getAll()
    {
        $templates = [];
        $template_dir = COMPONENTDIR . 'invoice_templates' . DS;

        if ($dir = opendir($template_dir)) {
            for ($i = 0; ($file = readdir($dir)) !== false;) {
                if (substr($file, 0, 1) != '.' && is_dir($template_dir . $file)) {
                    $temp = new stdClass();
                    $temp->class = $file;

                    try {
                        $template = $this->loadTemplate($temp->class);
                    } catch (Exception $e) {
                        // Template could not be loaded
                        continue;
                    }

                    $templates[$i] = $temp;
                    unset($temp);

                    $info = $this->getTemplateInfo($template);
                    foreach ((array) $info as $key => $value) {
                        $templates[$i]->$key = $value;
                    }

                    $i++;
                }
            }
        }
        return $templates;
    }

    /**
     * Fetches all supported paper sizes
     *
     * @return array A list of supported paper sizes and their language names
     */
    public function getPaperSizes()
    {
        return [
            'a4' => $this->_('InvoiceTemplateManager.getpapersizes.a4'),
            'letter' => $this->_('InvoiceTemplateManager.getpapersizes.letter')
        ];
    }

    /**
     * Returns all fonts available to the PDF library
     *
     * @return array A list of fonts available
     */
    public function getPdfFonts()
    {
        $dir = VENDORDIR . 'tecnickcom' . DS . 'tcpdf' . DS . 'fonts' . DS;
        $fonts = [];
        $exclude_types = ['b', 'i', 'bi'];

        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
                $ext = '.php';
                if (substr($file, -strlen($ext)) == $ext) {
                    $name = substr($file, 0, -4);
                    if (in_array(substr($name, -2), $exclude_types)
                        && file_exists($dir . substr($name, 0, -2) . $ext)
                    ) {
                        continue;
                    }
                    if (in_array(substr($name, -1), $exclude_types)
                        && file_exists($dir . substr($name, 0, -1) . $ext)
                    ) {
                        continue;
                    }
                    $fonts[$name] = $name;
                }
            }
        }
        asort($fonts);
        return $fonts;
    }

    /**
     * Instantiates the given invoice template and returns its instance
     *
     * @param string $class The name of the class in file_case to load
     * @return An instance of the invoice template specified
     */
    private function loadTemplate($class)
    {

        // Load the invoice template factory if not already loaded
        if (!isset($this->InvoiceTemplates)) {
            Loader::loadComponents($this, ['InvoiceTemplates']);
        }

        // Instantiate the template and return the instance
        return $this->InvoiceTemplates->create($class);
    }

    /**
     * Fetch information about the given invoice template object
     *
     * @param object $template The invoice template object to fetch info on
     */
    private function getTemplateInfo($template)
    {
        // Fetch supported interfaces
        $reflect = new ReflectionClass($template);
        $class = Loader::fromCamelCase($reflect->getName());

        // Set each mime type extension
        $mime_types = [];
        $supported_mime_types = $template->supportedMimeTypes();

        foreach ($supported_mime_types as $type) {
            $mime_types[$type] = strtoupper($template->getFileExtension($type));
        }

        $info = [
            'class' => $class,
            'name' => $template->getName(),
            'version' => $template->getVersion(),
            'authors' => $template->getAuthors(),
            'mime_types' => $mime_types
        ];

        unset($reflect);

        return $info;
    }
}
