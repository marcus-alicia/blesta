<?php
// Emails
Configure::set('Order.install.emails', [
    [
        'action' => 'Order.received',
        'type' => 'staff',
        'plugin_dir' => 'order',
        'tags' => '{order},{services},{invoice},{client}',
        'from' => 'sales@mydomain.com',
        'from_name' => 'Blesta Order System',
        'subject' => 'An order has been received',
        'text' => 'A new order has been received by the system.

Summary

Order Form: {order.order_form_name}
Order Number: {order.order_number}
Status: {order.status}
Amount: {invoice.total} {order.currency}
IP Address: {order.ip_address}{% if order.fraud_status !="" %}
Fraud Status: {order.fraud_status}{% endif %}

Client Details

{order.client_id_code}
{order.client_first_name} {order.client_last_name}
{order.client_company}
{order.client_address1}
{order.client_email}

Items Ordered

{% for item in services %}{item.package.name}
{item.name}{% for option in item.options %}
{option.option_label} x{option.qty}: {option.option_value}{% endfor %}
--
{% endfor %}',
        'html' => '<p>
	A new order has been received by the system.</p>
<p>
	<strong>Summary</strong></p>
<p>
	Order Form: {order.order_form_name}<br />
	Order Number: {order.order_number}<br />
	Status: {order.status}<br />
	Amount: {invoice.total} {order.currency}<br />
    IP Address: {order.ip_address}{% if order.fraud_status !="" %}<br />
	Fraud Status: {order.fraud_status}{% endif %}</p>
<p>
	<strong>Client Details</strong></p>
<p>
	{order.client_id_code}<br />
	{order.client_first_name} {order.client_last_name}<br />
	{order.client_company}<br />
	{order.client_address1}<br />
	{order.client_email}</p>
<p>
	<strong>Items Ordered</strong></p>
<p>
	{% for item in services %}{item.package.name}<br />
	{item.name}{% for option in item.options %}<br />
	{option.option_label} x{option.qty}: {option.option_value}{% endfor %}</p>
<p>
	--<br />
	{% endfor %}</p>
'
    ],
    [
        'action' => 'Order.received_mobile',
        'type' => 'staff',
        'plugin_dir' => 'order',
        'tags' => '{order},{services},{invoice},{client}',
        'from' => 'sales@mydomain.com',
        'from_name' => 'Blesta Order System',
        'subject' => 'An order has been received',
        'text' => 'Order Form: {order.order_form_name}
Order Number: {order.order_number}
Status: {order.status}
Amount: {invoice.total} {order.currency}
IP Address: {order.ip_address}{% if order.fraud_status !="" %}
Fraud Status: {order.fraud_status}{% endif %}

Client Details

{order.client_id_code}
{order.client_first_name} {order.client_last_name}
{order.client_company}
{order.client_address1}
{order.client_email}

Items Ordered

{% for item in services %}{item.package.name}
{item.name}{% for option in item.options %}
{option.option_label} x{option.qty}: {option.option_value}{% endfor %}
--
{% endfor %}
',
        'html' => '<p>
	<strong>Summary</strong></p>
<p>
	Order Form: {order.order_form_name}<br />
	Order Number: {order.order_number}<br />
	Status: {order.status}<br />
	Amount: {invoice.total} {order.currency}<br />
    IP Address: {order.ip_address}{% if order.fraud_status !="" %}<br />
	Fraud Status: {order.fraud_status}{% endif %}</p>
<p>
	<strong>Client Details</strong></p>
<p>
	{order.client_id_code}<br />
	{order.client_first_name} {order.client_last_name}<br />
	{order.client_company}<br />
	{order.client_address1}<br />
	{order.client_email}</p>
<p>
	<strong>Items Ordered</strong></p>
<p>
	{% for item in services %}{item.package.name}<br />
	{item.name}{% for option in item.options %}<br />
	{option.option_label} x{option.qty}: {option.option_value}{% endfor %}<br />
	--<br />
	{% endfor %}</p>
'
    ],
    [
        'action' => 'Order.affiliate_payout_request',
        'type' => 'staff',
        'plugin_dir' => 'order',
        'tags' => '{staff},{client},{affiliate},{payout}',
        'from' => 'sales@mydomain.com',
        'from_name' => 'Blesta Order System',
        'subject' => 'Affiliate Payout Request',
        'text' => 'Hello {staff.first_name},
A payout request has been made by {client.first_name} {client.last_name} (#{affiliate.id}) in the amount of {payout.requested_amount | currency_format payout.requested_currency}.
',
        'html' => '<p>Hello {staff.first_name},</p>
<p>A payout request has been made by {client.first_name} {client.last_name} (#{affiliate.id}) in the amount of {payout.requested_amount | currency_format payout.requested_currency}.</p>
'
    ],
    [
        'action' => 'Order.affiliate_payout_request_received',
        'type' => 'client',
        'plugin_dir' => 'order',
        'tags' => '{client},{affiliate},{payout}',
        'from' => 'sales@mydomain.com',
        'from_name' => 'Blesta Order System',
        'subject' => 'Affiliate Payout Request Received',
        'text' => 'Hello {client.first_name},
Your affiliate payout request for {payout.requested_amount | currency_format payout.requested_currency} has been received by staff and is currently under review.
',
        'html' => '<p>Hello {client.first_name},</p>
<p>Your affiliate payout request for {payout.requested_amount | currency_format payout.requested_currency} has been received by staff and is currently under review.</p>
'
    ],
    [
        'action' => 'Order.affiliate_monthly_report',
        'type' => 'client',
        'plugin_dir' => 'order',
        'tags' => '{client},{affiliate},{signups},{referrals}',
        'from' => 'sales@mydomain.com',
        'from_name' => 'Blesta Order System',
        'subject' => 'Monthly Affiliate Report',
        'text' => 'Hello {client.first_name},
This is your monthly affiliate report.

Total visitors referred: {affiliate.visits}
Current earnings: {affiliate.meta.total_available | currency_format affiliate.meta.withdrawal_currency}
Amount withdrawn: {affiliate.meta.total_withdrawn | currency_format affiliate.meta.withdrawal_currency}

New referrals this month
{% if signups %}
{% for referral in referrals %}

----

Service: {referral.name}
Sign-up Date: {referral.date_added | date date_format}
Amount: {referral.amount | currency_format referral.currency}
Commission: {referral.commission | currency_format referral.currency}
Status: {referral.status_formatted}
{% endfor %}
{% else %}
You have not received any sign-ups during this period.
{% endif %}
',
        'html' => '<p>Hello {client.first_name},<br />
This is your monthly affiliate report.</p>

<p>
Total visitors referred: {affiliate.visits}<br />
Current earnings: {affiliate.meta.total_available | currency_format affiliate.meta.withdrawal_currency}<br />
Amount withdrawn: {affiliate.meta.total_withdrawn | currency_format affiliate.meta.withdrawal_currency}
</p>

<p>
New referrals this month
</p>
{% if signups %}
{% for referral in referrals %}
<p>
Service: {referral.name}<br />
Sign-up Date: {referral.date_added | date date_format}<br />
Amount: {referral.amount | currency_format referral.currency}<br />
Commission: {referral.commission | currency_format referral.currency}<br />
Status: {referral.status_formatted}
</p>
{% endfor %}
{% else %}
<p>
You have not received any sign-ups during this period.
</p>
{% endif %}'
    ],
    [
        'action' => 'Order.abandoned_cart_first',
        'type' => 'client',
        'plugin_dir' => 'order',
        'tags' => '{order},{services},{client},{payment_url}',
        'from' => 'sales@mydomain.com',
        'from_name' => 'Blesta Order System',
        'subject' => 'Abandoned Order (1st)',
        'text' => 'Hello {client.first_name},
        
We noticed you left the following items in your cart:

{% for item in services %}{item.package.name}
{item.name}{% for option in item.options %}
{option.option_label} x{option.qty}: {option.option_value}{% endfor %}
--
{% endfor %}

Would you like to resume your order? Pay Now: http://{payment_url} (No Login Required)',
        'html' => '<p>Hello {client.first_name},</p>

<p>  
We noticed you left the following items in your cart:
</p>

<p>
	{% for item in services %}{item.package.name}<br />
	{item.name}{% for option in item.options %}<br />
	{option.option_label} x{option.qty}: {option.option_value}{% endfor %}<br />
	--<br />
	{% endfor %}
</p>

<p>
Would you like to resume your order? <a href="http://{payment_url}">Pay Now</a> (No Login Required)
</p>'
    ],
    [
        'action' => 'Order.abandoned_cart_second',
        'type' => 'client',
        'plugin_dir' => 'order',
        'tags' => '{order},{services},{client},{payment_url}',
        'from' => 'sales@mydomain.com',
        'from_name' => 'Blesta Order System',
        'subject' => 'Abandoned Order (2nd)',
        'text' => 'Hello {client.first_name},
        
We noticed you left the following items in your cart:

{% for item in services %}{item.package.name}
{item.name}{% for option in item.options %}
{option.option_label} x{option.qty}: {option.option_value}{% endfor %}
--
{% endfor %}

Would you like to resume your order? Pay Now: http://{payment_url} (No Login Required)',
        'html' => '<p>Hello {client.first_name},</p>

<p>  
We noticed you left the following items in your cart:
</p>

<p>
	{% for item in services %}{item.package.name}<br />
	{item.name}{% for option in item.options %}<br />
	{option.option_label} x{option.qty}: {option.option_value}{% endfor %}<br />
	--<br />
	{% endfor %}
</p>

<p>
Would you like to resume your order? <a href="http://{payment_url}">Pay Now</a> (No Login Required)
</p>'
    ],
    [
        'action' => 'Order.abandoned_cart_third',
        'type' => 'client',
        'plugin_dir' => 'order',
        'tags' => '{order},{services},{client},{payment_url}',
        'from' => 'sales@mydomain.com',
        'from_name' => 'Blesta Order System',
        'subject' => 'Abandoned Order (3rd)',
        'text' => 'Hello {client.first_name},
        
We noticed you left the following items in your cart:

{% for item in services %}{item.package.name}
{item.name}{% for option in item.options %}
{option.option_label} x{option.qty}: {option.option_value}{% endfor %}
--
{% endfor %}

Would you like to resume your order? Pay Now: http://{payment_url} (No Login Required)',
        'html' => '<p>Hello {client.first_name},</p>

<p>  
We noticed you left the following items in your cart:
</p>

<p>
	{% for item in services %}{item.package.name}<br />
	{item.name}{% for option in item.options %}<br />
	{option.option_label} x{option.qty}: {option.option_value}{% endfor %}<br />
	--<br />
	{% endfor %}
</p>

<p>
Would you like to resume your order? <a href="http://{payment_url}">Pay Now</a> (No Login Required)
</p>'
    ]
]);

// Order Forms
Configure::set('Order.order_forms.default_template', 'standard');
