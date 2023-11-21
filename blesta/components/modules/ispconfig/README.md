# ISPConfig Module

[![Build Status](https://travis-ci.org/blesta/module-ispconfig.svg?branch=master)](https://travis-ci.org/blesta/module-ispconfig) [![Coverage Status](https://coveralls.io/repos/github/blesta/module-ispconfig/badge.svg?branch=master)](https://coveralls.io/github/blesta/module-ispconfig?branch=master)

This is a module for Blesta that integrates with [ISPConfig](https://www.ispconfig.org/).

## Install the Module

1. You can install the module via composer:

    ```
    composer require blesta/ispconfig
    ```

2. OR upload the source code to a /components/modules/ispconfig/ directory within
your Blesta installation path.

    For example:

    ```
    /var/www/html/blesta/components/modules/ispconfig/
    ```

3. Log in to your admin Blesta account and navigate to
> Settings > Modules

4. Find the ISPConfig module and click the "Install" button to install it

5. You're done!

### Blesta Compatibility

|Blesta Version|Module Version|
|--------------|--------------|
|< v4.2.0|v1.0.0|
|>= v4.2.0|v1.1.0|
|>= v4.9.0|v1.6.0|
