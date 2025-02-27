=== Paid Memberships Pro - Gift Levels Add On ===
Contributors: strangerstudios
Tags: pmpro, paid memberships pro, membership, gift, gift level, gift card, giftcard, gift certificate
Requires at least: 4.5
Tested up to: 6.7
Stable tag: 1.1.1

== Description ==

Sell a gift certificate for membership to your site. This plugin generates a unique code for the gift recipient to claim their membership account.

The person who purchases a gift of membership can optionally enter the recipient's email address and a personalized message at checkout. The gift code is automatically delivered to the recipient by email.

Note that users who purchase gifts will not be given a membership level in your site. Gift purchasers can log in as a user and view the history of purchases made, available gift codes, and claimed gift codes.

The plugin adds three new email templates that you can use to modify the default messages sent as part of a gift purchase:
* Gift Recipient: This email is sent when the gift giver provides the recipient email address at checkout.
* Gift Purchased: This email is sent to the gift giver as confirmation of their purchase after checkout.
* Gift Purchased (admin): This email is sent to the admin as confirmation of gift purchase after checkout.

== Installation ==

1. Upload the `pmpro-gift-levels` directory to the `/wp-content/plugins/` directory of your site.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to Memberships > Settings > Levels to create and manage gift membership levels.
4. Navigate to Memberships > Settings > Email Templates to modify default messages sent through this plugin.

Refer to the [Gift Levels Add On documentation page](https://www.paidmembershipspro.com/add-ons/pmpro-gift-levels/) for more information on how to set up your gift levels.
	
== Frequently Asked Questions ==
= I found a bug in the plugin.
Please post it in the issues section of GitHub and we'll fix it as soon as we can. Thanks for helping. https://github.com/strangerstudios/pmpro-gift-levels/issues

== Changelog ==
= 1.1.1 - 2025-02-27 =
* ENHANCEMENT: Updated email logic to use the new `PMPro_Email_Template` class to show email template variables when editing email templates in PMPro v3.4+. 73 (@MaximilianoRicoTabo)

= 1.1 - 2024-07-31 =
* ENHANCEMENT: Updated the frontend UI for compatibility with PMPro v3.1. #71 (@dparker1005, @kimcoleman)
* BUG FIX: Fixed conflict with the PMPro Pay By Check Add On. #70 (@dparker1005)
* DEPRECATED: Removed `sample-setup.php` as settings can now be configured on the Edit Level page. #72 (@kimcoleman)

= 1.0.4 - 2023-12-11 =
* ENHANCEMENT: Updating `<h3>` tags to `<h2>` tags for better accessibility. #66 (@michaelbeil)
* BUG FIX: Fixed issue where gift level emails may not be sent when using Stripe Checkout or other asynchronous gateways. #64 (@dparker1005)
* BUG FIX: Fixed issue where gift level emails sent to administrators may have their account credentials in place of the gift purchaser's. #64 (@dparker1005)
* BUG FIX: Fixed PHP errors when the core PMPro plugin is not active. #68 (@mircobabini)
* REFACTOR: No longer pulling the checkout level from the `$_REQUEST` variable. #65 (@dparker1005)

= 1.0.3 - 2023-01-30 =
* BUG FIX: Resolved issue where the recurring payment warning was showing on all levels, not just gift levels.

= 1.0.2 - 2022-07-19 =
* ENHANCEMENT: Added Gift level template and support for PMPro v2.9+ settings UI.

= 1.0.1 - 2021-09-14 =
* BUG FIX: Fixed fatal error when viewing Edit User page
* BUG FIX: Fixed incorrect output on invoice page when a gift order was purchased

= 1.0 - 2021-09-13 =
* FEATURE: Gift levels can now be configured on the Edit Level page
* FEATURE: Users can now enter a recipient email address during checkout to send a gift code to
* FEATURE: Added email templates for gift checkouts (both user and admin) and for gift recipient emails
* ENHANCEMENT: Added filter pmprogl_gift_code_purchased (Thanks, Mirco Babini)
* ENHANCEMENT: Gift code data is now being stored in order meta
* ENHANCEMENT: Admins can now see the gift codes that a user has purchased on the Edit User page or by editing a discount code
* BUG FIX/ENHANCEMENT: Added warning if membership level is set up with recurring payment or expiration date and gift level in GUI
* BUG FIX/ENHANCEMENT: Gift levels are now immediately removed after checkout for all users (Thanks, Mirco Babini)
* BUG FIX/ENHANCEMENT: Moved gift level confirmation message to end of checkout email (Thanks, Mirco Babini)
* BUG FIX: Fixed issue where purchasing gift level with Stripe could set an expiration date on previous membership
* BUG FIX/ENHANCEMENT: Now clearing cached membership levels after removing gift level (Thanks, Mirco Babini)
* BUG FIX: Fixed issue where gift code may not be generated at checkout with specific gateways (Thanks, knit-pay on GitHub)
* BUG FIX: Fixed PHP notice (Thanks, Mirco Babini)
* REFACTOR: Broke code into separate files

= 0.4 - 2020-12-09 =
* BUG FIX: Fixed an issue when checking out with PayPal would change the user's level. User's will now keep their current level when purchasing a gift.
* ENHANCEMENT: Plugin strings have been localized and now support translations.
* ENHANCEMENT: New filter added to allow changing of discount code settings during checkout process. Filter: 'pmprogl_gift_code_settings'

= .3 - 2019-01-08 =
* BUG FIX: Fixed bug where Gift Levels was not working with Stripe checkout for existing users. Users are no longer given an expiration date 3 days in the future.

= .2.3 =
* BUG: Fixed bug where $pmprogl_existing_member_flag was not being set correctly.

= .2.2 =
* Now preserving enddate when giving users their previous level back after checking out for a gift level. (Thanks, andrewatduckpin)

= .2.1 =
* Added code to set status of last level to "active" explicitly after checkout. Makes this addon compatible with PMPro v1.8+

= .2 =
* Commented out $pmprogl_gift_levels example. Should be put into a custom plugin or the active theme's functions.php.
* Fixed bug where entries for gift level purchases were not being deleted from the pmpro_memberships_users table. (Thanks, andrewatduckpin)

= .1.3 =
* Added gift code link to confirmation email

= .1.2 =
* Added readme.txt
* Fixed bug on account page for users without purchased gift codes.

= .1 =
* This is the initial version of the plugin.
