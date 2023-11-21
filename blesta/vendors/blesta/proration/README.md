# blesta/proration

Proration Calculator

## Installation

Install via composer:

```sh
composer require blesta/proration:~2.0
```

## Basic Usage

```php
use Blesta\Proration\Proration;

$start_date = date('c');
$prorate_day = 1;
$term = 1;
$period = 'month';
$proration = new Proration($start_date, $prorate_day, $term, $period);

echo $proration->prorateDate();
echo $proration->canProrate();
echo $proration->prorateDays();
echo $proration->proratePrice(5.00);
```
