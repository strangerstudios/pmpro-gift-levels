<?php

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
		if ( version_compare( '2.5', PMPRO_VERSION, '<=' ) ) {
			$gift_code_id = get_pmpro_membership_order_meta( $order->id, 'pmprogl_code_id', true );
		} else {
			$purchased_gift_codes = get_user_meta( $order->user_id, "pmprogl_gift_codes_purchased", true );
			if ( is_array( $purchased_gift_codes ) ) {
				$gift_code_id = end( $purchased_gift_codes );
			}
		}
		
		if ( ! empty( $gift_code_id ) ) {
			$code = $wpdb->get_row( "SELECT * FROM $wpdb->pmpro_discount_codes WHERE id = '" . intval( $gift_code_id ) . "' LIMIT 1" );
			$code_level_id = $wpdb->get_var("SELECT level_id FROM $wpdb->pmpro_discount_codes_levels WHERE code_id = '" . intval($gift_code_id) . "' LIMIT 1");
			if ( ! empty( $code ) && ! empty( $code_level_id ) ) {
				$code_url = pmpro_url("checkout", "?level=" . $code_level_id . "&discount_code=" . $code->code);
				?>
				<li><strong><?php esc_html_e( 'Gift Code:', 'pmpro-gift-levels'); ?></strong> <?php echo esc_html( $code->code ); ?></li>
				<li>
					<strong><?php esc_html_e( 'Gift Checkout URL:', 'pmpro-gift-levels' ); ?></strong> <?php echo esc_html( $code_url ); ?></li>
				<?php 
			}
		}
	}
}
add_filter( 'pmpro_invoice_bullets_bottom', 'pmprogl_invoice_bullets_bottom' );

/**
 * Show all gift codes that the current user has purchased on the Membership Account page.
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
			$temp_content = pmprogl_build_gift_code_list();
			$content = str_replace('<!-- end pmpro_account-profile -->', '<!-- end pmpro_account-profile -->' . $temp_content, $content);
 		}
	}
	
	return $content;
}
add_filter("the_content", "pmprogl_the_content_account_page", 30);
