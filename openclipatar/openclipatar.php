<?php
/**
 *
 * Name: Openclipatar
 * Description: Allows you to select a profile photo from openclipart.org easily
 * Version: 0.7
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
		
	$x =  z_fetch_url('https://openclipart.org/search/json/?amount=20&query=' . urlencode($search) . '&page=' . $a->pager['page']);
	
	$entries = array();
	
	if($x['success']) {
		$j = json_decode($x['body'], true);
		if($j && !empty($j['payload'])) {
			foreach($j['payload'] as $rr) {
				$dbt = empty($rr['drawn_by']) ? (t('Uploaded by: ') . $rr['uploader']) : (t('Drawn by: ') . $rr['drawn_by']);
				$e = array(
					'title' => $rr['title'],
					'uploader' => $rr['uploader'],
					'drawn_by' => $rr['drawn_by'],
					'ncomments' => count($rr['comments'], COUNT_NORMAL),
					'nfaves' => $rr['total_favorites'],
					'ndownloads' => $rr['downloaded_by'],
					'desc' => $rr['description'],
					'tags' => $rr['tags'],
					'link' => $rr['detail_link'],
					'thumb' => 'https://openclipart.org/image/80px/svg_to_png/' . $rr['id'] . '/' . $rr['id'] . '.png',
					'id' => $rr['id'],
					'created' => $rr['created'],
					'dbtext' => $dbt,
					'uselink' => '/openclipatar/use/' . $rr['id'],
				);
				$entries[] = $e;
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
		
		// unselect whatever previous profile photo as 'THE' profile photo
		$r = q("update photo set xchan='', profile = 0 where aid = %d and uid = %d",
			intval(get_account_id()),
			intval(local_user())
		);
		//probably also need to handle photo_flags
		
		// make lib to the hard work - grabs the url and resizes for zoom 4,5,6
		$x = import_profile_photo('https://openclipart.org/image/250px/svg_to_png/' .$id . '/' . $id . '.png', $chan['xchan_hash']);
		
		//import returns true in param 4 when error
		if(!$x[4]) {
				$r = q("update xchan set xchan_photo_date = '%s', xchan_photo_l = '%s', xchan_photo_m = '%s', xchan_photo_s = '%s', xchan_photo_mimetype = '%s' 
					where xchan_hash = '%s' limit 1",
					dbesc(datetime_convert()),
					dbesc($x[0]),
					dbesc($x[1]),
					dbesc($x[2]),
					dbesc($x[3]),
					dbesc($chan['xchan_hash'])
				);
				
				// <resource_id>-<zoom_level>
				$fname = basename($x[0]);
				$resource_id = substr($fname, 0, strpos($fname, '-'));
				
				// import_profile_photo doesn't set uid, import_channel_photo does, setting it is necessary for canonical /photo/profile/<size>/<uid> link to work. import_channel_photo OTOH doesn't give us a resource_id back to update the xchan which are critical for all other functions to work -- TODO: check with red devs for proper procedure
				// notes: tried 3 separate ways using import_channel_photo(), just doesn't seem workable unique ID wise. we cheat and overwrite album name to get around this.
				$r = q("update photo set aid = %d, uid = %d, photo_flags = %d, album = '%s' where resource_id = '%s'",
					intval(get_account_id()),
					intval(local_user()),
					intval(PHOTO_PROFILE),
					dbesc(t('Profile Photos')),
					$resource_id
				);
				
				// set highest res available photo as the official profile pic
				$r = q("update photo set profile = 1 where aid = %d and uid = %d and resource_id = '%s' order by scale limit 1",
					intval(get_account_id()),
					intval(local_user()),
					$resource_id
				);
				
				$profile_addr = $a->get_baseurl() . '/profile/' . $chan['channel_address'];
				$o .= '<P>' . t('Your profile image has been updated. Click the following link to view: ') . '<a href="' . $profile_addr . '">' . $profile_addr . '</a></P>';
					
		}
		// TODO: MX for failure, waiting until technical bits of photo api are better understood

	} else {
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