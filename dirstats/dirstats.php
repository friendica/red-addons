<?php
/**
* Name: DirStats
* Description: Show some statistics about the directory.  
* This will list the number of RedMatrix, Friendica and Diaspora
* hubs that your own hub is aware of.  
* As the name suggets, this is intended for directory servers, where
* this will provide accurate counts of all known Red hubs and channels.
*
* If you are not a directory server - and for Friendica and Diaspora 
* even if you are - these counts are merely those your own hub is aware of
* and not all that exist in the network.
*
* Version: 1.0
* Author: Thomas Willingham <zot:beardyunixer@beardyunixer.com>
*/

function dirstats_load() {}

function dirstats_unload() {}

function dirstats_module() {}



function dirstats_content(&$a) {


	$r = q("SELECT count(distinct hubloc_host) as total FROM `hubloc`");
		if ($r)
		$hubcount = $r[0]['total'];

		$r = q("SELECT count(distinct hubloc_host) as total FROM `hubloc` where hubloc_network = 'zot'");
			if ($r)
			$zotcount = $r[0]['total'];

		$r = q("SELECT count(distinct hubloc_host) as total FROM `hubloc` where hubloc_network = 'friendica-over-diaspora'");
		if ($r)
			$friendicacount = $r[0]['total'];

		$r = q("SELECT count(distinct hubloc_host) as total FROM `hubloc` where hubloc_network = 'diaspora'");
		if ($r)
			$diasporacount = $r[0]['total'];

		$r = q("SELECT count(distinct xchan_hash) as total FROM `xchan` where xchan_network = 'zot'");
		if ($r)
			$channelcount = $r[0]['total'];

		$r = q("SELECT count(distinct xchan_hash) as total FROM `xchan` where xchan_network = 'friendica-over-diaspora'");
		if ($r)
			$friendicachannelcount = $r[0]['total'];
		
		$r = q("SELECT count(distinct xchan_hash) as total FROM `xchan` where xchan_network = 'diaspora'");
		if ($r)
			$diasporachannelcount = $r[0]['total'];
	
	$tpl = get_markup_template( "dirstats.tpl", "addon/dirstats/" );
	 return replace_macros($tpl, array(
		'$hubcount' => $hubcount,
		'$zotcount' => $zotcount,
		'$friendicacount' => $friendicacount,
		'$diasporacount' => $diasporacount,
		'$channelcount' => $channelcount,
		'$friendicachannelcount' => $friendicachannelcount,
		'$diasporachannelcount' => $diasporachannelcount
		));
}
