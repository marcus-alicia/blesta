# cPanel Module

[![Build Status](https://travis-ci.org/blesta/module-cpanel.svg?branch=master)](https://travis-ci.org/blesta/module-cpanel) [![Coverage Status](https://coveralls.io/repos/github/blesta/module-cpanel/badge.svg?branch=master)](https://coveralls.io/github/blesta/module-cpanel?branch=master)

This is a module for Blesta that integrates with [cPanel](https://www.cpanel.net/).

## Install the Module

1. You can install the module via composer:

    ```
    composer require blesta/cpanel
    ```

2. OR upload the source code to a /components/modules/cpanel/ directory within
your Blesta installation path.

    For example:

    ```
    /var/www/html/blesta/components/modules/cpanel/
    ```

3. Log in to your admin Blesta account and navigate to
> Settings > Modules

4. Find the cPanel module and click the "Install" button to install it

5. You're done!

### Blesta Compatibility

|Blesta Version|Module Version|
|--------------|--------------|
|< v4.2.0|v2.4.0|
|>= v4.2.0|v2.5.0+|
|>= v4.9.0|v2.12.0+|
|>= v5.0.0|v2.15.0+|
