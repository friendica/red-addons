<?php
/**
 * Name: DebianArt.org widget
 * Description: Creates a DebianArt.org widget to place in aside (right or left)
 * Version: 0.1
 * Author: tazman@tazmandevil.info
 */

function debianart_load() {
//	register_hook('channel_mod_init', 'addon/debianart/debianart.php', 'debianart_channel_mod_init');
	register_hook('network_mod_init', 'addon/debianart/debianart.php', 'debianart_network_mod_init');
	register_hook('feature_settings', 'addon/debianart/debianart.php', 'debianart_settings');
	register_hook('feature_settings_post', 'addon/debianart/debianart.php', 'debianart_settings_post');

}

function debianart_unload() {
//	unregister_hook('channel_mod_init', 'addon/debianart/debianart.php', 'debianart_channel_mod_init');
	unregister_hook('network_mod_init', 'addon/debianart/debianart.php', 'debianart_network_mod_init');
	unregister_hook('feature_settings', 'addon/debianart/debianart.php', 'debianart_settings');
	unregister_hook('feature_settings_post', 'addon/debianart/debianart.php', 'debianart_settings_post');

}

/** function debianart_channel_mod_init(&$a,&$b) {

    if(! intval(get_pconfig(local_user(),'debianart','debianart_enable')))
        return;
	logger('debianart_chan: ' . print_r($debianart,true));
    $a->page['htmlhead'] .= '<link rel="stylesheet"  type="text/css" href="' . $a->get_baseurl() . '/addon/debianart/debianart.css' . '" media="all" />' . "\r\n";

    $debianart = '<div id="debianart_channel" class="widget">';
	$debianart .= '<div id="debianart-channel" class="widget">
	<div class="title tool">
	<h4>'.t("DebianArt.org").'</h4></div>';

    $debianart .= '<script src="http://l.yimg.com/a/i/us/pps/imagebadge_1.5.js">{"pipe_id":"f2c734d2e480b74b4dfedc88372b6029","_btype":"image"}</script>';

    $debianart .= '</div><div class="clear"></div>';

    if (! intval(get_pconfig(local_user(), 'debianart', 'debianart_right'))) {
	    $a->page['aside'] = $debianart.$a->page['aside'];
    }	else {
	    $a->page['right_aside'] = $debianart.$a->page['right_aside'];
    }

} **/

function debianart_network_mod_init(&$a,&$b) {
	if(! intval(get_pconfig(local_user(),'debianart','debianart_enable')))
        return;
	
	$a->page['htmlhead'] .= '<link rel="stylesheet"  type="text/css" href="' . $a->get_baseurl() . '/addon/debianart/debianart.css' . '" media="all" />' . "\r\n";

	$debianart = '<div id="debianart-network" class="widget">
	<div class="title tool">
	<h4>'.t("DebianArt.org").'</h4></div>';

	$debianart .= '<script type="text/javascript" src="http://l.yimg.com/a/i/us/pps/imagebadge_1.5.js">{"pipe_id":"f2c734d2e480b74b4dfedc88372b6029","_btype":"image"}</script>';

	$debianart .= '</div><div class="clear"></div>';
	
	if (! intval(get_pconfig(local_user(), 'debianart', 'debianart_right'))) {
		$a->page['aside'] = $debianart.$a->page['aside'];
	} else {
		$a->page['right_aside'] = $debianart.$a->page['right_aside'];
	}

}

function debianart_settings_post($a,$s) {
	if(! local_user() || (! x($_POST,'debianart-settings-submit')))
		return;
	set_pconfig(local_user(),'debianart','debianart_right',intval($_POST['debianart_right']));
	set_pconfig(local_user(),'debianart','debianart_enable',intval($_POST['debianart_enable']));

	info( t('DebianArt widget settings updated.') . EOL);
}


function debianart_settings(&$a,&$s) {

	if(! local_user())
		return;

	/* Add our stylesheet to the debianart so we can make our settings look nice */

	$a->page['htmlhead'] .= '<link rel="stylesheet"  type="text/css" href="' . $a->get_baseurl() . '/addon/debianart/debianart.css' . '" media="all" />' . "\r\n";

	/* Get the current state of our config variable */

	$enable = intval(get_pconfig(local_user(),'debianart','debianart_enable'));
	$enable_checked = (($enable) ? ' checked="checked" ' : '');
	$right = intval(get_pconfig(local_user(),'debianart','debianart_right'));
	$right_checked = (($right) ? ' checked="checked" ' : '');
	
	
	/* Add some HTML to the existing form */

	$s .= '<div class="settings-block">';
	$s .= '<h3>' . t('DebianArt widget Settings') . '</h3>';
	$s .= '<div id="debianart-settings-wrapper">';
	$s .= '<label id="debianart-right-label" for="debianart_right">' . t('Place in Right Aside (left aside is default)') . '</label>';
	$s .= '<input id="debianart-right" type="checkbox" name="debianart_right" value="1" ' . $right_checked . '/><br />';
	$s .= '<label id="debianart-enable-label" for="debianart_enable">' . t('Enable DebianArt widget') . '</label>';
	$s .= '<input id="debianart-enable" type="checkbox" name="debianart_enable" value="1" ' . $enable_checked . '/>';
	$s .= '<div class="clear"></div>';

	$s .= '</div>';

	/* provide a submit button */

	$s .= '<div class="settings-submit-wrapper" ><input type="submit" name="debianart-settings-submit" class="settings-submit" value="' . t('Submit') . '" /></div></div>';

}


