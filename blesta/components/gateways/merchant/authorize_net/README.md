# Authorize.net Gateway

[![Build Status](https://travis-ci.org/blesta/gateway-authorize_net.svg?branch=master)](https://travis-ci.org/blesta/gateway-authorize_net) [![Coverage Status](https://coveralls.io/repos/github/blesta/gateway-authorize_net/badge.svg?branch=master)](https://coveralls.io/github/blesta/gateway-authorize_net?branch=master)

This is a merchant gateway for Blesta that integrates with [Authorize.net](https://www.authorize.net/).

## Install the Gateway

1. You can install the gateway via composer:

    ```
    composer require blesta/authorize_net
    ```

2. Upload the source code to a /components/gateways/merchant/authorize_net/ directory within
your Blesta installation path.

    For example:

    ```
    /var/www/html/blesta/components/merchant/authorize_net/
    ```

3. Log in to your admin Blesta account and navigate to
> Settings > Payment Gateways

4. Find the Authorize.net gateway and click the "Install" button to install it

5. You're done!

### Blesta Compatibility

|Blesta Version|Module Version|
|--------------|--------------|
|< v4.9.0|v1.7.0|
|>= v4.9.0|v1.8.0|
|>= v5.0.0|v1.9.0|
