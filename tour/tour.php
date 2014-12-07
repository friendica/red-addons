<?php
/**
     *
     * Name: Red Matrix UI Tour
     * Description: Show a tour for new users
     * Version: 0.0
     * Author: Stefan Parviainen <pafcu@iki.fi>
     *
     */

function tour_load() {
	register_hook('page_header','addon/tour/tour.php','tour_alterheader');
	register_hook('page_end','addon/tour/tour.php','tour_addfooter');
}

function tour_unload() {
	unregister_hook('page_header','addon/tour/tour.php','tour_alterheader');
	unregister_hook('page_end','addon/tour/tour.php','tour_addfooter');
}

function tour_alterheader($a, &$navHtml) {
	$addScriptTag = '<link href="addon/tour/jquery-tourbus.min.css" rel="stylesheet">';
	$a->page['htmlhead'] .= $addScriptTag;
}

function tour_addfooter($a,&$navHtml) {
	$addScriptTag = '<script type="text/javascript" src="' . $a->get_baseurl() . '/addon/tour/jquery-tourbus.min.js"></script>' . "\r\n";

	$legs = array();
	$legs[] = array('avatar','Clicking here allows you to change your settings.');
	$legs[] = array('network_nav_btn','Click here to see activity from your connections.');
	$legs[] = array('home_nav_btn','Click here to see your channel home.');
	$legs[] = array('mail_nav_btn','You can access your private messages from here.');
	$legs[] = array('events_nav_btn','Create new events here.');
	$legs[] = array('connections_nav_btn','You can accept new connections and change permissions for existing ones here. You can also e.g. create groups of contacts.');
	$legs[] = array('notifications_nav_btn','System notifications will arrive here');
	$legs[] = array('nav-search-text','Search for content and users');
	$legs[] = array('directory_nav_btn','Browse for new contacts');
	$legs[] = array('apps_nav_btn','Launch installed apps');
	$legs[] = array('help_nav_btn','Looking for help? Click here.');

	$addScriptTag .= "<ol id='tour' class='tourbus-legs'>";
	foreach($legs as $leg) {
		$addScriptTag .= "<li data-el='#$leg[0]'><p>$leg[1]</p><a href='javascript:void(0);' class='tourbus-next'>Next...</a><a href='javascript:void();' class='tourbus-stop'>Stop</a></li>";
	}

	$addScriptTag .= '</ol>';

	$addScriptTag .= <<<EOD
<script>
$(window).load(function() {
  var tour = $("#tour").tourbus({
    leg: { align:'left', arrow:15}
    /* Options will go here */
  });

  tour.trigger('depart.tourbus');
});
</script>
EOD;

	$navHtml .= $addScriptTag;
}
?>
