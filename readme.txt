=== Paid Memberships Pro - Gift Levels Add On ===
Contributors: strangerstudios
Tags: pmpro, paid memberships pro, membership, gift, gift level, gift card, giftcard, gift certificate
Requires at least: 4.5
Tested up to: 5.6
Stable tag: 0.4

== Description ==
Setup some PMPro levels to allow for the purchase of gift certificates. A discount code for a "real" level is generated when checking out for the gift level.

* Define "gift giver" and "gift recipeient" level relationships, with customized gift levels.
* When users checkout for a gift giver level (or are assigned one by an admin), a discount code is generated to allow gift recipient members to sign up for the customized gift level.
* If a user has purchased any gift levels, their claimed and unclaimed levels will be displayed on the Account page.
* Restrict users from registering for the gift level if they don't have a gift code. Additionally, gift givers can not use their own gift codes.
* Gift giver members will be linked to their giftee through their pmprogl_gift_codes_purchased user meta.

== Installation ==
1. Upload the `pmpro-gift-levels` directory to the `/wp-content/plugins/` directory of your site.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Create one level for gift givers and one level for gift recipients.
4. Add code like this to your active theme's functions.php or a custom plugin to setup the global gift levels array.

`
// Each key is the id of the "purchase level"
// Each value is an array of values for the discount code created.
global $pmprogl_gift_levels;
	$pmprogl_gift_levels = array(
		5 => array(						//5 is the purchase level
			'level_id' => 6,			//6 is the level gifted
			'initial_payment' => '', 	//discount code is setup for $0
			'billing_amount' => '', 
			'cycle_number' => '', 
			'cycle_period' => '', 
			'billing_limit' => '', 
			'trial_amount' => '', 
			'trial_limit' => '', 
			'expiration_number' => 1, 		//membership expires after 1
			'expiration_period' => 'Year'	//year
		)
	);
`
	
4. Add a global array to set certain levels to require a discount code to check out. (Optional)

`
global $pmprogl_require_gift_code;
$pmprogl_require_gift_code = array(6);
`
	
== Frequently Asked Questions ==
= I found a bug in the plugin.
Please post it in the issues section of GitHub and we'll fix it as soon as we can. Thanks for helping. https://github.com/strangerstudios/pmpro-gift-levels/issues

== Changelog ==
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
