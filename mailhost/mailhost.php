<?php

/**
 * Name: mailhost
 * Description: Select one server to send email notifications when you have multiple clones
 * Version: 1.0
 * Author: Mike Macgirvin <mike@zothub.com>
 * 
 */

function mailhost_install() {
	register_hook('feature_settings', 'addon/mailhost/mailhost.php', 'mailhost_addon_settings');
	register_hook('feature_settings_post', 'addon/mailhost/mailhost.php', 'mailhost_addon_settings_post');
}


function mailhost_uninstall() {
	unregister_hook('feature_settings', 'addon/mailhost/mailhost.php', 'mailhost_addon_settings');
	unregister_hook('feature_settings_post', 'addon/mailhost/mailhost.php', 'mailhost_addon_settings_post');
}

function mailhost_addon_settings(&$a,&$s) {


	if(! local_channel())
		return;

    /* Add our stylesheet to the page so we can make our settings look nice */

	head_add_css('/addon/mailhost/mailhost.css');

	$mailhost = get_pconfig(local_channel(),'system','email_notify_host');
	if(! $mailhost)
		$mailhost = $a->get_hostname();
		
    $s .= '<div class="settings-block">';
    $s .= '<button class="btn btn-default" data-target="#settings-mailhost-wrapper" data-toggle="collapse" type="button">' . t('Mailhost Settings') . '</button>';
    $s .= '<div id="settings-mailhost-wrapper" class="collapse well">';
    
    $s .= '<div id="mailhost-wrapper">';
    $s .= '<p>' . t ('Allow only the following hub to send you email notifications.') . '</p>';
    $s .= '<label id="mailhost-label" for="mailhost-mailhost">' . t('Email notification hub (hostname)') . ' </label>';
    $s .= '<input id="mailhost-mailhost" type="text" name="mailhost-mailhost" value="' . $mailhost . '" />';
    $s .= '</div><div class="clear"></div>';

    $s .= '<div class="settings-submit-wrapper" ><input type="submit" id="mailhost-submit" name="mailhost-submit" class="settings-submit" value="' . t('Submit Mailhost Settings') . '" /></div>';
	$s .= '</div></div>';

	return;

}

function mailhost_addon_settings_post(&$a,&$b) {

	if(! local_channel())
		return;

	if($_POST['mailhost-submit']) {
		set_pconfig(local_channel(),'system','email_notify_host',trim($_POST['mailhost-mailhost']));
		info( t('MAILHOST Settings saved.') . EOL);
	}
}

