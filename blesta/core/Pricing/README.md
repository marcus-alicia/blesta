## Blesta Pricing

The Blesta\Core\Pricing can be used to construct and calculate costs
from a set of items, or data, while considering discount coupons, taxes,
and proration.

These items can then be used as invoice line items or to determine totals.

Blesta services and service data can also be used to construct items.


### Basic Usage

#### Factories

Several pricing-related factories exist to abstract object instantiation.

##### Pricing Factory

The _Blesta\Core\Pricing\PricingFactory_ provides methods to instantiate
price-related objects, such as meta item prices, descriptions, coupons, and
proration.

```
use Blesta\Core\Pricing\PricingFactory;
use Blesta\Items\Item\ItemInterface;

$options = ['dateFormat' => 'M d, Y', 'dateTimeFormat' => 'M d, Y H:i'];
$factory = new PricingFactory($options);

// Blesta\Core\Pricing\MetaItem\MetaItemPrice
$metaItem = $factory->metaItemPrice(5.00, 1, 'meta-item');

// Blesta\Core\Pricing\MetaItem\MetaDiscountPrice
$metaDiscount = $factory->metaDiscountPrice(10, 'percent');

// Blesta\Core\Pricing\MetaItem\MetaTaxPrice
$metaTax = $factory->metaTaxPrice(10, 'exclusive');

// Blesta\Core\Pricing\ItemComparator\ItemComparator
$comparator = $factory->itemComparator(
    function ($oldPrice, $newPrice, $oldMeta, $newMeta) {
        // Combined price
        return ($oldPrice - $newPrice);
    },
    function ($oldMeta, $newMeta) {
        return 'Combined Description';
    }
);

// Blesta\Core\Pricing\Modifier\Type\Discount\Coupon
$couponItem = new ItemInterface();
$coupon = $factory->coupon($couponItem, '2017-06-30T00:00:00+00:00');

// Blesta\Core\Pricing\Modifier\Type\Proration\Proration
$proration = $factory->proration();

// Blesta\Core\Pricing\Modifier\Type\Description\Description
$description = $factory->description();

// Minphp\Date\Date
$date = $factory->date();
```


##### Presenter Factory

The _Blesta\Core\Pricing\Presenter\PresenterFactory_ provides methods to
instantiate presenters, such as the invoice and service presenters.

```
use Blesta\Core\Pricing\Presenter\PresenterFactory;
use Blesta\Pricing\Collection\ItemPriceCollection;

$factory = new PresenterFactory();

// Blesta\Core\Pricing\Presenter\Type\ServicePresenter
$service = $factory->service(new ItemPriceCollection());

// Blesta\Core\Pricing\Presenter\Type\ServiceDataPresenter
$serviceData = $factory->serviceData(new ItemPriceCollection());

// Blesta\Core\Pricing\Presenter\Type\ServiceChangePresenter
$serviceChange = $factory->serviceChange(new ItemPriceCollection());

// Blesta\Core\Pricing\Presenter\Type\InvoicePresenter
$invoice = $factory->invoice(new ItemPriceCollection());

// Blesta\Core\Pricing\Presenter\Type\InvoiceDataPresenter
$invoiceData = $factory->invoiceData(new ItemPriceCollection());
```

##### Service Factory

The _Blesta\Core\Pricing\Presenter\Items\ServiceFactory_ instantiates
service item objects from a set of data.

```
use Blesta\Core\Pricing\Presenter\Items\ServiceFactory;
use Blesta\Core\Pricing\PricingFactory;
use Blesta\Items\Item\Item;
use Blesta\Items\ItemFactory;
use Blesta\Items\Collection\ItemCollection;

$factory = new ServiceFactory();

// Blesta\Core\Pricing\Presenter\Items\Service\ServiceItems
$service = $factory->service(
    new PricingFactory(...),
    new ItemFactory(),
    new Item(),
    new ItemCollection(),
    new ItemCollection(),
    new Item()
);

// Blesta\Core\Pricing\Presenter\Items\Service\ServiceDataItems
$serviceData = $factory->serviceData(
    new PricingFactory(...),
    new ItemFactory(),
    new Item(),
    new ItemCollection(),
    new ItemCollection(),
    new Item()
);

// Blesta\Core\Pricing\Presenter\Items\Invoice\InvoiceItems
$invoice = $factory->invoice(
    new PricingFactory(...),
    new ItemFactory(),
    new Item(),
    new ItemCollection(),
    new Item()
);

// Blesta\Core\Pricing\Presenter\Items\Invoice\InvoiceDataItems
$invoiceData = $factory->invoiceData(
    new PricingFactory(...),
    new ItemFactory(),
    new Item(),
    new ItemCollection(),
    new Item()
);
```

##### Description Factory

The _Blesta\Core\Pricing\Modifier\Type\Description\DescriptionFactory_ provides
methods to instantiate description objects, such as the service, package
options, taxes, and discounts, which can generate text descriptions based on the
data they are built with.

```
use Blesta\Core\Pricing\Modifier\Type\Description\DescriptionFactory;
use Minphp\Date\Date;

$date = new Date();
$factory = new DescriptionFactory($date);

// Blesta\Core\Pricing\Modifier\Type\Description\Type\Service\Service
$service = $factory->service();

// Blesta\Core\Pricing\Modifier\Type\Description\Type\Option\Option
$option = $factory->option();

// Blesta\Core\Pricing\Modifier\Type\Description\Type\Discount\Discount
$discount = $factory->discount();

// Blesta\Core\Pricing\Modifier\Type\Description\Type\Tax\Tax
$tax = $factory->tax();
```


##### Format Factory

The _Blesta\Core\Pricing\Presenter\Format\FormatFactory_ provides methods to
instantiate object formatters, which correlate data into expected formats, such
as for services, packages, taxes, and discounts.

```
use Blesta\Core\Pricing\Presenter\Format\FormatFactory;
use Blesta\Core\Pricing\Presenter\Format\Fields\FormatFields;
use Blesta\Items\ItemFactory;

$factory = new FormatFactory(new ItemFactory(), new FormatFields());

// Blesta\Core\Pricing\Presenter\Format\Type\Discount\DiscountFormatter
$discount = $factory->discount();

// Blesta\Core\Pricing\Presenter\Format\Type\Option\OptionFormatter
$option = $factory->option();

// Blesta\Core\Pricing\Presenter\Format\Type\Package\PackageFormatter
$package = $factory->package();

// Blesta\Core\Pricing\Presenter\Format\Type\Pricing\PricingFormatter
$pricing = $factory->pricing();

// Blesta\Core\Pricing\Presenter\Format\Type\Options\SettingsFormatter
$settings = $factory->settings();

// Blesta\Core\Pricing\Presenter\Format\Type\Service\ServiceFormatter
$service = $factory->service();

// Blesta\Core\Pricing\Presenter\Format\Type\Tax\TaxFormatter
$tax = $factory->tax();

// Blesta\Core\Pricing\Presenter\Format\Type\Invoice\InvoiceFormatter
$invoice = $factory->invoice();

// Blesta\Core\Pricing\Presenter\Format\Type\Invoice\LineFormatter
$invoiceLine = $factory->invoiceLine();
```

##### Builder Factory

The _Blesta\Core\Pricing\Presenter\Build\BuilderFactory_ provides methods to
instantiate builders, such as the service builders,
for the construction of presenters (see Presenters below). It requires several
other factories be provided to it on initialization.

```
use Blesta\Core\Pricing\Presenter\Build\BuilderFactory;
use Blesta\Core\Pricing\Presenter\Format\FormatFactory;
use Blesta\Core\Pricing\Presenter\Items\ServiceFactory;
use Blesta\Core\Pricing\Presenter\PresenterFactory;
use Blesta\Core\Pricing\PricingFactory;
use Blesta\Items\ItemFactory;

$builder = new BuilderFactory(
    new ServiceFactory(),
    new FormatFactory(...),
    new PricingFactory(...),
    new PresenterFactory(),
    new ItemFactory()
);

// Blesta\Core\Pricing\Presenter\Build\Service\ServiceBuilder
$service = $builder->service();

// Blesta\Core\Pricing\Presenter\Build\ServiceData\ServiceDataBuilder
$serviceData = $builder->serviceData();

// Blesta\Core\Pricing\Presenter\Build\ServiceChange\ServiceChangeBuilder
$serviceChange = $builder->serviceChange();

// Blesta\Core\Pricing\Presenter\Build\Invoice\InvoiceBuilder
$invoice = $builder->invoice();

// Blesta\Core\Pricing\Presenter\Build\InvoiceData\InvoiceDataBuilder
$invoiceData = $builder->invoiceData();
```


#### Presenters

##### Invoice Presenter

The invoice presenter constructs a
_Blesta\Core\Pricing\Presenter\Type\PresenterInterface_.

The invoice presenter does not accept discounts, as Blesta treats
discounts as line items for invoices.

```
use Minphp\Bridge\Initializer;
use Configure;

...

// Fetch an invoice
Loader::loadModels($this, ['Companies', 'Invoices']);
Loader::loadComponents($this, ['SettingsCollection']);
$invoice = $this->Invoices->get(1);

// Fetch the pricing builder for services and set options
$container = Initializer::get()->getContainer();
$container['pricing.options'] = [
    'date' => $this->Companies->getSetting(Configure::get('Blesta.company_id'), 'date_format')->value,
    'date_time' => $this->Companies->getSetting(Configure::get('Blesta.company_id'), 'datetime_format')->value
];

// Build a service
$factory = $container['pricingBuilder'];
$invoiceBuilder = $factory->invoice();

// Set additional information about taxes, discounts, and client settings
$invoiceBuilder->settings($this->SettingsCollection->fetchClientSettings($invoice->client_id));
$invoiceBuilder->taxes($invoice->taxes);

$invoicePresenter = $invoiceBuilder->build($invoice);

// All items, including individual taxes, discounts, totals
print_r($invoicePresenter->items());

// A list of all item totals, with and without taxes/discounts applied
print_r($invoicePresenter->totals());

// A list of all taxes applied
print_r($invoicePresenter->taxes());

// The Blesta\Pricing\ItemPriceCollection containing all of the items
foreach ($invoicePresenter->collection() as $item) {
    print_r($item);
}
```

##### Invoice Data Presenter

The invoice data presenter constructs a
_Blesta\Core\Pricing\Presenter\Type\PresenterInterface_.

The invoice data presenter does not accept discounts, as Blesta treats
discounts as line items for invoices.

```
use Minphp\Bridge\Initializer;
use Configure;

...

// Fetch a client and build invoice data
Loader::loadModels($this, ['Companies', 'Clients', 'Invoices']);
Loader::loadComponents($this, ['SettingsCollection']);

// Fetch the client
$client = $this->Clients->get(1);

$data = [
    'client_id' => $client->id,
    'status' => 'active',
    'date_billed' => '2019-01-01 00:00:00',
    'date_due' => '2019-02-01 00:00:00',
    'autodebit' => 1,
    'currency' => 'USD',
    'lines' => [
        [
            'service_id' => null,
            'description' => 'Line item #1',
            'qty' => 1,
            'amount' => 10.00,
            'tax' => 'true'
        ],
        [
            'service_id' => 1500,
            'description' => 'Service #1500',
            'qty' => 1,
            'amount' => 25.00,
            'tax' => 'false'
        ]
    ]
];

// Fetch the pricing builder for invoices and set options
$container = Initializer::get()->getContainer();
$container['pricing.options'] = [
    'date' => $this->Companies->getSetting(Configure::get('Blesta.company_id'), 'date_format')->value,
    'date_time' => $this->Companies->getSetting(Configure::get('Blesta.company_id'), 'datetime_format')->value
];

$factory = $container['pricingBuilder'];
$invoiceData = $factory->invoiceData();

// Build the service change presenter
$invoiceData->settings($this->SettingsCollection->fetchClientSettings($client->id));
$invoiceData->taxes($this->Invoices->getTaxRules($client->id));

$invoicePresenter = $invoiceData->build($data);

// All items, including individual taxes, discounts, totals
print_r($invoicePresenter->items());

// A list of all item totals, with and without taxes/discounts applied
print_r($invoicePresenter->totals());

// A list of all taxes applied
print_r($invoicePresenter->taxes());

// The Blesta\Pricing\ItemPriceCollection containing all of the items
foreach ($invoicePresenter->collection() as $item) {
    print_r($item);
}
```

##### Service Presenter

The service presenter constructs a
_Blesta\Core\Pricing\Presenter\Type\PresenterInterface_.

Setup fees and cancel fees may be conditionally included.
See _Presenter Options_ below.

```
use Minphp\Bridge\Initializer;
use Configure;

...

// Fetch a service
Loader::loadModels($this, ['Companies', 'Coupons', 'Invoices', 'Services']);
Loader::loadComponents($this, ['SettingsCollection']);
$service = $this->Services->get(1);

// Fetch the pricing builder for services and set options
$container = Initializer::get()->getContainer();
$container['pricing.options'] = [
    'date' => $this->Companies->getSetting(Configure::get('Blesta.company_id'), 'date_format')->value,
    'date_time' => $this->Companies->getSetting(Configure::get('Blesta.company_id'), 'datetime_format')->value
];

// Build a service
$factory = $container['pricingBuilder'];
$serviceBuilder = $factory->service();

$options = ['includeSetupFees' => false];

// Set additional information about taxes, discounts, and client settings
$serviceBuilder->settings($this->SettingsCollection->fetchClientSettings($service->client_id));
$serviceBuilder->taxes($this->Invoices->getTaxRules($service->client_id));
$serviceBuilder->discounts([$this->Coupons->get($service->coupon_id)]);
$serviceBuilder->options($options);

$servicePresenter = $serviceBuilder->build($service);

// All items, including individual taxes, discounts, totals
print_r($servicePresenter->items());

// A list of all item totals, with and without taxes/discounts applied
print_r($servicePresenter->totals());

// A list of all discounts applied
print_r($servicePresenter->discounts());

// A list of all taxes applied
print_r($servicePresenter->taxes());

// The Blesta\Pricing\ItemPriceCollection containing all of the items
foreach ($servicePresenter->collection() as $item) {
    print_r($item);
}
```


##### Service Data Presenter

The service data presenter constructs a
_Blesta\Core\Pricing\Presenter\Type\PresenterInterface_.

Setup fees and cancel fees may be conditionally included.
See _Presenter Options_ below.

```
use Minphp\Bridge\Initializer;
use Configure;

...

// Fetch a service
Loader::loadModels($this, ['Companies', 'Clients', 'Coupons', 'Invoices', 'Packages', 'PackageOptions']);
Loader::loadComponents($this, ['SettingsCollection']);
$data = [
    'qty' => 1,
    'pricing_id' => 1,
    'configoptions' => [
        9 => 1
    ]
];

// Fetch the client
$client = $this->Clients->get(1);

// Fetch the package and the selected pricing
$pricing = null;
if ($data['pricing_id'] && ($package = $this->Packages->getByPricingId($data['pricing_id']))) {
    foreach ($package->pricing as $price) {
        if ($price->id == $pricing_id) {
            $pricing = $price;
            break;
        }
    }
}

// Fetch all package options
$package_options = $this->PackageOptions->getAllByPackageId(
    $package->id,
    $pricing->term,
    $pricing->period,
    $pricing->currency
);

// Fetch the pricing builder for services and set options
$container = Initializer::get()->getContainer();
$container['pricing.options'] = [
    'date' => $this->Companies->getSetting(Configure::get('Blesta.company_id'), 'date_format')->value,
    'date_time' => $this->Companies->getSetting(Configure::get('Blesta.company_id'), 'datetime_format')->value
];

$factory = $container['pricingBuilder'];
$serviceData = $factory->serviceData();

$options = ['includeSetupFees' => true];

// Build the service change presenter
$serviceData->settings($this->SettingsCollection->fetchClientSettings($client->id));
$serviceData->taxes($this->Invoices->getTaxRules($client->id));
$serviceData->discounts([]);
$serviceData->options($options);

$servicePresenter = $serviceData->build($data, $package, $pricing, $package_options);

// All items, including individual taxes, discounts, totals
print_r($servicePresenter->items());

// A list of all item totals, with and without taxes/discounts applied
print_r($servicePresenter->totals());

// A list of all discounts applied
print_r($servicePresenter->discounts());

// A list of all taxes applied
print_r($servicePresenter->taxes());

// The Blesta\Pricing\ItemPriceCollection containing all of the items
foreach ($servicePresenter->collection() as $item) {
    print_r($item);
}
```


##### Service Change Presenter

The service change presenter constructs a
_Blesta\Core\Pricing\Presenter\Type\PresenterInterface_.

The service change presenter merges a service and service data into a single,
combined, set.

Cancel fees are *not* included, and setup fees are conditionally included for
*new* items. See _Presenter Options_ below.

The service change presenter is constructed identically to a
_Service Presenter_ or a _Service Data Presenter_ except that it takes both
sets of data:

```
Loader::loadModels($this, ['Services']);

...
// Retrieve a service builder
$factory = $container['pricingBuilder'];
$serviceChange = $factory->serviceChange();

// Set options
$options = ['includeSetupFees' => true];
$serviceData->options($options);

// Retrieve data to merge the service (old) with the service data (new)
$service = $this->Services->get(1);
$data = [
    'qty' => 1,
    'pricing_id' => 1,
    'configoptions' => [
        9 => 1
    ]
];
$package = (object)['id' => 1, ...];
$pricing = (object)['term' => 1, ...];
$package_options = [(object)['id' => 1, 'price' => 2.000, ...]];

$servicePresenter = $serviceChange->build($service, $data, $package, $pricing, $package_options);

// All items, including individual taxes, discounts, totals
print_r($servicePresenter->items());

// A list of all item totals, with and without taxes/discounts applied
print_r($servicePresenter->totals());

// A list of all discounts applied
print_r($servicePresenter->discounts());

// A list of all taxes applied
print_r($servicePresenter->taxes());

// The Blesta\Pricing\ItemPriceCollection containing all of the items
foreach ($servicePresenter->collection() as $item) {
    print_r($item);
}
```

##### Presenter Options

Options may be passed when building the Service Presenter,
Service Data Presenter, or the Service Change Presenter to alter how they are
constructed.

The Invoice Presenter and Invoice Data Presenter accept _no options_.

For example:

```
$options = [
    'includeSetupFees' => true,
    'includeCancelFees' => false
];

// Fetch the pricing factory from the container
$factory = $container['pricingBuilder'];

// Build a service data object using the set options
$serviceData = $factory->serviceData();
$serviceData->options($options);
```

Options include:

* includeSetupFees - (optional) _true/false_, whether to include applicable
setup fees. Default false.

* includeCancelFees - (optional) _true/false_, whether to include applicable
cancel fees. Default false.

* applyDate* - (optional) The effective _date_ the items apply. Coupons only
apply with respect to this date. Default now.

* startDate* - (optional) The effective _date_ the service term begins.
Services are dated from this startDate unless overridden by proration. Default
now.

* recur - (optional) _true/false_, whether to treat the service as recurring,
i.e., the service already exists and is renewing. May affect coupon discounts.
Default false.

* prorateStartDate* - (optional) _datetime_ stamp. If set, will prorate the
service from this date to the prorateEndDate

* prorateEndDate* - (optional) _datetime_ stamp. If set, will override the
otherwise calculated prorate end date.

* prorateEndDateData* - (optional) _datetime_ stamp. If set, will override
the otherwise set _prorateEndDate_ when included with the Service Change
Presenter only. This is typically used to prorate a service from its current
renew date to a new renew date by providing the _new_ renew date here while
the service's current renew date is the _prorateEndDate_.

* config_options - (optional) _array_, a list of the config options
currently on a service which is having a price change calculated.

* upgrade - (optional) _true/false_, whether this price is being
calculated for a package upgrade.

\* Dates should include timezone information to be accurate, otherwise they are
assumed to be in UTC.

Setup fees and cancel fees may be conditionally included.

```
$options['includeSetupFees'] = true;
$options['includeCancelFees'] = true;
```

Proration is attempted if a start date is given and proration is allowed for
the package to a prorata day:

```
$options['prorateStartDate'] = date('c');
```

The prorata day setting can be overridden by prorating to a specific end date:

```
$options['prorateEndDate'] = date('c', strtotime('now +1 month'));
```
