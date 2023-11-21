# Kassa Compleet (Credit/Debit) Gateway

[![Build Status](https://travis-ci.org/blesta/gateway-kassacompleet.svg?branch=master)](https://travis-ci.org/blesta/gateway-kassacompleet) [![Coverage Status](https://coveralls.io/repos/github/blesta/gateway-kassacompleet/badge.svg?branch=master)](https://coveralls.io/github/blesta/gateway-kassacompleet?branch=master)

This is a non-merchant gateway for Blesta that integrates with [Kassa Compleet](https://www.ing.nl/zakelijk/betalen/geld-ontvangen/kassa-compleet/index.html).

## Install the Gateway

1. You can install the gateway via composer:

    ```
    composer require blesta/kassacompleet
    ```

2. Upload the source code to a /components/gateways/nonmerchant/kassacompleet/ directory within
your Blesta installation path.

    For example:

    ```
    /var/www/html/blesta/components/nonmerchant/kassacompleet/
    ```

3. Log in to your admin Blesta account and navigate to
> Settings > Payment Gateways

4. Find the Credit/Debit Cards (Kassa Compleet) gateway and click the "Install" button to install it

5. You're done!

### Blesta Compatibility

|Blesta Version|Module Version|
|--------------|--------------|
|< v4.9.0|v1.0.1|
|>= v4.9.0|v1.1.0|
