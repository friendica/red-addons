<?php

/**
 * Name: Qrator
 * Description: QR generator
 * Version: 1.0
 * Author: Macgirvin
 *
 */


function qrator_load() {}
function qrator_unload() {}
function qrator_module() {}


function qrator_content(&$a) {

$header = t('QR Generator');
$prompt = t('Enter some text');

$o .= <<< EOT
<h2>$header</h2>

<div>$prompt</div>
<input type="text" id="qr-input" onkeyup="makeqr();" />
<div id="qr-output"></div>

<script>
function makeqr() {
	var txt = $('#qr-input').val();

	$('#qr-output').html('<img src="/photo/qr/?f=&qr=' + txt + '" /></img>');

}
</script>


EOT;
return $o;

}