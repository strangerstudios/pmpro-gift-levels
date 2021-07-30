<?php

/**
 * Append the PMPro Gift Levels confirmation message to the current
 * confirmation message on the PMPro confirmation page.
 *
 * @param string $message confirmation message.
 * @return string
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

/**
 * Add gift code to frontend invoice page.
 *
 * @param MemberOrder $order being shown to user.
 */
function pmprogl_invoice_bullets_bottom( $order ) {
	global $pmprogl_gift_levels, $wpdb;

	// Check if the level for the order is a gift level.
	if ( ! empty( $order->membership_id ) && ! empty( $pmprogl_gift_levels ) && ! empty( $pmprogl_gift_levels[ $order->membership_id ] ) ) {
		//get the user's last purchased gift code
		if ( version_compare( '2.5', PMPRO_VERSION, '<=' ) && false ) {
			$gift_code_id = get_pmpro_membership_order_meta( $order->id, 'pmprogl_code_id', true );
		} else {
			$purchased_gift_codes = get_user_meta( $order->user_id, "pmprogl_gift_codes_purchased", true );
			if ( is_array( $purchased_gift_codes ) ) {
				$gift_code_id = end( $purchased_gift_codes );
			}
		}
		
		if ( ! empty( $gift_code_id ) ) {
			$code = $wpdb->get_row( "SELECT * FROM $wpdb->pmpro_discount_codes WHERE id = '" . intval( $gift_code_id ) . "' LIMIT 1" );
			if ( ! empty( $code ) ) {
				?><li><strong><?php _e('Gift Code', 'pmprogl');?>: </strong><?php echo $code->code;?></li><?php
			}			
		}
	}
}
add_filter( 'pmpro_invoice_bullets_bottom', 'pmprogl_invoice_bullets_bottom' );

/**
 * Show all gift codes that the current user has purchased on the PMPro
 * Account page.
 *
 * @param string $content of `[pmpro_account]` shortcode
 * @return string
 */
function pmprogl_the_content_account_page($content)
{
	global $post, $pmpro_pages, $current_user, $wpdb, $pmprogl_gift_levels;
			
	if(!is_admin() && isset( $post ) && $post->ID == $pmpro_pages['account'])
	{
		//get the user's last purchased gift code
		$gift_codes = get_user_meta($current_user->ID, "pmprogl_gift_codes_purchased", true);
		
		if(!empty($gift_codes))
		{
			$temp_content = pmprogl_build_gift_code_table();
			$content = str_replace('<!-- end pmpro_account-profile -->', '<!-- end pmpro_account-profile -->' . $temp_content, $content);
 		}
	}
	
	return $content;
}
add_filter("the_content", "pmprogl_the_content_account_page", 30);