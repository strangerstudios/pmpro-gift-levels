<?php

/**
 * If the gift levels array is not yet set up, populate it from GUI settings.
 */
function pmprogl_populate_gift_levels_array() {
	global $pmprogl_gift_levels;
	if ( isset( $pmprogl_gift_levels ) ) {
		// Array is already set up.
		return;
	}

	if ( ! function_exists( 'pmpro_getAllLevels' ) ) {
		// PMPro is not active.
		return;
	}

	$pmprogl_gift_levels = array();
	$levels = pmpro_getAllLevels();
	foreach ( $levels as $level_id => $level ) {
		// Update $pmprogl_gift_levels.
		$gift_level_enabled = get_pmpro_membership_level_meta( $level_id, 'pmprogl_enabled_for_level', true );
		if ( 'yes' === $gift_level_enabled ) {
			$gift_level = intval( get_pmpro_membership_level_meta( $level_id, 'pmprogl_gift_level', true ) );
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

/**
 * Build a unordered list showing all gift codes and claim status purchased by the current user.
 *
 * @param int|null $user_id to build list for.
 * @return string
 */
function pmprogl_build_gift_code_list( $user_id = null ){
	global $current_user, $wpdb;

	if ( empty( $user_id ) ) {
		$user_id = $current_user->ID;
	}

	$gift_codes = get_user_meta( $user_id, "pmprogl_gift_codes_purchased", true);
	if ( empty( $gift_codes ) ) {
		return '';
	}

	ob_start();
	?>
	<div id="pmpro_account-gift_codes" class="pmpro_box">	

		<h2><?php esc_html_e( "Gift Codes", "pmpro-gift-levels" ); ?></h2>
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
						 if ( ! empty( $code_use ) ) {
							$code_user = get_userdata( $code_use ); 
							printf( __( '%s: claimed by %s', 'pmpro-gift-levels' ), esc_html( $code->code ), esc_html( $code_user->display_name ) );
						} else { ?>
							<a target="_blank" href="<?php echo esc_attr( $code_url );?>"><?php echo esc_html( $code->code ); ?></a>
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
