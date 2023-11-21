# Coin Payments Gateway

[![Build Status](https://travis-ci.org/blesta/gateway-coin_payments.svg?branch=master)](https://travis-ci.org/blesta/gateway-coin_payments) [![Coverage Status](https://coveralls.io/repos/github/blesta/gateway-coin_payments/badge.svg?branch=master)](https://coveralls.io/github/blesta/gateway-coin_payments?branch=master)

This is a nonmerchant gateway for Blesta that integrates with [Coin Payments](https://coinpayments.net).

## Install the Gateway

1. You can install the gateway via composer:

    ```
    composer require blesta/coin_payments
    ```

2. Upload the source code to a /components/gateways/nonmerchant/coin_payments/ directory within
your Blesta installation path.

    For example:

    ```
    /var/www/html/blesta/components/nonmerchant/coin_payments/
    ```

3. Log in to your admin Blesta account and navigate to
> Settings > Payment Gateways

4. Find the Coin Payments gateway and click the "Install" button to install it

5. You're done!

### Blesta Compatibility

|Blesta Version|Module Version|
|--------------|--------------|
|< v4.9.0|v1.3.0|
|>= v4.9.0|v1.4.0|
