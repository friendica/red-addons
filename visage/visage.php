<?php
/**
 * Name: Visage
 * Description: Who viewed my channel/profile
 * Version: 1.0
 * Author: Mike Macgirvin <mike@zothub.com>
 *
 */


/**
 * Visit $yoursite/visage
 * Lots of room for improvement, turning into a widget, etc.
 * The time of visit can be seen by hovering over the name (not the photo). This way I could re-use an existing template.  
 */


function visage_load() {

	register_hook('magic_auth_success', 'addon/visage/visage.php', 'visage_magic_auth');

}


function visage_unload() {

	unregister_hook('magic_auth_success', 'addon/visage/visage.php', 'visage_magic_auth');

}



function visage_magic_auth($a, &$b) {

//	logger('visage: ' . print_r($b,true));

	if((! strstr($b['url'],'/channel/')) && (! strstr($b['url'],'/profile/'))) {
//		logger('visage: exiting: ' . $b['url']);
		return;
	}

	$p = preg_match('/http(.*?)(channel|profile)\/(.*?)($|[\/\?\&])/',$b['url'],$matches);
	if(! $p) {
//		logger('visage: no matching pattern');
		return;
	}

//	logger('visage: matches ' . print_r($matches,true));
	
	$nick = $matches[3];

	if($_SERVER['HTTP_DNT'] == '1' || intval($_SESSION['DNT']))
		return;

	$c = q("select channel_id, channel_hash from channel where channel_address = '%s' limit 1",
		dbesc($nick)
	);

	if(! $c)
		return;

	$x = get_pconfig($c[0]['channel_id'],'visage','visitors');
	if(! is_array($x))
		$n = array(array($b['xchan']['xchan_hash'],datetime_convert()));
	else {
		$n = array();

		for($z = ((count($x) > 24) ? count($x) - 24 : 0); $z < count($x); $z ++)
			if($x[$z][0] != $b['xchan']['xchan_hash'])
				$n[] = $x[$z];
		$n[] = array($b['xchan']['xchan_hash'],datetime_convert());
	}

//	logger('visage set: ' . print_r($n,true));

	set_pconfig($c[0]['channel_id'],'visage','visitors',$n);
	return;

}


function visage_module() {}

function visage_content(&$a) {

	if(! local_user())
		return;

	$o = '<h3>' . t('Recent Channel/Profile Viewers') . '</h3>';
	$x = get_pconfig(local_user(),'visage','visitors');
	if((! $x) || (! is_array($x)))
		return;
	$chans = '';
	for($n = 0; $n < count($x); $n ++) {
		if($chans)
			$chans .= ',';
		$chans .= "'" . dbesc($x[$n][0]) . "'";
	}
	if($chans) {
		$r = q("select * from xchan where xchan_hash in ( $chans )");
	}
	if($r) {
        $tpl = get_markup_template('common_friends.tpl');

		for($g = count($x) - 1; $g >= 0; $g --) {
			foreach($r as $rr) {
				if($x[$g][0] == $rr['xchan_hash'])
					break;
			}

            $o .= replace_macros($tpl,array(
                '$url'   => (($rr['xchan_flags'] & XCHAN_FLAGS_HIDDEN) ? z_root() : chanlink_url($rr['xchan_url'])),
                '$name'  => $rr['xchan_name'],
                '$photo' => $rr['xchan_photo_m'],
                '$tags'  => (($rr['xchan_flags'] & XCHAN_FLAGS_HIDDEN) ? z_root() : chanlink_url($rr['xchan_url'])),
				'$note'  => relative_date($x[$g][1])
            ));
        }

        $o .= cleardiv();
    }

    return $o;
}

		
