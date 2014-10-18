<?php
/**
 * Name: poroscopo
 * Description: A weekly random horoscope (Italian only). To enable this addon: util/pconfig $channel_id poroscopo enable 1
 * Version: 1.0
 * Author: Paolo Tacconi (based on Randpost by Mike Macgirvin)
 */

require_once('include/crypto.php');

function poroscopo_load() {
	register_hook('cron_weekly', 'addon/poroscopo/poroscopo.php', 'poroscopo_fetch');
}

function poroscopo_unload() {
	unregister_hook('cron_weekly', 'addon/poroscopo/poroscopo.php', 'poroscopo_fetch');
}


function poroscopo_fetch(&$a,&$b) {

	$r = q("select * from pconfig where cat = 'poroscopo' and k = 'enable'");

	if($r) {
		foreach($r as $rr) {
			if(! $rr['v'])
				continue;
			logger('poroscopo');

			$c = q("select * from channel where channel_id = %d limit 1",
				intval($rr['uid'])
			);
			if(! $c)
				continue;

			$mention = '';

			require_once('include/html2bbcode.php');
			require_once('addon/poroscopo/poroscopo_data.php');
			$oroscopo = poroscopo_create_string();

			$x = array();
			$x['uid'] = $c[0]['channel_id'];
			$x['aid'] = $c[0]['channel_account_id'];
			$x['mid'] = $x['parent_mid'] = item_message_id();
			$x['author_xchan'] = $x['owner_xchan'] = $c[0]['channel_hash'];
			$x['item_flags'] = ITEM_THREAD_TOP|ITEM_ORIGIN|ITEM_WALL|ITEM_VERIFIED;

			$x['body'] = $mention . html2bbcode($oroscopo);

			$x['sig'] = base64url_encode(rsa_sign($x['body'],$c[0]['channel_prvkey']));

			$post = item_store($x);
			$post_id = $post['item_id'];

			$x['id'] = $post_id;

			call_hooks('post_local_end', $x);

			proc_run('php','include/notifier.php','wall-new',$post_id);
		}
	}
}

