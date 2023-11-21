# Kassa Compleet (iDeal) Gateway

[![Build Status](https://travis-ci.org/blesta/gateway-kassacompleetideal.svg?branch=master)](https://travis-ci.org/blesta/gateway-kassacompleetideal) [![Coverage Status](https://coveralls.io/repos/github/blesta/gateway-kassacompleetideal/badge.svg?branch=master)](https://coveralls.io/github/blesta/gateway-kassacompleetideal?branch=master)

This is a non-merchant gateway for Blesta that integrates with [Kassa Compleet (iDeal)](https://www.ing.nl/zakelijk/betalen/geld-ontvangen/kassa-compleetideal/index.html).

## Install the Gateway

1. You can install the gateway via composer:

    ```
    composer require blesta/kassacompleetideal
    ```

2. Upload the source code to a /components/gateways/nonmerchant/kassacompleetideal/ directory within
your Blesta installation path.

    For example:

    ```
    /var/www/html/blesta/components/nonmerchant/kassacompleetideal/
    ```

3. Log in to your admin Blesta account and navigate to
> Settings > Payment Gateways

4. Find the iDeal (Kassa Compleet) gateway and click the "Install" button to install it

5. You're done!

### Blesta Compatibility

|Blesta Version|Module Version|
|--------------|--------------|
|< v4.9.0|v1.0.1|
|>= v4.9.0|v1.1.0|
