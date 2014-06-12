<?php
/**
 * Name: Randpost
 * Description: Make random posts/replies
 * Version: 1.0
 * Author: Mike Macgirvin
 */

require_once('include/crypto.php');

function randpost_load() {
	register_hook('cron', 'addon/randpost/randpost.php', 'randpost_fetch');
	register_hook('enotify_store', 'addon/randpost/randpost.php', 'randpost_enotify_store');
}

function randpost_unload() {
	unregister_hook('cron', 'addon/randpost/randpost.php', 'randpost_fetch');
	unregister_hook('enotify_store', 'addon/randpost/randpost.php', 'randpost_enotify_store');
}


function randpost_enotify_store(&$a,&$b) {

	if(! ($b['type'] == NOTIFY_COMMENT || $b['type'] == NOTIFY_TAGSELF))
		return;

	if(! get_pconfig($b['uid'],'randpost','enable'))
		return;


	$fort_server = get_config('fortunate','server');
	if(! $fort_server)
		return;


	$c = q("select * from channel where channel_id = %d limit 1",
		intval($b['uid'])
	);
	if(! $c)
		return;


	$p = q("select id, item_flags from item where parent_mid = mid and parent_mid = '%s' and uid = %d limit 1",
		dbesc($b['item']['parent_mid']),
		intval($b['uid'])
	);
	if(! $p)
		return;

	if($p[0]['item_flags'] & ITEM_OBSCURED)
		return;

	$replies = array(
		t('You\'re welcome.'),
		t('Ah shucks...'),
		t('Don\'t mention it.'),
		t('&lt;blush&gt;'),
		':like'
	);

	require_once('include/bbcode.php');
	require_once('include/html2plain.php');

	if($b['item'] && $b['item']['body']) {
		$txt = preg_replace('/\@\[z(.*?)\[\/zrl\]/','',$b['item']['body']);
		$txt = html2plain(bbcode($txt));
		$pattern = substr($txt,0,255);
	}

	if($b['item']['author_xchan']) {
		$z = q("select * from xchan where xchan_hash = '%s' limit 1",
			dbesc($b['item']['author_xchan'])
		);
		if($z) {
			$mention = '@' . '[zrl=' . $z[0]['xchan_url'] . ']' . $z[0]['xchan_name'] . '[/zrl]' . "\n\n";
		}
	}

	if(stristr($b['item']['body'],$c[0]['channel_name']) && mb_strlen($pattern) < 36 && stristr($pattern,'thank')) {
		$reply = $replies[mt_rand(0,count($replies)-1)];
	}

	require_once('include/html2bbcode.php');
 
	$url = 'http://' . $fort_server . '/cookie.php?f=&lang=any&off=a&pattern=' . urlencode($pattern);

// logger('randpost: ' . $url);

	$s = z_fetch_url($url);

	if($s['success'] && (! $s['body']))
		$s = z_fetch_url('http://' . $fort_server . '/cookie.php');

	if((! $s['success']) || (! $s['body']))
		return;

	$x = array();


	if($reply) {
		$x['body'] = $mention . $reply;
	}
	else {
		// if it might be a quote make it a quote
		if(strpos($s['body'],'--'))
			$x['body'] = $mention . '[quote]' . html2bbcode($s['body']) . '[/quote]';
		else
			$x['body'] = $mention . html2bbcode($s['body']);
	}

	if($mention) {
		$x['term'] = array(array(
			'uid' => $c[0]['channel_id'],
			'type' => TERM_MENTION,
			'otype' => TERM_OBJ_POST,
			'term' => $z[0]['xchan_name'],
			'url' => $z[0]['xchan_url']
		));
	}

	$x['uid'] = $c[0]['channel_id'];
	$x['aid'] = $c[0]['channel_account_id'];
	$x['mid'] = item_message_id();
	$x['parent'] = $p[0]['id'];
	$x['parent_mid'] = $b['item']['parent_mid'];
	$x['author_xchan'] = $c[0]['channel_hash'];
	$x['owner_xchan'] = $b['item']['owner_xchan'];

	$x['item_flags'] = ITEM_ORIGIN|ITEM_VERIFIED;

	// You can't pass a Turing test if you reply in milliseconds. 
	// Also I believe we've got ten minutes fudge before we declare a post as time traveling.
	// Otherwise we'll just set it to now and it will still go out in milliseconds. 
	// So set the reply to post sometime in the next 15-45 minutes (depends on poller interval)

	$fudge = mt_rand(15,30);
	$x['created'] = $x['edited'] = datetime_convert('UTC','UTC','now + ' . $fudge . ' minutes');

	$x['body'] = trim($x['body']);
	$x['sig'] = base64url_encode(rsa_sign($x['body'],$c[0]['channel_prvkey']));

	$post = item_store($x);
	$post_id = $post['item_id'];

	$x['id'] = $post_id;

	call_hooks('post_local_end', $x);

	proc_run('php','include/notifier.php','comment-new',$post_id);	

}



function randpost_fetch(&$a,&$b) {

	$fort_server = get_config('fortunate','server');
	if(! $fort_server)
		return;

	$r = q("select * from pconfig where cat = 'randpost' and k = 'enable'");

	if($r) {
		foreach($r as $rr) {
			if(! $rr['v'])
				continue;
//			logger('randpost');

			// cronhooks run every 10-15 minutes typically
			// try to keep posting limited to say once every few hours on average.

			$test = mt_rand(0,20);
			if($test == 9) {
				$c = q("select * from channel where channel_id = %d limit 1",
					intval($rr['uid'])
				);
				if(! $c)
					continue;

				$mention = '';

				require_once('include/html2bbcode.php');

				$s = z_fetch_url('http://' . $fort_server . '/cookie.php?numlines=2&equal=1&rand=' . mt_rand());
				if(! $s['success'])
					continue;

				$x = array();
				$x['uid'] = $c[0]['channel_id'];
				$x['aid'] = $c[0]['channel_account_id'];
				$x['mid'] = $x['parent_mid'] = item_message_id();
				$x['author_xchan'] = $x['owner_xchan'] = $c[0]['channel_hash'];
				$x['item_flags'] = ITEM_THREAD_TOP|ITEM_ORIGIN|ITEM_WALL|ITEM_VERIFIED;

				// if it might be a quote make it a quote
				if(strpos($s['body'],'--'))
					$x['body'] = $mention . '[quote]' . html2bbcode($s['body']) . '[/quote]';
				else
					$x['body'] = $mention . html2bbcode($s['body']);

				$x['sig'] = base64url_encode(rsa_sign($x['body'],$c[0]['channel_prvkey']));

				$post = item_store($x);
				$post_id = $post['item_id'];

				$x['id'] = $post_id;

				call_hooks('post_local_end', $x);

				proc_run('php','include/notifier.php','wall-new',$post_id);
			}
		}
	}
}

