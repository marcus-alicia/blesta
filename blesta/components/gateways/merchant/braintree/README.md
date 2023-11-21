# Braintree Gateway

[![Build Status](https://travis-ci.org/blesta/gateway-braintree.svg?branch=master)](https://travis-ci.org/blesta/gateway-braintree) [![Coverage Status](https://coveralls.io/repos/github/blesta/gateway-braintree/badge.svg?branch=master)](https://coveralls.io/github/blesta/gateway-braintree?branch=master)

This is a merchant gateway for Blesta that integrates with [Braintree](https://www.braintreepayments.com/).

## Install the Gateway

1. You can install the gateway via composer:

    ```
    composer require blesta/braintree
    ```

2. Upload the source code to a /components/gateways/merchant/braintree/ directory within
your Blesta installation path.

    For example:

    ```
    /var/www/html/blesta/components/merchant/braintree/
    ```

3. Log in to your admin Blesta account and navigate to
> Settings > Payment Gateways

4. Find the Braintree gateway and click the "Install" button to install it

5. You're done!

### Blesta Compatibility

|Blesta Version|Module Version|
|--------------|--------------|
|< v4.9.0|v1.1.0|
|>= v4.9.0|v1.2.0|
|>= v5.0.0|v1.3.0|
