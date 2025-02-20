<?php

class PMPro_Email_Template_PMProGL_Gift_Recipient extends PMPro_Email_Template {

	/**
	 * The Order that was created for the gift purchase.
	 *
	 * @var MemberOrder
	 */
	protected $order;

	/**
	 * The Level gifted id
	 *
	 * @var int
	 */
	protected $gift_level_id;

	/**
	 * The user who gifted the membership.
	 *
	 * @var WP_User
	 */
	protected $giver;

	/**
	 * Email recipient.
	 *
	 * @var string
	 */
	protected  $email_recipient;

	/**
	 * The gift code.
	 * 
	 * @var string
	 */
	protected $gcode;

	/**
	 * The gift message.
	 * 
	 * @var string
	 */
	protected $gift_message;
	/**
	 * Constructor.
	 *
	 * @since TBD
	 *
	 * @param String $email_recipient The email address of the recipient.
	 */
	public function __construct( MemberOrder $order, int $gift_level_id,  WP_User $giver,  String $email_recipient, 
		String $gcode, String $gift_message ) {
		$this->order = $order;
		$this->gift_level_id = $gift_level_id;
		$this->giver = $giver;
		$this->email_recipient = $email_recipient;
		$this->gcode = $gcode;
		$this->gift_message = $gift_message;
	}

	/**
	 * Get the email template slug.
	 *
	 * @since TBD
	 *
	 * @return string The email template slug.
	 */
	public static function get_template_slug() {
		return 'pmprogl_gift_recipient';
	}

	/**
	 * Get the "nice name" of the email template.
	 *
	 * @since TBD
	 *
	 * @return string The "nice name" of the email template.
	 */
	public static function get_template_name() {
		return esc_html__( 'Gift Recipient', 'pmpro-gift-levels' );
	}

	/**
	 * Get "help text" to display to the admin when editing the email template.
	 *
	 * @since TBD
	 *
	 * @return string The "help text" to display to the admin when editing the email template.
	 */
	public static function get_template_description() {
		return esc_html__( 'This email is sent when the gift giver provides the recipient email address at checkout. 
		Additional placeholder variables you can use in this email template include !!pmprogl_giver_display_name!!, 
		!!pmprogl_gift_message!!, !!pmprogl_gift_code!!, and !!pmprogl_gift_code_url!!.', 'pmpro-gift-levels' );
	}

	/**
	 * Get the default subject for the email.
	 *
	 * @since TBD
	 *
	 * @return string The default subject for the email.
	 */
	public static function get_default_subject() {
		return esc_html( sprintf( __( 'You have been gifted a membership to %s', 'pmpro_gift_levels' ), get_option( 'blogname' ) ) );
	}

	/**
	 * Get the default body content for the email.
	 *
	 * @since TBD
	 *
	 * @return string The default body content for the email.
	 */
	public static function get_default_body() {

		return wp_kses_post( __( '<p>!!pmprogl_giver_display_name!! has just sent you a gift membership to !!sitename!!!</p>
<p>!!pmprogl_gift_message!!</p>
<p>Use this link to set up your membership: <a href="!!pmprogl_gift_code_url!!">!!pmprogl_gift_code_url!!</a></p>', 'pmpro-gift-levels' ) );
	}

	/**
	 * Get the email template variables for the email paired with a description of the variable.
	 *
	 * @since TBD
	 *
	 * @return array The email template variables for the email (key => value pairs).
	 */
	public static function get_email_template_variables_with_description() {
		return array(
			'!!pmprogl_giver_display_name!!' => esc_html__( 'The display name of the user who gifted the membership.', 'pmpro-gift-levels' ),
			'!!pmprogl_giver_email!! ' => esc_html__( 'The email address of the user who gifted the membership.', 'pmpro-gift-levels' ),
			'!!pmprogl_gift_code_url!!' => esc_html__( 'The URL to the page where the recipient can redeem their gift.', 'pmpro-gift-levels' ),
			'!!pmprogl_gift_message!!' => esc_html__( 'The message the giver included with the gift.', 'pmpro-gift-levels' ),
			'!!pmprogl_gift_code!!' => esc_html__( 'The gift code the recipient can use to redeem their gift.', 'pmpro-gift-levels' ),
			'!!order_id!!' => esc_html__( 'The ID of the order.', 'pmpro-gift-levels' ),
			'!!order_date!!' => esc_html__( 'The date of the order.', 'pmpro-gift-levels' ),
			'!!order_total!!' => esc_html__( 'The total cost of the order.', 'pmpro-gift-levels' ),
			'!!billing_name!!' => esc_html__( 'Billing Info Name', 'pmpro-gift-levels' ),
			'!!billing_street!!' => esc_html__( 'Billing Info Street', 'pmpro-gift-levels' ),
			'!!billing_street2!!' => esc_html__( 'Billing Info Street 2', 'pmpro-gift-levels' ),
			'!!billing_city!!' => esc_html__( 'Billing Info City', 'pmpro-gift-levels' ),
			'!!billing_state!!' => esc_html__( 'Billing Info State', 'pmpro-gift-levels' ),
			'!!billing_zip!!' => esc_html__( 'Billing Info Zip', 'pmpro-gift-levels' ),
			'!!billing_country!!' => esc_html__( 'Billing Info Country', 'pmpro-gift-levels' ),
			'!!billing_phone!!' => esc_html__( 'Billing Info Phone', 'pmpro-gift-levels' ),
			'!!billing_address!!' => esc_html__( 'Billing Info Complete Address', 'pmpro-gift-levels' ),
			'!!cardtype!!' => esc_html__( 'Credit Card Type', 'pmpro-gift-levels' ),
			'!!accountnumber!!' => esc_html__( 'Credit Card Number (last 4 digits)', 'pmpro-gift-levels' ),
			'!!expirationmonth!!' => esc_html__( 'Credit Card Expiration Month (mm format)', 'pmpro-gift-levels' ),
			'!!expirationyear!!' => esc_html__( 'Credit Card Expiration Year (yyyy format)', 'pmpro-gift-levels' ),
			'!!order_url!!' => esc_html__( 'The URL of the order.', 'pmpro-gift-levels' ),
		);
	}

	/**
	 * Get the email template variables for the email.
	 *
	 * @since TBD
	 *
	 * @return array The email template variables for the email (key => value pairs).
	 */
	public function get_email_template_variables() {
		$morder = $this->order;

		$email_template_variables = array(
			'pmprogl_giver_display_name' => $this->giver->display_name,
			'pmprogl_gift_code_url' => pmpro_url( 'checkout', '?level=' . $this->gift_level_id . '&discount_code=' . $this->gcode ),
			'pmprogl_gift_message' => wp_unslash( $this->gift_message ),
			'pmprogl_gift_code' => $this->gcode,
			'order_id' => $morder->code,
			'order_total' => pmpro_formatPrice( $morder->total ),
			'order_date' => date_i18n( get_option( 'date_format' ), $morder->getTimestamp() ),
			'billing_name' => $morder->billing->name,
			'billing_street' => $morder->billing->street,
			'billing_city' => $morder->billing->city,
			'billing_state' => $morder->billing->state,
			'billing_zip' => $morder->billing->zip,
			'billing_country' => $morder->billing->country,
			'billing_phone' => $morder->billing->phone,
			'cardtype' => $morder->cardtype,
			'accountnumber' => hideCardNumber( $morder->accountnumber ),
			'expirationmonth' => $morder->expirationmonth,
			'expirationyear' => $morder->expirationyear,
			'billing_address' => pmpro_formatAddress( $morder->billing->name,
													  $morder->billing->street,
													  $morder->billing->street2,
													  $morder->billing->city,
													  $morder->billing->state,
													  $morder->billing->zip,
													  $morder->billing->country,
													  $morder->billing->phone ),
			'order_url' => pmpro_login_url( pmpro_url( 'invoice', '?invoice=' . $morder->code ) ),
		);
		return $email_template_variables;
	}

	/**
	 * Get the email address to send the email to.
	 *
	 * @since TBD
	 *
	 * @return string The email address to send the email to.
	 */
	public function get_recipient_email() {
		return $this->email_recipient;
	}

	/**
	 * Get the name of the email recipient.
	 *
	 * @since TBD
	 *
	 * @return string The name of the email recipient.
	 */
	public function get_recipient_name() {
		return $this->get_recipient_email();
	}
}
/**
 * Register the email template.
 *
 * @since TBD
 *
 * @param array $email_templates The email templates (template slug => email template class name)
 * @return array The modified email templates array.
 */
function pmprogl_email_template_gift_recipient( $email_templates ) {
	$email_templates['pmprogl_gift_recipient'] = 'PMPro_Email_Template_PMProGL_Gift_Recipient';
	return $email_templates;
}
add_filter( 'pmpro_email_templates', 'pmprogl_email_template_gift_recipient' );