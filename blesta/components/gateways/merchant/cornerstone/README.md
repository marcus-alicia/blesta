# Cornerstone

[![Build Status](https://travis-ci.org/blesta/gateway-cornerstone.svg?branch=master)](https://travis-ci.org/blesta/gateway-cornerstone) [![Coverage Status](https://coveralls.io/repos/github/blesta/gateway-cornerstone/badge.svg?branch=master)](https://coveralls.io/github/blesta/gateway-cornerstone?branch=master)

This is a merchant gateway for Blesta that integrates with [Cornerstone](https://cornerstonepaymentsystems.com).

## Install the Gateway

1. You can install the gateway via composer:

    ```
    composer require blesta/cornerstone
    ```

2. Upload the source code to a /components/gateways/merchant/cornerstone/ directory within
your Blesta installation path.

    For example:

    ```
    /var/www/html/blesta/components/merchant/cornerstone/
    ```

3. Log in to your admin Blesta account and navigate to
> Settings > Payment Gateways

4. Find the Cornerstone and click the "Install" button to install it

5. You're done!
