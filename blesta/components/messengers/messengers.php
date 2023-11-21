<?php
Loader::load(COMPONENTDIR . 'messengers' . DS . 'messenger.php');

/**
 * Factory class for creating Messenger objects
 *
 * @package blesta
 * @subpackage blesta.components.messengers
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Messengers
{
    /**
     * Returns an instance of the requested messenger
     *
     * @param string $messenger_name The name of the messenger to instantiate
     * @return mixed An object of type $messenger_name
     * @throws Exception Thrown when the messenger does not exists or does not inherit from the appropriate parent
     */
    public static function create($messenger_name)
    {
        $messenger_name = Loader::toCamelCase($messenger_name);
        $messenger_file = Loader::fromCamelCase($messenger_name);

        if (!Loader::load(COMPONENTDIR . 'messengers' . DS . $messenger_file . DS . $messenger_file . '.php')) {
            throw new Exception("Messenger '" . $messenger_name . "' does not exist.");
        }

        if (class_exists($messenger_name) && is_subclass_of($messenger_name, 'Messenger')) {
            return new $messenger_name();
        }

        throw new Exception("Messenger '" . $messenger_name . "' is not a recognized messenger.");
    }
}
