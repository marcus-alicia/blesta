# BluePay Gateway

[![Build Status](https://travis-ci.org/blesta/gateway-blue_pay.svg?branch=master)](https://travis-ci.org/blesta/gateway-blue_pay) [![Coverage Status](https://coveralls.io/repos/github/blesta/gateway-blue_pay/badge.svg?branch=master)](https://coveralls.io/github/blesta/gateway-blue_pay?branch=master)

This is a merchant gateway for Blesta that integrates with [BluePay](https://www.bluepay.com/).

## Install the Gateway

1. You can install the gateway via composer:

    ```
    composer require blesta/blue_pay
    ```

2. Upload the source code to a /components/gateways/merchant/blue_pay/ directory within
your Blesta installation path.

    For example:

    ```
    /var/www/html/blesta/components/merchant/blue_pay/
    ```

3. Log in to your admin Blesta account and navigate to
> Settings > Payment Gateways

4. Find the BluePay gateway and click the "Install" button to install it

5. You're done!

### Blesta Compatibility

|Blesta Version|Module Version|
|--------------|--------------|
|< v4.9.0|v1.1.0|
|>= v4.9.0|v1.2.0|
