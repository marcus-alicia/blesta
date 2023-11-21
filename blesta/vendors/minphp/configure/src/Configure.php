<?php
namespace Minphp\Configure;

use Minphp\Configure\Reader;
use Minphp\Configure\Reader\JsonReader;
use Minphp\Configure\Reader\PhpReader;
use ArrayIterator;
use SplFileObject;
use UnexpectedValueException;

/**
 * Generic configuration library
 */
class Configure
{
    /**
     * @var \ArrayIterator
     */
    protected $data;

    /**
     * Initialize
     */
    public function __construct()
    {
        $this->data = new ArrayIterator();
    }

    /**
     * Loads a config file
     *
     * @param mixed $reader The reader to use or the path to the PHP/JSON file to load
     *  i.e. an instance of Reader\ReaderInterface, or a *.(php/json) file
     * @throws ConfigureLoadException If the file is not valid
     * @throws \UnexpectedValueException If $reader failed to return the expected type
     * @throws \Minphp\Configure\Reader\Exception\ReaderParseException If $reader failed to be parsed
     */
    public function load($reader)
    {
        $data = null;

        // Load the reader from string if given
        if (is_string($reader)) {
            $reader = $this->getReader($reader);
        }

        if ($reader instanceof Reader\ReaderInterface) {
            $data = $reader->getIterator();
        }

        if (!($data instanceof ArrayIterator)) {
            throw new UnexpectedValueException(
                (is_string($reader) ? $reader : get_class($reader)) . ' failed to return an instance of \ArrayIterator.'
            );
        }

        foreach ($data as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Set a value in the config
     *
     * @param mixed $key
     * @param mixed $value
     */
    public function set($key, $value)
    {
        $this->data->offsetSet($key, $value);
    }

    /**
     * Retrieve a value in the config
     *
     * @param mixed $key
     * @return mixed
     */
    public function get($key)
    {
        if ($this->exists($key)) {
            return $this->data->offsetGet($key);
        }
        return null;
    }

    /**
     * Verify that a key exists
     *
     * @param mixed $key
     * @return boolean
     */
    public function exists($key)
    {
        return $this->data->offSetExists($key);
    }

    /**
     * Removes a value from the config
     *
     * @param mixed $key
     */
    public function remove($key)
    {
        if ($this->exists($key)) {
            $this->data->offsetUnset($key);
        }
    }

    /**
     * Determines a reader from string
     *
     * @param string $filename The path to the file to load for the reader
     * @return \Minphp\Configure\Reader\ReaderInterface An instance of the corresponding reader
     */
    private function getReader($filename)
    {
        // Determine the reader from the file path based on extension
        if (($position = strrpos($filename, '.')) === false
            || !in_array(($extension = strtolower(substr($filename, $position + 1))), array('php', 'json'))
        ) {
            throw new UnexpectedValueException(
                $filename . ' is not a valid PHP or JSON file.'
            );
        }

        $file = new SplFileObject($filename);

        if ($extension === 'php') {
            return new PhpReader($file);
        }

        return new JsonReader($file);
    }
}
