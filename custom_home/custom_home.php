<?php
/**
 * Name: Custom Home
 * Description: Set a custom home page.  Display a random channel from this server on the home page.
 * Version: 1.0  
 * Author: Thomas Willingham <zot:beardyunixer@beardyunixer.com>
 */


function custom_home_load() {
    register_hook('home_content', 'addon/custom_home/custom_home.php', 'custom_home_home');
    logger("loaded random_channel_home");
}
 
function custom_home_unload() {
    unregister_hook('home_content', 'addon/custom_home/custom_home.php', 'custom_home_home');
    logger("removed random_channel_home");
}
 
function custom_home_home(&$a, &$o){
    
    $x = get_config('system','custom_home');
    if($x) { 
	$x = z_root() . '/' . $x;
        goaway(zid($x));
	}
    else 
        return $o;
}

