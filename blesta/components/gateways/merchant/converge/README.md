# Converge Gateway

[![Build Status](https://travis-ci.org/blesta/gateway-converge.svg?branch=master)](https://travis-ci.org/blesta/gateway-converge) [![Coverage Status](https://coveralls.io/repos/github/blesta/gateway-converge/badge.svg?branch=master)](https://coveralls.io/github/blesta/gateway-converge?branch=master)

This is a merchant gateway for Blesta that integrates with [Converge](https://support.convergepay.com/s/).

## Install the Gateway

1. You can install the gateway via composer:

    ```
    composer require blesta/converge
    ```

2. Upload the source code to a /components/gateways/merchant/converge/ directory within
your Blesta installation path.

    For example:

    ```
    /var/www/html/blesta/components/merchant/converge/
    ```

3. Log in to your admin Blesta account and navigate to
> Settings > Payment Gateways

4. Find the Converge gateway and click the "Install" button to install it

5. You're done!

### Blesta Compatibility

|Blesta Version|Module Version|
|--------------|--------------|
|< v4.9.0|v1.1.0|
|>= v4.9.0|v1.2.0|
