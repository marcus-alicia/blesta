# Import Manager Plugin

[![Build Status](https://travis-ci.org/blesta/plugin-import_manager.svg?branch=master)](https://travis-ci.org/blesta/plugin-import_manager) [![Coverage Status](https://coveralls.io/repos/github/blesta/plugin-import_manager/badge.svg?branch=master)](https://coveralls.io/github/blesta/plugin-import_manager?branch=master)

Import Manager is a plugin that imports data from legacy Blesta, or a competing billing application.

## Install the Plugin

1. You can install the plugin via composer:

    ```
    composer require blesta/import_manager
    ```

2. OR upload the source code to a /plugins/import_manager/ directory within
your Blesta installation path.

    For example:

    ```
    /var/www/html/blesta/plugins/import_manager/
    ```

3. Log in to your admin Blesta account and navigate to
> Settings > Plugins

4. Find the Import Manager plugin and click the "Install" button to install it

5. You're done!

### Blesta Compatibility

|Blesta Version|Module Version|
|--------------|--------------|
|< v4.9.0|v1.8.0|
|>= v4.9.0|v1.9.0+|
|>= v5.0.0|v1.12.0+|
