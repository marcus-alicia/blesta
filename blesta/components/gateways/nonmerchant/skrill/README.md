# Skrill Gateway

[![Build Status](https://travis-ci.org/blesta/gateway-skrill.svg?branch=master)](https://travis-ci.org/blesta/gateway-skrill) [![Coverage Status](https://coveralls.io/repos/github/blesta/gateway-skrill/badge.svg?branch=master)](https://coveralls.io/github/blesta/gateway-skrill?branch=master)

This is a nonmerchant gateway for Blesta that integrates with [Skrill](https://www.skrill.com/).

## Install the Gateway

1. You can install the gateway via composer:

    ```
    composer require blesta/skrill
    ```

2. Upload the source code to a /components/gateways/nonmerchant/skrill/ directory within
your Blesta installation path.

    For example:

    ```
    /var/www/html/blesta/components/nonmerchant/skrill/
    ```

3. Log in to your admin Blesta account and navigate to
> Settings > Payment Gateways

4. Find the Skrill gateway and click the "Install" button to install it

5. You're done!

### Blesta Compatibility

|Blesta Version|Module Version|
|--------------|--------------|
|< v4.9.0|v1.3.0|
|>= v4.9.0|v1.4.0|
