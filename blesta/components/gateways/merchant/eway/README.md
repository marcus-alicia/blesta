# eWay Gateway

[![Build Status](https://travis-ci.org/blesta/gateway-eway.svg?branch=master)](https://travis-ci.org/blesta/gateway-eway) [![Coverage Status](https://coveralls.io/repos/github/blesta/gateway-eway/badge.svg?branch=master)](https://coveralls.io/github/blesta/gateway-eway?branch=master)

This is a merchant gateway for Blesta that integrates with [eWay](https://www.eway.com.au/).

## Install the Gateway

1. You can install the gateway via composer:

    ```
    composer require blesta/eway
    ```

2. Upload the source code to a /components/gateways/merchant/eway/ directory within
your Blesta installation path.

    For example:

    ```
    /var/www/html/blesta/components/merchant/eway/
    ```

3. Log in to your admin Blesta account and navigate to
> Settings > Payment Gateways

4. Find the eWay gateway and click the "Install" button to install it

5. You're done!

### Blesta Compatibility

|Blesta Version|Module Version|
|--------------|--------------|
|< v4.9.0|v1.2.0|
|>= v4.9.0|v1.3.0|
