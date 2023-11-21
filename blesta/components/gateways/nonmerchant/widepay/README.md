# Wide Pay Gateway

[![Build Status](https://travis-ci.org/blesta/gateway-widepay.svg?branch=master)](https://travis-ci.org/blesta/gateway-widepay) [![Coverage Status](https://coveralls.io/repos/github/blesta/gateway-widepay/badge.svg?branch=master)](https://coveralls.io/github/blesta/gateway-widepay?branch=master)

This is a non-merchant gateway for Blesta that integrates with [Wide Pay](https://widepay.com/).

## Install the Gateway

1. You can install the gateway via composer:

    ```
    composer require blesta/widepay
    ```

2. Upload the source code to a /components/gateways/nonmerchant/widepay/ directory within
your Blesta installation path.

    For example:

    ```
    /var/www/html/blesta/components/nonmerchant/widepay/
    ```

3. Log in to your admin Blesta account and navigate to
> Settings > Payment Gateways

4. Find the Wide Pay gateway and click the "Install" button to install it

5. Set wallet ID and token

6. Set up custom client field (see https://docs.blesta.com/display/user/Wide+Pay)

7. You're done!
