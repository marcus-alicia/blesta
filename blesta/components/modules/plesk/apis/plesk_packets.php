<?php
Loader::load(dirname(__FILE__) .  DS . 'plesk_packet.php');

/**
 * Plesk Packet factory
 *
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @package plesk
 */
class PleskPackets
{
    /**
     * Creates a new instance of the given Plesk API command
     *
     * @param string $processor The exchange rate process to initialize
     * @param array $params Parameters to pass to the construtor (if any)
     * @return object Returns an instance of the given class
     */
    public static function create($command, array $params = [])
    {
        $command = Loader::toCamelCase($command);
        $command_file = Loader::fromCamelCase($command);

        if (!Loader::load(dirname(__FILE__) . DS . 'commands' . DS . $command_file . '.php')) {
            throw new Exception("API command '" . $command . "' does not exist.");
        }

        if (class_exists($command) && is_subclass_of($command, 'PleskPacket')) {
            $reflect = new ReflectionClass($command);
            return $reflect->newInstanceArgs($params);
        }

        throw new Exception("API command '" . $command . "' is not a Plesk API command.");
    }
}
