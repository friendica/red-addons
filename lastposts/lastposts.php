<?php
/**
 * Name: Last Posts widget
 * Description: Latest Posts aside widget, displays linked list of latest 5 posts
 * Version: 0.1
 * Author: tazman@tazmandevil.info
 * Author: tony baldwin
 */

include '../../.htconfig.php';

function lastposts_load() {
	register_hook('network_mod_init', 'addon/lastposts/lastposts.php', 'lastposts_network_mod_init');
	register_hook('channel_mod_init', 'addon/lastposts/lastposts.php', 'lastposts_channel_mod_init');
	register_hook('feature_settings', 'addon/lastposts/lastposts.php', 'lastposts_settings');
	register_hook('feature_settings_post', 'addon/lastposts/lastposts.php', 'lastposts_settings_post');

}

function lastposts_unload() {
	unregister_hook('network_mod_init', 'addon/lastposts/lastposts.php', 'lastposts_network_mod_init');
	unregister_hook('channel_mod_init', 'addon/lastposts/lastposts.php', 'lastposts_channel_mod_init');
	unregister_hook('feature_settings', 'addon/lastposts/lastposts.php', 'lastposts_settings');
	unregister_hook('feature_settings_post', 'addon/lastposts/lastposts.php', 'lastposts_settings_post');

}

function lastposts_channel_mod_init(&$a,&$b) {

    if(! intval(get_pconfig(local_user(),'lastposts','lastposts_enable')))
        return;

    $a->page['htmlhead'] .= '<link rel="stylesheet"  type="text/css" href="' . $a->get_baseurl() . '/addon/lastposts/lastposts.css' . '" media="all" />' . "\r\n";


$lastposts = '<div id="lastposts-channel" class="widget">
	<div class="title tool">
	<h4>'.t("Latest Posts").'</h4></div>';
$lastposts .= "<ul>";

$channel = $a->get_channel();
$channel_hash = $channel['channel_hash'];

mysql_connect("$db_host", "$db_user", "$db_pass") or die(mysql_error());
mysql_select_db("$db_data") or die(mysql_error());

$query  = "SELECT title, plink, created FROM item where author_xchan=$channel_hash LIMIT 0,5";

$result = mysql_query($query);
while($row = mysql_fetch_assoc($result)) {
	
	$plink = $row['plink'];
	$created = $row['created'];
	if (! $row['title'] ) {
		$title="$created";
            } else {
		$title=$row['title'];
	}
	$lastposts .= "<li><a href=\"$plink\">$title</a></li>";
}
	$lastposts .= "</ul>";
	mysql_close();

	$lastposts .= '</div><div class="clear"></div>';

	if (! intval(get_pconfig(local_user(), 'lastposts', 'lastposts_right'))) {
		$a->page['aside'] = $lastposts.$a->page['aside'];
	} else {
		$a->page['right_aside'] = $lastposts.$a->page['right_aside'];
    }

}

function lastposts_settings_post($a,$s) {
	if(! local_user() || (! x($_POST,'lastposts-settings-submit')))
		return;
	set_pconfig(local_user(),'lastposts','lastposts_right',intval($_POST['lastposts_right']));
	set_pconfig(local_user(),'lastposts','lastposts_enable',intval($_POST['lastposts_enable']));

	info( t('HTML widget settings updated.') . EOL);
}


function lastposts_settings(&$a,&$s) {

	if(! local_user())
		return;

	/* Add our stylesheet to the lastposts so we can make our settings look nice */

	$a->page['htmlhead'] .= '<link rel="stylesheet"  type="text/css" href="' . $a->get_baseurl() . '/addon/lastposts/lastposts.css' . '" media="all" />' . "\r\n";

	/* Get the current state of our config variable */

	$enable = intval(get_pconfig(local_user(),'lastposts','lastposts_enable'));
	$right = intval(get_pconfig(local_user(),'lastposts','lastposts_right'));
	$enable_checked = (($enable) ? ' checked="checked" ' : '');
	$right_checked = (($right) ? ' checked="checked" ' : '');
	
	
	/* Add some HTML to the existing form */

	$s .= '<div class="settings-block">';
	$s .= '<h3>' . t('Last Posts widget Settings') . '</h3>';
	$s .= '<div id="lastposts-settings-wrapper">';
	$s .= '<label id="lastposts-right-label" for="lastposts_right">' . t('Place in Right Aside (left aside is default)') . '</label>';
	$s .= '<input id="lastposts-right" type="checkbox" name="lastposts_right" value="1" ' . $right_checked . '/><br />';
	$s .= '<label id="lastposts-enable-label" for="lastposts_enable">' . t('Enable Last Posts widget') . '</label>';
	$s .= '<input id="lastposts-enable" type="checkbox" name="lastposts_enable" value="1" ' . $enable_checked . '/>';
	$s .= '<div class="clear"></div>';

	$s .= '</div>';

	/* provide a submit button */

	$s .= '<div class="settings-submit-wrapper" ><input type="submit" name="lastposts-settings-submit" class="settings-submit" value="' . t('Submit') . '" /></div></div>';

}

function lastposts_content($a,$s) {
}

