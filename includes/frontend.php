<?php

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