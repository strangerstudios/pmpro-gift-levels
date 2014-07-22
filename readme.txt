=== PMPro Gift Levels ===
Contributors: strangerstudios
Tags: pmpro, membership, gifts
Requires at least: 3.5
Tested up to: 3.9.1
Stable tag: .1.3

Generate discount codes to give to others to use for gift memberships.

== Description ==

Generate discount codes to give to others to use for gift memberships.

* Define "gift giver" and "gift recipeient" level relationships, with customized gift levels.
* When users checkout for a gift giver level (or are assigned one by an admin), a discount code is generated to allow gift recipient members to sign up for the customized gift level.
* If a user has purchased any gift levels, their claimed and unclaimed levels will be displayed on the Account page.
* Restrict users from registering for the gift level if they don't have a gift code. Additionally, gift givers can not use their own gift codes.
* Gift giver members will be linked to their giftee through their pmprogl_gift_codes_purchased user meta.

== Installation ==

1. Upload the `pmpro-gift-levels` directory to the `/wp-content/plugins/` directory of your site.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Create one level for gift givers and one level for gift recipients.
1. Use the $pmprogl_gift_levels global array to define giver-recipient level relationships and customize the gift levels.
1. (Optional) Restrict gift levels to users with gift codes with the $pmprogl_require_gift_code global array.

== Frequently Asked Questions ==

= I found a bug in the plugin. =

Please post it in the issues section of GitHub and we'll fix it as soon as we can. Thanks for helping. https://github.com/strangerstudios/pmpro-gift-levels/issues

== Changelog ==
= .1.3 =
* Added gift code link to confirmation email

= .1.2 =
* Added readme.txt
* Fixed bug on account page for users without purchased gift codes.

= .1 =
* Initial version of the plugin.