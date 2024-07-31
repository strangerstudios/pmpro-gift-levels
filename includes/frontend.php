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

		// Show the gift code and checkout URL.
		if ( ! empty( $gift_code_id ) ) {
			$code = $wpdb->get_row( "SELECT * FROM $wpdb->pmpro_discount_codes WHERE id = '" . intval( $gift_code_id ) . "' LIMIT 1" );
			$code_level_id = $wpdb->get_var("SELECT level_id FROM $wpdb->pmpro_discount_codes_levels WHERE code_id = '" . intval($gift_code_id) . "' LIMIT 1");
			if ( ! empty( $code ) && ! empty( $code_level_id ) ) {
				$code_url = pmpro_url("checkout", "?level=" . $code_level_id . "&discount_code=" . $code->code);
				?>
				<li class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item' ) ); ?>"><strong><?php esc_html_e( 'Gift Code:', 'pmpro-gift-levels'); ?></strong> <span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_tag pmpro_tag-discount-code', 'pmpro_tag-discount-code' ) ); ?>"><?php echo esc_html( $code->code ); ?></span></li>
				<li class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item' ) ); ?>">
					<strong><?php esc_html_e( 'Gift Checkout URL:', 'pmpro-gift-levels' ); ?></strong> <a href="<?php echo esc_url( $code_url ); ?>"><?php echo esc_html( $code_url ); ?></a>
				</li>
				<?php 
			}
		}

		// If the order status is pending, show a message.
		if ( $order->status == 'pending' ) {
			?>
			<li class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item' ) ); ?>"><?php esc_html_e( 'Your gift code will be available after payment is received.', 'pmpro-gift-levels' ); ?></li>
			<?php
		}
	}
}
add_filter( 'pmpro_invoice_bullets_bottom', 'pmprogl_invoice_bullets_bottom', 15 );

/**
 * Show all gift codes that the current user has purchased on the Membership Account page.
 *
 * @param string $content of `[pmpro_account]` shortcode
 * @return string
 */
function pmprogl_the_content_account_page($content)
{
	global $post, $pmpro_pages, $current_user, $wpdb, $pmprogl_gift_levels;
			
	if(!is_admin() && isset( $post ) && isset( $pmpro_pages['account'] ) && $post->ID == $pmpro_pages['account'])
	{
		//get the user's last purchased gift code
		$gift_codes = get_user_meta($current_user->ID, "pmprogl_gift_codes_purchased", true);
		
		if(!empty($gift_codes))
		{
			ob_start();
			?>
			<section id="pmpro_account-pmprogl" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section', 'pmpro_account-pmprogl' ) ); ?>">
				<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section_title pmpro_font-x-large' ) ); ?>"><?php esc_html_e( 'Purchased Gift Codes', 'pmpro-gift-levels' );  ?></h2>
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section_content' ) ); ?>">
					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
							<p><?php esc_html_e( 'Below is a list of gift codes you have purchased.', 'pmpro-gift-levels' ); ?></p>
							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_divider' ) ); ?>"></div>
							<?php echo pmprogl_build_gift_code_list(); ?>
						</div>
					</div>
				</div>
			</section>
			<?php
			$temp_content = ob_get_clean();
			$content = str_replace('<!-- end pmpro_account-profile -->', '<!-- end pmpro_account-profile -->' . $temp_content, $content);
 		}
	}
	
	return $content;
}
add_filter("the_content", "pmprogl_the_content_account_page", 30);
