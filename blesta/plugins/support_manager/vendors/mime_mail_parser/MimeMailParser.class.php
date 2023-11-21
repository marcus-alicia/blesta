<?php

require_once('attachment.class.php');

/**
 * Fast Mime Mail parser Class using PHP's MailParse Extension
 * @author gabe@fijiwebdesign.com
 * @url http://www.fijiwebdesign.com/
 * @license http://creativecommons.org/licenses/by-sa/3.0/us/
 * @version $Id$
 */
class MimeMailParser
{

  /**
   * PHP MimeParser Resource ID
   */
  public $resource;

  /**
   * A file pointer to email
   */
  public $stream;

  /**
   * A text of an email
   */
  public $data;

  /**
   * Stream Resources for Attachments
   */
  public $attachment_streams;

  /**
   * Inialize some stuff
   * @return 
   */
  public function __construct()
  {
    $this->attachment_streams = array();
  }

  /**
   * Free the held resouces
   * @return void
   */
  public function __destruct()
  {
    // clear the email file resource
    if (is_resource($this->stream))
    {
      fclose($this->stream);
    }
    // clear the MailParse resource
    if (is_resource($this->resource))
    {
      mailparse_msg_free($this->resource);
    }
    // remove attachment resources
    foreach ($this->attachment_streams as $stream)
    {
      fclose($stream);
    }
  }

  /**
   * Set the file path we use to get the email text
   * @return Object MimeMailParser Instance
   * @param $mail_path Object
   */
  public function setPath($path)
  {
    // should parse message incrementally from file
    $this->resource = mailparse_msg_parse_file($path);
    $this->stream = fopen($path, 'r');
    $this->parse();
    return $this;
  }

  /**
   * Set the Stream resource we use to get the email text
   * @return Object MimeMailParser Instance
   * @param $stream Resource
   */
  public function setStream($stream)
  {
    // streams have to be cached to file first
    if (get_resource_type($stream) == 'stream')
    {
      $tmp_fp = tmpfile();
      if ($tmp_fp)
      {
        while (!feof($stream))
        {
          fwrite($tmp_fp, fread($stream, 2028));
        }
        fseek($tmp_fp, 0);
        $this->stream = & $tmp_fp;
      }
      else
      {
        throw new Exception('Could not create temporary files for attachments. Your tmp directory may be unwritable by PHP.');
        return false;
      }
      fclose($stream);
    }
    else
    {
      $this->stream = $stream;
    }

    $this->resource = mailparse_msg_create();
    // parses the message incrementally low memory usage but slower
    while (!feof($this->stream))
    {
      mailparse_msg_parse($this->resource, fread($this->stream, 2082));
    }
    $this->parse();
    return $this;
  }

  /**
   * Set the email text
   * @return Object MimeMailParser Instance 
   * @param $data String
   */
  public function setText($data)
  {
    $this->resource = mailparse_msg_create();
    // does not parse incrementally, fast memory hog might explode
    mailparse_msg_parse($this->resource, $data);
    $this->data = $data;
    $this->parse();
    return $this;
  }

  /**
   * Parse the Message into parts
   * @return void
   */
  protected function parse()
  {
    $structure = mailparse_msg_get_structure($this->resource);
    $this->parts = array();
    foreach ($structure as $part_id)
    {
      $part = mailparse_msg_get_part($this->resource, $part_id);
      $this->parts[$part_id] = mailparse_msg_get_part_data($part);
    }
  }

  /**
   * Retrieve the Email Headers
   * @return Array
   */
  public function getHeaders()
  {
    if (isset($this->parts[1]))
    {
      return $this->getPartHeaders($this->parts[1]);
    }
    else
    {
      throw new Exception('MimeMailParser::setPath() or MimeMailParser::setText() must be called before retrieving email headers.');
    }
    return false;
  }

  /**
   * Retrieve the raw Email Headers
   * @return string
   */
  public function getHeadersRaw()
  {
    if (isset($this->parts[1]))
    {
      return $this->getPartHeaderRaw($this->parts[1]);
    }
    else
    {
      throw new Exception('MimeMailParser::setPath() or MimeMailParser::setText() must be called before retrieving email headers.');
    }
    return false;
  }

  /**
   * Retrieve a specific Email Header
   * @return String
   * @param $name String Header name
   * @param $to_charset The charset to convert to
   */
  public function getHeader($name, $to_charset = "UTF-8")
  {
    if (isset($this->parts[1]))
    {
      $headers = $this->getPartHeaders($this->parts[1]);
      if (isset($headers[$name]))
      {
        return $this->decodeHeader($headers[$name], $to_charset);
      }
    }
    else
    {
      throw new Exception('MimeMailParser::setPath() or MimeMailParser::setText() must be called before retrieving email headers.');
    }
    return false;
  }

  /**
   * Returns the email message body in the specified format. Assumes all body
   * parts are encoding in the same character encoding.
   * 
   * @return Mixed String Body or False if not found
   * @param string $type The content type to fetch (text or html)
   * @see MimeMailParser::getMessageBodies()
   */
  public function getMessageBody($type = 'text')
  {
    $bodies = $this->getMessageBodies($type);
    if (empty($bodies))
      return false;
    
    $body = "";
    foreach ($bodies as $elem) {
      $body .= $elem;
    }
    return $body;
  }
  
  /**
   * Returns the email message bodies in the specified format
   * @return Array
   * @param string $type The content type to fetch (text or html)
   */
  public function getMessageBodies($type = 'text')
  {
    $body = array();
    $mime_types = array(
      'text' => 'text/plain',
      'html' => 'text/html'
    );
    $dispositions = array("attachment");
    
    if (in_array($type, array_keys($mime_types)))
    {
      foreach ($this->parts as $part)
      {
        $disposition = $this->getPartContentDisposition($part);
        if (in_array($disposition, $dispositions))
          continue;
        
        if ($this->getPartContentType($part) == $mime_types[$type])
        {
          $headers = $this->getPartHeaders($part);
          $body[] = $this->decode($this->getPartBody($part), array_key_exists('content-transfer-encoding', $headers) ? $headers['content-transfer-encoding']
                : '');
        }
      }
    }
    else
    {
      throw new Exception('Invalid type specified for MimeMailParser::getMessageBody. "type" can either be text or html.');
    }
    return $body;
  }

  /**
   * get the headers for the message body parts.
   * @return Array
   * @param string $type The content type to fetch (text or html)
   */
  public function getMessageBodyHeaders($type = 'text')
  {
    $headers = false;
    $mime_types = array(
      'text' => 'text/plain',
      'html' => 'text/html'
    );
    $dispositions = array("attachment");
    
    if (in_array($type, array_keys($mime_types)))
    {
      foreach ($this->parts as $part)
      {
        $disposition = $this->getPartContentDisposition($part);
        if (in_array($disposition, $dispositions))
          continue;
        
        if ($this->getPartContentType($part) == $mime_types[$type])
        {
          $headers[] = $this->getPartHeaders($part);
        }
      }
    }
    else
    {
      throw new Exception('Invalid type specified for MimeMailParser::getMessageBody. "type" can either be text or html.');
    }
    return $headers;
  }

  /**
   * Returns the attachments contents in order of appearance
   * @return Array
   */
  public function getAttachments()
  {
    $attachments = array();
    $dispositions = array("attachment", "inline");
    foreach ($this->parts as $part)
    {
      $disposition = $this->getPartContentDisposition($part);
      if (in_array($disposition, $dispositions) && isset($part['disposition-filename']))
      {
        $attachments[] = new MimeMailParser_attachment(
            $part['disposition-filename'],
            $this->getPartContentType($part),
            $this->getAttachmentStream($part),
            $disposition,
            $this->getPartHeaders($part)
        );
      }
    }
    return $attachments;
  }
  
  /**
   * Return the string in the given charset
   *
   * @return string
   * @param $text string
   * @param $from_charset string
   * $param $to_charset string
   */
  public function convertEncoding($text, $from_charset, $to_charset = "UTF-8")
  {
    // Prefer conversion using iconv, but if the character set is not
    // supported, then fallback to mb_convert_encoding
    try
	{
      return iconv($from_charset, $to_charset, $text);
    }
    catch (Exception $e)
	{
      try
	  {
        return mb_convert_encoding($text, $to_charset, $from_charset);
      }
      catch (Exception $e)
	  {
        // Can't convert at all... return what we have because at least that's something
        return $text;
      }
    }
  }

  /**
   * Return the Headers for a MIME part
   * @return Array
   * @param $part Array
   */
  protected function getPartHeaders($part)
  {
    if (isset($part['headers']))
    {
      return $part['headers'];
    }
    return false;
  }

  /**
   * Return a Specific Header for a MIME part
   * @return Array
   * @param $part Array
   * @param $header String Header Name
   */
  protected function getPartHeader($part, $header)
  {
    if (isset($part['headers'][$header]))
    {
      return $part['headers'][$header];
    }
    return false;
  }

  /**
   * Return the ContentType of the MIME part
   * @return String
   * @param $part Array
   */
  protected function getPartContentType($part)
  {
    if (isset($part['content-type']))
    {
      return $part['content-type'];
    }
    return false;
  }

  /**
   * Return the Content Disposition
   * @return String
   * @param $part Array
   */
  protected function getPartContentDisposition($part)
  {
    if (isset($part['content-disposition']))
    {
      return $part['content-disposition'];
    }
    return false;
  }

  /**
   * Retrieve the raw Header of a MIME part
   * @return String
   * @param $part Object
   */
  protected function getPartHeaderRaw(&$part)
  {
    $header = '';
    if ($this->stream)
    {
      $header = $this->getPartHeaderFromFile($part);
    }
    else if ($this->data)
    {
      $header = $this->getPartHeaderFromText($part);
    }
    else
    {
      throw new Exception('MimeMailParser::setPath() or MimeMailParser::setText() must be called before retrieving email parts.');
    }
    return $header;
  }

  /**
   * Retrieve the Body of a MIME part
   * @return String
   * @param $part Object
   */
  protected function getPartBody(&$part)
  {
    $body = '';
    if ($this->stream)
    {
      $body = $this->getPartBodyFromFile($part);
    }
    else if ($this->data)
    {
      $body = $this->getPartBodyFromText($part);
    }
    else
    {
      throw new Exception('MimeMailParser::setPath() or MimeMailParser::setText() must be called before retrieving email parts.');
    }
    return $body;
  }

  /**
   * Retrieve the Header from a MIME part from file
   * @return String Mime Header Part
   * @param $part Array
   */
  protected function getPartHeaderFromFile(&$part)
  {
    $start = $part['starting-pos'];
    $end = $part['starting-pos-body'];
	if ($end - $start <= 0)
		return null;
    fseek($this->stream, $start, SEEK_SET);
    $header = fread($this->stream, $end - $start);
    return $header;
  }

  /**
   * Retrieve the Body from a MIME part from file
   * @return String Mime Body Part
   * @param $part Array
   */
  protected function getPartBodyFromFile(&$part)
  {
    $start = $part['starting-pos-body'];
    $end = $part['ending-pos-body'];
	if ($end - $start <= 0)
		return null;
    fseek($this->stream, $start, SEEK_SET);
    $body = fread($this->stream, $end - $start);
    return $body;
  }

  /**
   * Retrieve the Header from a MIME part from text
   * @return String Mime Header Part
   * @param $part Array
   */
  protected function getPartHeaderFromText(&$part)
  {
    $start = $part['starting-pos'];
    $end = $part['starting-pos-body'];
    $header = substr($this->data, $start, $end - $start);
    return $header;
  }

  /**
   * Retrieve the Body from a MIME part from text
   * @return String Mime Body Part
   * @param $part Array
   */
  protected function getPartBodyFromText(&$part)
  {
    $start = $part['starting-pos-body'];
    $end = $part['ending-pos-body'];
    $body = substr($this->data, $start, $end - $start);
    return $body;
  }

  /**
   * Read the attachment Body and save temporary file resource
   * @return String Mime Body Part
   * @param $part Array
   */
  protected function getAttachmentStream(&$part)
  {
    $temp_fp = tmpfile();

    array_key_exists('content-transfer-encoding', $part['headers']) ? $encoding = $part['headers']['content-transfer-encoding'] : $encoding = '';

    if ($temp_fp)
    {
      if ($this->stream)
      {
        $start = $part['starting-pos-body'];
        $end = $part['ending-pos-body'];
        fseek($this->stream, $start, SEEK_SET);
        $len = $end - $start;
        $written = 0;
        $write = min($len, 2028);
        $body = '';
        while ($written < $len)
        {
          if (($written + $write < $len))
          {
            $write = $len - $written;
          }
          $part = fread($this->stream, $write);
          fwrite($temp_fp, $this->decode($part, $encoding));
          $written += $write;
        }
      }
      else if ($this->data)
      {
        $attachment = $this->decode($this->getPartBodyFromText($part), $encoding);
        fwrite($temp_fp, $attachment, strlen($attachment));
      }
      fseek($temp_fp, 0, SEEK_SET);
    }
    else
    {
      throw new Exception('Could not create temporary files for attachments. Your tmp directory may be unwritable by PHP.');
      return false;
    }
    return $temp_fp;
  }

  /**
   * Decode the string depending on encoding type.
   * @return String the decoded string.
   * @param $encodedString    The string in its original encoded state.
   * @param $encodingType     The encoding type from the Content-Transfer-Encoding header of the part.
   */
  protected function decode($encodedString, $encodingType)
  {
    if (strtolower($encodingType) == 'base64')
    {
      return base64_decode($encodedString);
    }
    else if (strtolower($encodingType) == 'quoted-printable')
    {
      return quoted_printable_decode($encodedString);
    }
    else
    {
      return $encodedString;
    }
  }

  /**
   * Copied from PEAR Mail_Mime class
   * http://pear.php.net/manual/en/package.mail.mail-mimedecode.php
   * http://svn.php.net/viewvc/pear/packages/Mail_Mime/trunk/mimeDecode.php?view=markup
   *
   * LICENSE: This LICENSE is in the BSD license style.
   * Copyright (c) 2002-2003, Richard Heyes <richard@phpguru.org>
   * Copyright (c) 2003-2006, PEAR <pear-group@php.net>
   * All rights reserved.
   *
   * Uses imap_mime_header_decode() if available for faster decoding
   * 
   * Given a header, this function will decode it
   * according to RFC2047. Probably not *exactly*
   * conformant, but it does pass all the given
   * examples (in RFC2047).
   *
   * @param string Input header value to decode
   * @return Array an array of text and charsets
   * @access private
   */
  private function mimeDecodeHeader($input)
  {
	
	// Use the faster impac_mime_header_decode function if it exists
	if (function_exists("imap_mime_header_decode")) {
		return imap_mime_header_decode($input);
	}
	
	// Remove white space between encoded-words
	$input = preg_replace('/(=\?[^?]+\?(q|b)\?[^?]*\?=)(\s)+=\?/i', '\1=?', $input);

	$parts = array();
	$parts[] = (object)array('charset' => "default", 'text' => $input);

	// For each encoded-word...
	$i=0;
	while (preg_match('/(=\?([^?]+)\?(q|b)\?([^?]*)\?=)/i', $input, $matches)) {

		$encoded  = $matches[1];
		$charset  = $matches[2];
		$encoding = $matches[3];
		$text     = $matches[4];

		switch (strtolower($encoding)) {
			case 'b':
				$text = base64_decode($text);
				break;

			case 'q':
				$text = str_replace('_', ' ', $text);
				preg_match_all('/=([a-f0-9]{2})/i', $text, $matches);
				foreach($matches[1] as $value)
					$text = str_replace('='.$value, chr(hexdec($value)), $text);
				break;
		}

		$parts[$i++] = (object)array('charset' => $charset, 'text' => $text);
		$input = str_replace($encoded, $text, $input);
	}

	return $parts;
  }

  /**
   * $input can be a string or an array
   * @param string,array $input
   * @return string,array
   */
  private function decodeHeader($input, $to_charset = "UTF-8")
  {
	  if(is_array($input))
	  {
		  $new = array();
		  foreach($input as $i)
			  $new[] = $this->convertHeader($this->mimeDecodeHeader($i), $to_charset);
		  return $new;
	  }
	  else
		  return $this->convertHeader($this->mimeDecodeHeader($input), $to_charset);
  }
  
  /**
   * Conver an array of MIME header text to a string of text
   * 
   * @param array $mime_header An array of stdClass objects with 'text' and 'charset' properties
   * @param string $to_charset The character set to convert the header into
   * @return string A string representing the text of the MIME headers
   */
  private function convertHeader($mime_header, $to_charset)
  {
	$text = null;
	foreach ($mime_header as $part)
	{
		$text .= $this->convertEncoding($part->text, $part->charset == "default" ? "ASCII" : $part->charset, $to_charset);
	}
	return $text;
	
  }
}