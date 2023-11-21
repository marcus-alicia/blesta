# Offline Gateway

[![Build Status](https://travis-ci.org/blesta/gateway-offline.svg?branch=master)](https://travis-ci.org/blesta/gateway-offline) [![Coverage Status](https://coveralls.io/repos/github/blesta/gateway-offline/badge.svg?branch=master)](https://coveralls.io/github/blesta/gateway-offline?branch=master)

This is a placeholder gateway that will present users will instructions for offline payment.

## Install the Gateway

1. You can install the gateway via composer:

    ```
    composer require blesta/offline
    ```

2. Upload the source code to a /components/gateways/nonmerchant/offline/ directory within
your Blesta installation path.

    For example:

    ```
    /var/www/html/blesta/components/nonmerchant/offline/
    ```

3. Log in to your admin Blesta account and navigate to
> Settings > Payment Gateways

4. Find the Offline gateway and click the "Install" button to install it

5. You're done!

### Blesta Compatibility

|Blesta Version|Module Version|
|--------------|--------------|
|< v4.9.0|v1.2.0|
|>= v4.9.0|v1.3.0|
