<?php

/**
 * Name: Diaspora Post Connector
 * Description: Post to Diaspora
 * Version: 0.1
 * Author: Michael Vogel <heluecht@pirati.ca>
 */

function diaspora_load() {
	register_hook('post_local',           'addon/diaspora/diaspora.php', 'diaspora_post_local');
	register_hook('notifier_normal',      'addon/diaspora/diaspora.php', 'diaspora_send');
	register_hook('jot_networks',         'addon/diaspora/diaspora.php', 'diaspora_jot_nets');
	register_hook('feature_settings',      'addon/diaspora/diaspora.php', 'diaspora_settings');
	register_hook('feature_settings_post', 'addon/diaspora/diaspora.php', 'diaspora_settings_post');
//	register_hook('queue_predeliver', 'addon/diaspora/diaspora.php', 'diaspora_queue_hook');
}
function diaspora_unload() {
	unregister_hook('post_local',       'addon/diaspora/diaspora.php', 'diaspora_post_local');
	unregister_hook('notifier_normal',  'addon/diaspora/diaspora.php', 'diaspora_send');
	unregister_hook('jot_networks',     'addon/diaspora/diaspora.php', 'diaspora_jot_nets');
	unregister_hook('feature_settings',      'addon/diaspora/diaspora.php', 'diaspora_settings');
	unregister_hook('feature_settings_post', 'addon/diaspora/diaspora.php', 'diaspora_settings_post');
//	unregister_hook('queue_predeliver', 'addon/diaspora/diaspora.php', 'diaspora_queue_hook');
}


function diaspora_jot_nets(&$a,&$b) {
    if((! local_channel()) || (! perm_is_allowed(local_channel(),'','view_stream')))
        return;

    $diaspora_post = get_pconfig(local_channel(),'diaspora','post');
    if(intval($diaspora_post) == 1) {
        $diaspora_defpost = get_pconfig(local_channel(),'diaspora','post_by_default');
        $selected = ((intval($diaspora_defpost) == 1) ? ' checked="checked" ' : '');
        $b .= '<div class="profile-jot-net"><input type="checkbox" name="diaspora_enable"' . $selected . ' value="1" /> <img src="addon/diaspora/diaspora.png" /> ' . t('Post to Diaspora') . '</div>';
    }
}

function diaspora_queue_hook(&$a,&$b) {
	$hostname = $a->get_hostname();

	$qi = q("SELECT * FROM `queue` WHERE `network` = '%s'",
		dbesc(NETWORK_DIASPORA2)
	);
	if(! count($qi))
		return;

	require_once('include/queue_fn.php');

	foreach($qi as $x) {
		if($x['network'] !== NETWORK_DIASPORA2)
			continue;

		logger('diaspora_queue: run');

		$r = q("SELECT `user`.* FROM `user` LEFT JOIN `contact` on `contact`.`uid` = `user`.`uid`
			WHERE `contact`.`self` = 1 AND `contact`.`id` = %d LIMIT 1",
			intval($x['cid'])
		);
		if(! count($r))
			continue;

		$userdata = $r[0];

		$diaspora_username = get_pconfig($userdata['uid'],'diaspora','diaspora_username');
		$diaspora_password = get_pconfig($userdata['uid'],'diaspora','diaspora_password');
		$diaspora_url = get_pconfig($userdata['uid'],'diaspora','diaspora_url');

		$success = false;

		if($diaspora_url && $diaspora_username && $diaspora_password) {
			require_once("addon/diaspora/diasphp.php");

                        logger('diaspora_queue: able to post for user '.$diaspora_username);

			$z = unserialize($x['content']);

			$post = $z['post'];

			logger('diaspora_queue: post: '.$post, LOGGER_DATA);

			try {
				logger('diaspora_queue: prepare', LOGGER_DEBUG);
				$conn = new Diasphp($diaspora_url);
				logger('diaspora_queue: try to log in '.$diaspora_username, LOGGER_DEBUG);
				$conn->login($diaspora_username, $diaspora_password);
				logger('diaspora_queue: try to send '.$body, LOGGER_DEBUG);
				$conn->post($post, $hostname);

                                logger('diaspora_queue: send '.$userdata['uid'].' success', LOGGER_DEBUG);

                                $success = true;

                                remove_queue_item($x['id']);
			} catch (Exception $e) {
				logger("diaspora_queue: Send ".$userdata['uid']." failed: ".$e->getMessage(), LOGGER_DEBUG);
			}
		} else
			logger('diaspora_queue: send '.$userdata['uid'].' missing username or password', LOGGER_DEBUG);

		if (!$success) {
			logger('diaspora_queue: delayed');
			update_queue_time($x['id']);
		}
	}
}

function diaspora_settings(&$a,&$s) {

	if(! local_channel())
		return;

	/* Add our stylesheet to the page so we can make our settings look nice */

	//$a->page['htmlhead'] .= '<link rel="stylesheet"  type="text/css" href="' . $a->get_baseurl() . '/addon/diaspora/diaspora.css' . '" media="all" />' . "\r\n";

	/* Get the current state of our config variables */

	$enabled = get_pconfig(local_channel(),'diaspora','post');
	$checked = (($enabled) ? '1' : false);
	$css = (($enabled) ? '' : '-disabled');

	$def_enabled = get_pconfig(local_channel(),'diaspora','post_by_default');

	$def_checked = (($def_enabled) ? 1 : false);

	$diaspora_username = get_pconfig(local_channel(), 'diaspora', 'diaspora_username');
	$diaspora_password = get_pconfig(local_channel(), 'diaspora', 'diaspora_password');
	$diaspora_url = get_pconfig(local_channel(), 'diaspora', 'diaspora_url');

	$status = "";

	if ($diaspora_username AND $diaspora_password AND $diaspora_url) {
		try {
			require_once("addon/diaspora/diasphp.php");

			$conn = new Diasphp($diaspora_url);
			$conn->login($diaspora_username, $diaspora_password);
		} catch (Exception $e) {
			$status = t("Can't login to your Diaspora account. Please check username and password and ensure you used the complete address (including http...)");
		}
	}

	/* Add some HTML to the existing form */
	if ($status) {
		$sc .= '<div class="section-content-danger-wrapper">';
		$sc .= '<strong>' . $status . '</strong>';
		$sc .= '</div>';
	}

	$sc .= replace_macros(get_markup_template('field_checkbox.tpl'), array(
		'$field'	=> array('diaspora', t('Enable Diaspora Post Plugin'), $checked, '', array(t('No'),t('Yes'))),
	));

	$sc .= replace_macros(get_markup_template('field_input.tpl'), array(
		'$field'	=> array('diaspora_username', t('Diaspora username'), $diaspora_username, '')
	));

	$sc .= replace_macros(get_markup_template('field_password.tpl'), array(
		'$field'	=> array('diaspora_password', t('Diaspora password'), $diaspora_password, '')
	));

	$sc .= replace_macros(get_markup_template('field_input.tpl'), array(
		'$field'	=> array('diaspora_url', t('Diaspora site URL'), $diaspora_url, 'Example: https://joindiaspora.com')
	));

	$sc .= replace_macros(get_markup_template('field_checkbox.tpl'), array(
		'$field'	=> array('diaspora_bydefault', t('Post to Diaspora by default'), $def_checked, '', array(t('No'),t('Yes'))),
	));

	$s .= replace_macros(get_markup_template('generic_addon_settings.tpl'), array(
		'$addon' 	=> array('diaspora', '<img src="addon/diaspora/diaspora.png" style="width:auto; height:1em; margin:-3px 5px 0px 0px;">' . t('Diaspora Post Settings'), '', t('Submit')),
		'$content'	=> $sc
	));

	return;
}


function diaspora_settings_post(&$a,&$b) {

	if(x($_POST,'diaspora-submit')) {

		set_pconfig(local_channel(),'diaspora','post',intval($_POST['diaspora']));
		set_pconfig(local_channel(),'diaspora','post_by_default',intval($_POST['diaspora_bydefault']));
		set_pconfig(local_channel(),'diaspora','diaspora_username',trim($_POST['diaspora_username']));
		set_pconfig(local_channel(),'diaspora','diaspora_password',trim($_POST['diaspora_password']));
		set_pconfig(local_channel(),'diaspora','diaspora_url',trim($_POST['diaspora_url']));

	}

}

function diaspora_post_local(&$a,&$b) {

    if($b['created'] != $b['edited'])
        return;

    if(! perm_is_allowed($b['uid'],'','view_stream'))
        return;


	if((! local_channel()) || (local_channel() != $b['uid']))
		return;

	if($b['item_private'])
		return;

	$diaspora_post   = intval(get_pconfig(local_channel(),'diaspora','post'));

	$diaspora_enable = (($diaspora_post && x($_REQUEST,'diaspora_enable')) ? intval($_REQUEST['diaspora_enable']) : 0);

	if($_REQUEST['api_source'] && intval(get_pconfig(local_channel(),'diaspora','post_by_default')))
		$diaspora_enable = 1;

    if(! $diaspora_enable)
       return;

    if(strlen($b['postopts']))
       $b['postopts'] .= ',';
     $b['postopts'] .= 'diaspora';
}




function diaspora_send(&$a,&$b) {
	$hostname = 'redmatrix ' . '(' . $a->get_hostname() . ')';

	logger('diaspora_send: invoked',LOGGER_DEBUG);

    if($b['mid'] != $b['parent_mid'])
        return;

    if(($b['item_restrict'] & ITEM_DELETED) || $b['item_private'] || ($b['created'] !== $b['edited']))
        return;


    if(! perm_is_allowed($b['uid'],'','view_stream'))
        return;


	if(! strstr($b['postopts'],'diaspora'))
		return;


	logger('diaspora_send: prepare posting', LOGGER_DEBUG);

	$diaspora_username = get_pconfig($b['uid'],'diaspora','diaspora_username');
	$diaspora_password = get_pconfig($b['uid'],'diaspora','diaspora_password');
	$diaspora_url = get_pconfig($b['uid'],'diaspora','diaspora_url');

	if($diaspora_url && $diaspora_username && $diaspora_password) {

		logger('diaspora_send: all values seem to be okay', LOGGER_DEBUG);

		require_once('include/bb2diaspora.php');
		$tag_arr = array();
		$tags = '';
		$x = preg_match_all('/\#\[(.*?)\](.*?)\[/',$b['tag'],$matches,PREG_SET_ORDER);

		if($x) {
			foreach($matches as $mtch) {
				$tag_arr[] = $mtch[2];
			}
		}
		if(count($tag_arr))
			$tags = implode(',',$tag_arr);

		$title = $b['title'];
		$body = $b['body'];
		// Insert a newline before and after a quote
		$body = str_ireplace("[quote", "\n\n[quote", $body);
		$body = str_ireplace("[/quote]", "[/quote]\n\n", $body);

		// strip bookmark indicators

		$body = preg_replace('/\#\^\[([zu])rl/i', '[$1rl', $body);

		$body = preg_replace('/\#\^http/i', 'http', $body);


		if(intval(get_pconfig($item['uid'],'system','prevent_tag_hijacking'))) {
			$new_tag	 = html_entity_decode('&#x22d5;',ENT_COMPAT,'UTF-8');
			$new_mention = html_entity_decode('&#xff20;',ENT_COMPAT,'UTF-8');

			// #-tags
			$body = preg_replace('/\#\[url/i', $new_tag . '[url', $body);
			$body = preg_replace('/\#\[zrl/i', $new_tag . '[zrl', $body);
			// @-mentions
			$body = preg_replace('/\@\!?\[url/i', $new_mention . '[url', $body);
			$body = preg_replace('/\@\!?\[zrl/i', $new_mention . '[zrl', $body);
		}

		// remove multiple newlines
		do {
			$oldbody = $body;
			$body = str_replace("\n\n\n", "\n\n", $body);
		} while ($oldbody != $body);

		// convert to markdown
		$body = bb2diaspora($body, false, true);

		// Adding the title
		if(strlen($title))
			$body = "## ".html_entity_decode($title)."\n\n".$body;

		require_once("addon/diaspora/diasphp.php");

		try {
			logger('diaspora_send: prepare', LOGGER_DEBUG);
			$conn = new Diasphp($diaspora_url);
			logger('diaspora_send: try to log in '.$diaspora_username, LOGGER_DEBUG);
			$conn->login($diaspora_username, $diaspora_password);
			logger('diaspora_send: try to send '.$body, LOGGER_DEBUG);

			//throw new Exception('Test');
			$conn->post($body, $hostname);

			logger('diaspora_send: success');
		} catch (Exception $e) {
			logger("diaspora_send: Error submitting the post: " . $e->getMessage());

//			logger('diaspora_send: requeueing '.$b['uid'], LOGGER_DEBUG);

//			$r = q("SELECT `id` FROM `contact` WHERE `uid` = %d AND `self`", $b['uid']);
//			if (count($r))
//				$a->contact = $r[0]["id"];

//			$s = serialize(array('url' => $url, 'item' => $b['id'], 'post' => $body));
//			require_once('include/queue_fn.php');
//			add_to_queue($a->contact,NETWORK_DIASPORA2,$s);
//			notice(t('Diaspora post failed. Queued for retry.').EOL);
		}
	}
}
