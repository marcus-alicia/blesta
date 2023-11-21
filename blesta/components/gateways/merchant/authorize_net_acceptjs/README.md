# Authorize Net Accept.js

[![Build Status](https://travis-ci.org/blesta/gateway-authorize_net_acceptjs.svg?branch=master)](https://travis-ci.org/blesta/gateway-authorize_net) [![Coverage Status](https://coveralls.io/repos/github/blesta/gateway-authorize_net/badge.svg?branch=master)](https://coveralls.io/github/blesta/gateway-authorize_net?branch=master)

This is a merchant gateway for Blesta that integrates with [Authorize.net](https://www.authorize.net/).

## Install the Gateway

1. You can install the gateway via composer:

    ```
    composer require blesta/authorize_net_acceptjs
    ```

2. Upload the source code to a /components/gateways/merchant/authorize_net_acceptjs/ directory within
your Blesta installation path.

    For example:

    ```
    /var/www/html/blesta/components/merchant/authorize_net_acceptjs/
    ```

3. Log in to your admin Blesta account and navigate to
> Settings > Payment Gateways

4. Find the Authorize Net Accept.js gateway and click the "Install" button to install it

5. You're done!

### Blesta Compatibility

|Blesta Version|Module Version|
|--------------|--------------|
|>= v5.3.0|v1.0.0|
