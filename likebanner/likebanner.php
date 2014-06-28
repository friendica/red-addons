<?php
function likebanner_load() {}
function likebanner_unload() {}
function likebanner_module() {}

function likebanner_init(&$a) {
	if(argc() > 1 && argv(1) == 'show' && $_REQUEST['addr']) {
		header("Content-Type: image/png");
		$im = ImageCreateFromPng('addon/likebanner/like_banner.png');
		$black = ImageColorAllocate($im, 0,0,0);
		$start_x = 18;
		$start_y = 110;
		$fontsize=(($_REQUEST['size'])? intval($_REQUEST['size']) : 28);
		imagettftext($im,$fontsize,0,$start_x,$start_y,$black, 'addon/likebanner/FreeSansBold.ttf',$_REQUEST['addr']);
		imagepng($im);
		ImageDestroy($im);
		killme();
	}
}




function likebanner_content(&$a) {

	$o = '<h1>Like Banner</h1>';

	$o .= '<form action="likebanner" method="get" >';
	$o .= t('Your Webbie:');
	$o .= '<br /><br />';
	$o .= '<input type="text" name="addr" size="32" value="' . $_REQUEST['addr'] . '" />';
	$o .= '<br /><br />' . t('Fontsize (px):');
	$o .= '<br /><br />';
	$o .= '<input type="text" name="size" size="32" value="' . (($_REQUEST['size']) ? $_REQUEST['size'] : 28) . '" /><br /><br />';
	$o .= '<input type="submit" name="submit" value="' . t('Submit'). '" /></form><br /><br/>';

	if($_REQUEST['addr']) {
		$o .= '<img style="border: 1px solid #000;" src="likebanner/show/?f=&addr=' . urlencode($_REQUEST['addr']) . '&size=' . $_REQUEST['size'] . '" alt="banner" />';
	}
	
	return $o;

}