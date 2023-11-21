# Portal/CMS Plugin

[![Build Status](https://travis-ci.org/blesta/plugin-cms.svg?branch=master)](https://travis-ci.org/blesta/plugin-cms) [![Coverage Status](https://coveralls.io/repos/github/blesta/plugin-cms/badge.svg?branch=master)](https://coveralls.io/github/blesta/plugin-cms?branch=master)

Portal is a plugin that makes a web portal available at the default installation URL.

## Install the Plugin

1. You can install the plugin via composer:

    ```
    composer require blesta/cms
    ```

2. OR upload the source code to a /plugins/cms/ directory within
your Blesta installation path.

    For example:

    ```
    /var/www/html/blesta/plugins/cms/
    ```

3. Log in to your admin Blesta account and navigate to
> Settings > Plugins

4. Find the Portal plugin and click the "Install" button to install it

5. You're done!

### Blesta Compatibility

|Blesta Version|Module Version|
|--------------|--------------|
|< v4.9.0|v2.4.0|
|>= v4.9.0|v2.5.0+|
|>= v5.0.0|v2.6.0+|
