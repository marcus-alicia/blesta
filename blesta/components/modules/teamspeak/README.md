# TCAdmin Module

[![Build Status](https://travis-ci.org/blesta/module-tcadmin.svg?branch=master)](https://travis-ci.org/blesta/module-tcadmin) [![Coverage Status](https://coveralls.io/repos/github/blesta/module-tcadmin/badge.svg?branch=master)](https://coveralls.io/github/blesta/module-tcadmin?branch=master)

This is a module for Blesta that integrates with [TCAdmin](https://www.teamspeak.com/en/).

## Install the Module

1. You can install the module via composer:

    ```
    composer require blesta/tcadmin
    ```

2. OR upload the source code to a /components/modules/tcadmin/ directory within
your Blesta installation path.

    For example:

    ```
    /var/www/html/blesta/components/modules/tcadmin/
    ```

3. Log in to your admin Blesta account and navigate to
> Settings > Modules

4. Find the TCAdmin module and click the "Install" button to install it

5. You're done!

### Blesta Compatibility

|Blesta Version|Module Version|
|--------------|--------------|
|< v4.2.0|N/A|
|>= v4.2.0|v1.1.0+|
|>= v4.9.0|v1.5.0+|
