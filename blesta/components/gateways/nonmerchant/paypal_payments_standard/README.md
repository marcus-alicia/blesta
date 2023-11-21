# Paypal Payments Standard Gateway

[![Build Status](https://travis-ci.org/blesta/gateway-paypal_payments_standard.svg?branch=master)](https://travis-ci.org/blesta/gateway-paypal_payments_standard) [![Coverage Status](https://coveralls.io/repos/github/blesta/gateway-paypal_payments_standard/badge.svg?branch=master)](https://coveralls.io/github/blesta/gateway-paypal_payments_standard?branch=master)

This is a nonmerchant gateway for Blesta that integrates with [Paypal Payments Standard](https://www.paypal.com/US/webapps/mpp/referral/paypal-payments-standard).

## Install the Gateway

1. You can install the gateway via composer:

    ```
    composer require blesta/paypal_payments_standard
    ```

2. Upload the source code to a /components/gateways/nonmerchant/paypal_payments_standard/ directory within
your Blesta installation path.

    For example:

    ```
    /var/www/html/blesta/components/nonmerchant/paypal_payments_standard/
    ```

3. Log in to your admin Blesta account and navigate to
> Settings > Payment Gateways

4. Find the Paypal Payments Standard gateway and click the "Install" button to install it

5. You're done!

### Blesta Compatibility

|Blesta Version|Module Version|
|--------------|--------------|
|< v4.9.0|v1.5.0|
|>= v4.9.0|v1.6.0|
