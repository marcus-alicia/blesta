## Blesta Utility Events

The _Blesta\Core\Util\Events_ namespace can be used to define listeners for
event state changes and invoke observers to perform actions against those events.

### Basic Usage

#### Factories

##### Event Factory

The _Blesta\Core\Util\Events\EventFactory_ provides methods to instantiate
events, listeners, and observers.

```
use Blesta\Core\Util\Events\EventFactory;

$factory = new EventFactory();

// Create an event that may hold options
// Blesta\Core\Util\Events\Event
$event = $factory->event('Event.name', ['option' => 'value']);

// Create a listener that registers and triggers callbacks for registered events
// Blesta\Core\Util\Events\Listener
$listener = $factory->listener();

// Create an observer that can trigger all registered system and plugin events
// Blesta\Core\Util\Events\Observer
$observer = $factory->observer();
```

#### Event

An event stores information on an object representing the subject being changed.

```
use Blesta\Core\Util\Events\Event;

// Create an event of the given name that contains the given data as
// event parameters
$event = new Event('MyEvent.name', ['data' => ['id' => 1]]);

// You can then retrieve the event name, parameters, and return values
$event->getName(); // MyEvent.name
$event->getParams(); // ['data' => ['id' => 1]]
$event->getReturnValue(); // null

// You may also overwrite the event parameters or set them if they were not
// passed into the constructor
$event->setParams(['test' => true]);
$event->getParams(); // ['test' => true]

// You may also overwrite the event return value
$event->setReturnValue(['value' => false]);
$event->getReturnValue(); // ['value' => false]
```

#### Listener

The listener can register and trigger events.

##### Register Event Callbacks

Register an event callback with an anonymous function:

```
use Blesta\Core\Util\Events\Listener;

$listener = new Listener();

// If an event name is registered with the listener, a callback must be set to
// be called once the event name is triggered
$listener->register(
    'MyEvent.name',
    function($event) {
        $event->setReturnValue(true);
    }
);
```

Register an event callback from a file:

```
// Assuming the file is located at /path/to/file/test.php
use Blesta\Core\Util\Events\Common\EventInterface;

class Test
{
    public function doStuff(EventInterface $event)
    {
        $event->setReturnValue(false);
    }
}

...

// Define the callback to be loaded from file
$listener->register(
    'MyEvent.name',
    ['Test', 'doStuff'],
    '/path/to/file/test.php'
);
```

Register an event to call an existing Blesta event by not defining a callback

```
// Registers the core AppController.preAction callback already defined
// internally within Blesta
// These are defined in Blesta\Core\Util\Events\Observers\ by matching the name
$listener->register('AppController.preAction');
```

##### Trigger Registered Event Callbacks

If an event name is being listened for and an event by that name is triggered,
any registered callbacks for that event name will be called:

```
use Blesta\Core\Util\Events\Event;
use Blesta\Core\Util\Events\Listener;

$listener = new Listener();

// Trigger the event far any listeners that were previously registered
$event = $listener->trigger(new Event('MyEvent.name'));
```

There were 2 events registered previously (see above, i.e. an anonymous function
that set the event to a boolean true value, and ['Test', 'doStuff'] that set the
event to a boolean false value).

Since the event callbacks are triggered in the order they are registered,
the anonymous function set the event return value to true, and subsequently the
['Test', 'doStuff'] callback set the event return value to false.

```
$event->getReturnValue(); // false
```

#### Observer

An observer is notified and passed an event to perform some action. The base
_Blesta\Core\Util\Events\Observer_ class provides a _triggerEvent_ method
that automatically notifies Blesta system and plugin events.

```
use Blesta\Core\Util\Events\Event;
use Blesta\Core\Util\Events\Observer;

$observer = new Observer();

// All system events and plugin events already registered in Blesta
// will automatically be triggered for this event name
$observer->triggerEvent(new Event('AppController.preAction'));
```

#### Observing Events

##### Plugins

Plugins may observe changes to events by implementing the _Plugin::getEvents_
method as defined in the
[documentation](https://docs.blesta.com/display/dev/Plugin+Events).

##### System

An observer may be notified of a change of state for a system event if it

1. Registers itself with the Event name (i.e. exists in the `system_events` table)
2. Implements the _Blesta\Core\Util\Events\Common\Observable_ interface
