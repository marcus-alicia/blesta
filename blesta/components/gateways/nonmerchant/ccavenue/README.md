# CCAvenue Gateway

[![Build Status](https://travis-ci.org/blesta/gateway-ccavenue.svg?branch=master)](https://travis-ci.org/blesta/gateway-ccavenue) [![Coverage Status](https://coveralls.io/repos/github/blesta/gateway-ccavenue/badge.svg?branch=master)](https://coveralls.io/github/blesta/gateway-ccavenue?branch=master)

This is a nonmerchant gateway for Blesta that integrates with [CCAvenue](https://www.ccavenue.com/).

## Install the Gateway

1. You can install the gateway via composer:

    ```
    composer require blesta/ccavenue
    ```

2. Upload the source code to a /components/gateways/nonmerchant/ccavenue/ directory within
your Blesta installation path.

    For example:

    ```
    /var/www/html/blesta/components/nonmerchant/ccavenue/
    ```

3. Log in to your admin Blesta account and navigate to
> Settings > Payment Gateways

4. Find the CCAvenue gateway and click the "Install" button to install it

5. You're done!

### Blesta Compatibility

|Blesta Version|Module Version|
|--------------|--------------|
|< v4.9.0|v2.1.0|
|>= v4.9.0|v2.2.0|
