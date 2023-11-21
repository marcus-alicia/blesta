<?php

use Blesta\Core\Util\Events\Common\EventInterface;
use Blesta\Core\Util\Events\Common\Observable;

/**
 * Events model
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2018, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class SystemEvents extends AppModel
{
    /**
     * Initialize language
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['system_events']);
    }

    /**
     * Adds an observer to an event
     *
     * @param array $vars System event information, including:
     *
     *  - event The name of the event
     *  - observer The name of the observer
     */
    public function register(array $vars)
    {
        $rules = [
            'event' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('SystemEvents.!error.event.empty')
                ],
                'length' => [
                    'rule' => ['maxLength', 255],
                    'message' => $this->_('SystemEvents.!error.event.length', 255)
                ]
            ],
            'observer' => [
                'valid' => [
                    'rule' => function ($observer) {
                        return $this->isCallableObserver($observer);
                    },
                    'message' => $this->_('SystemEvents.!error.observer.valid')
                ]
            ]
        ];

        $this->Input->setRules($rules);

        if ($this->Input->validates($vars)) {
            $this->Record->duplicate('observer', '=', $vars['observer'])
                ->insert('system_events', $vars, ['event', 'observer']);
        }
    }

    /**
     * Removes the event and all observers, or a specific observer
     *
     * @param string $event The name of the event to delete
     * @param string $observer The fully-qualified name of the observer, including namespace
     */
    public function unregister($event, $observer = null)
    {
        $this->Record->from('system_events')
            ->where('event', '=', $event);

        if ($observer !== null) {
            $this->Record->where('observer', '=', $observer);
        }

        $this->Record->delete();
    }

    /**
     * Retrieves all observers matching the given event
     *
     * @param string $event The name of the event
     * @return array An array of all matching observers
     */
    public function getObservers($event)
    {
        $observers = [];
        $events = $this->Record->select()
            ->from('system_events')
            ->where('event', '=', $event)
            ->fetchAll();

        foreach ($events as $event) {
            $observers[] = $event->observer;
        }

        return $observers;
    }

    /**
     * Triggers the given event on all observers
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event The event being triggered
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public function trigger(EventInterface $event)
    {
        // Trigger the given event on all observers
        foreach ($this->getObservers($event->getName()) as $observer) {
            // Instantiate the observer and call it's update method, and retrieve back the event
            // if it returned it like it was supposed to
            if ($this->isCallableObserver($observer) && ($instance = $this->getObserverInstance($observer))) {
                $e = $instance->update($event);
                $event = ($e instanceof EventInterface ? $e : $event);
            }
        }

        return $event;
    }

    /**
     * Determines whether the given observer is callable
     *
     * @param string $observer The observer
     * @return bool True if the observer is callable, or false otherwise
     */
    private function isCallableObserver($observer)
    {
        return (($instance = $this->getObserverInstance($observer)) && $instance instanceof Observable);
    }

    /**
     * Retrieves an instance of the observer
     *
     * @param string $observer The namespace of the observer class, or the full system path
     * @return bool|Blesta\Core\Util\Events\Common\Observable An instance of the observer if available, otherwise false
     */
    private function getObserverInstance($observer)
    {
        // We must be given a valid string
        if (empty($observer) || !is_string($observer)) {
            return false;
        }

        $instance = false;

        try {
            // Load the class by filename if it is one
            if (Loader::load($observer)) {
                // Remove the file path with forward or backslashes so we are left with the file name
                // e.g. /path/to/file_name.php becomes FileName
                $file = preg_replace('/^.*[|\\\\|\/]\s*[\.\s*]*/', '', $observer);
                $observer = Loader::toCamelCase(substr($file, 0, strrpos($file, '.')));
            }

            // Instantiate the class and ensure it implements the Observable interface
            $instance = call_user_func_array(
                [
                    new ReflectionClass($observer),
                    'newInstance'
                ],
                []
            );
        } catch (Throwable $e) {
            // Nothing to do
        }

        return $instance;
    }
}
