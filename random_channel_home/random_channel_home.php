<?php
/**
 * Name: Random Channel Home
 * Description: Display a random channel from this server on the home page.
 * Version: 1.0
 * Author: Thomas Willingham <http://yamkote.com/channel/kogatosamgladen>
 */


function random_channel_home_load() {
	register_hook('home_content', 'addon/random_channel_home/random_channel_home.php', 'random_channel_home_home');
	logger("loaded random_channel_home");
}

function random_channel_home_unload() {
	unregister_hook('home_content', 'addon/random_channel_home/random_channel_home.php', 'random_channel_home_home');
	logger("removed random_channel_home");
}

function random_channel_select(){
        $r = q("select channel_address from channel where channel_r_stream = 1 and channel_address != 'sys' order by rand() limit 1");
if($r)
        return $r[0]['channel_address'];
return '';

}

function random_channel_home_home(&$a, &$o){
 
	$x = random_channel_select();
	$clicky = (z_root() . '/channel/' . $x);

	if($clicky)
		goaway(zid($clicky));
	else 
		return;
}
