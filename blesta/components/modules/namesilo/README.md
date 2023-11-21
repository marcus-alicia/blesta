# Blesta/Namesilo
Blesta Namesilo module

This module interfaces with Namesilo's domain API to allow domain registrations, transfers and renewals through Blesta.

www.blesta.com

www.namesilo.com

**Installation**

Extract files to components/modules/namesilo/.

Log on to the Blesta admin dashboard and go to Settings -> Company -> Modules -> Available.

Enable the Namesilo module and go to Manage to enter your Namesilo user ID and API key. Enable or disable sandbox mode to enable or disable module testing.

**Email Templates**

The module provides the following template tags for use in welcome email templates.

| Tag  | Description |
| ------------- | ------------- |
| service.auth  | The EPP code submitted.  |
| service.transfer | Value is true for transfers.  Will not exist for new registrations |
| service.domain  | The domain name being registered or transferred |
| service.ns1 | The first nameserver value submitted on the order form. |
| service.ns2 | The second nameserver value submitted on the order form. |
| service.ns3 | The third nameserver value submitted on the order form. |
| service.ns4 | The fourth nameserver value submitted on the order form. |
| service.ns5 | The fifth nameserver value submitted on the order form. |
| service.ad | WHOIS Address Line 1 |
| service.ad2 | WHOIS Address Line 2 |
| service.ct | WHOIS Country |
| service.st | WHOIS State/Province |
| service.cy | WHOIS City |
| service.zp | WHOIS Zip/Postal Code |
| service.em | WHOIS Email |
| service.fn | WHOIS First Name |
| service.ln | WHOIS Last Name |
| service.cp | WHOIS Company |
| service.ph | WHOIS Phone Number |
| service.years | Years of registration or extension if it's a transfer |
| package.meta.ns | Array of default nameservers configured on the package.  This may not match what the customer submitted if they changed it. |

**[Get your Namesilo account here](https://www.namesilo.com/)**

### Blesta Compatibility

|Blesta Version|Module Version|
|--------------|--------------|
|< v4.9.0|v1.9.0|
|>= v4.9.0|v1.10.0+|
|>= v5.0.0|v1.13.0+|
|>= v5.1.0|v1.14.0+|
|>= v5.3.0|v1.15.0+|
