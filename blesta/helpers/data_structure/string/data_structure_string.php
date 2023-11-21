<?php
/**
 * String Data Structure helper
 *
 * Provides utility methods to assist in manipulating strings.
 *
 * @package blesta
 * @subpackage blesta.helpers.data_structure.string
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class DataStructureString
{
    /**
     * Generates a random string from a list of characters
     *
     * @param int $length The length of the random string
     * @param string $pool The pool of characters to include in the random string,
     *  defaults to alpha numeric characters. Can be configured further in $options['types']
     * @param array $options An array of options including:
     *
     *  - types A numerically indexed-array of character-types that may be used to
     *      generate the random string (i.e. "alpha", "alpha_lower", "alpha_upper", and/or "numeric") (optional)
     * @return string A randomly generated word with the given length
     */
    public function random($length = 8, $pool = null, array $options = [])
    {
        $alpha_lower = 'abcdefghijklmnopqrstuvwxyz';
        $alpha_upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numeric = '0123456789';

        if (!$pool) {
            // Set the valid characters to use in the random word
            if (!isset($options['types']) || !is_array($options['types'])) {
                $pool .= $alpha_lower . $alpha_upper . $numeric;
            } else {
                // Filter out the given character types
                foreach ($options['types'] as $character_type) {
                    switch ($character_type) {
                        case 'alpha_lower':
                            $pool .= $alpha_lower;
                            break;
                        case 'alpha_upper':
                            $pool .= $alpha_upper;
                            break;
                        case 'alpha':
                            $pool .= $alpha_lower . $alpha_upper;
                            break;
                        case 'numeric':
                            $pool .= $numeric;
                            break;
                    }
                }
            }
        }

        $str = '';
        $max_index = strlen($pool) - 1;

        if ($max_index >= 0) {
            for ($i = 0; $i < $length; $i++) {
                $str .= $pool[mt_rand(0, $max_index)];
            }
        }

        return $str;
    }

    /**
     * Truncates the string using the given options
     *
     * @param string $input The input string to truncate
     * @param array $options A list of options including any of the following:
     *
     *  - length The number of characters to truncate to - NOT multi-byte-safe (optional, default null)
     *  - word_length The number of words to truncate to, broken at spaces (optional, default null)
     *  - line_break True to truncate at the first new line, or false to allow new line breaks (optional, default true)
     */
    public function truncate($input, array $options = [])
    {
        if (!is_string($input)) {
            return $input;
        }

        $result = $input;

        // Set the default options
        $default_options = [
            'length' => null,
            'word_length' => null,
            'line_break' => true
        ];

        $options = array_merge($default_options, $options);

        // Cut at the first newline
        if ($options['line_break']) {
            $newline_break = preg_split("/[\n]+|[\r\n]+/", $result);

            if (isset($newline_break[0])) {
                $result = $newline_break[0];
            }
        }

        // Cut at a specific length
        if ($options['length'] && $options['length'] > 0) {
            $result = substr($result, 0, $options['length']);
        }

        if ($options['word_length'] && $options['word_length'] > 0) {
            // Determine the words
            $words = preg_split("/[\s]+/", $result);
            $spaces = preg_split("/[^\s]+/", $result);
            $word_count = count($words);

            // Rebuild the words
            if ($word_count > $options['word_length']) {
                $result = '';
                for ($i = 0; $i < $options['word_length']; $i++) {
                    $result .= ($i > 0 && isset($spaces[$i]) ? $spaces[$i] : '') . $words[$i];
                }
            }
        }

        return $result;
    }

    /**
     * Converts the HTML given to text
     *
     * @param string $html The HTML string
     * @return string The textual representation of the HTML
     */
    public function htmlToText($html)
    {
        $text = $html;

        // Convert the HTML to text
        if (!empty($html)) {
            if (!isset($this->Html2text)) {
                Loader::loadHelpers($this, ['TextParser']);
                $this->Html2text = $this->TextParser->create('html2text');
            }

            $this->Html2text->setHtml($html);
            $text = $this->Html2text->getText();
        }

        return $text;
    }

    /**
     * Removes the given $content from the provided $text
     *
     * @param string $text The full text string
     * @param string|array $content A string value, or numerically-indexed array of strings, representing exact
     *  textual matches from which to remove from $text
     * @return string The provided $text with specific $content removed
     */
    public function removeFromText($text, $content)
    {
        $text = ($text ? $text : '');

        // Remove content from the text to ignore
        if (!empty($content)) {
            $text = str_replace($content, '', $text);
        }

        return trim($text);
    }
}
