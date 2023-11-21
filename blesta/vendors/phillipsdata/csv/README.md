# Csv

[![Build Status](https://travis-ci.org/phillipsdata/csv.svg?branch=master)](https://travis-ci.org/phillipsdata/csv) [![Coverage Status](https://coveralls.io/repos/phillipsdata/csv/badge.svg)](https://coveralls.io/r/phillipsdata/csv)

A CSV reader and writer with no external dependencies, that allows just in time
formatting and filtering, and operates on files as well as streams.

## Install

```sh
composer require phillipsdata/csv
```

## Basic Usage

### Reading a CSV

```php
<?php

use PhillipsData\Csv\Reader;

// Set the input for the reader
$reader = Reader::input(new SplFileObject('php://stdin'));

$lines = [];
// Fetch the result for each line read
foreach ($reader->fetch() as $line) {
    $lines[] = $line;
}

```

### Writing a CSV

```php
<?php

use PhillipsData\Csv\Writer;

$writer = Writer::output(new SplFileObject('/path/to/file.csv'));

$data = [
    ['colA', 'colB'],
    ['A1', 'B1'],
    ['A2', 'B2']
];

// Write all rows (works great with Iterators)
$writer->write($data);

// Or, write a single row at a time
foreach ($data as $row) {
    $writer->writeRow($row);
}

```

### Factories

```php
<?php

use PhillipsData\Csv\Factory;

// returns \PhillipsData\Csv\Writer
$writer = Factory::writer('path/to/file', ',', '"', '\\');

// returns \PhillipsData\Csv\Reader
$reader = Factory::reader('path/to/file', ',', '"', '\\');

```

### Formatting and Filtering

> **Formatting** and **Filtering** work for both reading and writing.

To format data while iterating, simply specify the **format** callback function.

```php

// The formatter is called for each line parsed
$reader->format(function ($line, $key, $iterator) {
    return [
        'first_name' => $line['First Name'],
        'last_name' => $line['Last Name'],
        'email' => strtolower($line['Email']),
        'date' => date('c', strtotime($line['Date']))
    ];
});

foreach ($reader->fetch() as $line) {
    // $line now contains 'first_name', 'last_name', 'email', and 'date'.
}

```

To filter data while iterating, simply specify the **filter** callback function.

```php

// The formatter is called for each line parsed
$reader->filter(function ($line, $key, $iterator) {
    return $line['Last Name'] === 'Smith';
});

foreach ($reader->fetch() as $line) {
    // $line only contains records where $line['Last Name'] === 'Smith'
}

```
