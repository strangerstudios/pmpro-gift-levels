<?php

/*
 * This is a sample $pmprogl_gift_levels array to set up a website
 * such that purchasing level 5 generates a discount code that lets
 * another user redeem a level 6 membership that expires in 1 year.
 *  
 * - Each key is the id of the "purchase level"
 * - Each value is an array of values for the discount code created.
 * - Add this to your active theme's functions.php or a custom plugin.
 */
global $pmprogl_gift_levels;
$pmprogl_gift_levels = array(
    5 => array(
        'level_id' => 6,
        'initial_payment' => '', 
        'billing_amount' => '', 
        'cycle_number' => '', 
        'cycle_period' => '', 
        'billing_limit' => '', 
        'trial_amount' => '', 
        'trial_limit' => '', 
        'expiration_number' => 1, 
        'expiration_period' => 'Year'
    )
);

/*
 * This is a sample $pmprogl_require_gift_code array to require
 * a discount code to be used while signing up for level 6.
 */
global $pmprogl_require_gift_code;
$pmprogl_require_gift_code = array(6);
