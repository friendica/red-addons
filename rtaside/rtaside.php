<?php
/**
 * Name: Right Aside
 * Description: Creates a right aside 
 * Version: 0.1
 * Author: tazman@tazmandevil.info
 * Author: tony baldwin
 */

function rtaside_load() {
	register_hook('channel_mod_init', 'addon/rtaside/rtaside.php', 'rtaside_network_mod_init');
	register_hook('feature_settings', 'addon/rtaside/rtaside.php', 'rtaside_settings');
	register_hook('feature_settings_post', 'addon/rtaside/rtaside.php', 'rtaside_settings_post');

}

function rtaside_unload() {
	unregister_hook('channel_mod_init', 'addon/rtaside/rtaside.php', 'rtaside_network_mod_init');
	unregister_hook('feature_settings', 'addon/rtaside/rtaside.php', 'rtaside__settings');
	unregister_hook('feature_settings_post', 'addon/rtaside/rtaside.php', 'rtaside_settings_post');

}


function rtaside_network_mod_init(&$a,&$b) {

    if(! intval(get_pconfig(local_user(),'rtaside','rtaside_enable')))
        return;

    $a->page['htmlhead'] .= '<link rel="stylesheet"  type="text/css" href="' . $a->get_baseurl() . '/addon/rtaside/rtaside.css' . '" media="all" />' . "\r\n";

    // the getweather file does all the work here
    // the $rpt value is needed for location
    // which getweather uses to fetch the weather data for weather and temp
    $rtaside_content = get_pconfig(local_user(), 'rtaside', 'rtaside_content');
    $rtaside = '<div id="rtaside_channel" class="widget">
                <div class="title tool">
                <h4>'.t("Right Aside").'</h4></div>';

    $rtaside .= "$rtaside_content";

    $rtaside .= '</div><div class="clear"></div>';

    $a->page['right_aside'] = $rtaside.$a->page['right_aside'];

}


function rtaside_settings_post($a,$s) {
	if(! local_user() || (! x($_POST,'rtaside-settings-submit')))
		return;
	set_pconfig(local_user(),'rtaside','rtaside_content',trim($_POST['rtaside_loc']));
	set_pconfig(local_user(),'rtaside','rtaside_enable',intval($_POST['rtaside_enable']));

	info( t('Right Aside settings updated.') . EOL);
}


function rtaside_settings(&$a,&$s) {

	if(! local_user())
		return;

	/* Add our stylesheet to the rtaside so we can make our settings look nice */

	$a->page['htmlhead'] .= '<link rel="stylesheet"  type="text/css" href="' . $a->get_baseurl() . '/addon/rtaside/rtaside.css' . '" media="all" />' . "\r\n";

	/* Get the current state of our config variable */

	$rtaside_content = get_pconfig(local_user(), 'rtaside', 'rtaside_content');
	$enable = intval(get_pconfig(local_user(),'rtaside','rtaside_enable'));
	$enable_checked = (($enable) ? ' checked="checked" ' : '');
	
	
	/* Add some HTML to the existing form */

	$s .= '<div class="settings-block">';
	$s .= '<h3>' . t('Right Aside Settings') . '</h3>';
	$s .= '<div id="rtaside-settings-wrapper">';
	$s .= '<label id="rtaside-content-label" for="rtaside_content">' . t('Right Aside Content: ') . '</label>';
	$s .= '<textarea id="rtaside-content" type="text" name="rtaside_content">' . $rtaside_content . '</textarea>';
	$s .= '<div class="clear"></div>';
	$s .= '<label id="rtaside-enable-label" for="rtaside_enable">' . t('Enable Right Aside') . '</label>';
	$s .= '<input id="rtaside-enable" type="checkbox" name="rtaside_enable" value="1" ' . $enable_checked . '/>';
	$s .= '<div class="clear"></div>';

	$s .= '</div>';

	/* provide a submit button */

	$s .= '<div class="settings-submit-wrapper" ><input type="submit" name="rtaside-settings-submit" class="settings-submit" value="' . t('Submit') . '" /></div></div>';

}


