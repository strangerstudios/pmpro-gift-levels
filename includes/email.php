<?php
/**
 * Add Gift Membership-specific templates to the email templates.
 *
 * @param array $templates that can be edited.
 */
function pmprogl_template_callback( $templates ) {
	$templates['pmprogl_gift_recipient'] = array(
		'subject' => esc_html( sprintf( __( 'You have been gifted a membership to %s', 'pmpro_gift_levels' ), get_option( 'blogname' ) ) ),
		'description' => __( 'Gift Recipient', 'pmpro-gift-levels' ),
		'body' => pmprogl_get_default_gift_recipient_email_body(),
		'help_text' => __( 'This email is sent when the gift giver provides the recipient email address at checkout. Additional placeholder variables you can use in this email template include !!pmprogl_giver_display_name!!, !!pmprogl_gift_message!!, !!pmprogl_gift_code!!, and !!pmprogl_gift_code_url!!.', 'pmpro-gift-levels' )
	);
	$templates['pmprogl_gift_purchased'] = array(
		'subject' => esc_html( sprintf( __( 'Gift membership purchase confirmation for %s', 'pmpro_gift_levels' ), get_option( 'blogname' ) ) ),
		'description' => __( 'Gift Purchased', 'pmpro-gift-levels' ),
		'body' => pmprogl_get_default_gift_purchased_email_body(),
		'help_text' => __( 'This email is sent to the gift giver as confirmation of their purchase after checkout. Additional placeholder variables you can use in this email template include !!pmprogl_giver_display_name!!, !!pmprogl_gift_message!!, !!pmprogl_gift_code!!, and !!pmprogl_gift_code_url!!.', 'pmpro-gift-levels' )
	);
	$templates['pmprogl_gift_purchased_admin'] = array(
		'subject' => esc_html( sprintf( __( '!!pmprogl_giver_display_name!! has purchased a gift membership to %s', 'pmpro_gift_levels' ), get_option( 'blogname' ) ) ),
		'description' => __( 'Gift Purchased (admin)', 'pmpro-gift-levels' ),
		'body' => pmprogl_get_default_gift_purchased_admin_email_body(),
		'help_text' => __( 'This email is sent to the admin as confirmation of gift purchase after checkout. Additional placeholder variables you can use in this email template include !!pmprogl_giver_display_name!!, !!pmprogl_gift_message!!, !!pmprogl_gift_code!!, and !!pmprogl_gift_code_url!!.', 'pmpro-gift-levels' )
	);
	
	return $templates;
}

/**
 * Default email content for the gift recipient email template.
 *
 */
function pmprogl_get_default_gift_recipient_email_body() {
	ob_start(); ?>
<p><?php esc_html_e( '!!pmprogl_giver_display_name!! has just sent you a gift membership to !!sitename!!!', 'pmpro-gift-levels' ); ?></p>
!!pmprogl_gift_message!!
<p><?php esc_html_e( 'Use this link to set up your membership:', 'pmpro-gift-levels' ); ?> <a href="!!pmprogl_gift_code_url!!">!!pmprogl_gift_code_url!!</a></p><?php
	$body = ob_get_contents();
	ob_end_clean();
	return $body;
}

/**
 * Default email content for the gift purchased email template sent to the user purchasing the gift.
 *
 */
function pmprogl_get_default_gift_purchased_email_body() {
	ob_start(); ?>
<p><?php esc_html_e( 'Thank you for your purchase at !!sitename!!. Below is a receipt for your purchase.', 'pmpro-gift-levels' ); ?></p>
<p><?php esc_html_e( 'Account: !!pmprogl_giver_display_name!! (!!pmprogl_giver_email!!)', 'pmpro-gift-levels' ); ?></p>
<p>
	<?php esc_html_e( 'Invoice #!!invoice_id!! on !!invoice_date!!', 'pmpro-gift-levels' ); ?><br />
	<?php esc_html_e( 'Total Billed: !!invoice_total!!', 'pmpro-gift-levels' ); ?>
</p>
<p><strong><?php esc_html_e( 'Share this link with your gift recipient:', 'pmpro-gift-levels' ); ?> <a href="!!pmprogl_gift_code_url!!">!!pmprogl_gift_code_url!!</a></strong></p>
<p><?php esc_html_e( 'Log in to view your purchase history here: !!login_url!!', 'pmpro-gift-levels' ); ?></p>
<p><?php esc_html_e( 'To view an online version of this invoice, click here: !!invoice_url!!', 'pmpro-gift-levels' ); ?></p><?php
	$body = ob_get_contents();
	ob_end_clean();
	return $body;
}

/**
 * Default email content for the gift purchased email template sent to admin after a user purchases a gift.
 *
 */
function pmprogl_get_default_gift_purchased_admin_email_body() {
	ob_start(); ?>
<p><?php esc_html_e( 'There was a new gift membership checkout at !!sitename!!.', 'pmpro-gift-levels' ); ?></p>
<p><?php esc_html_e( 'Below are details about the purchase and a receipt for the initial invoice.', 'pmpro-gift-levels' ); ?></p>
<p><?php esc_html_e( 'Account: !!pmprogl_giver_display_name!! (!!pmprogl_giver_email!!)', 'pmpro-gift-levels' ); ?></p>
<p>
	<?php esc_html_e( 'Invoice #!!invoice_id!! on !!invoice_date!!', 'pmpro-gift-levels' ); ?><br />
	<?php esc_html_e( 'Total Billed: !!invoice_total!!', 'pmpro-gift-levels' ); ?><br />
	<?php esc_html_e( 'Gift Code: !!pmprogl_gift_code!!', 'pmpro-gift-levels' ); ?>
</p><?php
	$body = ob_get_contents();
	ob_end_clean();
	return $body;
}
