=== Affiliates MailChimp ===
Contributors: itthinx, proaktion, gtsiokos
Donate link: http://www.itthinx.com/shop/
Tags: affiliate, affiliates, referral, growth marketing, MailChimp
Requires at least: 4.9
Tested up to: 5.0.3
Requires PHP: 5.6.0
Stable tag: 3.1.0
License: GPLv3

Integrates [Affiliates](https://wordpress.org/plugins/affiliates/), [Affiliates Pro](https://www.itthinx.com/shop/affiliates-pro/) and [Affiliates Enterprise](https://www.itthinx.com/shop/affiliates-enterprise/) with [MailChimp](https://mailchimp.com).

== Description ==

This plugin integrates [Affiliates](https://wordpress.org/plugins/affiliates/), [Affiliates Pro](https://www.itthinx.com/shop/affiliates-pro/) and [Affiliates Enterprise](https://www.itthinx.com/shop/affiliates-enterprise/) with [MailChimp](https://mailchimp.com).

This integration features:
- Add new affiliates to your MailChimp mailing list.
- Sync existing affiliates with your MailChimp mailing list.

Requirements:

- [Affiliates](https://wordpress.org/plugins/affiliates/) or [Affiliates Pro](https://www.itthinx.com/shop/affiliates-pro/) or [Affiliates Enterprise](https://www.itthinx.com/shop/affiliates-enterprise/) : This integration works fully with all versions.
- [Affiliates MailChimp](https://wordpress.org/plugins/affiliates-mailchimp) : This plugin.
- An active account in MailChimp

Documentation:

- [Integration with Affiliates](http://docs.itthinx.com/document/affiliates/setup/settings/integrations/)
- [Integration with Affiliates Pro](http://docs.itthinx.com/document/affiliates-pro/setup/settings/integrations/)
- [Integration with Affiliates Enterprise](http://docs.itthinx.com/document/affiliates-enterprise/setup/settings/integrations/)

== Installation ==

1. Install and activate [Affiliates](https://wordpress.org/plugins/affiliates/) or [Affiliates Pro](https://www.itthinx.com/shop/affiliates-pro/) or [Affiliates Enterprise](https://www.itthinx.com/shop/affiliates-enterprise/).
2. Install and activate this integration plugin [Affiliates MailChimp](https://wordpress.org/plugins/affiliates-mailchimp).
3. Get your [MailChimp API key](https://mailchimp.com/help/about-api-keys/) and add it to the integration settings. 
4. Create a List in your MailChimp account. Use this List name in the integration settings.
Please refer to the documentation for details.

Note that you can install the plugins from your WordPress installation directly: use the *Add new* option found in the *Plugins* menu.
You can also upload and extract them in your site's `/wp-content/plugins/` directory or use the *Upload* option.

== Frequently Asked Questions ==

Please refer to the documentation.

== Screenshots ==

1. Affiliates MailChimp menu item in the Affiliates menu.
2. Settings of the Affiliates MailChimp integration.

Please refer to the Documentation for further details:

- [Integration with Affiliates](http://docs.itthinx.com/document/affiliates/setup/settings/integrations/)
- [Integration with Affiliates Pro](http://docs.itthinx.com/document/affiliates-pro//setup/settings/integrations/)
- [Integration with Affiliates Enterprise](http://docs.itthinx.com/document/affiliates-enterprise/setup/settings/integrations/)

== Changelog ==

= 3.1.0 =
* Switched to API provided by MailChimp.
* Fixed API handler to use the new API.
* Added shortcode to let affiliates (un)subscribe from the front-end.
* Improved code formatting.
* WordPress 5.0.2 compatible.
* Updated the translation template.
* Fixed Affiliates, Affiliates Pro and Affiliates Enterprise plugin dependency check.


== Upgrade Notice ==

- This version is compatible with MailChimp API v3 - previous version's of MailChimp's API are not supported.
- This version is compatible with Affiliates, Affiliates Pro and Affiliates Enterprise 2.x and 3.x. but version 3.x is recommended.
