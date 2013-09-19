<?php
/**
 * Name: HTML widget
 * Description: Creates a widget to place html (or plain text) in aside (right or left)
 * Version: 0.1
 * Author: tazman@tazmandevil.info
 * Author: tony baldwin
 */

function htmlwidget_load() {
	register_hook('channel_mod_init', 'addon/htmlwidget/htmlwidget.php', 'htmlwidget_channel_mod_init');
	register_hook('network_mod_init', 'addon/htmlwidget/htmlwidget.php', 'htmlwidget_network_mod_init');
	register_hook('feature_settings', 'addon/htmlwidget/htmlwidget.php', 'htmlwidget_settings');
	register_hook('feature_settings_post', 'addon/htmlwidget/htmlwidget.php', 'htmlwidget_settings_post');

}

function htmlwidget_unload() {
	unregister_hook('channel_mod_init', 'addon/htmlwidget/htmlwidget.php', 'htmlwidget_channel_mod_init');
	unregister_hook('network_mod_init', 'addon/htmlwidget/htmlwidget.php', 'htmlwidget_network_mod_init');
	unregister_hook('feature_settings', 'addon/htmlwidget/htmlwidget.php', 'htmlwidget_settings');
	unregister_hook('feature_settings_post', 'addon/htmlwidget/htmlwidget.php', 'htmlwidget_settings_post');

}


function htmlwidget_channel_mod_init(&$a,&$b) {


    if(! intval(get_pconfig(local_user(),'htmlwidget','htmlwidget_enable')))
        return;
	logger('htmlchan widget invoked');
	$a = get_app();
	$title = "htmlwidget";

	logger('htmlchaninvoked2');

    $a->page['htmlhead'] .= '<link rel="stylesheet"  type="text/css" href="' . $a->get_baseurl() . '/addon/htmlwidget/htmlwidget.css' . '" media="all" />' . "\r\n";

    $htmlwidget_content = get_pconfig(local_user(), 'htmlwidget', 'htmlwidget_content');
    $htmlwidget = '<div id="htmlwidget_channel" class="widget">';

    $htmlwidget .= "$htmlwidget_content";

    $htmlwidget .= '</div><div class="clear"></div>';

    if (! intval(get_pconfig(local_user(), 'htmlwidget', 'htmlwidget_right'))) {
	    $a->set_widget($title,$htmlwidget,$location = 'aside');
    }	else {
	    $a->set_widget($title,$htmlwidget,$location = 'right_aside');
    }

}

function htmlwidget_network_mod_init(&$a,&$b) {

    if(! intval(get_pconfig(local_user(),'htmlwidget','htmlwidget_enable')))
        return;
    logger('htmlnetinvoked');

    $a->page['htmlhead'] .= '<link rel="stylesheet"  type="text/css" href="' . $a->get_baseurl() . '/addon/htmlwidget/htmlwidget.css' . '" media="all" />' . "\r\n";

    $htmlwidget_content = get_pconfig(local_user(), 'htmlwidget', 'htmlwidget_content');
    $htmlwidget = '<div id="htmlwidget_network" class="widget">';

    $htmlwidget .= "$htmlwidget_content";

    $htmlwidget .= '</div><div class="clear"></div>';

    if (! intval(get_pconfig(local_user(), 'htmlwidget', 'htmlwidget_right'))) {
	    $a->page['aside'] = $htmlwidget.$a->page['aside'];
    }	else {
	    $a->page['right_aside'] = $htmlwidget.$a->page['right_aside'];
    }

}

function htmlwidget_settings_post($a,$s) {
	if(! local_user() || (! x($_POST,'htmlwidget-settings-submit')))
		return;
	set_pconfig(local_user(),'htmlwidget','htmlwidget_content',trim($_POST['htmlwidget_content']));
	set_pconfig(local_user(),'htmlwidget','htmlwidget_right',intval($_POST['htmlwidget_right']));
	set_pconfig(local_user(),'htmlwidget','htmlwidget_enable',intval($_POST['htmlwidget_enable']));

	info( t('HTML widget settings updated.') . EOL);
}


function htmlwidget_settings(&$a,&$s) {

	if(! local_user())
		return;

	/* Add our stylesheet to the htmlwidget so we can make our settings look nice */

	$a->page['htmlhead'] .= '<link rel="stylesheet"  type="text/css" href="' . $a->get_baseurl() . '/addon/htmlwidget/htmlwidget.css' . '" media="all" />' . "\r\n";

	/* Get the current state of our config variable */

	$htmlwidget_content = get_pconfig(local_user(), 'htmlwidget', 'htmlwidget_content');
	$enable = intval(get_pconfig(local_user(),'htmlwidget','htmlwidget_enable'));
	$right = intval(get_pconfig(local_user(),'htmlwidget','htmlwidget_right'));
	$enable_checked = (($enable) ? ' checked="checked" ' : '');
	$right_checked = (($right) ? ' checked="checked" ' : '');
	
	
	/* Add some HTML to the existing form */

	$s .= '<div class="settings-block">';
	$s .= '<h3>' . t('HTML widget Settings') . '</h3>';
	$s .= '<div id="htmlwidget-settings-wrapper">';
	$s .= '<label id="htmlwidget-content-label" for="htmlwidget_content">' . t('HTML Widget Content: ') . '</label><br />';
	$s .= '<textarea id="htmlwidget-content" type="text" name="htmlwidget_content" >' . $htmlwidget_content . '</textarea>'; 
	$s .= '<div class="clear"></div>';
	$s .= '<label id="htmlwidget-right-label" for="htmlwidget_right">' . t('Place in Right Aside (left aside is default)') . '</label>';
	$s .= '<input id="htmlwidget-right" type="checkbox" name="htmlwidget_right" value="1" ' . $right_checked . '/><br />';
	$s .= '<label id="htmlwidget-enable-label" for="htmlwidget_enable">' . t('Enable HTML widget') . '</label>';
	$s .= '<input id="htmlwidget-enable" type="checkbox" name="htmlwidget_enable" value="1" ' . $enable_checked . '/>';
	$s .= '<div class="clear"></div>';

	$s .= '</div>';

	/* provide a submit button */

	$s .= '<div class="settings-submit-wrapper" ><input type="submit" name="htmlwidget-settings-submit" class="settings-submit" value="' . t('Submit') . '" /></div></div>';

}


