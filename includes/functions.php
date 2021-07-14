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

	$pmprogl_gift_levels = array();
	$levels = pmpro_getAllLevels();
	foreach ( $levels as $level_id => $level ) {
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

/**
 * Build a PMPro Gift Levels confirmation message.
 *
 * @param int $level_id that was used to purchase $gift_code.
 * @param string $gift_code to get confirmation message for.
 * @return string
 */
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

/**
 * Build a table showing all gift codes purchased by the current user.
 *
 * @return string
 */
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
