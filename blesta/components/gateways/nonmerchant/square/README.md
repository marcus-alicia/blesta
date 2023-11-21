# Square Gateway

[![Build Status](https://travis-ci.org/blesta/gateway-square.svg?branch=master)](https://travis-ci.org/blesta/gateway-square) [![Coverage Status](https://coveralls.io/repos/github/blesta/gateway-square/badge.svg?branch=master)](https://coveralls.io/github/blesta/gateway-square?branch=master)

This is a nonmerchant gateway for Blesta that integrates with [Square](https://www.squareup.com/).

## Install the Gateway

1. You can install the gateway via composer:

    ```
    composer require blesta/square
    ```

2. Upload the source code to a /components/gateways/nonmerchant/square/ directory within
your Blesta installation path.

    For example:

    ```
    /var/www/html/blesta/components/nonmerchant/square/
    ```

3. Log in to your admin Blesta account and navigate to
> Settings > Payment Gateways

4. Find the Square gateway and click the "Install" button to install it

5. You're done!

### Blesta Compatibility

|Blesta Version|Module Version|
|--------------|--------------|
|< v4.9.0|v1.1.0|
|>= v4.9.0|v1.2.0|
