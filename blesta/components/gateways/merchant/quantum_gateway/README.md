# Quantum Gateway

[![Build Status](https://travis-ci.org/blesta/gateway-quantum_gateway.svg?branch=master)](https://travis-ci.org/blesta/gateway-quantum_gateway) [![Coverage Status](https://coveralls.io/repos/github/blesta/gateway-quantum_gateway/badge.svg?branch=master)](https://coveralls.io/github/blesta/gateway-quantum_gateway?branch=master)

This is a merchant gateway for Blesta that integrates with [Quantum Gateway](https://www.quantumgateway.com/index.php/).

## Install the Gateway

1. You can install the gateway via composer:

    ```
    composer require blesta/quantum_gateway
    ```

2. Upload the source code to a /components/gateways/merchant/quantum_gateway/ directory within
your Blesta installation path.

    For example:

    ```
    /var/www/html/blesta/components/merchant/quantum_gateway/
    ```

3. Log in to your admin Blesta account and navigate to
> Settings > Payment Gateways

4. Find the Quantum Gateway and click the "Install" button to install it

5. You're done!

### Blesta Compatibility

|Blesta Version|Module Version|
|--------------|--------------|
|< v4.9.0|v1.1.0|
|>= v4.9.0|v1.2.0|
