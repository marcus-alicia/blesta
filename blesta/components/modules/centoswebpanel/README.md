# CentOS Web Panel

[![Build Status](https://travis-ci.org/blesta/module-centoswebpanel.svg?branch=master)](https://travis-ci.org/blesta/module-centoswebpanel) [![Coverage Status](https://coveralls.io/repos/github/blesta/module-centoswebpanel/badge.svg?branch=master)](https://coveralls.io/github/blesta/module-centoswebpanel?branch=master)

This is a module for Blesta that integrates with [CentOS Web Panel](https://centos-webpanel.com/).

## Install the Module

1. You can install the module via composer:

    ```
    composer require blesta/centoswebpanel
    ```

2. OR upload the source code to a /components/modules/centoswebpanel/ directory within
your Blesta installation path.

    For example:

    ```
    /var/www/html/blesta/components/modules/centoswebpanel/
    ```

3. Log in to your admin Blesta account and navigate to
> Settings > Modules

4. Find the CentOS Web Panel module and click the "Install" button to install it

5. You're done!

### Blesta Compatibility

|Blesta Version|Module Version|
|--------------|--------------|
|< v4.9.0|v2.1.0|
|>= v4.9.0|v2.2.0+|
|>= v5.0.0|v2.4.0+|
