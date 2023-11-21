# cWatch Module

[![Build Status](https://travis-ci.org/blesta/module-cwatch.svg?branch=master)](https://travis-ci.org/blesta/module-cwatch) [![Coverage Status](https://coveralls.io/repos/github/blesta/module-cwatch/badge.svg?branch=master)](https://coveralls.io/github/blesta/module-cwatch?branch=master)

This is a module for Blesta that integrates with [cWatch](https://cwatch.comodo.com/).

## Install the Module

1. You can install the module via composer:

    ```
    composer require blesta/cwatch
    ```

2. OR upload the source code to a /components/modules/cwatch/ directory within
your Blesta installation path.

    For example:

    ```
    /var/www/html/blesta/components/modules/cwatch/
    ```

3. Log in to your admin Blesta account and navigate to
> Settings > Modules

4. Find the cWatch module, click the "Install", and enter your cWatch username and password

### Blesta Compatibility

|Blesta Version|Module Version|
|--------------|--------------|
|< v4.2.0|N/A|
|>= v4.2.0|v2.0.0+|
|>= v4.9.0|v2.4.0+|
|>= v5.0.0|v2.6.0+|

## When creating a package
#### Multi-license Packages

1. Add quantity configurable options for each license type you wish to offer.  Available products include:

    ```
    BASIC_DETECTION
    STARTER
    PRO
    PRO_FREE
    PRO_FREE_60D
    PREMIUM
    PREMIUM_FREE
    PREMIUM_FREE_60D
    ```

2. The 'Name' of your configurable option must match the product exactly as shown here (case-sensitive).

3. The type of your configurable options should primarily be quantity

4. The licenses are only available for the following payment terms, set config option payment terms accordingly:

    ```
    BASIC_DETECTION - any
    STARTER - 1 Month, 12 Months, 24 Month, 36 Months
    PRO - 1 Month, 12 Months, 24 Month, 36 Months
    PRO_FREE - 1 Month
    PRO_FREE_60D - 2 Months
    PREMIUM - 1 Month, 12 Months, 24 Month, 36 Months
    PREMIUM_FREE - 1 Month
    PREMIUM_FREE_60D - 2 Months
    ```

#### Single-license Packages

1. The licenses are only available for the following payment terms, set package payment terms accordingly:

    ```
    BASIC_DETECTION - any
    STARTER - 1 Month, 12 Months, 24 Month, 36 Months
    PRO - 1 Month, 12 Months, 24 Month, 36 Months
    PRO_FREE - 1 Month
    PRO_FREE_60D - 2 Months
    PREMIUM - 1 Month, 12 Months, 24 Month, 36 Months
    PREMIUM_FREE - 1 Month
    PREMIUM_FREE_60D - 2 Months
    ```

## Creating a Service
#### Multi-license Packages
1. First make sure you have your multi-license package set up along with configurable options for all the licenses you want to offer.
2. Start creating a service either through the order system or the admin interface, selecting your package and term.
3. Enter the name/email for the user to assign these licenses to.
4. Enter a quantity for each configurable option corresponding to the number of cWatch licenses of that type you want to provision in cWatch.
5. After finishing creation, a customer will be created or updated in cWatch, then the given licenses will be provisioned.

#### Single-license Packages
1. First make sure you have your single-license package set up with the license type you want to offer.
2. Start creating a service either through the order system or the admin interface, selecting your package and term.
3. Enter the name/email for the user to assign this license to.
4. Single license packages have the ability to automatically attach a site to the license that will be created.  Enter the domain for the site you want to attach to the license.
5. After finishing creation, a customer will be created or updated in cWatch, then the given licenses will be provisioned and the site will be attached to it.

## Adding More Licenses
#### Multi-license Packages
1. Manage the service.
2. Change the configurable options to the desired number of licenses.
3. New licenses will be provisioned in cWatch until the total number of active licenses matches the configurable options.

#### Single-license Packages
1. A new service must be created.

## Removing Licenses
#### Multi-license Packages
1. Manage the service.
2. Change the configurable options to the desired number of licenses.
3. Licenses without attached sites will be selected for removal arbitrarily until enough are selected to match the configurable options.  An error will be given if not enough licenses are without a domain.
4. These license will be deactivated in cWatch.
5. All licenses on the service may also be deactivated and their sites removed by canceling the service.

#### Single-license Packages
1. The service must be canceled, which will remove the site from the license and deactivate it.

## Adding a Site to a License
1. Manage the service.
2. Go to the Manage Licenses tab.
3. Select the Add Site option on one of the available licenses.
4. Enter your domain.
5. Click the Submit button.
6. A request will be sent to cWatch which will begin the process of attaching the site to the license.
7. The progress of this can be seen on the Manage Licenses tab under the Domain Status column.

## Removing a Site
1. Manage the service.
2. Go to the Manage Licenses tab.
3. Select and confirm the Remove Site option.
4. The site will be detached from the license and available to be added to another.

## Upgrading/downgrading the License for a Site
#### Multi-license Packages
1. Manage the service.
2. Change the configurable options to add a license of the type you want to upgrade the domain to.
3. Manage the service.
4. Go to the Manage Licenses tab.
5. To downgrade first select and confirm the Deactivate License option.
5. Select the Upgrade Site option on the license with the site you want to transfer.
6. Select license you want to transfer to.
7. Click 'Upgrade Site'.
8. cWatch will attempt to move the site from one license to the other while preserving scanners and settings.

#### Single-license Packages
1. Manage the service.
2. Change service to a single-license package of the desired license type.
3. cWatch will attempt to provision a license of the new type, deactivate the current license, and move the site from one license to the other while preserving scanners and settings.
4. If the license type is changing from Starter to Pro or Premium, or changing from Pro to Premium, the license itself will be upgraded instead.

## Adding a Malware Scanner
1. Manage the service.
2. Go to the Malware Control tab.
3. Select the domain to add a scanner to.
4. Enter FTP credentials for the domain.
5. Check the credentials by clicking the Test These Credentials button.
6. If the test succeeds, submit the credentials.
7. cWatch will use these to automatically install a malware scanner at that domain.
