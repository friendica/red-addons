<?php

/**
 * Name: Donate
 * Description: Support the RedMatrix
 * Version: 1.0
 * Author: Macgirvin
 *
 */

function load(){}
function unload(){}
function donate_module(){}

function donate_content(&$a) {

/* Format - array( display name, paypal id, description of services or skills you provide to the matrix) */

$contributors = array(
array('The RedMatrix Project', 'max@macgirvin.com', t('Project Servers and Resources')),
array('Mike Macgirvin','mike@macgirvin.com',t('Project Creator and Tech Lead')),

/* Developers and public hubs - add your donatable resource here */

);


$text .= t('The RedMatrix is provided by volunteers giving their time and expertise, and often paying out of pocket.') . EOL;
$text .= t('There is no corporate funding and no ads, and we do not collect and sell your personal information.') . EOL;
$text .= t('Help support our work. Your donations keep servers and services running and also helps us provide feature development and bugfixes.');

$o = replace_macros(get_markup_template('donate.tpl','addon/donate'),array(
	'$header' => t('Donate'),
	'$text' => $text,
	'$choice' => t('Choose a project, developer, or public hub to support'),
	'$onetime' => t('Donate Now'),
	'$repeat' => t('Continuing donation'),
	'$contributors' => $contributors,
));

return $o;

}