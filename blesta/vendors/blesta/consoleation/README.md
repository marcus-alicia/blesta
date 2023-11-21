# Consoleation
[![Build Status](https://travis-ci.org/blesta/consoleation.svg?branch=master)](https://travis-ci.org/blesta/consoleation)

A simple library for output to command-line and setting a progress bar

## Installation
Install via Composer

```
composer require blesta/consoleation
```

## Basic Usage

### Retrieve a single line from standard input

```
use Blesta/Consoleation/Console;

$console = new Console();
$line = $console->getLine();
```

### Display content to standard output

```
use Blesta/Consoleation/Console;

$console = new Console();
$console->output("This is a single line with a blank new line\n");
```

Any number of additional arguments may be passed to _output_ to perform string replacements via `sprintf`.

```
use Blesta/Consoleation/Console;

$console = new Console();
$console->output("Would you like %s or %s?", "apples", "oranges");
```

### Display a progress bar

```
use Blesta/Consoleation/Console;

$console = new Console();
$console->output("Performing the installation...\n");

$start = 1;
$finish = 5;
$progressBarCharLength = 50;
foreach (range($start, $finish) as $index => $number) {
    $console->progressBar($index + 1, $finish, $progressBarCharLength);
    sleep(1);
}
```
