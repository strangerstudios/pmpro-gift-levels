<?php

/**
 * Add PMPro Gift Code email template variables to checkout email.
 *
 * @param array $data current email template variables.
 * @param PMProEmail $pmpro_email being sent.
 * @return array
 */
function pmprogl_checkout_email_data( $data, $pmpro_email ) {
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
add_filter( 'pmpro_email_data', 'pmprogl_checkout_email_data', 10, 2 );

/**
 * If a PMPro Gift Level email template variable was not used, add the
 * PMPro Gift Levels confirmation message to the checkout email.
 *
 * @param string $body of email
 * @param PMProEmail $pmpro_email being sent.
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

function pmprogl_send_gift_code_to_gift_recipient( $recipient_email, $gift_message, $gift_code ) {
	global $pmprogl_gift_levels;

	$email = new PMProEmail();
	$email->email = $recipient_email;
	$email->template = "pmprogl_gift_recipient";
	$email->subject = esc_html__( "You have been gifted a membership to ", 'pmpro_gift_levels' ) . "!!sitename!!!";
	$email->body = pmprogl_get_default_gift_recipient_email_body();
	$email->pmprogl_gift_message = $gift_message; // Save this for later.
	$email->pmprogl_gift_code = $gift_code; // Save this for later.

	add_filter( 'option_pmpro_email_header_disabled', '__return_true', 15 );
	add_filter( 'default_option_pmpro_email_header_disabled', '__return_true', 15 );
	add_filter( 'option_pmpro_email_footer_disabled', '__return_true', 15 );
	add_filter( 'default_option_pmpro_email_footer_disabled', '__return_true', 15 );
	$email->sendEmail();
	remove_filter( 'option_pmpro_email_header_disabled', '__return_true', 15 );
	remove_filter( 'default_option_pmpro_email_header_disabled', '__return_true', 15 );
	remove_filter( 'option_pmpro_email_footer_disabled', '__return_true', 15 );
	remove_filter( 'default_option_pmpro_email_footer_disabled', '__return_true', 15 );
}

/**
 * Add "pmprogl_gift_recipient" as an editable email template.
 *
 * @param array $templates that can be edited.
 */
function pmprogl_template_callback( $templates ) {
	$templates['pmprogl_gift_recipient'] = array(
		'subject' => esc_html( sprintf( __( "You have been gifted a membership to %s", 'pmpro_gift_levels' ), get_option("blogname") ) ),
		'description' => 'Gift Recipient',
		'body' => pmprogl_get_default_gift_recipient_email_body(),
	);
	
	return $templates;
}
add_filter( 'pmproet_templates', 'pmprogl_template_callback');

/**
 * Add PMPro Gift Code email template variables to the gift recipeient email.
 *
 * @param array $data current email template variables.
 * @param PMProEmail $pmpro_email being sent.
 * @return array
 */
function pmprogl_gift_recipient_email_data( $data, $pmpro_email ) {
	global $pmprogl_gift_levels, $pmpro_level, $current_user;
	if ( $pmpro_email->template === 'pmprogl_gift_recipient' ) {
		if ( empty( $pmpro_email->pmprogl_gift_code ) ) {
			$pmpro_email->pmprogl_gift_code = '';
		}
		if ( empty( $pmpro_email->pmprogl_gift_message ) ) {
			$empty_gift_message = '[' . esc_html__( 'No Message Provided', 'pmpro-gift-levels' ) . ']';
			$pmpro_email->pmprogl_gift_message = apply_filters( 'pmprogl_empty_gift_message', $empty_gift_message, $pmpro_email );
		}
		$data['pmprogl_giver_display_name']   = $current_user->display_name;
		$data['pmprogl_gift_message']  = $pmpro_email->pmprogl_gift_message;
		$data['pmprogl_gift_code']     = $pmpro_email->pmprogl_gift_code;
		$data['pmprogl_gift_code_url'] = pmpro_url("checkout", "?level=" . $pmprogl_gift_levels[ intval( $pmpro_level->id ) ]['level_id'] . "&discount_code=" . $pmpro_email->pmprogl_gift_code);
	}
	return $data;
}
add_filter( 'pmpro_email_data', 'pmprogl_gift_recipient_email_data', 10, 2 );

function pmprogl_get_default_gift_recipient_email_body() {
	ob_start();
	?>
<p><?php esc_html_e( '!!pmprogl_giver_display_name!! has just sent you a gift membership to !!sitename!!!', 'pmpro-gift_levels' ); ?></p>

<p><?php esc_html_e( 'Message:', 'pmpro-gift_levels' ); ?></p>
<p>!!pmprogl_gift_message!!</p>
<hr>
<p><?php esc_html_e( 'Use this link to setup your membership', 'pmpro-gift_levels' ); ?>: <a href="!!pmprogl_gift_code_url!!">!!pmprogl_gift_code_url!!</a></p>
	<?php
	$body = ob_get_contents();
	ob_end_clean();
	return $body;
}
