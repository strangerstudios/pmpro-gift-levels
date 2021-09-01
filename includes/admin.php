<?php

/**
 * Add PMPro Gift Levels settings to the "Edit Level" page.
 *
 * Not using $level parameter as it will not be available for PMPro version < 2.5.10.
 */
function pmprogl_membership_level_after_other_settings() {
	global $wpdb, $wp_version;
	$edit_level_id        = $_REQUEST['edit'];

	$enabled              = 'yes' === get_pmpro_membership_level_meta( $edit_level_id, 'pmprogl_enabled_for_level', true );
	$gift_level_checked   = $enabled ? ' checked' : '';

	$gift_level           = intval( get_pmpro_membership_level_meta( $edit_level_id, 'pmprogl_gift_level', true ) );

	$confirmation_message = get_pmpro_membership_level_meta( $edit_level_id, 'pmprogl_confirmation_message', true );
	if ( empty( $confirmation_message ) ) {
		$confirmation_message = '<p><strong>' . __( 'Share this link with your gift recipient' ) . ': <a href="!!pmprogl_gift_code_url!!">!!pmprogl_gift_code_url!!</a></p></strong>';
	}

	$allow_gift_emails = get_pmpro_membership_level_meta( $edit_level_id, 'pmprogl_allow_gift_emails', true );
	if ( empty( $allow_gift_emails ) ) {
		$allow_gift_emails = 'no';
	}

	$expiration_number = intval( get_pmpro_membership_level_meta( $edit_level_id, 'pmprogl_expiration_number', true ) );
	$expiration_period = get_pmpro_membership_level_meta( $edit_level_id, 'pmprogl_expiration_period', true );
	if ( empty( $expiration_period ) ) {
		$expiration_period = 'Day';
	}

	?>
	<hr>
	<h2 class="title"><?php esc_html_e( 'Gift Membership', 'pmpro-gift-levels' ); ?></h2>
	<p><?php esc_html_e( 'This level can be assigned as a "gift level" that users can purchase. After checkout, the gift giver will receive a code they can share with the recipient. To enable this feature, choose a level from the dropdown below. The selected level is the level that will be given to the user who claims the gift code generated after purchase.', 'pmpro-gift-levels' ); ?> <a href="https://www.paidmembershipspro.com/add-ons/pmpro-gift-levels/?utm_source=plugin&utm_medium=pmpro-membershiplevels&utm_campaign=add-ons&utm_content=pmpro-gift-levels" target="_blank"><?php esc_html_e( 'Click here to read the Gift Membership Add On documentation.', 'pmpro-gift-levels' ); ?></a></p>
	<?php
		// Populate an array of all membership levels, then remove the currently edited level from the array.
		$giftable_levels = pmpro_sort_levels_by_order( pmpro_getAllLevels(false, true) );
		if ( ! empty( strval( $edit_level_id ) ) && ! empty( $giftable_levels[ strval( $edit_level_id ) ] ) ) {
			$current_level = $giftable_levels[ strval( $edit_level_id ) ];
			unset( $giftable_levels[ strval( $edit_level_id ) ] );
		}

		// Show an error if currently edited level has a recurring subscription or membership expiration set.
		if ( ! empty( $current_level ) && ( ! empty( intval( $current_level->billing_amount ) ) || ! empty( intval( $current_level->expiration_number ) ) ) ) {
			?>
			<div class="pmprogl_gift_level_toggle_setting notice error" <?php if( empty( $gift_level ) ) {?>style="display: none;"<?php } ?>>
				<p><strong><?php esc_html_e( 'Gift Membership Warning:', 'pmpro-gift-levels' ); ?></strong> <?php esc_html_e( 'The settings of this level are not recommended. Remove the recurring subscription and membership expiration from this level.', 'pmpro-gift-levels' ); ?></p>
			</div>
			<?php
		}
	?>
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row" valign="top"><label><?php esc_html_e('Gift Level?', 'pmpro-gift-levels' );?>:</label></th>
				<td>
					<input type="checkbox" id="pmprogl_enabled_for_level" name="pmprogl_enabled_for_level" value="1" <?php echo $gift_level_checked; ?> />
				</td>
			</tr>
			<tr class="pmprogl_gift_level_toggle_setting" <?php if( ! $enabled ) {?>style="display: none;"<?php }  ?>>
				<th scope="row" valign="top"><label><?php esc_html_e('Level to Gift', 'pmpro-gift-levels' );?>:</label></th>
				<td>
					<select id="pmprogl_gift_level" name="pmprogl_gift_level">
						<?php
						// Show a dropdown of all membership levels, excluding the currently edited level.
						foreach ( $giftable_levels as $level_id => $level ) {
							echo "<option value='" . esc_attr( $level_id ) . "' " . selected( $gift_level, intval ( $level_id ), false ) . ">" . esc_html( $level->name ) . "</option>";
						}
						?>
					</select>
				</td>
			</tr>
			<tr class="pmprogl_gift_level_toggle_setting" <?php if( ! $enabled ) {?>style="display: none;"<?php }  ?>>
				<th scope="row" valign="top"><label for="pmprogl_confirmation_message"><?php esc_html_e('Gift Confirmation Message', 'pmpro-gift-levels' );?>:</label></th>
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
						<p class="description">
							<?php esc_html_e( 'This message will be shown in the checkout confirmation email.', 'pmpro-gift-levels' ); ?>
							<?php echo wp_kses_post( 'Available variables are <code>!!pmprogl_gift_code!!</code> and <code>!!pmprogl_gift_code_url!!</code>', 'pmpro-gift-levels' ); ?>
						</p>
					</div>
				</td>
			</tr>
			<tr class="pmprogl_gift_level_toggle_setting" <?php if( ! $enabled ) {?>style="display: none;"<?php } ?>>
				<th scope="row" valign="top"><?php esc_html_e('Allow Gift Emails', 'pmpro-gift-levels' ); ?>:</th>
 				<td>
 					<input id="pmprogl_allow_gift_emails" name="pmprogl_allow_gift_emails" type="checkbox" value="yes" <?php if( 'yes' === $allow_gift_emails ) { ?>checked="checked"<?php } ?> />
					<label for="pmprogl_allow_gift_emails"><?php esc_html_e( 'Check to allow customers to enter the recipient email address at checkout.', 'pmpro-gift-levels' );?></label>
					<p class="description"><?php esc_html_e( 'If an email address is provided, the recipient will automatically receive an email containing the gift code. You can customize the "Gift Recipient" template on the Memberships > Settings > Email Templates page in the WordPress admin.', 'pmpro-gift-levels' ); ?></p>
 				</td>
 			</tr>
			<tr class="pmprogl_gift_level_toggle_setting" <?php if( ! $enabled ) {?>style="display: none;"<?php } ?>>
				<th scope="row" valign="top"><?php esc_html_e('Gift Membership Expires', 'pmpro-gift-levels' ); ?>:</th>
				<td>
					<input id="pmprogl_gift_expires" name="pmprogl_gift_expires" type="checkbox" value="yes" <?php if ( $expiration_number ) { echo "checked='checked'"; } ?>/>
					<label for="pmprogl_gift_expires"><?php esc_html_e( 'Check this to set an expiration period for the gifted membership level.', 'pmpro_gift_levels' ); ?></label>
					<p class="description"><?php esc_html_e( 'If you do not set an expiration period, the gift recipient\'s membership will never expire.', 'pmpro-gift-levels' ); ?></p>
				</td>
			</tr>
			<tr id="pmprogl_period_tr" <?php if( ! $enabled || empty( $expiration_number ) ) {?>style="display: none;"<?php } ?>>
				<th scope="row" valign="top"><label><?php esc_html_e( 'Gift Expiration Period', 'pmpro-gift-levels' );?>:</label></th>
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
	$enabled              = empty( $_REQUEST['pmprogl_enabled_for_level'] ) ? 'no' : 'yes';
	$gift_level			  = intval( empty( $_REQUEST['pmprogl_gift_level'] ? 0 : $_REQUEST['pmprogl_gift_level'] ) );
	$confirmation_message = wp_kses( wp_unslash( $_REQUEST['pmprogl_confirmation_message'] ), $allowedposttags);
	$allow_gift_emails = empty( $_REQUEST['pmprogl_allow_gift_emails'] ) ? 'no' : 'yes';
	if ( empty( $gift_level ) || empty( $_REQUEST['pmprogl_gift_expires'] ) ) {
		$expiration_number = 0;
		$expiration_period = 'day';
	} else {
		$expiration_number = intval( $_REQUEST['pmprogl_expiration_number'] );
		$expiration_period = sanitize_text_field( $_REQUEST['pmprogl_expiration_period'] );
	}

	update_pmpro_membership_level_meta( $save_id, 'pmprogl_enabled_for_level', $enabled );
	update_pmpro_membership_level_meta( $save_id, 'pmprogl_gift_level', $gift_level );
	update_pmpro_membership_level_meta( $save_id, 'pmprogl_confirmation_message', $confirmation_message );
	update_pmpro_membership_level_meta( $save_id, 'pmprogl_allow_gift_emails', $allow_gift_emails );
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

	$gift_recipient = get_pmpro_membership_order_meta( $order->id, 'pmprogl_recipient_email', true );
	if ( empty( $gift_recipient ) ) {
		return;
	}
	?>
	<tr>
		<th><?php esc_html_e( 'Gift Recipient Email', 'pmpro-gift-levels' ); ?></th>
		<td>
			<?php
				echo esc_html( $gift_recipient );
			?>
		</td>
	</tr>
	<?php
}
add_action( 'pmpro_after_order_settings', 'pmprogl_after_order_settings', 10, 1 );

/**
 * Show gift codes that the user has purchased on the Edit User page.
 */
function pmprogl_after_membership_level_profile_fields( $user ) {
	echo pmprogl_build_gift_code_table( $user->id );
}
add_action( 'pmpro_after_membership_level_profile_fields', 'pmprogl_after_membership_level_profile_fields' );

/**
 * Show the user who purchased a discount code while editing the code.
 */
function pmprogl_discount_code_after_settings( $discount_code_id ) {
	global $wpdb;
	if ( version_compare( '2.5', PMPRO_VERSION, '>' ) ) {
		// Order meta was only implemented in PMPro v2.5.
		return;
	}

	$order_id = $wpdb->get_var("SELECT pmpro_membership_order_id FROM $wpdb->pmpro_membership_ordermeta WHERE meta_key = 'pmprogl_code_id' AND meta_value = '" . intval($discount_code_id) . "' LIMIT 1");
	if ( empty( $order_id ) ) {
		return;
	}

	$order = new MemberOrder( $order_id );
	if ( empty( $order->user_id ) ) {
		return;
	}

	$user = get_userdata( $order->user_id );
	if ( ! empty( $user ) ) {
		echo '<strong>' . esc_html__( 'This discount code was purchased as a gift by', 'pmpro-gift-levels' ) . ' ' . '<a href="user-edit.php?user_id=' . $user->ID . '">' . $user->display_name . '</a></h3>';
	}
}
add_action( 'pmpro_discount_code_after_settings', 'pmprogl_discount_code_after_settings' );
