<?php
/**
 * Add the gift membership level template
 * @since 1.0.2
 */
function pmprogl_membershiplevels_template_level( $level, $template ) {
	if ( $template === 'gift' ) {
		$level->billing_amount = NULL;
		$level->trial_amount = NULL;
		$level->initial_payment = '25';
		$level->billing_limit = NULL;
		$level->trial_limit = NULL;
		$level->expiration_number = NULL;
		$level->expiration_period = NULL;
		$level->cycle_number = 1;
		$level->cycle_period = 'Month';
	}
	return $level;
}
add_filter( 'pmpro_membershiplevels_template_level', 'pmprogl_membershiplevels_template_level', 10, 2 );

/**
 * Add PMPro Gift Levels settings to the "Edit Level" page.
 *
 * Not using $level parameter as it will not be available for PMPro version < 2.5.10.
 */
function pmprogl_membership_level_after_other_settings() {
	global $wpdb, $wp_version;
	$edit_level_id        = $_REQUEST['edit'];

	// Get the template if passed in the URL.
	if ( isset( $_REQUEST['template'] ) ) {
		$template = sanitize_text_field( $_REQUEST['template'] );
	} else {
		$template = false;
	}

	// Set template default if this is a new gift level.
	if ( $template === 'gift' && $edit_level_id === '-1' ) {
		$enabled = 'yes';
		$gift_level_checked = ' checked';
		$gift_level = 0;
		$allow_gift_emails = 'yes';
		$expiration_number = 3;
		$expiration_period = 'Month';
	} else {
		$enabled          = 'yes' === get_pmpro_membership_level_meta( $edit_level_id, 'pmprogl_enabled_for_level', true );

		$gift_level_checked   = $enabled ? ' checked' : '';

		$gift_level           = intval( get_pmpro_membership_level_meta( $edit_level_id, 'pmprogl_gift_level', true ) );

		$allow_gift_emails = get_pmpro_membership_level_meta( $edit_level_id, 'pmprogl_allow_gift_emails', true );
		if ( empty( $allow_gift_emails ) ) {
			$allow_gift_emails = 'no';
		}

		$expiration_number = intval( get_pmpro_membership_level_meta( $edit_level_id, 'pmprogl_expiration_number', true ) );
		$expiration_period = get_pmpro_membership_level_meta( $edit_level_id, 'pmprogl_expiration_period', true );
		if ( empty( $expiration_period ) ) {
			$expiration_period = 'Day';
		}
	}

	// Hide or show this section based on settings
	if ( $template === 'gift' || $enabled == 'yes' ) {
		$section_visibility = 'shown';
		$section_activated = 'true';
	} else {
		$section_visibility = 'hidden';
		$section_activated = 'false';
	}
	?>
	<div id="gift-settings" class="pmpro_section" data-visibility="<?php echo esc_attr( $section_visibility ); ?>" data-activated="<?php echo esc_attr( $section_activated ); ?>">
		<div class="pmpro_section_toggle">
			<button class="pmpro_section-toggle-button" type="button" aria-expanded="<?php echo $section_visibility === 'hidden' ? 'false' : 'true'; ?>">
				<span class="dashicons dashicons-arrow-<?php echo $section_visibility === 'hidden' ? 'down' : 'up'; ?>-alt2"></span>
				<?php esc_html_e( 'Gift Membership', 'pmpro-gift-levels' ); ?>
			</button>
		</div>
		<div class="pmpro_section_inside" <?php echo $section_visibility === 'hidden' ? 'style="display: none"' : ''; ?>>
			<p><?php esc_html_e( 'This level can be assigned as a "gift level" that users can purchase. After checkout, the gift giver will receive a code they can share with the recipient. To enable this feature, choose a level from the dropdown below. The selected level is the level that will be given to the user who claims the gift code generated after purchase.', 'pmpro-gift-levels' ); ?> <a rel="nofollow noopener" href="https://www.paidmembershipspro.com/add-ons/pmpro-gift-levels/?utm_source=plugin&utm_medium=pmpro-membershiplevels&utm_campaign=add-ons&utm_content=pmpro-gift-levels" target="_blank"><?php esc_html_e( 'Click here to read the Gift Membership Add On documentation.', 'pmpro-gift-levels' ); ?></a></p>
			<?php
				// Populate an array of all membership levels, then remove the currently edited level from the array.
				$giftable_levels = pmpro_sort_levels_by_order( pmpro_getAllLevels(false, true) );
				if ( ! empty( strval( $edit_level_id ) ) && ! empty( $giftable_levels[ strval( $edit_level_id ) ] ) ) {
					$current_level = $giftable_levels[ strval( $edit_level_id ) ];
					unset( $giftable_levels[ strval( $edit_level_id ) ] );
				}

				// Show an error if currently edited level has a recurring subscription or membership expiration set.
				if ( ! empty( $current_level ) && ! empty( $enabled ) && ( ! empty( intval( $current_level->billing_amount ) ) || ! empty( intval( $current_level->expiration_number ) ) ) ) {
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
						<th scope="row" valign="top"><label><?php esc_html_e( 'Gift Level?', 'pmpro-gift-levels' ); ?></label></th>
						<td>
							<input type="checkbox" id="pmprogl_enabled_for_level" name="pmprogl_enabled_for_level" value="1" <?php echo $gift_level_checked; ?> />
						</td>
					</tr>
					<tr class="pmprogl_gift_level_toggle_setting" <?php if( ! $enabled ) {?>style="display: none;"<?php }  ?>>
						<th scope="row" valign="top"><label><?php esc_html_e( 'Level to Gift', 'pmpro-gift-levels' );?></label></th>
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
					<tr class="pmprogl_gift_level_toggle_setting" <?php if( ! $enabled ) {?>style="display: none;"<?php } ?>>
						<th scope="row" valign="top"><?php esc_html_e( 'Allow Gift Emails', 'pmpro-gift-levels' ); ?></th>
		 				<td>
		 					<input id="pmprogl_allow_gift_emails" name="pmprogl_allow_gift_emails" type="checkbox" value="yes" <?php if( 'yes' === $allow_gift_emails ) { ?>checked="checked"<?php } ?> />
							<label for="pmprogl_allow_gift_emails"><?php esc_html_e( 'Check to allow customers to enter the recipient email address at checkout.', 'pmpro-gift-levels' );?></label>
							<p class="description"><?php esc_html_e( 'If an email address is provided, the recipient will automatically receive an email containing a personalized message and a link to claim the gift code. You can customize the "Gift Recipient" template on the Memberships > Settings > Email Templates page in the WordPress admin.', 'pmpro-gift-levels' ); ?></p>
		 				</td>
		 			</tr>
					<tr class="pmprogl_gift_level_toggle_setting" <?php if( ! $enabled ) {?>style="display: none;"<?php } ?>>
						<th scope="row" valign="top"><?php esc_html_e( 'Gift Membership Expires', 'pmpro-gift-levels' ); ?></th>
						<td>
							<input id="pmprogl_gift_expires" name="pmprogl_gift_expires" type="checkbox" value="yes" <?php if ( $expiration_number ) { echo "checked='checked'"; } ?>/>
							<label for="pmprogl_gift_expires"><?php esc_html_e( 'Check this to set an expiration period for the gifted membership level.', 'pmpro_gift_levels' ); ?></label>
							<p class="description"><?php esc_html_e( 'If you do not set an expiration period, the gift recipient\'s membership will never expire.', 'pmpro-gift-levels' ); ?></p>
						</td>
					</tr>
					<tr id="pmprogl_period_tr" <?php if( ! $enabled || empty( $expiration_number ) ) {?>style="display: none;"<?php } ?>>
						<th scope="row" valign="top"><label><?php esc_html_e( 'Gift Expiration Period', 'pmpro-gift-levels' ); ?></label></th>
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
							<p class="description"><?php esc_html_e('Set the duration of membership access once the gift membership is redeemed.', 'pmpro-gift-levels' );?></p>
						</td>
					</tr>
				</tbody>
			</table>
		</div> <!-- end pmpro_section_inside -->
	</div> <!-- end pmpro_section -->
	<?php
}
/**
 * Load settings section using new hook on Edit Level screen.
 */
if ( defined( 'PMPRO_VERSION' ) && PMPRO_VERSION >= '2.9' ) {
	add_action( 'pmpro_membership_level_before_content_settings', 'pmprogl_membership_level_after_other_settings' );
} else {
	add_action( 'pmpro_membership_level_after_other_settings', 'pmprogl_membership_level_after_other_settings' );
}

function pmprogl_save_membership_level( $save_id ) {
	global $allowedposttags;
	$enabled              = empty( $_REQUEST['pmprogl_enabled_for_level'] ) ? 'no' : 'yes';
	$gift_level			  = empty( $_REQUEST['pmprogl_gift_level'] ) ? 0 : intval( $_REQUEST['pmprogl_gift_level'] );
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
	echo pmprogl_build_gift_code_list( $user->ID );
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
