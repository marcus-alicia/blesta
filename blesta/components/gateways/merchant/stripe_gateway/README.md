# Stripe Gateway

[![Build Status](https://travis-ci.org/blesta/gateway-stripe_gateway.svg?branch=master)](https://travis-ci.org/blesta/gateway-stripe_gateway) [![Coverage Status](https://coveralls.io/repos/github/blesta/gateway-stripe_gateway/badge.svg?branch=master)](https://coveralls.io/github/blesta/gateway-stripe_gateway?branch=master)

This is a merchant gateway for Blesta that integrates with [Stripe](https://www.stripe.com/).

## Install the Gateway

1. You can install the gateway via composer:

    ```
    composer require blesta/stripe_gateway
    ```

2. Upload the source code to a /components/gateways/merchant/stripe_gateway/ directory within
your Blesta installation path.

    For example:

    ```
    /var/www/html/blesta/components/merchant/stripe_gateway/
    ```

3. Log in to your admin Blesta account and navigate to
> Settings > Payment Gateways

4. Find the Stripe gateway and click the "Install" button to install it

5. You're done!

### Blesta Compatibility

|Blesta Version|Module Version|
|--------------|--------------|
|< v4.9.0|v1.6.0|
|>= v4.9.0|v1.7.0|
