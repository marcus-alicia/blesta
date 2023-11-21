# {{name}}

{{description}}

## Install the Gateway

1. You can install the gateway via composer:

    ```
    composer require parent_repository/{{snake_case_name}}
    ```

2. OR upload the source code to a /components/gateways/nonmerchant/{{snake_case_name}}/ directory within
your Blesta installation path.

    For example:

    ```
    /var/www/html/blesta/plugins/{{snake_case_name}}/
    ```

3. Log in to your admin Blesta account and navigate to
> Settings > Payment Gateways

4. Find the {{name}} gateway and click the "Install" button to install it

5. You're done!
