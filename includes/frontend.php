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