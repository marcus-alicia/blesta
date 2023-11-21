# Priority Schedule

[![Build Status](https://travis-ci.org/phillipsdata/priority-schedule.svg?branch=master)](https://travis-ci.org/phillipsdata/priority-schedule)
[![Coverage Status](https://coveralls.io/repos/phillipsdata/priority-schedule/badge.svg)](https://coveralls.io/r/phillipsdata/priority-schedule)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/phillipsdata/priority-schedule/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/phillipsdata/priority-schedule/?branch=master)

A priority schedule library.

This library provides data structures for performing **Round Robin** and
**First Available** access to items.

## Installation

```sh
composer require phillipsdata/priority-schedule
```

## Usage

There are two built-in priority schedules:

1. **Round Robin**
    - Returns the item with the least weight such that the weight
of each of the items is proportional.
2. **First Available**
    - Returns items in the order added added (FIFO) such that
only valid items are returned.

### Round Robin

The Round Robin schedule allows you retrieve items in an evenly distributed
manner, by specifying a comparator that defines the priority for items returned.

Suppose you have 3 bucket objects:

|Bucket|Count|
|------|-----|
|A     |0    |
|B     |2    |
|C     |4    |

Assuming each time we retrieve a bucket we increment its count, then add it
back to the schedule, we would expect to retrieve buckets in the following
order:

```
A, A, A, B, B, A, A, C, B
```


```php
use PhillipsData\PrioritySchedule\RoundRobin;

// Create our buckets
$a = new stdClass();
$a->name = 'A';
$a->count = 0;

$b = new stdClass();
$b->name = 'B';
$b->count = 2;

$c = new stdClass();
$c->name = 'C';
$c->count = 4;

// Initialize the priority schedule with a custom comparator
$rr = new RoundRobin();
$rr->setCallback(function ($x, $y) {
    if ($x->count === $y->count) {
        return 0;
    }
    // we want low items first so they have higher (1) priority
    return $x->count < $y->count
        ? 1
        : -1;
});

// Add items to the schedule
$rr->insert($a);
$rr->insert($b);
$rr->insert($c);

// Fetch items
foreach ($rr as $item) {
    echo $item->name . " (" . ++$item->count . ")\n";

    if ($item->count < 5) {
        $rr->insert($item);
    }
}
```

Output:

```
A (1)
A (2)
A (3)
B (3)
B (4)
A (4)
A (5)
C (5)
B (5)
```

### First Available

The First Available schedule allows you to retrieve elements in the order in
which they were added (FIFO), skipping elements that are not eligible (think
of the line to get into a night club) using a callback.

Suppose you have 3 bucket objects:

|Bucket|Count|
|------|-----|
|A     |0    |
|B     |2    |
|C     |4    |

Assuming each time we retrieve a bucket we decrement its count, then add it
back to the schedule, we would expect to retrieve buckets in the following
order:

```
B, C, B, C, C, C
```

This is because once a bucket reaches `0` we no longer consider it valid.


```php
use PhillipsData\PrioritySchedule\FirstAvailable;

// Create our buckets
$a = new stdClass();
$a->name = 'A';
$a->count = 0;

$b = new stdClass();
$b->name = 'B';
$b->count = 2;

$c = new stdClass();
$c->name = 'C';
$c->count = 4;

// Initialize the priority schedule with a custom filter
$fa = new FirstAvailable();
$fa->setCallback(function ($item) {
    return $item->count > 0;
});

// Add items to the schedule
$fa->insert($a);
$fa->insert($b);
$fa->insert($c);

foreach ($fa as $item) {
    echo $item->name . " (" . --$item->count . ")\n";

    if ($item->count > 0) {
        $fa->insert($item);
    }
}

```

Output:

```
B (1)
C (3)
B (0)
C (2)
C (1)
C (0)
```
