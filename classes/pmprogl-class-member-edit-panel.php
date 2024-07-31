<?php

class PMProgl_Member_Edit_Panel extends PMPro_Member_Edit_Panel {
	/**
	 * Set up the panel.
	 */
	public function __construct() {
		$this->slug = 'pmprogl';
		$this->title = esc_html__( 'Purchased Gift Codes', 'pmpro-gift-levels' );
	}

	/**
	 * Display the panel contents.
	 */
	protected function display_panel_contents() {
		// Get the user being edited.
		$user = self::get_user();
        echo pmprogl_build_gift_code_list( $user->ID );
	}
}
