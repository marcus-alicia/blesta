# Plesk Module

[![Build Status](https://travis-ci.org/blesta/module-plesk.svg?branch=master)](https://travis-ci.org/blesta/module-plesk) [![Coverage Status](https://coveralls.io/repos/github/blesta/module-plesk/badge.svg?branch=master)](https://coveralls.io/github/blesta/module-plesk?branch=master)

This is a module for Blesta that integrates with [Plesk](https://www.plesk.com/).

## Install the Module

1. You can install the module via composer:

    ```
    composer require blesta/plesk
    ```

2. OR upload the source code to a /components/modules/plesk/ directory within
your Blesta installation path.

    For example:

    ```
    /var/www/html/blesta/components/modules/plesk/
    ```

3. Log in to your admin Blesta account and navigate to
> Settings > Modules

4. Find the Plesk module and click the "Install" button to install it

5. You're done!

### Blesta Compatibility

|Blesta Version|Module Version|
|--------------|--------------|
|< v4.2.0|v2.4.0|
|>= v4.2.0|v2.5.0+|
|>= v4.3.0|v2.8.1+|
|>= v4.9.0|v2.10.0+|
|>= v5.0.0|v2.12.0+|
