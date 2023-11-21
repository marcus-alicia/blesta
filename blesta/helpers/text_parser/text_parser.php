<?php

use Html2Text\Html2Text;

/**
 * Wrapper for text parsers
 *
 * @package blesta
 * @subpackage blesta.helpers.text_parser
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class TextParser
{
    /**
     * Creates and returns an instance of the requested text parser
     *
     * @param string $parser The parser to use for encouding. Acceptable types are:
     *
     *  - markdown
     * @return mixed Returns an instance of the parser object that was loaded, false if the parser was not found
     */
    public function create($parser)
    {
        switch ($parser) {
            case 'markdown':
                return new Parsedown();
            case 'html2text':
                return new Html2Text();
        }
        return false;
    }

    /**
     * Encodes a string using the given parser
     *
     * @param string $parser The parser to use for encouding. Acceptable types are:
     *
     *  - markdown
     * @param string $text The text to encode using the given parser
     * @return string The encoded text using the given parser
     */
    public function encode($parser, $text)
    {
        switch ($parser) {
            case 'markdown':
                if (!isset($this->Parsedown)) {
                    $this->Parsedown = $this->create($parser)->setBreaksEnabled(true)->setSafeMode(true);
                }

                return $this->Parsedown->text($text);
        }
        return null;
    }

    /**
     * Load the given file from the vendor directory
     *
     * @param string $file The file, including its relative path from the vendor directory, to load
     */
    private function load($file)
    {
        Loader::load(VENDORDIR . $file);
    }
}
