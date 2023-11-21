# PHPIDS Plugin

[![Build Status](https://travis-ci.org/blesta/plugin-phpids.svg?branch=master)](https://travis-ci.org/blesta/plugin-phpids) [![Coverage Status](https://coveralls.io/repos/github/blesta/plugin-phpids/badge.svg?branch=master)](https://coveralls.io/github/blesta/plugin-phpids?branch=master)

Intrusion Detection System to help identify suspicious activity.

## Install the Plugin

1. You can install the plugin via composer:

    ```
    composer require blesta/phpids
    ```

2. OR upload the source code to a /plugins/phpids/ directory within
your Blesta installation path.

    For example:

    ```
    /var/www/html/blesta/plugins/phpids/
    ```

3. Log in to your admin Blesta account and navigate to
> Settings > Plugins

4. Find the PHPIDS plugin and click the "Install" button to install it

5. You're done!

### Blesta Compatibility

|Blesta Version|Module Version|
|--------------|--------------|
|< v4.9.0|v1.5.0|
|>= v4.9.0|v1.6.0+|
|>= v5.0.0|v1.7.0+|
