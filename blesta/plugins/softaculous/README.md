# Softaculous Plugin

[![Build Status](https://travis-ci.org/blesta/plugin-softaculous.svg?branch=master)](https://travis-ci.org/blesta/plugin-softaculous) [![Coverage Status](https://coveralls.io/repos/github/blesta/plugin-softaculous/badge.svg?branch=master)](https://coveralls.io/github/blesta/plugin-softaculous?branch=master)

This is a plugin for Blesta that integrates with [Softaculous](https://www.softaculous.com/).  When a service is created by cPanel, CentOS Web Panel, Plesk, Interworx, or ISPmanager this plugin runs a softaculous script.

## Install the Plugin

1. You can install the plugin via composer:

    ```
    composer require blesta/softaculous
    ```

2. OR upload the source code to a /plugins/softaculous/ directory within
your Blesta installation path.

    For example:

    ```
    /var/www/html/blesta/plugins/softaculous/
    ```

3. Log in to your admin Blesta account and navigate to
> Settings > Plugins

4. Find the Softaculous plugin and click the "Install" button to install it

5. Add the following configurable options:
 - admin_name: The admin username to be used by software installed by softaculous
 - admin_pass: The admin password to be used by software installed by softaculous
 - directory: The directory in which software will be installed installed
 - script: The softaculous script to run

You're done!
