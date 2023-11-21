<?php
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mailer\Transport\NullTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mailer\Mailer as SymfonyMailer;
use Symfony\Component\Mime\Email as SymfonyEmail;
use Blesta\Core\Util\Common\Traits\Container;

/**
 * A wrapper component for Symfony Mailer.
 *
 * https://symfony.com/doc/current/mailer.html#other-options
 *
 * @package blesta
 * @subpackage blesta.components.email
 * @copyright Copyright (c) 2022, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Email
{
    // Load traits
    use Container;

    /**
     * @var string The left tag enclosure
     */
    public $tag_start = '[';

    /**
     * @var string The right tag enclosure
     */
    public $tag_end = ']';

    /**
     * @var Logs The logs Model, used to record outgoing messages
     */
    public $Logs;

    /**
     * @var array All tags set for replacement
     */
    private $tags = [];

    /**
     * @var SymfonyEmail The email object for this instance
     */
    private $email;

    /**
     * @var SymfonyMailer The mailer used to send the message
     */
    private $mailer;

    /**
     * @var array An array of options to log when this message is attempted to be sent
     */
    private $options = [];

    /**
     * @var array An array of options containing the maximum number of messages to send before re-starting the transport
     */
    private $threshold = [];

    /**
     * @var string The default character set encoding.
     */
    private $charset;

    /**
     * Constructs a new Email component
     *
     * @param string $charset The default character set encoding.
     */
    public function __construct(string $charset = 'utf-8')
    {
        $this->charset = $charset;
        $this->email = new SymfonyEmail();

        // Initialize logger
        $logger = $this->getFromContainer('logger');
        $this->logger = $logger;

        Loader::loadModels($this, ['Logs']);
    }

    /**
     * Sets the transport object to be used for all subsequent requests
     *
     * @param TransportInterface The transport object used to send the message (SMTP, Sendmail, etc.)
     */
    public function setTransport(TransportInterface $transport)
    {
        if (method_exists($transport, 'setRestartThreshold') && isset($this->threshold['threshold'])) {
            $transport->setRestartThreshold($this->threshold['threshold'], $this->threshold['sleep']);
        }
        $this->mailer = new SymfonyMailer($transport);
    }

    /**
     * Gets the transport object to be used for all subsequent requests
     *
     * @return TransportInterface The transport object used to send the message (SMTP, Sendmail, etc.)
     */
    public function getTransport(TransportInterface $transport)
    {
        return $this->mailer;
    }

    /**
     * Builds and initializes a transport class
     *
     * @param string $transport The name of the transport to build
     * @param array $vars The parameters to pass to the transport
     * @return TransportInterface The transport object use to send the message
     */
    public function buildTransport($transport, array $vars = [])
    {
        // For backwards compatibility, set php as sendmail
        if (strtolower($transport) == 'php') {
            $transport = 'sendmail';
        }

        // Build the class name, including the full namespace
        $class = Loader::toCamelCase(strtolower($transport)) . 'Transport';
        if (strtolower($transport) == 'smtp') {
            // Use Esmtp to support setting credentials
            $class = Loader::toCamelCase(strtolower($transport)) . "\\EsmtpTransport";
        }
        $namespace = "\\Symfony\\Component\\Mailer\\Transport\\" . $class;

        if (class_exists($namespace)) {
            if (strtolower($transport) == 'smtp') {
                $transport_object = new $namespace($vars['host'], $vars['port']);
                return $transport_object;
            } else {
                try {
                    $reflect = new ReflectionClass($namespace);

                    return $reflect->newInstanceArgs(array_values($vars));
                } catch (InvalidArgumentException $e) {
                    try {
                        $reflect = new ReflectionClass($namespace);

                        if (isset($vars['command'])) {
                            $vars['command'] = $vars['command'] . ' -bs';
                        }

                        return $reflect->newInstanceArgs(array_values($vars));
                    } catch (Throwable $e) {
                        $this->logger->error($e->getMessage());
                    }
                } catch (Throwable $e) {
                    $this->logger->error($e->getMessage());
                }
            }
        }

        return new NullTransport();
    }

    /**
     * Set the flood resistance for sending messages
     *
     * @param int $threshold The maximum number of messages to send before
     *  disconnecting/reconnecting to the mail server
     * @param int $sleep The number of seconds to pause before reconnecting
     */
    public function setFloodResistance(int $threshold, int $sleep = 0)
    {
        $this->threshold = [
            'threshold' => $threshold,
            'sleep' => $sleep
        ];
    }

    /**
     * Sets the log options to be recorded when the message is attempted
     *
     * @param array $options An array of options to log when this message is attempted to be sent including:
     *
     *  - company_id The ID of the company the message is being sent by
     *  - to_client_id The ID of the client the message is being sent to (optional)
     *  - from_staff_id The ID of the staff member the message is sent by (optional)
     *  - log Bool true or false, whether to log the email (optional, default true)
     */
    public function setLogOptions(array $options)
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Sets the given array of tags for replacement.
     *
     * @param array $tags The tags to set for replacement.
     * @see Email::replaceTags()
     */
    public function setTags(array $tags)
    {
        $this->tags = $tags;
    }

    /**
     * Sets the subject of the message, replacing the given tags with their
     * key/value pairs.
     *
     * @param string $subject The subject of the message
     * @param array $replacements The key/value pairs of tag replacements
     * @see Email::setTags()
     */
    public function setSubject($subject, array $replacements = [])
    {
        $this->email->subject($this->replaceTags($subject, $replacements));
    }

    /**
     * Sets the body of the message, replacing the given tags with their
     * key/value pairs.
     *
     * @param string $body The body of the message
     * @param bool $is_html True if $body is HTML, false otherwise
     * @param array $replacements The key/value pairs of tag replacements
     * @see Email::setTags()
     */
    public function setBody($body, bool $is_html = false, array $replacements = [])
    {
        if ($is_html) {
            $this->email->html($this->replaceTags($body, $replacements), $this->charset);
        } else {
            $this->email->text($this->replaceTags($body, $replacements), $this->charset);
        }
    }

    /**
     * Sets the alternate body of the message, replacing the given tags with their
     * key/value pairs.
     *
     * @param string $body The body of the message
     * @param array $replacements The key/value pairs of tag replacements
     * @see Email::setTags()
     */
    public function setAltBody($body, array $replacements = [])
    {
        $this->email->attachPart(
            new DataPart($this->replaceTags($body, $replacements), null, 'text/plain')
        );
    }

    /**
     * Sets an alternate body for logging purposes, replacing the given tags with their
     * key/value pairs.
     *
     * @param stdClass $body The masked email containing the body of the message
     * @param array $replacements The key/value pairs of tag replacements
     * @see Email::setTags()
     */
    public function setLogBody($body, array $replacements = [])
    {
        $this->options['log_masked'] = [
            'text' => $this->replaceTags($body->text, $replacements),
            'html' => $this->replaceTags($body->html, $replacements)
        ];
    }

    /**
     * Invokes parent::SetFrom()
     *
     * @param string $from The from address
     * @param string $from_name The from name for this from address
     */
    public function setFrom($from, $from_name = '')
    {
        $this->email->from(
            new Address($from, $from_name)
        );
    }

    /**
     * Invokes parent::AddAddress()
     *
     * @param string $address The email address to add as a TO address
     * @param string $name The TO name
     */
    public function addAddress($address, $name = '')
    {
        $this->email->addTo(
            new Address($address, $name)
        );
    }

    /**
     * Invokes parent::AddCC()
     *
     * @param string $address The email address to add as a CC address
     * @param string $name The CC name
     */
    public function addCc($address, $name = '')
    {
        $this->email->addCc(
            new Address($address, $name)
        );
    }

    /**
     * Invokes parent::AddBCC()
     *
     * @param string $address The email address to add as a BCC address
     * @param string $name The BCC name
     */
    public function addBcc($address, $name = '')
    {
        $this->email->addBcc(
            new Address($address, $name)
        );
    }

    /**
     * Invokes parent::setReplyTo()
     *
     * @param string $address The email address to add as a ReplyTo address
     * @param string $name The ReplyTo name
     */
    public function addReplyTo($address, $name = '')
    {
        $this->email->replyTo(
            new Address($address, $name)
        );
    }

    /**
     * Adds the attachment
     *
     * @param string $path The path to the file
     * @param string $name The name of the file
     * @param string $encoding The encoding of the file (deprecated, for backwards compatibility only)
     * @param string $type The MIME type of the file (optional)
     */
    public function addAttachment($path, $name = null, $encoding = null, $type = null)
    {
        $this->email->attachFromPath($path, $name, $type);
    }

    /**
     * Invokes parent::Send() and logs the result
     */
    public function send($throw_exceptions = false)
    {
        $error = null;
        $sent = false;

        try {
            $this->mailer->send($this->email);
            $sent = true;
        } catch (Exception $e) {
            $error = $e->getMessage();
            $this->logger->error($error);

            if ($throw_exceptions) {
                $vars = ['sent' => 0, 'error' => $error];
                $vars = array_merge($vars, $this->options);
                $this->log($vars);

                throw new Exception($error);
            }
        }

        $vars = ['sent' => ($sent ? 1 : 0), 'error' => $error];

        $vars = array_merge($vars, $this->options);
        $this->log($vars);

        return $sent;
    }

    /**
     * Log the last sent message to the Logs
     *
     * @param array $vars A key/value array of data to log, including:
     *
     *  - log If false, will not log the email
     *  - * Any other fields to log
     */
    protected function log($vars)
    {
        // Skip logging if the log option is disabled
        if (array_key_exists('log', $vars) && $vars['log'] === false) {
            return;
        }

        if (!empty($vars['log_masked']) && is_array($vars['log_masked'])) {
            $body_text = $vars['log_masked']['text'];
            $body_html = $vars['log_masked']['html'];
        } else {
            $body_text = $this->email->getTextBody();
            $body_html = $this->email->getHtmlBody();

            if (empty($body_text)) {
                $body_text = strip_tags($body_html);
            }
            if (empty($body_html)) {
                $body_html = nl2br($body_text);
            }
        }

        $vars = array_merge(
            $vars,
            [
                'to_address' => $this->getAddresses($this->email->getTo()),
                'from_address' => $this->getAddresses($this->email->getFrom()),
                'from_name' => $this->getAddresses($this->email->getFrom(), true),
                'cc_address' => $this->getAddresses($this->email->getCc()),
                'subject' => $this->email->getSubject(),
                'body_text' => $body_text,
                'body_html' => $body_html
            ]
        );

        $this->Logs->addEmail($vars);
    }

    /**
     * Returns a string of address from an array of Address objects
     *
     * @param array $addresses An array of Address objects
     * @param bool $names_only True to return the name of the addresses
     * @return string A string of addresses
     */
    private function getAddresses(array $addresses, bool $names_only = false)
    {
        $address_line = '';
        foreach ($addresses as $address) {
            $address_line .= ($names_only ? $address->getName() : $address->getAddress()) . ',';
        }

        if (empty($address_line)) {
            $address_line = null;
        } else {
            $address_line = trim($address_line, ',');
        }

        return $address_line;
    }

    /**
     * Resets all recipients, replytos, attachments, and custom headers, body
     * and subject, and replacement tags (if any).
     */
    public function resetAll()
    {
        $this->email = new SymfonyEmail();
        $this->options = [];
        $this->tags = [];
    }

    /**
     * Replaces tags in the given $str with the supplied key/value replacements,
     * if a tag exists in Email::$tags, but is not found in $replacements, it
     * will be replaced with null.
     *
     * @param string $str The string to run replacements on.
     * @param array $replacements The key/value replacements.
     * @return string The string with all replacements done.
     */
    private function replaceTags($str, array $replacements)
    {
        $tag_count = count($this->tags);
        for ($i = 0; $i < $tag_count; $i++) {
            $str = str_replace(
                $this->tag_start . $this->tags[$i] . $this->tag_end,
                ($replacements[$this->tags[$i]] ?? null),
                $str
            );
        }

        return $str;
    }
}
