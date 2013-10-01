<?php

/**
 * Name: Red-to-Red Connector (redred)
 * Description: Relay public postings to another Red channel
 * Version: 1.0
 */
 
/*
 *   Red to Red
 */


function redred_load() {
	//  we need some hooks, for the configuration and for sending tweets
	register_hook('feature_settings', 'addon/redred/redred.php', 'redred_settings'); 
	register_hook('feature_settings_post', 'addon/redred/redred.php', 'redred_settings_post');
	register_hook('notifier_normal', 'addon/redred/redred.php', 'redred_post_hook');
	register_hook('post_local', 'addon/redred/redred.php', 'redred_post_local');
	register_hook('jot_networks',    'addon/redred/redred.php', 'redred_jot_nets');
	logger("loaded redred");
}


function redred_unload() {
	unregister_hook('feature_settings', 'addon/redred/redred.php', 'redred_settings'); 
	unregister_hook('feature_settings_post', 'addon/redred/redred.php', 'redred_settings_post');
	unregister_hook('notifier_normal', 'addon/redred/redred.php', 'redred_post_hook');
	unregister_hook('post_local', 'addon/redred/redred.php', 'redred_post_local');
	unregister_hook('jot_networks',    'addon/redred/redred.php', 'redred_jot_nets');

}

function redred_jot_nets(&$a,&$b) {
	if(! local_user())
		return;

	$redred_post = get_pconfig(local_user(),'redred','post');
	if(intval($redred_post) == 1) {
		$redred_defpost = get_pconfig(local_user(),'redred','post_by_default');
		$selected = ((intval($redred_defpost) == 1) ? ' checked="checked" ' : '');
		$b .= '<div class="profile-jot-net"><input type="checkbox" name="redred_enable"' . $selected . ' value="1" /> ' 
			. t('Post to Red') . '</div>';
	}
}

function redred_settings_post ($a,$post) {
	if(! local_user())
		return;
	// don't check redred settings if redred submit button is not clicked
	if (! x($_POST,'redred_submit')) 
		return;

	$channel = $a->get_channel();
	// Don't let somebody post to their self channel. Since we aren't passing message-id this would be very very bad.

	if(! trim($_POST['redred_channel'])) {
		notice( t('Channel is required.') . EOL);
		return;
	}

	if($channel['channel_address'] === trim($_POST['redred_channel'])) {
		notice( t('Invalid channel.') . EOL);
		return;
	}

 

	
	set_pconfig(local_user(), 'redred', 'baseapi',         trim($_POST['redred_baseapi']));
	set_pconfig(local_user(), 'redred', 'username',        trim($_POST['redred_username']));
	set_pconfig(local_user(), 'redred', 'password',        trim($_POST['redred_password']));
	set_pconfig(local_user(), 'redred', 'channel',         trim($_POST['redred_channel']));
	set_pconfig(local_user(), 'redred', 'post',            intval($_POST['redred_enable']));
	set_pconfig(local_user(), 'redred', 'post_by_default', intval($_POST['redred_default']));
        info( t('redred Settings saved.') . EOL);

}

function redred_settings(&$a,&$s) {
	if(! local_user())
		return;
	head_add_css('/addon/redred/redred.css');

	$api     = get_pconfig(local_user(), 'redred', 'baseapi');
	$username    = get_pconfig(local_user(), 'redred', 'username' );
	$password = get_pconfig(local_user(), 'redred', 'password' );
	$channel = get_pconfig(local_user(), 'redred', 'channel' );
	$enabled = get_pconfig(local_user(), 'redred', 'post');
	$checked = (($enabled) ? ' checked="checked" ' : '');
	$defenabled = get_pconfig(local_user(),'redred','post_by_default');
	$defchecked = (($defenabled) ? ' checked="checked" ' : '');

	$s .= '<div class="settings-block">';
	$s .= '<h3>'. t('Red to Red (redred) Post Settings').'</h3>';
	$s .= '<label id="redred-enable-label" for="redred-checkbox">'. t('Allow posting to Red Channel') .'</label>';
	$s .= '<input id="redred-checkbox" type="checkbox" name="redred_enable" value="1" ' . $checked . '/>';
	$s .= '<div class="clear"></div>';
	$s .= '<label id="redred-default-label" for="redred-default">'. t('Send public postings to Red by default') .'</label>';
	$s .= '<input id="redred-default" type="checkbox" name="redred_default" value="1" ' . $defchecked . '/>';
	$s .= '<div class="clear"></div>';    
	$s .= '<label id="redred-baseapi-label" for="redred_baseapi">'. t('Red API Path (https://{sitename}/api)') .'</label>';
	$s .= '<input id="redred-baseapi" type="text" name="redred_baseapi" value="' . $api . '" size="35" />';
	$s .= '<div class="clear"></div>';
	$s .= '<label id="redred-username-label" for="redred_username">'. t('Red login name (email)') .'</label>';
	$s .= '<input id="redred-username" type="text" name="redred_username" size="35" value="' . $username . '" />';
	$s .= '<div class="clear"></div>';
	$s .= '<label id="redred-channel-label" for="redred_channel">'. t('Red channel (nick)name') .'</label>';
	$s .= '<input id="redred-channel" type="text" name="redred_channel" size="35" value="' . $channel . '" />';
	$s .= '<div class="clear"></div>';
	$s .= '<label id="redred-password-label" for="redred_password">'. t('Red password') .'</label>';
	$s .= '<input id="redred-password" type="password" name="redred_password" size="35" value="' . $password . '" />';
	$s .= '<div class="clear"></div>';
	$s .= '<div class="settings-submit-wrapper" ><input type="submit" name="redred_submit" class="settings-submit" value="' . t('Submit') . '" /></div>';
	$s .= '</div>';
}


function redred_post_local(&$a,&$b) {
	if($b['created'] != $b['edited'])
		return;

	if(! perm_is_allowed($b['uid'],'','view_stream'))
		return;

	if((local_user()) && (local_user() == $b['uid']) && (! $b['item_private'])) {

		$redred_post = get_pconfig(local_user(),'redred','post');
		$redred_enable = (($redred_post && x($_REQUEST,'redred_enable')) ? intval($_REQUEST['redred_enable']) : 0);

		// if API is used, default to the chosen settings
		if($_REQUEST['api_source'] && intval(get_pconfig(local_user(),'redred','post_by_default')))
			$redred_enable = 1;

       if(! $redred_enable)
            return;

       if(strlen($b['postopts']))
           $b['postopts'] .= ',';
       $b['postopts'] .= 'redred';
    }
}


function redred_post_hook(&$a,&$b) {

	/**
	 * Post to Red
	 */

	// for now, just top level posts.

	if($b['mid'] != $b['parent_mid'])
		return;

	if(($b['item_restrict'] & ITEM_DELETED) || $b['item_private'] || ($b['created'] !== $b['edited']))
		return;


	if(! perm_is_allowed($b['uid'],'','view_stream'))
		return;


	if(! strstr($b['postopts'],'redred'))
		return;

	logger('Red-to-Red post invoked');

	load_pconfig($b['uid'], 'redred');

	
	$api      = get_pconfig($b['uid'], 'redred', 'baseapi');
	if(substr($api,-1,1) != '/')
		$api .= '/';
	$username = get_pconfig($b['uid'], 'redred', 'username');
	$password = get_pconfig($b['uid'], 'redred', 'password');
	$channel  = get_pconfig($b['uid'], 'redred', 'channel');

	$msg = $b['body'];

	$postdata = array('status' => $b['body'], 'title' => $b['title'], 'channel' => $channel);

	if(strlen($b['body'])) {
		$ret = z_post_url($api . 'statuses/update', $postdata, 0, array('http_auth' => $username . ':' . $password));
		if($ret['success'])
			logger('redred: returns: ' . print_r($ret['body'],true));
		else
			logger('redred: z_post_url failed: ' . print_r($ret['debug'],true));
	}
}

