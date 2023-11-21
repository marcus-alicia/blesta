# Razorpay Gateway

[![Build Status](https://travis-ci.org/blesta/gateway-razorpay.svg?branch=master)](https://travis-ci.org/blesta/gateway-razorpay) [![Coverage Status](https://coveralls.io/repos/github/blesta/gateway-razorpay/badge.svg?branch=master)](https://coveralls.io/github/blesta/gateway-razorpay?branch=master)

This is a non-merchant gateway for Blesta that integrates with [Razorpay](https://razorpay.com/).

## Install the Gateway

1. You can install the gateway via composer:

    ```
    composer require blesta/razorpay
    ```

2. Upload the source code to a /components/gateways/nonmerchant/razorpay/ directory within
your Blesta installation path.

    For example:

    ```
    /var/www/html/blesta/components/nonmerchant/razorpay/
    ```

3. Log in to your admin Blesta account and navigate to
> Settings > Payment Gateways

4. Find the Razorpay gateway and click the "Install" button to install it

5. You're done!
