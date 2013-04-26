<?php

/**
 * Name: Red-to-Friendica Connector (rtof)
 * Description: Relay public postings to a connected Friendica account
 * Version: 1.0
 */
 
/*
 *   Red to Friendica 
 */


function rtof_install() {
	//  we need some hooks, for the configuration and for sending tweets
	register_hook('feature_settings', 'addon/rtof/rtof.php', 'rtof_settings'); 
	register_hook('feature_settings_post', 'addon/rtof/rtof.php', 'rtof_settings_post');
	register_hook('notifier_normal', 'addon/rtof/rtof.php', 'rtof_post_hook');
	register_hook('post_local', 'addon/rtof/rtof.php', 'rtof_post_local');
	register_hook('jot_networks',    'addon/rtof/rtof.php', 'rtof_jot_nets');
	logger("installed rtof");
}


function rtof_uninstall() {
	unregister_hook('feature_settings', 'addon/rtof/rtof.php', 'rtof_settings'); 
	unregister_hook('feature_settings_post', 'addon/rtof/rtof.php', 'rtof_settings_post');
	unregister_hook('notifier_normal', 'addon/rtof/rtof.php', 'rtof_post_hook');
	unregister_hook('post_local', 'addon/rtof/rtof.php', 'rtof_post_local');
	unregister_hook('jot_networks',    'addon/rtof/rtof.php', 'rtof_jot_nets');

}

function rtof_jot_nets(&$a,&$b) {
	if(! local_user())
		return;

	$rtof_post = get_pconfig(local_user(),'rtof','post');
	if(intval($rtof_post) == 1) {
		$rtof_defpost = get_pconfig(local_user(),'rtof','post_by_default');
		$selected = ((intval($rtof_defpost) == 1) ? ' checked="checked" ' : '');
		$b .= '<div class="profile-jot-net"><input type="checkbox" name="rtof_enable"' . $selected . ' value="1" /> ' 
			. t('Post to Friendica') . '</div>';
	}
}

function rtof_settings_post ($a,$post) {
	if(! local_user())
		return;
	// don't check rtof settings if rtof submit button is not clicked
	if (! x($_POST,'rtof_submit')) 
		return;
	
	set_pconfig(local_user(), 'rtof', 'baseapi',         trim($_POST['rtof_baseapi']));
	set_pconfig(local_user(), 'rtof', 'username',        trim($_POST['rtof_username']));
	set_pconfig(local_user(), 'rtof', 'password',        trim($_POST['rtof_password']));
	set_pconfig(local_user(), 'rtof', 'post',            intval($_POST['rtof_enable']));
	set_pconfig(local_user(), 'rtof', 'post_by_default', intval($_POST['rtof_default']));

}

function rtof_settings(&$a,&$s) {
	if(! local_user())
		return;
	head_add_css('/addon/rtof/rtof.css');

	$api     = get_pconfig(local_user(), 'rtof', 'baseapi');
	$username    = get_pconfig(local_user(), 'rtof', 'username' );
	$password = get_pconfig(local_user(), 'rtof', 'password' );
	$enabled = get_pconfig(local_user(), 'rtof', 'post');
	$checked = (($enabled) ? ' checked="checked" ' : '');
	$defenabled = get_pconfig(local_user(),'rtof','post_by_default');
	$defchecked = (($defenabled) ? ' checked="checked" ' : '');

	$s .= '<div class="settings-block">';
	$s .= '<h3>'. t('Red to Friendica (rtof) Post Settings').'</h3>';
	$s .= '<label id="rtof-enable-label" for="rtof-checkbox">'. t('Allow posting to Friendica') .'</label>';
	$s .= '<input id="rtof-checkbox" type="checkbox" name="rtof_enable" value="1" ' . $checked . '/>';
	$s .= '<div class="clear"></div>';
	$s .= '<label id="rtof-default-label" for="rtof-default">'. t('Send public postings to Friendica by default') .'</label>';
	$s .= '<input id="rtof-default" type="checkbox" name="rtof_default" value="1" ' . $defchecked . '/>';
	$s .= '<div class="clear"></div>';    
	$s .= '<label id="rtof-baseapi-label" for="rtof_baseapi">'. t('Friendica API Path (https://{sitename}/api)') .'</label>';
	$s .= '<input id="rtof-baseapi" type="text" name="rtof_baseapi" value="' . $api . '" size="35" />';
	$s .= '<div class="clear"></div>';
	$s .= '<label id="rtof-username-label" for="rtof_username">'. t('Friendica login name') .'</label>';
	$s .= '<input id="rtof-username" type="text" name="rtof_username" size="35" value="' . $username . '" />';
	$s .= '<div class="clear"></div>';
	$s .= '<label id="rtof-password-label" for="rtof_password">'. t('Friendica password') .'</label>';
	$s .= '<input id="rtof-password" type="password" name="rtof_password" size="35" value="' . $password . '" />';
	$s .= '<div class="clear"></div>';
	$s .= '<div class="settings-submit-wrapper" ><input type="submit" name="rtof_submit" class="settings-submit" value="' . t('Submit') . '" /></div>';
	$s .= '</div>';
}


function rtof_post_local(&$a,&$b) {
	if($b['created'] != $b['edited'])
		return;

	if((local_user()) && (local_user() == $b['uid']) && (! $b['item_private'])) {

		$rtof_post = get_pconfig(local_user(),'rtof','post');
		$rtof_enable = (($rtof_post && x($_REQUEST,'rtof_enable')) ? intval($_REQUEST['rtof_enable']) : 0);

		// if API is used, default to the chosen settings
		if($_REQUEST['api_source'] && intval(get_pconfig(local_user(),'rtof','post_by_default')))
			$rtof_enable = 1;

       if(! $rtof_enable)
            return;

       if(strlen($b['postopts']))
           $b['postopts'] .= ',';
       $b['postopts'] .= 'rtof';
    }
}


function rtof_post_hook(&$a,&$b) {

	/**
	 * Post to Friendica
	 */

	// for now, just top level posts.

	if($b['mid'] != $b['parent_mid'])
		return;

	if(($b['item_flags'] & ITEM_DELETED) || $b['item_private'] || ($b['created'] !== $b['edited']))
		return;

	if(! strstr($b['postopts'],'rtof'))
		return;

	logger('Red-to-Friendica post invoked');

	load_pconfig($b['uid'], 'rtof');

	
	$api      = get_pconfig($b['uid'], 'rtof', 'baseapi');
	if(substr($api,-1,1) != '/')
		$api .= '/';
	$username = get_pconfig($b['uid'], 'rtof', 'username');
	$password = get_pconfig($b['uid'], 'rtof', 'password');

	$msg = $b['body'];

	$postdata = array('status' => $b['body'], 'title' => $b['title'], 'source' => 'Red');

	if(strlen($b['body'])) {
		$ret = z_post_url($api . 'statuses/update', $postdata, 0, array('http_auth' => $username . ':' . $password));
		if($ret['success'])
			logger('rtof: returns: ' . print_r($ret['body'],true));
		else
			logger('rtof: z_post_url failed');
	}
}

