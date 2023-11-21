<?php
/**
 * Email parser components
 *
 * Uses the MimeMailParser class to parse emails into their relative parts.
 *
 * @package blesta
 * @subpackage blesta.plugins.support_manager.components
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class EmailParser
{
    /**
     * Returns the charset for the given content type header
     *
     * @param string $content_type The content type header of the MIME message
     * @return string The character used in the content type header (null if not found)
     */
    public function getCharset($content_type)
    {
        $charset = null;

        preg_match('/charset=(.*);|charset=(.*)$/i', $content_type, $matches);

        if (isset($matches[1]) && $matches[1] != '') {
            $charset = trim($matches[1]);
        } elseif (isset($matches[2]) && $matches[2] != '') {
            $charset = trim($matches[2]);
        }

        return trim($charset, "\"\'");
    }

    /**
     * Returns all email addresses contained in the given header
     * @param MimeMailParser $email The email to fetch text on
     * @param string $header The header to fetch email addresses from
     * @return array An array of email addresses
     */
    public function getAddress(MimeMailParser $email, $header)
    {
        $addresses = [];
        preg_match_all("/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+/i", $email->getHeader($header), $matches);

        foreach ($matches as $match) {
            if (isset($match[0])) {
                $addresses[] = $match[0];
            }
        }
        return array_unique($addresses);
    }

    /**
     * Fetches the text of an email, converts HTML to plain-text if no
     * plain-text given. Also converts encoding to UTF-8.
     *
     * @param MimeMailParser $email The email to fetch text on
     * @return string The email text in UTF-8
     */
    public function getText(MimeMailParser $email)
    {
        $text = $this->getBody($email, 'text');

        if ($text == '') {
            if (!isset($this->Html2text)) {
                Loader::loadHelpers($this, ['TextParser']);
                $this->Html2text = $this->TextParser->create('html2text');
            }

            $html = $this->getBody($email, 'html');
            $this->Html2text->setHtml($html);
            $text = $this->Html2text->getText();
            unset($html, $this->Html2text);
        }

        return $text;
    }

    /**
     * Fetches the body section of a given email and converts its character encoding
     * to UTF-8 where necessary
     *
     * @param MimeMailParser $email The email to fetch text on
     * @param string $type The type of body to fetch ("text" or "html")
     * @return string The email part in UTF-8
     */
    public function getBody(MimeMailParser $email, $type)
    {
        $text = '';
        $encodings = $email->getMessageBodyHeaders($type);
        $charset = 'UTF-8';

        foreach ($email->getMessageBodies($type) as $i => $body) {
            if (isset($encodings[$i]['content-type'])) {
                $charset = $this->getCharset($encodings[$i]['content-type']);
            }

            if (strtoupper($charset) != 'UTF-8') {
                $text .= $email->convertEncoding($body, $charset, 'UTF-8');
            } else {
                $text .= $body;
            }
        }

        return $text;
    }

    /**
     * Fetches the subject of an email. Also converts encoding to UTF-8.
     *
     * @param MimeMailParser $email The email to fetch subject on
     * @return string The subject in UTF-8
     */
    public function getSubject(MimeMailParser $email)
    {
        return $email->getHeader('subject', 'UTF-8');
    }

    /**
     * Writes the email attachments to a temp directory and returns an array
     * containing all attachments saved in the same format as that provided by
     * PHP through $_FILE
     *
     * @param MimeMailParser $email The email to fetch attachments on
     * @param string $tmp_dir The directory to write attachments to temporarily
     * @return array An array of attachments
     */
    public function getAttachments(MimeMailParser $email, $tmp_dir = null)
    {
        $files = [];

        if (!$tmp_dir) {
            $tmp_dir = ini_get('upload_tmp_dir');

            if (!$tmp_dir && function_exists('sys_get_temp_dir')) {
                $tmp_dir = sys_get_temp_dir();
            }
        }

        foreach ($email->getAttachments() as $attachment) {
            $tmp_name = tempnam($tmp_dir, 'support_');

            if ($fp = fopen($tmp_name, 'w')) {
                while ($bytes = $attachment->read()) {
                    fwrite($fp, $bytes);
                }
                fclose($fp);
            }

            $files['attachment']['name'][] = $attachment->getFilename();
            $files['attachment']['type'][] = $attachment->getContentType();
            $files['attachment']['tmp_name'][] = $tmp_name;
            $files['attachment']['error'][] = 0;
            $files['attachment']['size'][] = filesize($tmp_name);
        }
        return $files;
    }
}
