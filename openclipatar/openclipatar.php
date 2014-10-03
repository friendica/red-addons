<?php
/**
 *
 * Name: Openclipatar
 * Description: Allows you to select a profile photo from openclipart.org easily
 * Version: 0.8
 * Author: Habeas Codice <https://federated.social>
 *
 */

function openclipatar_load() {
	//register_hook('feature_settings', 'addon/openclipatar/openclipatar.php', 'openclipatar_settings');
	//register_hook('feature_settings_post', 'addon/openclipatar/openclipatar.php', 'openclipatar_settings_post');
	register_hook('profile_photo_content_end', 'addon/openclipatar/openclipatar.php', 'openclipatar_profile_photo_content_end');
}

function openclipatar_unload() {
	//unregister_hook('feature_settings', 'addon/openclipatar/openclipatar.php', 'openclipatar_settings');
	//unregister_hook('feature_settings_post', 'addon/openclipatar/openclipatar.php', 'openclipatar_settings_post');
	unregister_hook('profile_photo_content_end', 'addon/openclipatar/openclipatar.php', 'openclipatar_profile_photo_content_end');
}

function openclipatar_module() { return; }

function openclipatar_plugin_admin_post(&$a) {
	$prefclipids = ((x($_POST, 'prefclipids')) ? notags(trim($_POST['prefclipids'])) : '');
	$defsearch = ((x($_POST, 'defsearch')) ? notags(trim($_POST['defsearch'])) : '');
	set_config('openclipatar', 'prefclipids', $prefclipids);
	set_config('openclipatar', 'defsearch', $defsearch);
}

function openclipatar_plugin_admin(&$a, &$o) {
	$t = get_markup_template('admin.tpl', 'addon/openclipatar/');
	$prefclipids = get_config('openclipatar', 'prefclipids');
	$defsearch = get_config('openclipatar', 'defsearch');
	if(! $defsearch) 
		$defsearch = 'avatar';
	
	$o = replace_macros( $t, array(
		'$submit' => t('Submit'),
		'$prefclipids' => array('prefclipids', t('Preferred Clipart IDs'), $prefclipids, t('List of preferred clipart ids. These will be shown first.')),
		'$defsearch' => array('defsearch', t('Default Search Term'), $defsearch, t('The default search term. These will be shown second.')),
		//'$nperpage' => array('nperpage', t('Results pagination'), $nperpage, t('Enter the number of results you wish to pull from the server each page')),
	));
}

function openclipatar_decode_result($arr) {
	$dbt = empty($arr['drawn_by']) ? (t('Uploaded by: ') . $arr['uploader']) : (t('Drawn by: ') . $arr['drawn_by']);
	$r = array(
		'title' => $arr['title'],
		'uploader' => $arr['uploader'],
		'drawn_by' => $arr['drawn_by'],
		'ncomments' => count($arr['comments'], COUNT_NORMAL),
		'nfaves' => $arr['total_favorites'],
		'ndownloads' => $arr['downloaded_by'],
		'desc' => $arr['description'],
		'tags' => $arr['tags'],
		'link' => $arr['detail_link'],
		'thumb' => 'https://openclipart.org/image/80px/svg_to_png/' . $arr['id'] . '/' . $arr['id'] . '.png',
		'id' => $arr['id'],
		'created' => $arr['created'],
		'dbtext' => $dbt,
		'uselink' => '/openclipatar/use/' . $arr['id'],
	);
	return $r;
}

function openclipatar_profile_photo_content_end(&$a, &$o) {
	// until we get a better api from openclipart.org, preload everything and spit it out. hopefully ram doesn't run out
	
	$prefclipids = get_config('openclipatar', 'prefclipids');
	$defsearch = get_config('openclipatar', 'defsearch');
	
	head_add_css('addon/openclipatar/openclipatar.css');
	
	$t = get_markup_template('avatars.tpl', 'addon/openclipatar/');
	
	if(! $defsearch)
		$defsearch = 'avatar';
	
	if(x($_POST,'search'))
		$search = notags(trim($_POST['search']));
	else
		$search = ((x($_GET,'search')) ? notags(trim(rawurldecode($_GET['search']))) : '');
		
	if(! $search)
		$search = $defsearch;
	
	$entries = array();
	$eidlist = array();
		
	if($prefclipids && preg_match('/[\d,]+/',$prefclipids)) {
		$eidlist = explode(',', $prefclipids); // save for later
		$x = z_fetch_url('https://openclipart.org/search/json/?byids=' . dbesc($prefclipids));
		if($x['success']) {
			$j = json_decode($x['body'], true);
			if($j && !empty($j['payload'])) {
				foreach($j['payload'] as $rr) {
					$entries[] = openclipatar_decode_result($rr);
				}
			}
		}
	}
		
	$x =  z_fetch_url('https://openclipart.org/search/json/?amount=20&query=' . urlencode($search) . '&page=' . $a->pager['page']);
	
	if($x['success']) {
		$j = json_decode($x['body'], true);
		if($j && !empty($j['payload'])) {
			foreach($j['payload'] as $rr) {
				$e = openclipatar_decode_result($rr);
				if(!in_array($e['id'], $eidlist)) {
					$entries[] = $e;
				}
			}
			$o .= "<script> var page_query = 'openclipatar'; var extra_args = 'search=" . urlencode($search) . '&' . extra_query_args() . "' ; </script>";
		}
	}
	if($_REQUEST['aj']) {
		if($entries) {
			$o = replace_macros(get_markup_template('avatar-ajax.tpl', 'addon/openclipatar/'), array(
				'$use' => t('Use'),
				'$entries' => $entries,
			));
		} else {
			$o = '<div id="content-complete"></div>';
		}
		echo $o;
		killme();
	} else {
		$o .= replace_macros( $t, array(
			'$selectmsg' => t('Or select from a free OpenClipart.org image:'),
			'$use' => t('Use'),
			'$defsearch' => array('search', t('Search Term'), $search),
			//'$form_security_token' => get_form_security_token('profile_photo'),
			'$entries' => $entries,
		));
	}
}

function openclipatar_content(&$a) {
	if(! local_user())
		return;
		
	$o = '';
	if(argc() == 3 && argv(1) == 'use') {
		$id = argv(2);
		$chan = $a->get_channel();
		
		$x = z_fetch_url('https://openclipart.org/image/250px/svg_to_png/' .$id . '/' . $id . '.png',true);
		if($x['success'])
			$imagedata = $x['body'];
		
		$ph = photo_factory($imagedata, 'image/png');
		if(! $ph->is_valid())
			return t('Unknown error. Please try again later.');
			
		// create a unique resource_id
		$hash = photo_new_resource();
		
		// save an original or "scale 0" image
		$p = array('aid' => get_account_id(), 'uid' => local_user(), 'resource_id' => $hash, 'filename' => $id.'.png', 'album' => t('Profile Photos'), 'scale' => 0);
		$r = $ph->save($p);
		if($r) {
			// scale 0 success, continue 4, 5, 6
			// we'll skip scales 1,2 (640, 320 rectangular formats as these images are all less than this)
			
			// ensure squareness at first, subsequent scales keep ratio
			$ph->scaleImageSquare(175);
			$p['scale'] = 4;
			$r = $ph->save($p);
			if($r === false)
				$photo_failure = true;

			$ph->scaleImage(80);
			$p['scale'] = 5;
			$r = $ph->save($p);
			if($r === false)
				$photo_failure = true;

			$ph->scaleImage(48);
			$p['scale'] = 6;
			$r = $ph->save($p);
			if($r === false)
				$photo_failure = true;
		}
		
		$is_default_profile = 1;
		if($_REQUEST['profile']) {
			$r = q("select id, is_default from profile where id = %d and uid = %d limit 1",
				intval($_REQUEST['profile']),
				intval(local_user())
			);
			if(($r) && (! intval($r[0]['is_default'])))
				$is_default_profile = 0;
		} 
		if($is_default_profile) {
			// unset any existing profile photos
			$r = q("UPDATE photo SET profile = 0 WHERE profile = 1 AND uid = %d",
				intval(local_user()));
			$r = q("UPDATE photo SET photo_flags = (photo_flags ^ %d ) WHERE (photo_flags & %d ) AND uid = %d",
				intval(PHOTO_PROFILE),
				intval(PHOTO_PROFILE),
				intval(local_user()));

			// set all sizes of this one as profile photos
			$r = q("UPDATE photo SET profile = 1 WHERE uid = %d AND resource_id = '%s'",
				intval(local_user()),
				dbesc($hash)
				);
			$r = q("UPDATE photo SET photo_flags = ( photo_flags | %d ) WHERE uid = %d AND resource_id = '%s'",
				intval(PHOTO_PROFILE),
				intval(local_user()),
				dbesc($hash)
				);
			
			require_once('mod/profile_photo.php');
			profile_photo_set_profile_perms(); //Reset default profile photo permissions to public
			
			// only the default needs reload since it uses canonical url -- despite the slightly ambiguous message, left it so as to re-use translations
			info( t('Shift-reload the page or clear browser cache if the new photo does not display immediately.') . EOL);
		}
		else {
			// not the default profile, set the path in the correct entry in the profile DB
			$r = q("update profile set photo = '%s', thumb = '%s' where id = %d and uid = %d limit 1",
				dbesc(get_app()->get_baseurl() . '/photo/' . $hash . '-4'),
				dbesc(get_app()->get_baseurl() . '/photo/' . $hash . '-5'),
				intval($_REQUEST['profile']),
				intval(local_user())
			);
			info( t('Profile photo updated successfully.') . EOL);
		}
		// set a new photo_date on our xchan so that we can tell everybody to update their cached copy
		$r = q("UPDATE xchan set xchan_photo_date = '%s' where xchan_hash = '%s' limit 1",
			dbesc(datetime_convert()),
			dbesc($chan['xchan_hash'])
		);
		// tell everybody
		proc_run('php','include/directory.php',local_user());
		
		$profile_addr = $a->get_baseurl() . '/profile/' . ($_REQUEST['profile'] ? $_REQUEST['profile'].'/view' : $chan['channel_address']);
		goaway($profile_addr);
		
	} else {
		//invoked as module, we place in content pane the same as we would for the end of the profile photo page. Also handles json for endless scroll for either invokation.
		openclipatar_profile_photo_content_end($a, $o);
	}
	return $o;
}

/*function openclipatar_settings(&$a, &$s) {
	if(! local_user())
		return;
	head_add_css('addon/openclipatar/openclipatar.css');
	$enabled = get_pconfig(local_user(),'openclipatar','enable');
	$checked = (($enabled) ? ' checked="checked" ' : '');

        $s .= '<div class="settings-block">';
        $s .= '<button class="btn btn-default" data-target="#settings-openclipatar" data-toggle="collapse" type="button"><img src="addon/openclipatar/openclipart-banner.png" /> Openclipatar '. t('Settings') .'</button>';
        
        $s .= '<div id="settings-openclipatar" class="collapse well">';
        $s .= '<div id="openclipatar-enable-wrapper">';
        $s .= '<label id="openclipatar-enable-label" for="openclipatar-checkbox">' . t('Enable') . ' Openclipatar ' . t('Plugin') . '</label>';
        $s .= '<input id="openclipatar-checkbox" type="checkbox" name="openclipatar" value="1" ' . $checked . '/>';
        $s .= '</div><div class="clear"></div>';

        $s .= '<div class="settings-submit-wrapper" ><input type="submit" name="openclipatar-submit" class="settings-submit" value="' . t('Submit') . '" /></div></div></div>';
}*/