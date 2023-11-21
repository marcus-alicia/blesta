# Bitpay Gateway

[![Build Status](https://travis-ci.org/blesta/gateway-bitpay.svg?branch=master)](https://travis-ci.org/blesta/gateway-bitpay) [![Coverage Status](https://coveralls.io/repos/github/blesta/gateway-bitpay/badge.svg?branch=master)](https://coveralls.io/github/blesta/gateway-bitpay?branch=master)

This is a nonmerchant gateway for Blesta that integrates with [Bitpay](https://www.bitpay.com/).

## Install the Gateway

1. You can install the gateway via composer:

    ```
    composer require blesta/bitpay
    ```

2. Upload the source code to a /components/gateways/nonmerchant/bitpay/ directory within
your Blesta installation path.

    For example:

    ```
    /var/www/html/blesta/components/nonmerchant/bitpay/
    ```

3. Log in to your admin Blesta account and navigate to
> Settings > Payment Gateways

4. Find the Bitpay gateway and click the "Install" button to install it

5. You're done!

### Blesta Compatibility

|Blesta Version|Module Version|
|--------------|--------------|
|< v4.9.0|v1.1.1|
|>= v4.9.0|v1.2.0|
|>= v5.0.0|v2.0.0|
