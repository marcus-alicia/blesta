# Paystack Gateway

[![Build Status](https://travis-ci.org/blesta/gateway-paystack.svg?branch=master)](https://travis-ci.org/blesta/gateway-paystack) [![Coverage Status](https://coveralls.io/repos/github/blesta/gateway-paystack/badge.svg?branch=master)](https://coveralls.io/github/blesta/gateway-paystack?branch=master)

This is a nonmerchant gateway for Blesta that integrates with [Paystack](https://paystack.com/).

## Install the Gateway

1. You can install the gateway via composer:

    ```
    composer require blesta/paystack
    ```

2. Upload the source code to a /components/gateways/nonmerchant/paystack/ directory within
your Blesta installation path.

    For example:

    ```
    /var/www/html/blesta/components/nonmerchant/paystack/
    ```

3. Log in to your admin Blesta account and navigate to
> Settings > Payment Gateways

4. Find the Paystack gateway and click the "Install" button to install it

5. You're done!

### Blesta Compatibility

|Blesta Version|Module Version|
|--------------|--------------|
|< v4.9.0|v1.0.2|
|>= v4.9.0|v1.1.0|
