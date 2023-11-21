# BTCPay Server

BTCPay Server is a self-hosted, Bitcoin payment gateway with no fees.

## Install the Gateway

1. You can install the gateway via composer:

    ```
    composer require blesta/btcpay_server
    ```

2. OR upload the source code to a /components/gateways/nonmerchant/btcpay_server/ directory within
your Blesta installation path.

    For example:

    ```
    /var/www/html/blesta/gateways/nonmerchant/btcpay_server/
    ```

3. Log in to your admin Blesta account and navigate to
> Settings > Payment Gateways

4. Find the BTCPay Server gateway and click the "Install" button to install it

5. You're done!
