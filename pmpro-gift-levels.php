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

define( 'PMPROGL_VERSION', '0.4' );

/**
 * Load text domain
 * pmprogl_load_plugin_text_domain
 */
function pmprogl_load_plugin_text_domain() {
	load_plugin_textdomain( 'pmpro-gift-levels', false, basename( dirname( __FILE__ ) ) . '/languages' ); 
}
add_action( 'init', 'pmprogl_load_plugin_text_domain' ); 

function pmprogl_admin_enqueue_scripts() {
	wp_enqueue_script( 'pmprogl_admin', plugins_url( 'js/pmprogl-admin.js', __FILE__ ), array( 'jquery' ), PMPROGL_VERSION  );
}
add_action( 'admin_enqueue_scripts', 'pmprogl_admin_enqueue_scripts' );

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

/**
 * Add PMPro Gift Levels settings to the "Edit Level" page.
 *
 * Not using $level parameter as it will not be available for PMPro version < 2.5.10.
 */
function pmprogl_membership_level_after_other_settings() {
	global $wpdb, $wp_version;
	$edit_level_id = $_REQUEST['edit'];

	$require_gift_code = get_pmpro_membership_level_meta( $edit_level_id, 'pmprogl_require_gift_code', true );
	if ( empty( $require_gift_code ) ) {
		$require_gift_code = 'no';
	}

	$gift_level = intval( get_pmpro_membership_level_meta( $edit_level_id, 'pmprogl_gift_level', true ) );
	$confirmation_message = get_pmpro_membership_level_meta( $edit_level_id, 'pmprogl_confirmation_message', true );
	if ( empty( $confirmation_message ) ) {
		$confirmation_message = '<p><strong>' . __( 'Share this link with your gift recipient' ) . ': <a href="!!pmprogl_gift_code_url!!">!!pmprogl_gift_code_url!!</a></p></strong>';
	}

	$expiration_number = intval( get_pmpro_membership_level_meta( $edit_level_id, 'pmprogl_expiration_number', true ) );
	$expiration_period = get_pmpro_membership_level_meta( $edit_level_id, 'pmprogl_expiration_period', true );
	if ( empty( $expiration_period ) ) {
		$expiration_period = 'Day';
	}

	?>
	<hr>
	<h2 class="title"><?php esc_html_e( 'Gift Levels', 'pmpro-gift-levels' ); ?></h2>
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row" valign="top"><label><?php esc_html_e('Require Gift Code', 'pmpro-gift-levels' );?>:</label></th>
				<td>
					<input id="pmprogl_require_gift_code" name="pmprogl_require_gift_code" type="checkbox" value="yes" <?php if( 'yes' === $require_gift_code ) { ?>checked="checked"<?php } ?> />
					<label for="pmprogl_require_gift_code"><?php _e('Check to require a discount code in order to check out for this membesrhp level.', 'pmpro-gift-levels' );?></label>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label><?php esc_html_e('Gift Level', 'pmpro-gift-levels' );?>:</label></th>
				<td>
					<select id="pmprogl_gift_level" name="pmprogl_gift_level">
						<option value='0'></option>
						<?php
						$levels = pmpro_getAllLevels( true );
						$current_level = $levels[ strval( $edit_level_id ) ];
						unset( $levels[ strval( $edit_level_id ) ] );
						foreach ( $levels as $level_id => $level ) {
							echo "<option value='" . esc_attr( $level_id ) . "' " . selected( $gift_level, intval ( $level_id ), false ) . ">" . esc_html( $level->name ) . "</option>";
						}
						?>
					</select>
					<label for="pmprogl_gift_level"><?php esc_html_e('Choose the level that a gift code is generated for when this level is purchased.', 'pmpro-gift-levels' );?></label>
					<?php
					// Show error if level has an expiration period or recurring payments set.
					if ( ! empty( $current_level ) && ( ! empty( intval( $current_level->billing_amount ) ) || ! empty( intval( $current_level->expiration_number ) ) ) ) {
						?>
						<p class="description pmprogl_gift_level_toggle_setting" <?php if( empty( $gift_level ) ) {?>style="display: none;"<?php } ?>>
							<strong class="pmpro_red"><?php esc_html_e( 'Memberships with Gift Levels should not have recurring payments or an expiration period set.', 'pmpro-gift-levels' ); ?></strong>
						</p>
						<?php
					}
					?>
				</td>
			</tr>
			<tr class="pmprogl_gift_level_toggle_setting" <?php if( empty( $gift_level ) ) {?>style="display: none;"<?php }  ?>>
				<th scope="row" valign="top"><label><?php esc_html_e('Gift Confirmation Message', 'pmpro-gift-levels' );?>:</label></th>
				<td>
				<div class="pmpro_confirmation">
					<?php
						if(version_compare($wp_version, '3.3') >= 0) {
							wp_editor( $confirmation_message, 'pmprogl_confirmation_message', array( 'textarea_rows' => 5 ) );
						} else {
						?>
						<textarea rows="10" name="pmprogl_confirmation_message" id="pmprogl_confirmation_message" class="large-text"><?php echo esc_textarea($confirmation_message);?></textarea>
						<?php
						}
					?>
					<label for="pmprogl_confirmation_message"><?php esc_html_e('Available variables are !!pmprogl_gift_code!! and !!pmprogl_gift_code_url!!', 'pmpro-gift-levels' );?></label>
					</div>
				</td>
			</tr>
			<tr id="pmprogl_gift_expires_tr" <?php if( empty( $gift_level ) ) {?>style="display: none;"<?php } ?>>
				<th scope="row" valign="top"><label><?php esc_html_e('Gift Membership Expires', 'pmpro-gift-levels' );?>:</label></th>
				<td>
					<input id="pmprogl_gift_expires" name="pmprogl_gift_expires" type="checkbox" value="yes" <?php if ( $expiration_number ) { echo "checked='checked'"; } ?>/>
					<label for="pmprogl_gift_expires"><?php esc_html_e('Check this to set an expiration period for gift memberships.', 'pmpro_gift_levels' );?></label>
				</td>
			</tr>
			<tr id="pmprogl_period_tr" <?php if( empty( $expiration_number ) ) {?>style="display: none;"<?php } ?>>
				<th scope="row" valign="top"><label><?php esc_html_e('Gift Expiration Period', 'pmpro-gift-levels' );?>:</label></th>
				<td>
					<input id="pmprogl_expiration_number" name="pmprogl_expiration_number" type="number" value="<?php echo esc_attr( $expiration_number );?>" />
					<select id="pmprogl_expiration_period" name="pmprogl_expiration_period">
						<?php
						$cycles = array(  esc_html__('Day(s)', 'pmpro-gift-levels' ) => 'Day', esc_html__('Week(s)', 'pmpro-gift-levels' ) => 'Week', esc_html__('Month(s)', 'pmpro-gift-levels' ) => 'Month', esc_html__('Year(s)', 'pmpro-gift-levels' ) => 'Year' );													
						foreach ( $cycles as $name => $value ) {
							echo "<option value='$value' ".selected( $expiration_period, $value, true ).">$name</option>";
						}
						?>
					</select>
					<p class="description"><?php _e('Set the duration of membership access once the gift membership is redeemed.', 'pmpro-gift-levels' );?></p>
				</td>
			</tr>
		</tbody>
	</table>
	<?php
}
add_action( 'pmpro_membership_level_after_other_settings', 'pmprogl_membership_level_after_other_settings' );

function pmprogl_save_membership_level( $save_id ) {
	global $allowedposttags;
	$require_gift_code    = empty( $_REQUEST['pmprogl_require_gift_code'] ) ? 'no' : 'yes';
	$gift_level           = intval( $_REQUEST['pmprogl_gift_level'] );
	$confirmation_message = wp_kses( wp_unslash( $_REQUEST['pmprogl_confirmation_message'] ), $allowedposttags);
	if ( empty( $gift_level ) || empty( $_REQUEST['pmprogl_gift_expires'] ) ) {
		$expiration_number = 0;
		$expiration_period = 'day';
	} else {
		$expiration_number = intval( $_REQUEST['pmprogl_expiration_number'] );
		$expiration_period = sanitize_text_field( $_REQUEST['pmprogl_expiration_period'] );
	}
	
	update_pmpro_membership_level_meta( $save_id, 'pmprogl_require_gift_code', $require_gift_code );
	update_pmpro_membership_level_meta( $save_id, 'pmprogl_gift_level', $gift_level );
	update_pmpro_membership_level_meta( $save_id, 'pmprogl_confirmation_message', $confirmation_message );
	update_pmpro_membership_level_meta( $save_id, 'pmprogl_expiration_number', $expiration_number );
	update_pmpro_membership_level_meta( $save_id, 'pmprogl_expiration_period', $expiration_period );
}
add_action( 'pmpro_save_membership_level', 'pmprogl_save_membership_level', 10, 1 );

function pmprogl_populate_gift_levels_array() {
	global $pmprogl_gift_levels, $pmprogl_require_gift_code;
	if ( isset( $pmprogl_gift_levels ) ) {
		// Array is already set up.
		return;
	}

	$pmprogl_gift_levels = array();
	if ( empty( $pmprogl_require_gift_code ) ) {
		// If required gift code levels array is already set up, we can just extend it.
		$pmprogl_require_gift_code = array();
	}

	$levels = pmpro_getAllLevels();
	foreach ( $levels as $level_id => $level ) {
		// Update $pmprogl_require_gift_code.
		$require_gift_code = get_pmpro_membership_level_meta( $level_id, 'pmprogl_require_gift_code', true );
		if ( $require_gift_code === 'yes' ) {
			$pmprogl_require_gift_code[] = intval( $level_id );
		}

		// Update $pmprogl_gift_levels.
		$gift_level = intval( get_pmpro_membership_level_meta( $level_id, 'pmprogl_gift_level', true ) );
		if ( ! empty( $gift_level ) ) {
			$expiration_number = intval( get_pmpro_membership_level_meta( $level_id, 'pmprogl_expiration_number', true ) );
			$expiration_period = get_pmpro_membership_level_meta( $level_id, 'pmprogl_expiration_period', true );
			if ( empty( $expiration_period ) ) {
				$expiration_period = 'Day';
			}

			$pmprogl_gift_levels[ intval( $level_id ) ] = array(
				'level_id' => $gift_level,
				'initial_payment' => '', 
				'billing_amount' => '', 
				'cycle_number' => '', 
				'cycle_period' => '', 
				'billing_limit' => '', 
				'trial_amount' => '', 
				'trial_limit' => '', 
				'expiration_number' => $expiration_number, 
				'expiration_period' => $expiration_period,
			);
		}
	}
}
add_action( 'init', 'pmprogl_populate_gift_levels_array', 15 );

/*
	When checking out for the purchase gift level, create a code.
	
*/
function pmprogl_pmpro_after_checkout($user_id, $morder)
{	
	global $pmprogl_gift_levels, $wpdb, $pmprogl_existing_member_flag;
	
	//which level purchased
	$level_id = intval($morder->membership_id);	
		
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
	
	// set start date to -1 day, because we want to make
	// sure that the code is able to be used immediately
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

		// Attach gift code to order...
		if ( version_compare( '2.5', PMPRO_VERSION, '<=' ) ) {
			// Order meta was only implemented in PMPro v2.5.
			update_pmpro_membership_order_meta( $morder->id, 'pmprogl_code_id', $code_id );
		}


		do_action( 'pmprogl_gift_code_purchased', $code_id, $user_id, $morder->id );
	}

	// $pmprogl_existing_member_flag is set below.
	if(isset($pmprogl_existing_member_flag)) {
		//remove last row added to members_users table
		$sqlQuery = "DELETE FROM $wpdb->pmpro_memberships_users WHERE user_id = '" . $user_id . "' AND membership_id = '" . $level_id . "' ORDER BY id DESC LIMIT 1";
		$wpdb->query($sqlQuery);

		// remove cached level
		global $all_membership_levels;
		unset( $all_membership_levels[$user_id] );

		// remove levels cache for user
		$cache_key = 'user_' . $user_id . '_levels';
		wp_cache_delete( $cache_key, 'pmpro' );
		wp_cache_delete( $cache_key . '_all', 'pmpro' );
		wp_cache_delete( $cache_key . '_active', 'pmpro' );

		// update user data and call action
		pmpro_set_current_user();
	}
}
add_action("pmpro_after_checkout", "pmprogl_pmpro_after_checkout", 10, 2);

/*
 * Set existing member flags.
 */
function pmprogl_pmpro_checkout_before_change_membership_level( $user_id = false, $morder = false ) {
	global $pmprogl_existing_member_flag, $pmprogl_gift_levels;

	// Get the level that the user is checking out for.
	if ( ! empty( $_REQUEST['level'] ) ) {
		$level_id = intval( $_REQUEST['level'] );
	} elseif( ! empty( $morder->membership_id ) ) {
		$level_id = intval( $morder->membership_id );
	} else {
		// We don't know what level the user is checking out for.
		return;
	}

	// If checking out for a gift level, do not cancel old membership.
	if ( ! empty( $pmprogl_gift_levels ) && array_key_exists( $level_id, $pmprogl_gift_levels ) ) {
		add_filter('pmpro_cancel_previous_subscriptions', '__return_false');
		add_filter('pmpro_deactivate_old_levels', '__return_false');
		$pmprogl_existing_member_flag = true;
	}
}
add_action('pmpro_checkout_before_processing', 'pmprogl_pmpro_checkout_before_change_membership_level', 1);
add_action('pmpro_checkout_before_change_membership_level', 'pmprogl_pmpro_checkout_before_change_membership_level', 1, 2);

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
			
			if(!empty($code))
			{
				$message .= pmprogl_get_confirmation_message( $level_id, $code->code );
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
			
	if(!is_admin() && isset( $post ) && $post->ID == $pmpro_pages['account'])
	{
		//get the user's last purchased gift code
		$gift_codes = get_user_meta($current_user->ID, "pmprogl_gift_codes_purchased", true);
		
		if(!empty($gift_codes))
		{
			$temp_content = pmprogl_account_gift_codes_html();
			$content = str_replace('<!-- end pmpro_account-profile -->', '<!-- end pmpro_account-profile -->' . $temp_content, $content);
 		}
	}
	
	return $content;
}
add_filter("the_content", "pmprogl_the_content_account_page", 30);

function pmprogl_account_gift_codes_html(){
	global $current_user, $wpdb;
	$gift_codes = get_user_meta($current_user->ID, "pmprogl_gift_codes_purchased", true);
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
	
	return $temp_content;
}

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

function pmprogl_email_data( $data, $pmpro_email ) {
	global $wpdb, $pmprogl_gift_levels;

	// Only create these variables if we have an invoice.
	if ( strpos($pmpro_email->template, "checkout") !== false && strpos($pmpro_email->template, "admin") == false ) {
		// Default to empty.
		$data['pmprogl_gift_code']                 = '';
		$data['pmprogl_gift_code_url']             = '';
		$data['pmprogl_confirmation_message']      = '';
		$data['pmprogl_hide_confirmation_message'] = ''; // Can be used to just hide the default pmprogl email message.
		if ( version_compare( '2.5', PMPRO_VERSION, '<=' ) ) {
			// Order meta was only implemented in PMPro v2.5.
			// Get the order that was created for this checkout.
			$morder = new MemberOrder();
			$morder->getLastMemberOrder();

			// Get the gift code ID purchased in the order.
			$gift_code_id = get_pmpro_membership_order_meta( $morder->id, 'pmprogl_code_id', true );
			if ( ! empty( $gift_code_id ) ) {
				// Get the gift code purchased in the order.
				$gift_code = $wpdb->get_var("SELECT code FROM $wpdb->pmpro_discount_codes WHERE id = '" . intval( $gift_code_id ) . "' LIMIT 1");
				if ( ! empty( $gift_code ) ) {
					// Populate email data.
					$code_url = pmpro_url("checkout", "?level=" . $pmprogl_gift_levels[ intval( $data['membership_id'] ) ]['level_id'] . "&discount_code=" . $gift_code);
					$data['pmprogl_gift_code'] = $gift_code;
					$data['pmprogl_gift_code_url'] = $code_url;
					$data['pmprogl_confirmation_message'] = pmprogl_get_confirmation_message( $data['membership_id'], $gift_code );
				}
			}
		}
	}

	// Track whether an email template variable is being used so that we can avoid showing the default text.
	if ( strpos( $pmpro_email->body, '!!pmprogl_' ) !== false ) {
		global $pmprogl_email_template_var_used;
		$pmprogl_email_template_var_used = true;
	}
	return $data;
}
add_filter( 'pmpro_email_data', 'pmprogl_email_data', 10, 2 );

/*
	Add code to confirmation email.
*/
function pmprogl_pmpro_email_body($body, $pmpro_email)
{
    global $wpdb, $pmprogl_gift_levels, $current_user, $pmprogl_email_template_var_used;

    //only checkout emails, not admins
    if(strpos($pmpro_email->template, "checkout") !== false && strpos($pmpro_email->template, "admin") == false && empty( $pmprogl_email_template_var_used ) )
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
            if(!empty($code))
                $body .= pmprogl_get_confirmation_message( $level_id, $code );
        }
    }
	unset( $pmprogl_email_template_var_used );
    return $body;
}
add_filter("pmpro_email_body", "pmprogl_pmpro_email_body", 10, 2);

function pmprogl_get_confirmation_message( $level_id, $gift_code ) {
	global $wpdb, $pmprogl_gift_levels;

	// Get the gift membership level...
	$gift_membership_level = $pmprogl_gift_levels[ intval( $level_id ) ]['level_id'];
	if ( empty( $gift_membership_level ) ) {
		// $level_id is not a gift level. Return.
		return '';
	}

	// Get the raw confirmation message...
	$confirmation_message = get_pmpro_membership_level_meta( $level_id, 'pmprogl_confirmation_message', true );
	if ( empty( $confirmation_message ) ) {
		$confirmation_message = '<p><strong>' . __( 'Share this link with your gift recipient' ) . ': <a href="!!pmprogl_gift_code_url!!">!!pmprogl_gift_code_url!!</a></p></strong>';
	}

	// Replace the variables...
	$variables = array(
		'pmprogl_gift_code' => $gift_code,
		'pmprogl_gift_code_url' => pmpro_url( 'checkout', '?level=' . $gift_membership_level . '&discount_code=' . $gift_code ),
	);
	foreach($variables as $key => $value) {
		$confirmation_message = str_replace( '!!' . $key . '!!', $value, $confirmation_message );
	}

	return $confirmation_message;
}

function pmprogl_after_order_settings( $order ) {
	global $wpdb;

	if ( empty( $order->id ) ) {
		// This is a new order.
		return;
	}

	if ( version_compare( '2.5', PMPRO_VERSION, '>' ) ) {
		// Order meta was only implemented in PMPro v2.5.
		return;
	}

	$gift_code_id = get_pmpro_membership_order_meta( $order->id, 'pmprogl_code_id', true );
	if ( empty( $gift_code_id ) ) {
		// No gift code was purchased with this order.
		return;
	}

	$gift_code = $wpdb->get_var("SELECT code FROM $wpdb->pmpro_discount_codes WHERE id = '" . intval( $gift_code_id ) . "' LIMIT 1");
	if ( empty( $gift_code ) ) {
		$gift_code = __( '[DELETED]', 'pmpro-gift-levels' ); 
	}
	?>
	<tr>
		<th><?php esc_html_e( 'Gift Code Purchased', 'pmpro-gift-levels' ); ?></th>
		<td>
			<?php
				echo esc_html( $gift_code );
			?>
		</td>
	</tr>
	<?php
}
add_action( 'pmpro_after_order_settings', 'pmprogl_after_order_settings', 10, 1 );

/*
Function to add links to the plugin row meta
*/
function pmprogl_plugin_row_meta($links, $file) {
	if(strpos($file, 'pmpro-gift-levels.php') !== false)
	{
		$new_links = array(
			'<a href="' . esc_url('https://www.paidmembershipspro.com/add-ons/plugins-on-github/pmpro-gift-levels/')  . '" title="' . esc_attr( __( 'View Documentation', 'pmpro-gift-levels' ) ) . '">' . __( 'Docs', 'pmpro-gift-levels' ) . '</a>',
			'<a href="' . esc_url('https://www.paidmembershipspro.com/support/') . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'pmpro-gift-levels' ) ) . '">' . __( 'Support', 'pmpro-gift-levels' ) . '</a>',
		);
		$links = array_merge($links, $new_links);
	}
	return $links;
}
add_filter('plugin_row_meta', 'pmprogl_plugin_row_meta', 10, 2);
