# Blesta #

Blesta is a well-written, security-focused, user and developer-friendly client
management, billing, and support application.

## Minimum Requirements ##

* PHP version 7.2
* PDO, pdo_mysql, curl (version 7.10.5), and openssl (version 0.9.6) PHP extensions.
* MySQL version 5.6.0 or MariaDB 10.0.5
* Apache, IIS, or LiteSpeed Web Server
* ionCube PHP loader

## Recommended Requirements ##
* PHP version 7.2 through 7.4
* openssl (version 1.1.1a or later), gmp, imap, json, ldap, libxml, mailparse, iconv, mbstring, mcrypt (PHP <7.2), simplexml, soap, and zlib PHP extensions
* MySQL version 5.7.7, or MariaDB version 10.2.2 or later with max_allowed_packet = 128M or higher, and wait_timeout = 3600
* memory_limit set to 256 MB or greater

## Installation ##

To install, upload the contents of blesta to your web server and visit this
location in your browser.

For more detailed instructions, please see the
[documentation](http://docs.blesta.com/display/user/Installing+Blesta) for
installing Blesta.

## Upgrading ##

Note! Back up your database and files before beginning an upgrade.

To upgrade, overwrite the files in your existing installation and access
~/admin/upgrade in your browser.

For more detailed instructions, please see the
[documentation](http://docs.blesta.com/display/user/Upgrading+Blesta) for
upgrading Blesta.

## Patching ##

Note! Back up your database and files before applying a patch.

Patches contain all patches issued for the minor release. For example, a patch
labeled 3.0.6 will contain all patches issued from 3.0.1, so it is not necessary
to apply patches incrementally.

To patch your installation, overwrite the files in your existing installation
and access ~/admin/upgrade in your browser.

For more detailed instructions, please see the
[documentation](http://docs.blesta.com/display/user/Upgrading+Blesta#UpgradingBlesta-Patchinganexistinginstall)
for patching Blesta.

