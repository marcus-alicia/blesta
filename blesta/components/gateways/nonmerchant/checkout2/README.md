# 2Checkout Gateway

[![Build Status](https://travis-ci.org/blesta/gateway-2checkout.svg?branch=master)](https://travis-ci.org/blesta/gateway-2checkout) [![Coverage Status](https://coveralls.io/repos/github/blesta/gateway-2checkout/badge.svg?branch=master)](https://coveralls.io/github/blesta/gateway-2checkout?branch=master)

This is a nonmerchant gateway for Blesta that integrates with [2Checkout](https://www.2checkout.com/).

## Install the Gateway

1. You can install the gateway via composer:

    ```
    composer require blesta/checkout2
    ```

2. Upload the source code to a /components/gateways/nonmerchant/checkout2/ directory within
your Blesta installation path.

    For example:

    ```
    /var/www/html/blesta/components/nonmerchant/checkout2/
    ```

3. Log in to your admin Blesta account and navigate to
> Settings > Payment Gateways

4. Find the 2Checkout gateway and click the "Install" button to install it

5. You're done!

### Blesta Compatibility

|Blesta Version|Module Version|
|--------------|--------------|
|< v4.9.0|v2.0.1|
|>= v4.9.0|v2.1.0|
|>= v5.0.0|v3.0.1|
