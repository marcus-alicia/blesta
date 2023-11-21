<?php
namespace Blesta\Core\Util\Ftp;

use DiRete\MicroFtps;

/**
 * FTP wrapper for DiRete\microftps
 */
class Ftp
{
    // Exceptions
    const CONNECTION_ERROR = 'Could not connect to FTP server.';

    /**
     * An instance of \DiRete\microftps
     */
    private $ftp;
    /**
     * The FTP server URL
     */
    private $server;
    /**
     * The FTP server username
     */
    private $username;
    /**
     * The FTP server password
     */
    private $password;
    /**
     * Any options to pass for the FTP connection
     */
    private $options = [];

    /**
     *
     * @param string $server The IP/domain of the server to connent to
     * @param string $username The username to connect with
     * @param string $password The password to connect with
     * @param array $options The options to use for the connection including:
     *
     *  - passive
     *  - port
     *  - timeout
     *  - curlOptions
     */
    public function __construct($server = '', $username = '', $password = '', array $options = [])
    {
        $this->setServer($server);
        $this->setCredentials($username, $password);
        $this->setOptions($options);
    }

    /**
     * Attempts to connect to the FTP server
     */
    public function connect()
    {
        $this->ftp = new MicroFtps($this->server, $this->username, $this->password, $this->options);
    }

    /**
     * Sets the FTP server URL to connect to
     *
     * @param type $server
     */
    public function setServer($server)
    {
        $this->server = $server;
    }

    /**
     * Sets login credentials for the FTP connection
     *
     * @param string $username The FTP username
     * @param string $password The FTP password
     */
    public function setCredentials($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Sets options to use for the FTP connection
     *
     * @param array $options The options to use for the connection including:
     *
     *  - passive
     *  - port
     *  - timeout
     *  - curlOptions
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * Reads the file from the remote system
     *
     * @param string $path The path to read the file from the server
     * @return mixed The result
     * @throws Exception
     */
    public function read($path)
    {
        // Connect to the server
        $this->connectFtp();

        return $this->ftp->read($path);
    }

    /**
     * Writes the file from the local system to the remote system
     *
     * @param string $from_path The path to the local file
     * @param string $to_path The path to write the file to on the server
     * @return mixed The result
     * @throws Exception
     */
    public function write($from_path, $to_path)
    {
        // Connect to the server
        $this->connectFtp();

        return $this->ftp->write($to_path, $from_path);
    }

    /**
     * Deletes the remote file at the given path
     *
     * @param string $path The path to delete the file from the server
     * @return mixed The result
     * @throws Exception
     */
    public function delete($path)
    {
        // Connect to the server
        $this->connectFtp();

        return $this->ftp->delete($path);
    }

    /**
    * List directory
     *
    * @param string $dir Directory to list
    * @return array
    * @throws Exception
    */
    public function listDir($dir)
    {
        // Connect to the server
        $this->connectFtp();

        return $this->ftp->listDir($dir);
    }

    /**
     * Attempts to connect to the server
     *
     * @return bool Whether or not the FTP connection can be made
     * @throws Exception
     */
    private function connectFtp()
    {
        try {
            $this->connect();
        } catch (Exception $ex) {
            // Could not connect
            throw new Exception(self::CONNECTION_ERROR . ' - ' . $ex->getMessage());
        }

        return true;
    }
}
