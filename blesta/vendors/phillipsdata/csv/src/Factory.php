<?php
namespace PhillipsData\Csv;

use SplFileObject;

/**
 * Factory for generating reader/writer objects
 */
class Factory
{
    /**
     * Creates a CSV Writer
     *
     * @param string $filename
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape
     * @param string $openMode
     * @return \PhillipsData\Csv\Reader
     */
    public static function writer(
        $filename,
        $delimiter = ',',
        $enclosure = '"',
        $escape = '\\',
        $openMode = 'w'
    ) {
        $file = static::fileObject($filename, $openMode);
        $file->setCsvControl($delimiter, $enclosure, $escape);
        $writer = Writer::output($file);

        return $writer;
    }

    /**
     * Creates a CSV Reader
     *
     * @param string $filename
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape
     * @param bool $withHeader
     * @param string $openMode
     * @return \PhillipsData\Csv\Reader
     */
    public static function reader(
        $filename,
        $delimiter = ',',
        $enclosure = '"',
        $escape = '\\',
        $withHeader = true,
        $openMode = 'r'
    ) {
        $file = static::fileObject($filename, $openMode);
        $file->setCsvControl($delimiter, $enclosure, $escape);
        $reader = Reader::input($file, $withHeader);

        return $reader;
    }

    /**
     * Creates an SplFileObject instance
     *
     * @param string $filename
     * @param string $openMode
     * @return \SplFileObject
     */
    protected static function fileObject($filename, $openMode = 'r')
    {
        return new SplFileObject($filename, $openMode);
    }
}
