<?php


/**
 * Name: Rainbowtag
 * Description: Add some colour to tag clouds
 * Version: 1.0
 * Author: Mike Macgirvin
 */



function rainbowtag_load() {
	register_hook('construct_page', 'addon/rainbowtag/rainbowtag.php', 'rainbowtag_construct_page');
	register_hook('feature_settings', 'addon/rainbowtag/rainbowtag.php', 'rainbowtag_addon_settings');
	register_hook('feature_settings_post', 'addon/rainbowtag/rainbowtag.php', 'rainbowtag_addon_settings_post');

}

function rainbowtag_unload() {
	unregister_hook('construct_page', 'addon/rainbowtag/rainbowtag.php', 'rainbowtag_construct_page');
	unregister_hook('feature_settings', 'addon/rainbowtag/rainbowtag.php', 'rainbowtag_addon_settings');
	unregister_hook('feature_settings_post', 'addon/rainbowtag/rainbowtag.php', 'rainbowtag_addon_settings_post');

}



function rainbowtag_construct_page(&$a,&$b) {

	if(! $a->profile_uid)
		return;
	if(! intval(get_pconfig($a->profile_uid,'rainbowtag','enable')))
		return;


	$o = '<style>';
	$o .= '.tag1 { color: LawnGreen !important; }' . "\r\n";
	$o .= '.tag2 { color: DarkBlue !important; }' . "\r\n";
	$o .= '.tag3 { color: Red !important; }' . "\r\n";
	$o .= '.tag4 { color: DarkOrange !important; }' . "\r\n";
	$o .= '.tag5 { color: Gold !important; }' . "\r\n";
	$o .= '.tag6 { color: Teal !important; }' . "\r\n";
	$o .= '.tag7 { color: Sienna !important; }' . "\r\n";
	$o .= '.tag8 { color: DarkMagenta !important; }' . "\r\n";
	$o .= '.tag9 { color: GreenYellow !important; }' . "\r\n";
	$o .= '.tag10 { color: DeepPink !important; }' . "\r\n";
	$o .= '</style>';

	$a->page['htmlhead'] .= $o;

}

function rainbowtag_addon_settings(&$a,&$s) {


	if(! local_channel())
		return;

    /* Add our stylesheet to the page so we can make our settings look nice */

	head_add_css('/addon/rainbowtag/rainbowtag.css');

	$enable_checked = (intval(get_pconfig(local_channel(),'rainbowtag','enable')) ? ' checked="checked" ' : '');
		
    $s .= '<div class="settings-block">';
    $s .= '<button class="btn btn-default" data-target="#settings-rainbowtag-wrapper" data-toggle="collapse" type="button">' . t('Rainbowtag Settings') . '</button>';
    $s .= '<div id="settings-rainbowtag-wrapper" class="collapse well">';
    
    $s .= '<div id="rainbowtag-wrapper">';
    $s .= '<label id="rainbowtag-enable-label" for="rainbowtag-enable">' . t('Enable Rainbowtag') . ' </label>';
    $s .= '<input id="ranbowtag-enable" type="checkbox" name="rainbowtag-enable" value="1"' . $enable_checked . ' />';
	$s .= '<div class="clear"></div>';
    $s .= '</div><div class="clear"></div>';

    $s .= '<div class="settings-submit-wrapper" ><input type="submit" id="rainbowtag-submit" name="rainbowtag-submit" class="settings-submit" value="' . t('Submit Rainbowtag Settings') . '" /></div>';
	$s .= '</div></div>';

	return;

}

function rainbowtag_addon_settings_post(&$a,&$b) {

	if(! local_channel())
		return;

	if($_POST['rainbowtag-submit']) {
		$enable = ((x($_POST,'rainbowtag-enable')) ? intval($_POST['rainbowtag-enable']) : 0);
		set_pconfig(local_channel(),'rainbowtag','enable', $enable);
		info( t('Rainbowtag Settings saved.') . EOL);
	}
}
