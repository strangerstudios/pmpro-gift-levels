<?php

/**
 * Add PMPro Gift Levels settings to the "Edit Level" page.
 *
 * Not using $level parameter as it will not be available for PMPro version < 2.5.10.
 */
function pmprogl_membership_level_after_other_settings() {
	global $wpdb, $wp_version;
	$edit_level_id = $_REQUEST['edit'];

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
	$gift_level           = intval( $_REQUEST['pmprogl_gift_level'] );
	$confirmation_message = wp_kses( wp_unslash( $_REQUEST['pmprogl_confirmation_message'] ), $allowedposttags);
	if ( empty( $gift_level ) || empty( $_REQUEST['pmprogl_gift_expires'] ) ) {
		$expiration_number = 0;
		$expiration_period = 'day';
	} else {
		$expiration_number = intval( $_REQUEST['pmprogl_expiration_number'] );
		$expiration_period = sanitize_text_field( $_REQUEST['pmprogl_expiration_period'] );
	}
	
	update_pmpro_membership_level_meta( $save_id, 'pmprogl_gift_level', $gift_level );
	update_pmpro_membership_level_meta( $save_id, 'pmprogl_confirmation_message', $confirmation_message );
	update_pmpro_membership_level_meta( $save_id, 'pmprogl_expiration_number', $expiration_number );
	update_pmpro_membership_level_meta( $save_id, 'pmprogl_expiration_period', $expiration_period );
}
add_action( 'pmpro_save_membership_level', 'pmprogl_save_membership_level', 10, 1 );

/**
 * Show the gift code associated with an order on the Edit Order page.
 */
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