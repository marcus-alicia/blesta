# Pagseguro Gateway

[![Build Status](https://travis-ci.org/blesta/gateway-pagseguro.svg?branch=master)](https://travis-ci.org/blesta/gateway-pagseguro) [![Coverage Status](https://coveralls.io/repos/github/blesta/gateway-pagseguro/badge.svg?branch=master)](https://coveralls.io/github/blesta/gateway-pagseguro?branch=master)

This is a nonmerchant gateway for Blesta that integrates with [Pagseguro](https://pagseguro.uol.com.br/#rmcl).

## Install the Gateway

1. You can install the gateway via composer:

    ```
    composer require blesta/pagseguro
    ```

2. Upload the source code to a /components/gateways/nonmerchant/pagseguro/ directory within
your Blesta installation path.

    For example:

    ```
    /var/www/html/blesta/components/nonmerchant/pagseguro/
    ```

3. Log in to your admin Blesta account and navigate to
> Settings > Payment Gateways

4. Find the Pagseguro gateway and click the "Install" button to install it

5. You're done!

### Blesta Compatibility

|Blesta Version|Module Version|
|--------------|--------------|
|< v4.9.0|v1.2.0|
|>= v4.9.0|v1.3.0|
