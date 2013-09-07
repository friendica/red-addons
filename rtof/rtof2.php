<?php

/**
 * Name: Red-to-Friendica Connector (rtof2)
 * Description: Relay public postings to a connected Friendica account
 * Version: 1.0
 */
 
/*
 *   Red to Friendica 
 */


function rtof2_load() {
	//  we need some hooks, for the configuration and for sending tweets
	register_hook('feature_settings', 'addon/rtof2/rtof2.php', 'rtof2_settings'); 
	register_hook('feature_settings_post', 'addon/rtof2/rtof2.php', 'rtof2_settings_post');
	register_hook('notifier_normal', 'addon/rtof2/rtof2.php', 'rtof2_post_hook');
	register_hook('post_local', 'addon/rtof2/rtof2.php', 'rtof2_post_local');
	register_hook('jot_networks',    'addon/rtof2/rtof2.php', 'rtof2_jot_nets');
	logger("loaded rtof2");
}


function rtof2_unload() {
	unregister_hook('feature_settings', 'addon/rtof2/rtof2.php', 'rtof2_settings'); 
	unregister_hook('feature_settings_post', 'addon/rtof2/rtof2.php', 'rtof2_settings_post');
	unregister_hook('notifier_normal', 'addon/rtof2/rtof2.php', 'rtof2_post_hook');
	unregister_hook('post_local', 'addon/rtof2/rtof2.php', 'rtof2_post_local');
	unregister_hook('jot_networks',    'addon/rtof2/rtof2.php', 'rtof2_jot_nets');

}

function rtof2_jot_nets(&$a,&$b) {
	if(! local_user())
		return;

	$rtof2_post = get_pconfig(local_user(),'rtof2','post');
	if(intval($rtof2_post) == 1) {
		$rtof2_defpost = get_pconfig(local_user(),'rtof2','post_by_default');
		$selected = ((intval($rtof2_defpost) == 1) ? ' checked="checked" ' : '');
		$b .= '<div class="profile-jot-net"><input type="checkbox" name="rtof2_enable"' . $selected . ' value="1" /> ' 
			. t('Post to Friendica') . '</div>';
	}
}

function rtof2_settings_post ($a,$post) {
	if(! local_user())
		return;
	// don't check rtof2 settings if rtof2 submit button is not clicked
	if (! x($_POST,'rtof2_submit')) 
		return;
	
	set_pconfig(local_user(), 'rtof2', 'baseapi',         trim($_POST['rtof2_baseapi']));
	set_pconfig(local_user(), 'rtof2', 'username',        trim($_POST['rtof2_username']));
	set_pconfig(local_user(), 'rtof2', 'password',        trim($_POST['rtof2_password']));
	set_pconfig(local_user(), 'rtof2', 'post',            intval($_POST['rtof2_enable']));
	set_pconfig(local_user(), 'rtof2', 'post_by_default', intval($_POST['rtof2_default']));

}

function rtof2_settings(&$a,&$s) {
	if(! local_user())
		return;
	head_add_css('/addon/rtof2/rtof2.css');

	$api     = get_pconfig(local_user(), 'rtof2', 'baseapi');
	$username    = get_pconfig(local_user(), 'rtof2', 'username' );
	$password = get_pconfig(local_user(), 'rtof2', 'password' );
	$enabled = get_pconfig(local_user(), 'rtof2', 'post');
	$checked = (($enabled) ? ' checked="checked" ' : '');
	$defenabled = get_pconfig(local_user(),'rtof2','post_by_default');
	$defchecked = (($defenabled) ? ' checked="checked" ' : '');

	$s .= '<div class="settings-block">';
	$s .= '<h3>'. t('Red to Friendica (rtof2) Post Settings').'</h3>';
	$s .= '<label id="rtof2-enable-label" for="rtof2-checkbox">'. t('Allow posting to Friendica2') .'</label>';
	$s .= '<input id="rtof2-checkbox" type="checkbox" name="rtof2_enable" value="1" ' . $checked . '/>';
	$s .= '<div class="clear"></div>';
	$s .= '<label id="rtof2-default-label" for="rtof2-default">'. t('Send public postings to Friendica2 by default') .'</label>';
	$s .= '<input id="rtof2-default" type="checkbox" name="rtof2_default" value="1" ' . $defchecked . '/>';
	$s .= '<div class="clear"></div>';    
	$s .= '<label id="rtof2-baseapi-label" for="rtof2_baseapi">'. t('Friendica2 API Path (https://{sitename}/api)') .'</label>';
	$s .= '<input id="rtof2-baseapi" type="text" name="rtof2_baseapi" value="' . $api . '" size="35" />';
	$s .= '<div class="clear"></div>';
	$s .= '<label id="rtof2-username-label" for="rtof2_username">'. t('Friendica login name') .'</label>';
	$s .= '<input id="rtof2-username" type="text" name="rtof2_username" size="35" value="' . $username . '" />';
	$s .= '<div class="clear"></div>';
	$s .= '<label id="rtof2-password-label" for="rtof2_password">'. t('Friendica2 password') .'</label>';
	$s .= '<input id="rtof2-password" type="password" name="rtof2_password" size="35" value="' . $password . '" />';
	$s .= '<div class="clear"></div>';
	$s .= '<div class="settings-submit-wrapper" ><input type="submit" name="rtof2_submit" class="settings-submit" value="' . t('Submit') . '" /></div>';
	$s .= '</div>';
}


function rtof2_post_local(&$a,&$b) {
	if($b['created'] != $b['edited'])
		return;

	if((local_user()) && (local_user() == $b['uid']) && (! $b['item_private'])) {

		$rtof2_post = get_pconfig(local_user(),'rtof2','post');
		$rtof2_enable = (($rtof2_post && x($_REQUEST,'rtof2_enable')) ? intval($_REQUEST['rtof2_enable']) : 0);

		// if API is used, default to the chosen settings
		if($_REQUEST['api_source'] && intval(get_pconfig(local_user(),'rtof2','post_by_default')))
			$rtof2_enable = 1;

       if(! $rtof2_enable)
            return;

       if(strlen($b['postopts']))
           $b['postopts'] .= ',';
       $b['postopts'] .= 'rtof2';
    }
}


function rtof2_post_hook(&$a,&$b) {

	/**
	 * Post to Friendica
	 */

	// for now, just top level posts.

	if($b['mid'] != $b['parent_mid'])
		return;

	if(($b['item_flags'] & ITEM_DELETED) || $b['item_private'] || ($b['created'] !== $b['edited']))
		return;

	if(! strstr($b['postopts'],'rtof2'))
		return;

	logger('Red-to-Friendica post invoked');

	load_pconfig($b['uid'], 'rtof2');

	
	$api      = get_pconfig($b['uid'], 'rtof2', 'baseapi');
	if(substr($api,-1,1) != '/')
		$api .= '/';
	$username = get_pconfig($b['uid'], 'rtof2', 'username');
	$password = get_pconfig($b['uid'], 'rtof2', 'password');

	$msg = $b['body'];

	$postdata = array('status' => $b['body'], 'title' => $b['title'], 'message_id' => $b['mid'], 'source' => 'The Red Matrix');

	if(strlen($b['body'])) {
		$ret = z_post_url($api . 'statuses/update', $postdata, 0, array('http_auth' => $username . ':' . $password));
		if($ret['success'])
			logger('rtof2: returns: ' . print_r($ret['body'],true));
		else
			logger('rtof2: z_post_url failed');
	}
}

