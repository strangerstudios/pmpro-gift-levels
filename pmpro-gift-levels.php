<?php
/*
Plugin Name: Paid Memberships Pro - Gift Levels Add On
Plugin URI: http://www.paidmembershipspro.com/add-ons/pmpro-gift-levels/
Description: Some levels will generate discount codes to give to others to use for gift memberships.
Version: 0.4
Author: Stranger Studios
Author URI: http://www.strangerstudios.com
Text Domain: pmpro-gift-levels
Domain Path: /languages
*/

/**
 * Load text domain
 * pmprogl_load_plugin_text_domain
 */
function pmprogl_load_plugin_text_domain() {
	load_plugin_textdomain( 'pmpro-gift-levels', false, basename( dirname( __FILE__ ) ) . '/languages' ); 
}
add_action( 'init', 'pmprogl_load_plugin_text_domain' ); 

/*
	The Plan
	
	1. One level is the "purchase gift" level.
	- Array of prices and expirations.
	- Select on checkout to choose price.
	- After checkout DON'T change level. Give user code.
	
	2. One level is the "gift level".
	- Require a discount code to checkout for the gift level.
	
	3. Set a different checkout page for gift purchase and use.
	
	4. Way to view all gifts that are "unclaimed".
*/

/*
	Array of gift levels. 
	- Each key is the id of the "purchase level"
	- Each value is an array of values for the discount code created.
	- Add this to your active theme's functions.php or a custom plugin.
	
	e.g. 
	global $pmprogl_gift_levels;
	$pmprogl_gift_levels = array(
		5 => array(
			'level_id' => 6,
			'initial_payment' => '', 
			'billing_amount' => '', 
			'cycle_number' => '', 
			'cycle_period' => '', 
			'billing_limit' => '', 
			'trial_amount' => '', 
			'trial_limit' => '', 
			'expiration_number' => 1, 
			'expiration_period' => 'Year'
		)
	);
*/

/*
	These levels will require a gift code.
	Array should contain the level ids.
*/
/*
global $pmprogl_require_gift_code;
$pmprogl_require_gift_code = array(6);
*/
	
/*
	When checking out for the purchase gift level, create a code.
	
*/
function pmprogl_pmpro_after_checkout($user_id)
{	
	global $pmprogl_gift_levels, $wpdb, $pmprogl_existing_member_flag;
	
	//which level purchased
	$level_id = intval($_REQUEST['level']);	
		
	//gift for this? if not, stop now
	if(empty($pmprogl_gift_levels) || empty($pmprogl_gift_levels[$level_id]))
		return;

	/*
		Create Gift Code
	*/	
	//get array for the gifted discount code
	$gift = $pmprogl_gift_levels[$level_id];
		
	//create new gift code
	$code = "G" . pmpro_getDiscountCode();
	$starts = date("Y-m-d", strtotime("-1 day"));
	$expires = date("Y-m-d", strtotime("+1 year"));
	
	$gift_code_settings = apply_filters( 'pmprogl_gift_code_settings', array('code' => $code, 'starts' => $starts, 'expires' => $expires, 'uses' => 1 ) );

	// Set variables and escape them right before the SQL query.
	$gcode = esc_sql( $gift_code_settings['code'] );
	$gstarts = esc_sql( $gift_code_settings['starts'] );
	$gexpires = esc_sql( $gift_code_settings['expires'] );
	$guses = esc_sql( $gift_code_settings['uses'] );
			
	$sqlQuery = "INSERT INTO $wpdb->pmpro_discount_codes (code, starts, expires, uses) VALUES('" . esc_sql($gcode) . "', '" . $gstarts . "', '" . $gexpires . "', '$guses')";
	
	if($wpdb->query($sqlQuery) !== false)
	{
		//get id of new code
		$code_id = $wpdb->insert_id;
		
		//add code to level
		$sqlQuery = "INSERT INTO $wpdb->pmpro_discount_codes_levels (code_id, level_id, initial_payment, billing_amount, cycle_number, cycle_period, billing_limit, trial_amount, trial_limit, expiration_number, expiration_period) VALUES('" . esc_sql($code_id) . "',
													 '" . esc_sql($gift['level_id']) . "',
													 '" . esc_sql($gift['initial_payment']) . "',
													 '" . esc_sql($gift['billing_amount']) . "',
													 '" . esc_sql($gift['cycle_number']) . "',
													 '" . esc_sql($gift['cycle_period']) . "',
													 '" . esc_sql($gift['billing_limit']) . "',
													 '" . esc_sql($gift['trial_amount']) . "',
													 '" . esc_sql($gift['trial_limit']) . "',
													 '" . esc_sql($gift['expiration_number']) . "',
													 '" . esc_sql($gift['expiration_period']) . "')";
		$wpdb->query($sqlQuery);
		
		//get existing gift codes
		$gift_codes = get_user_meta($user_id, "pmprogl_gift_codes_purchased", true);	
		
		//default to array
		if(empty($gift_codes))
			$gift_codes = array();
		
		//add new gift code
		$gift_codes[] = $code_id;
		
		//save gift codes
		update_user_meta($user_id, "pmprogl_gift_codes_purchased", $gift_codes);
	}

	// $pmprogl_existing_member_flag is set below.
	if(isset($pmprogl_existing_member_flag)) {
		//remove last row added to members_users table
		$sqlQuery = "DELETE FROM $wpdb->pmpro_memberships_users WHERE user_id = '" . $user_id . "' AND membership_id = '" . $level_id . "' ORDER BY id DESC LIMIT 1";
		$wpdb->query($sqlQuery);

		//reset user
		global $all_membership_levels;
		unset($all_membership_levels[$user_id]);
		pmpro_set_current_user();
	}
}
add_action("pmpro_after_checkout", "pmprogl_pmpro_after_checkout");

/*
 * Set existing member flags.
 */
function pmprogl_pmpro_checkout_before_change_membership_level() {
	global $pmprogl_existing_member_flag, $pmprogl_gift_levels;

	if ( pmpro_hasMembershipLevel()
	&& ! empty( $_REQUEST['level'] )
	&& ! empty( $pmprogl_gift_levels ) ) {
		$checkout_level_id = intval( $_REQUEST['level'] );
		foreach ( $pmprogl_gift_levels as $level_id => $code ) {
			if ( $level_id == $checkout_level_id ) {
				add_filter('pmpro_cancel_previous_subscriptions', '__return_false');
				add_filter('pmpro_deactivate_old_levels', '__return_false');
				$pmprogl_existing_member_flag = true;
			}
		}
	}
}
add_action('pmpro_checkout_before_change_membership_level', 'pmprogl_pmpro_checkout_before_change_membership_level', 1);

/*
	Show last purchased gift code on the confirmation page.
*/
function pmprogl_pmpro_confirmation_message($message)
{
	global $current_user, $pmprogl_gift_levels, $wpdb;
	
	//which level purchased
	$level_id = intval($_REQUEST['level']);	
		
	//only if there is a gift for this level
	if(!empty($pmprogl_gift_levels) && !empty($pmprogl_gift_levels[$level_id]))
	{
		//get the user's last purchased gift code
		$gift_codes = get_user_meta($current_user->ID, "pmprogl_gift_codes_purchased", true);
		
		if(is_array($gift_codes))
			$last_code_id = end($gift_codes);
		
		if(!empty($last_code_id))
		{
			$code = $wpdb->get_row("SELECT * FROM $wpdb->pmpro_discount_codes WHERE id = '" . intval($last_code_id) . "' LIMIT 1");
			$code_url = pmpro_url("checkout", "?level=" . $pmprogl_gift_levels[$level_id]['level_id'] . "&discount_code=" . $code->code);
			
			if(!empty($code))
			{
				$message .= "<p><strong>" . __( "Share this link with your gift recipient", "pmpro-gift-levels" ) . ": <a href=\"" . $code_url . "\">" . $code_url . "</a></strong></p>";
			}			
		}
	}
	
	return $message;
}
add_filter("pmpro_confirmation_message", "pmprogl_pmpro_confirmation_message");

/*
	Show purchased gift codes on the account page and show if they have been claimed.
*/
//show a user's discount code on the account page
function pmprogl_the_content_account_page($content)
{
	global $post, $pmpro_pages, $current_user, $wpdb, $pmprogl_gift_levels;
			
	if(!is_admin() && $post->ID == $pmpro_pages['account'])
	{
		//get the user's last purchased gift code
		$gift_codes = get_user_meta($current_user->ID, "pmprogl_gift_codes_purchased", true);
		
		if(!empty($gift_codes))
		{
		ob_start();
		?>
		<div id="pmpro_account-gift_codes" class="pmpro_box">	
			 
			<h3><?php _e( "Gift Codes", "pmpro-gift-levels" ); ?></h3>
			<ul>
			<?php
				foreach($gift_codes as $gift_code_id)
				{
					$code = $wpdb->get_row("SELECT * FROM $wpdb->pmpro_discount_codes WHERE id = '" . intval($gift_code_id) . "' LIMIT 1");					
					if(!empty($code))
					{
						$code_level_id = $wpdb->get_var("SELECT level_id FROM $wpdb->pmpro_discount_codes_levels WHERE code_id = '" . intval($gift_code_id) . "' LIMIT 1");
						$code_url = pmpro_url("checkout", "?level=" . $code_level_id . "&discount_code=" . $code->code);
						$code_use = $wpdb->get_var("SELECT user_id FROM $wpdb->pmpro_discount_codes_uses WHERE code_id = '" . intval($gift_code_id) . "' LIMIT 1");
						?>
						<li>
							<?php 
							if(!empty($code_use)) 
							{ 
								echo $code->code, " ", __("claimed by", "pmpro-gift-levels" ), " ";
								$code_user = get_userdata( $code_use ); 
								echo $code_user->display_name;
							} else { ?>
								<a target="_blank" href="<?php echo $code_url;?>"><?php echo $code->code;?></a>
							<?php } ?>
						</li>
						<?php
					}
				}
			?>
			</ul>			
		</div>
		<?php
		$temp_content = ob_get_contents();
		ob_end_clean();
		$content = str_replace('<!-- end pmpro_account-profile -->', '<!-- end pmpro_account-profile -->' . $temp_content, $content);
        }
    }
	
	return $content;
}
add_filter("the_content", "pmprogl_the_content_account_page", 30);

/*
	Don't let users use their own gift codes.
	Require a discount code to checkout for the purchased level if it's in the pmprogl_require_gift_code array.
*/
function pmprogl_pmpro_registration_checks($pmpro_continue_registration)
{		
	global $current_user, $pmpro_level, $discount_code, $wpdb, $pmprogl_require_gift_code;

	//only bother if things are okay so far
	if(!$pmpro_continue_registration)
		return $pmpro_continue_registration;
		
	//don't let users use their own gift codes (probably an accident)
	if(!empty($discount_code) && !empty($current_user->ID))
	{
		$code_id = $wpdb->get_var("SELECT id FROM $wpdb->pmpro_discount_codes WHERE code = '" . esc_sql($discount_code) . "' LIMIT 1");
		
		if(!empty($code_id))
		{
			$gift_codes = get_user_meta($current_user->ID, "pmprogl_gift_codes_purchased", true);
			
			if(is_array($gift_codes) && in_array($code_id, $gift_codes))
			{
				pmpro_setMessage( __("You can't use a code you purchased yourself. This was probably an accident.", "pmpro-gift-levels" ), "pmpro_error" );
				return false;
			}
		}		
	}
	
	//does this level require a gift code?	
	if(is_array($pmprogl_require_gift_code) && in_array($pmpro_level->id, $pmprogl_require_gift_code) && empty($discount_code))
	{
		pmpro_setMessage( __("You must use a valid discount code to register for this level.", "pmpro-gift-levels" ), "pmpro_error" );
		return false;
	}					
	
	//okay
	return $pmpro_continue_registration;
}
add_filter("pmpro_registration_checks", "pmprogl_pmpro_registration_checks");

/*
	Add code to confirmation email.
*/
function pmprogl_pmpro_email_body($body, $pmpro_email)
{
    global $wpdb, $pmprogl_gift_levels, $current_user;

    //only checkout emails, not admins
    if(strpos($pmpro_email->template, "checkout") !== false && strpos($pmpro_email->template, "admin") == false)
    {
        //get the user_id from the email
        $user_id = $wpdb->get_var("SELECT ID FROM $wpdb->users WHERE user_email = '" . $pmpro_email->data['user_email'] . "' LIMIT 1");
        $level_id = $pmpro_email->data['membership_id'];

        //get the user's last purchased gift code
        $gift_codes = get_user_meta($current_user->ID, "pmprogl_gift_codes_purchased", true);

        if(is_array($gift_codes))
            $code_id = end($gift_codes);

        if(!empty($code_id))
        {
            $code = $wpdb->get_var("SELECT code FROM $wpdb->pmpro_discount_codes WHERE id = '" . intval($code_id) . "' LIMIT 1");
            $code_url = pmpro_url("checkout", "?level=" . $pmprogl_gift_levels[$level_id]['level_id'] . "&discount_code=" . $code);

            if(!empty($code))
                $body = "<p><strong> " . __( "Share this link with your gift recipient", "pmpro-gift-levels" ) . ": <a href=\"" . $code_url . "\">" . $code_url . "</a></strong></p>" . $body;
        }
    }
    return $body;
}
add_filter("pmpro_email_body", "pmprogl_pmpro_email_body", 10, 2);

/*
Function to add links to the plugin row meta
*/
function pmprogl_plugin_row_meta($links, $file) {
	if(strpos($file, 'pmpro-gift-levels.php') !== false)
	{
		$new_links = array(
			'<a href="' . esc_url('http://www.paidmembershipspro.com/add-ons/plugins-on-github/pmpro-gift-levels/')  . '" title="' . esc_attr( __( 'View Documentation', 'pmpro-gift-levels' ) ) . '">' . __( 'Docs', 'pmpro-gift-levels' ) . '</a>',
			'<a href="' . esc_url('http://paidmembershipspro.com/support/') . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'pmpro-gift-levels' ) ) . '">' . __( 'Support', 'pmpro-gift-levels' ) . '</a>',
		);
		$links = array_merge($links, $new_links);
	}
	return $links;
}
add_filter('plugin_row_meta', 'pmprogl_plugin_row_meta', 10, 2);
