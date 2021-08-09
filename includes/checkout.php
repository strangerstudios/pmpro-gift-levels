<?php

/**
 * Show field to enter recipeient email at checkout.
 *
 * Note: Can instead add this field with Register Helper by naming a field 'pmprogl_recipient_email`
 * and unchecking the allow gift emails box in the level settings.
 */
function pmprogl_checkout_boxes() {
	global $pmpro_level, $pmprogl_gift_levels;
	if ( ! array_key_exists( intval( $pmpro_level->id ), $pmprogl_gift_levels ) || 'yes' !== get_pmpro_membership_level_meta( $pmpro_level->id, 'pmprogl_allow_gift_emails', true ) ) {
		return;
	}

	$current_recipient_email = empty( $_REQUEST['pmprogl_recipient_email'] ) ? '' : $_REQUEST['pmprogl_recipient_email'];
	$current_gift_message = empty( $_REQUEST['pmprogl_gift_message'] ) ? '' : $_REQUEST['pmprogl_gift_message'];

	$send_recipient_email_checked = empty( $current_recipient_email . $current_gift_message ) ? '' : ' checked';
	$gift_field_style_attr = empty( $current_recipient_email . $current_gift_message ) ? 'style="display:none;"' : '';

	?>
	<div id="pmprogl_checkout_box" class="pmpro_checkout">
		<hr />
		<h3>
			<span class="pmpro_checkout-h3-name"><?php esc_html_e( 'Gift Code' );?></span>
		</h3>
		<div class="pmpro_checkout-field">
			<label for="pmprogl_send_recipient_email"><?php esc_html_e( 'Automatically email gift code to recipient after checkout' );?></label>
			<input type="checkbox" id="pmprogl_send_recipient_email" name="pmprogl_send_recipient_email" value="1" <?php echo $send_recipient_email_checked; ?> />
		</div>
		<div class="pmpro_checkout-field pmprogl_checkout_field_div" <?php echo $gift_field_style_attr ?>>
			<label for="pmprogl_recipient_email"><?php esc_html_e( 'Recipient Email' );?></label>
			<input type="text" name="pmprogl_recipient_email" value="<?php echo esc_attr( $current_recipient_email ) ?>" />
		</div>
		<div class="pmpro_checkout-field pmprogl_checkout_field_div" <?php echo $gift_field_style_attr ?>>
			<label for="pmprogl_gift_message"><?php esc_html_e( 'Gift Message' );?></label>
			<textarea name="pmprogl_gift_message"><?php echo esc_textarea( $current_gift_message ); ?> </textarea>
		</div>
	</div>
	<?php
}
add_action( 'pmpro_checkout_boxes', 'pmprogl_checkout_boxes' );

/**
 * Enqueue frontend JavaScript and CSS
 */
function pmprogl_enqueue_checkout_script() {
	// Checkout page JS
	if ( pmpro_is_checkout() ) {
		wp_enqueue_script( 'pmprogl_checkout', plugins_url( 'js/pmprogl-checkout.js', PMPROGL_BASE_FILE ), array( 'jquery' ), PMPROGL_VERSION  );
	}
}
add_action( 'wp_enqueue_scripts', 'pmprogl_enqueue_checkout_script' );

/**
 * Prevent checkout if a user attempts to use their own gift code
 * or if they are trying to check out for a "gift only" level
 * without a discount code.
 *
 * @param bool $pmpro_continue_registration whether checkout is valid.
 * @return bool
 */
function pmprogl_registration_checks_own_code( $pmpro_continue_registration ) {		
	global $current_user, $discount_code, $wpdb;

	//only bother if things are okay so far
	if ( ! $pmpro_continue_registration ) {
		return $pmpro_continue_registration;
	}
		
	//don't let users use their own gift codes (probably an accident)
	if ( ! empty( $discount_code ) && ! empty( $current_user->ID ) ) {
		$code_id = $wpdb->get_var("SELECT id FROM $wpdb->pmpro_discount_codes WHERE code = '" . esc_sql($discount_code) . "' LIMIT 1");
		if ( ! empty( $code_id ) ) {
			$gift_codes = get_user_meta($current_user->ID, "pmprogl_gift_codes_purchased", true);
			if ( is_array( $gift_codes ) && in_array( $code_id, $gift_codes ) ) {
				pmpro_setMessage( __( "You can't use a code you purchased yourself. This was probably an accident.", "pmpro-gift-levels" ), "pmpro_error" );
				return false;
			}
		}		
	}				

	//okay
	return $pmpro_continue_registration;
}
add_filter( "pmpro_registration_checks", "pmprogl_registration_checks_own_code" );

function pmprogl_registration_check_require_gift_code( $pmpro_continue_registration ) {		
	global $pmpro_level, $discount_code, $pmprogl_require_gift_code;

	//only bother if things are okay so far
	if ( ! $pmpro_continue_registration ) {
		return $pmpro_continue_registration;
	}
	
	//does this level require a gift code?	
	if ( is_array( $pmprogl_require_gift_code ) && in_array( $pmpro_level->id, $pmprogl_require_gift_code ) && empty( $discount_code ) ) {
		pmpro_setMessage( __( "You must use a valid discount code to register for this level.", "pmpro-gift-levels" ), "pmpro_error" );
		return false;
	}					
	
	//okay
	return $pmpro_continue_registration;
}
add_filter( "pmpro_registration_checks", "pmprogl_registration_check_require_gift_code" );

/**
 * Save recipient email when paying offiste.
 */
function pmprogl_paypalexpress_session_vars() {
	if ( isset( $_REQUEST['pmprogl_send_recipient_email'] ) ) {
		$_SESSION['pmprogl_send_recipient_email'] = $_REQUEST['pmprogl_send_recipient_email'];
	}
	if ( isset( $_REQUEST['pmprogl_recipient_email'] ) ) {
		$_SESSION['pmprogl_recipient_email'] = $_REQUEST['pmprogl_recipient_email'];
	}
	if ( isset( $_REQUEST['pmprogl_gift_message'] ) ) {
		$_SESSION['pmprogl_gift_message'] = $_REQUEST['pmprogl_gift_message'];
	}
}
add_action("pmpro_paypalexpress_session_vars", "pmprogl_paypalexpress_session_vars");
add_action("pmpro_before_send_to_twocheckout", "pmprogl_paypalexpress_session_vars", 10, 0);

/**
 * Prevent user's existing membership from changing if they are checking
 * out for a gift level.
 *
 * @param integer|bool $user_id of user checking out.
 * @param MemberOrder|bool $morder created at checkout.
 */
function pmprogl_pmpro_checkout_before_change_membership_level( $user_id = false, $morder = false ) {
	global $pmprogl_gift_levels;

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
	}
}
add_action('pmpro_checkout_before_processing', 'pmprogl_pmpro_checkout_before_change_membership_level', 1);
add_action('pmpro_checkout_before_change_membership_level', 'pmprogl_pmpro_checkout_before_change_membership_level', 1, 2);

/**
 * Create gift codes after checkout and remove gift memberships from givers.
 *
 * @param int $user_id of purchaser.
 * @param MemberOrder $morder generated during checkout.
 */
function pmprogl_pmpro_after_checkout($user_id, $morder)
{	
	global $pmprogl_gift_levels, $wpdb;
	
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

		// Get gift recipeint email if available
		if ( ! empty( $_REQUEST['pmprogl_send_recipient_email'] ) && ! empty( $_REQUEST['pmprogl_recipient_email'] ) ) {
			$recipient_email = sanitize_email( $_REQUEST['pmprogl_recipient_email'] );
			$gift_message    = empty( $_REQUEST['pmprogl_gift_message'] ) ? '' : sanitize_textarea_field( $_REQUEST['pmprogl_gift_message'] );
		} elseif ( ! empty( $_SESSION['pmprogl_send_recipient_email'] ) && ! empty( $_SESSION['pmprogl_recipient_email'] ) ) {
			$recipient_email = sanitize_email( $_SESSION['pmprogl_recipient_email'] );
			$gift_message    = empty( $_SESSION['pmprogl_gift_message'] ) ? '' : sanitize_textarea_field( $_SESSION['pmprogl_gift_message'] );
		}
		unset( $_SESSION['pmprogl_send_recipient_email'] ); // In case the user checks out again after.
		unset( $_SESSION['pmprogl_recipient_email'] ); // In case the user checks out again after.
		unset( $_SESSION['pmprogl_gift_message'] ); // In case the user checks out again after.

		// Save order meta...
		if ( version_compare( '2.5', PMPRO_VERSION, '<=' ) ) {
			// Order meta was only implemented in PMPro v2.5.
			update_pmpro_membership_order_meta( $morder->id, 'pmprogl_code_id', $code_id );
			if ( ! empty( $recipient_email ) ) {
				update_pmpro_membership_order_meta( $morder->id, 'pmprogl_recipient_email', $recipient_email );
			}
			if ( ! empty( $gift_message ) ) {
				update_pmpro_membership_order_meta( $morder->id, 'pmprogl_gift_message', $gift_message );
			}
		}

		// Send email to gift recipient...
		if ( ! empty( $recipient_email ) ) {
			$code = $wpdb->get_var("SELECT code FROM $wpdb->pmpro_discount_codes WHERE id = '" . intval($code_id) . "' LIMIT 1");
			if ( ! empty( $code ) ) {
				pmprogl_send_gift_code_to_gift_recipient( $recipient_email, $gift_message, $code );
			}
		}

		do_action( 'pmprogl_gift_code_purchased', $code_id, $user_id, $morder->id );
	}

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

	// Send user to invoice after checkout instead of confirmation page since we took their level away.
	add_action( 'pmpro_confirmation_url', 'pmprogl_overwrite_confirmation_url', 10, 2 );
}
add_action("pmpro_after_checkout", "pmprogl_pmpro_after_checkout", 10, 2);

function pmprogl_overwrite_confirmation_url( $url, $user_id ) {
	$morder = new MemberOrder();
	$morder->getLastMemberOrder( $user_id );
	if ( ! empty ( $morder->code ) ) {
		$invoice_url = pmpro_url( 'invoice', '?invoice=' . $morder->code );
		if ( ! empty( $invoice_url ) ) {
			$url = $invoice_url;
		}
	}
	return $url;
}
