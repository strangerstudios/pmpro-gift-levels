<?php
/*
Plugin Name: PMPro Gift Levels
Plugin URI: http://www.paidmembershipspro.com/add-ons/pmpro-gift-levels/
Description: Some levels will generate discount codes to give to others to use for gift memberships.
Version: .1.2
Author: Stranger Studios
Author URI: http://www.strangerstudios.com
*/

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
	
	e.g. 
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
global $pmprogl_require_gift_code;
//$pmprogl_require_gift_code = array(3);

/*
	When checking out for the purchase gift level, create a code.
	
*/
function pmprogl_pmpro_after_checkout($user_id)
{
	global $pmprogl_gift_levels, $wpdb, $pmpro_old_memberships_users_id;
	
	//which level purchased
	$level_id = intval($_REQUEST['level']);	
		
	//gift for this? if not, stop now
	if(empty($pmprogl_gift_levels) || empty($pmprogl_gift_levels[$level_id]))
		return;
	
	/*
		If they had an old level, change them back
		$pmprogl_existing_member_flag is set in pmprogl_pmpro_cancel_previous_subscriptions() below
	*/	
	if(!empty($pmprogl_existing_member_flag))
	{
		//remove last row added to members_users table
		$sqlQuery = "DELETE FROM $wpdb->pmpro_memberships_users WHERE user_id = '" . $user_id . "' AND membership_id = '" . $level_id . "' ORDER BY id DESC LIMIT 1";				
		$wpdb->query($sqlQuery);
		
		//reset user
		global $all_membership_levels;
		unset($all_membership_levels[$user_id]);
		pmpro_set_current_user();
	}
	
	/*
		Create Gift Code
	*/	
	//get array for the gifted discount code
	$gift = $pmprogl_gift_levels[$level_id];
		
	//create new gift code
	$code = "G" . pmpro_getDiscountCode();
	$starts = date("Y-m-d");
	$expires = date("Y-m-d", strtotime("+1 year"));		
	$sqlQuery = "INSERT INTO $wpdb->pmpro_discount_codes (code, starts, expires, uses) VALUES('" . esc_sql($code) . "', '" . $starts . "', '" . $expires . "', '1')";
	
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
}
add_action("pmpro_after_checkout", "pmprogl_pmpro_after_checkout");

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
				$message .= "<p><strong>Share this link with your gift recipient: <a href=\"" . $code_url . "\">" . $code_url . "</a></strong></p>";
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
			 
			<h3>Gift Codes</h3>
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
							<?php if(!empty($code_use)) { ?>
								<?php echo $code->code;?> claimed by <?php $code_user = get_userdata($code_use); echo $code_user->display_name;?>
							<?php } else { ?>
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
				pmpro_setMessage("You can't use a code you purchased yourself. This was probably an accident.", "pmpro_error");
				return false;
			}
		}		
	}
	
	//does this level require a gift code?	
	if(is_array($pmprogl_require_gift_code) && in_array($pmpro_level->id, $pmprogl_require_gift_code) && empty($discount_code))
	{
		pmpro_setMessage("You must use a valid discount code to register for this level.", "pmpro_error");
		return false;
	}					
	
	//okay
	return $pmpro_continue_registration;
}
add_filter("pmpro_registration_checks", "pmprogl_pmpro_registration_checks");

/*
	If checking out for the purchase gift level and you already have a membership, don't change membership levels.
*/
//disable cancelling old subscriptions
function pmprogl_pmpro_cancel_previous_subscriptions($cancel)
{
	global $pmprogl_gift_levels, $pmprogl_existing_member_flag, $current_user, $wpdb;
	
	//existing user
	if(pmpro_hasMembershipLevel() && !empty($_REQUEST['level']))
	{
		$checkout_level_id = intval($_REQUEST['level']);
		foreach($pmprogl_gift_levels as $level_id => $code)
		{
			if($level_id == $checkout_level_id)
			{				
				//store flag so we know to remove the membership row that gets inserted
				$pmprogl_existing_member_flag = true;
				
				//don't cancel their subscription
				$cancel = false;
								
				return $cancel;
			}
		}
	}
	
	return $cancel;
}
add_action("pmpro_cancel_previous_subscriptions", "pmprogl_pmpro_cancel_previous_subscriptions");