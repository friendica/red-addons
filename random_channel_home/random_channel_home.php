<?php
/**
 * Name: Random Channel Home
 * Description: Display a random channel from this server on the home page.
 * Version: 1.0
 * Author: Thomas Willingham <http://yamkote.com/channel/kogatosamgladen>
 */


function random_channel_home_install() {
	register_hook('home_content', 'addon/random_channel_home/random_channel_home.php', 'random_channel_home_home');
	logger("installed random_channel_home");
}

function random_channel_home_uninstall() {
	unregister_hook('home_content', 'addon/random_channel_home/random_channel_home.php', 'random_channel_home_home');
	logger("removed random_channel_home");
}

function random_channel_select(){
        $r = q("select channel_address from channel where channel_r_stream = 1 order by rand() limit 1");
if($r)
        return $r[0]['channel_address'];
return '';

}

function random_channel_home_home(&$a, &$o){
 
	$x = random_channel_select();
	$base = "/channel/";
	$clicky = ($base . $x);
	if($clicky)
		goaway(zid($clicky));
	goaway($a->get_baseurl() . '/profile');
}