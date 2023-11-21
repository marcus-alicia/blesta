# Auto Cancel Plugin

[![Build Status](https://travis-ci.org/blesta/plugin-auto_cancel.svg?branch=master)](https://travis-ci.org/blesta/plugin-auto_cancel) [![Coverage Status](https://coveralls.io/repos/github/blesta/plugin-auto_cancel/badge.svg?branch=master)](https://coveralls.io/github/blesta/plugin-auto_cancel?branch=master)

Automatically schedules suspended services for cancellation.

## Install the Plugin

1. You can install the plugin via composer:

    ```
    composer require blesta/auto_cancel
    ```

2. OR upload the source code to a /plugins/auto_cancel/ directory within
your Blesta installation path.

    For example:

    ```
    /var/www/html/blesta/plugins/auto_cancel/
    ```

3. Log in to your admin Blesta account and navigate to
> Settings > Plugins

4. Find the Auto Cancel plugin and click the "Install" button to install it

5. You're done!

### Blesta Compatibility

|Blesta Version|Module Version|
|--------------|--------------|
|< v4.9.0|v1.2.0|
|>= v4.9.0|v1.3.0+|

## Overview

This plugin adds an automated task to your Blesta installation that will
schedule suspended services for cancellation based on a couple settings:

- **Schedule Cancellation Days After Suspended**
    - This controls when a service receives a scheduled cancellation date.
- **Cancel Services Days After Suspended**
    - This controls the cancellation date a service receives.

For example, let's say a service is suspended on August 20. If
**Schedule Cancellation Days After Suspended** is set to **2 days**, on August
22 the service will receive a scheduled cancellation date. If
**Cancel Services Days After Suspended** is set to **4 days** the scheduled
cancellation date will then be set to August 24.

This allows you to control not only when a suspended service is canceled, but
when it receives its scheduled cancellation date.
