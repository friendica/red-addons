<?php
/**
     *
     * Name: Red Matrix UI Tour
     * Description: Show a tour for new users
     * Version: 0.0
     * Author: Stefan Parviainen <pafcu@iki.fi>
     *
     */

// Make addon a proper module so that we can use tour_content, tour_post functions
function tour_module(){};

function tour_load() {
	register_hook('page_header','addon/tour/tour.php','tour_alterheader');
	register_hook('page_end','addon/tour/tour.php','tour_addfooter');
}

function tour_unload() {
	unregister_hook('page_header','addon/tour/tour.php','tour_alterheader');
	unregister_hook('page_end','addon/tour/tour.php','tour_addfooter');
}

function tour_alterheader($a, &$navHtml) {
	// Add tourbus CSS
	$a->page['htmlhead'] .= '<link href="addon/tour/jquery-tourbus.min.css" rel="stylesheet">';
}

function tour_content(&$a) {
	// Being able to reset the state is useful during development
	// Should either be exposed through proper UI for users, but probably not needed at all

	if($_REQUEST['reset']) {
		$seen = '';
		set_pconfig(local_user(),'tour','seen','');
		set_pconfig(local_user(),'tour','notour',false);
		logger('Reset tour');
	}
}

function tour_post() {
	if(! local_user())
		return;

	// Never show tour again
	if(x($_POST,'notour') && $_POST['notour'] == '1') {
		set_pconfig(local_user(),'tour','notour',1);
	}

	// Add the recently seen element to the list of things not to show again
	$seen = get_pconfig(local_user(),'tour','seen');
	if(x($_POST,'seen') && $_POST['seen'])
		set_pconfig(local_user(),'tour','seen',$seen . ',' . $_POST['seen']); // Todo: validate input
}

function tour_addfooter($a,&$navHtml) {
	if(get_pconfig(local_user(),'tour','notour') == 1)
		return;

	$content = '<script type="text/javascript" src="' . $a->get_baseurl() . '/addon/tour/jquery-tourbus.min.js"></script>' . "\r\n";
	$content .= '<script type="text/javascript" src="' . $a->get_baseurl() . '/addon/tour/jquery.scrollTo.min.js"></script>' . "\r\n";

	$seen = explode(',',get_pconfig(local_user(),'tour','seen'));

	// TOOD: Check which elements are present on which pages, and only include the relevant stuff
	$legs = array();
	$legs[] = array('profile-jot-text',t('Share important stuff.'));
	$legs[] = array('jot-title',t('You can write an optional title for your update (good for long posts).'),'if($("#jot-title").css("display") == "none") { $("#profile-jot-text").trigger("click"); }');
	$legs[] = array('jot-category',t('Entering some categories here makes it easier to find your post later.'),'if($("#jot-title").css("display") == "none") { $("#profile-jot-text").trigger("click"); }');
	$legs[] = array('wall-image-upload',t('Share photos, links, location, etc.'),'if($("#jot-title").css("display") == "none") { $("#profile-jot-text").trigger("click"); }');
	$legs[] = array('profile-expires',t('Only want to share content for a while? Make it expire at a certain date.'),'if($("#jot-title").css("display") == "none") { $("#profile-jot-text").trigger("click"); }');
	$legs[] = array('profile-encrypt',t('You can password protect content.'),'if($("#jot-title").css("display") == "none") { $("#profile-jot-text").trigger("click"); }');
	$legs[] = array('dbtn-acl',t('Choose who you share with.'),'if($("#jot-title").css("display") == "none") { $("#profile-jot-text").trigger("click"); }');
	/* Todo: Preview */
	$legs[] = array('dbtn-submit',t('Click here when you are done.'),'if($("#jot-title").css("display") == "none") { $("#profile-jot-text").trigger("click"); }');
	$legs[] = array('avatar',t('Edit your profile and change settings.'));
	$legs[] = array('network_nav_btn',t('Click here to see activity from your connections.'));
	$legs[] = array('home_nav_btn',t('Click here to see your channel home.'));
	$legs[] = array('mail_nav_btn',t('You can access your private messages from here.'));
	$legs[] = array('events_nav_btn',t('Create new events here.'));
	$legs[] = array('connections_nav_btn',t('You can accept new connections and change permissions for existing ones here. You can also e.g. create groups of contacts.'));
	$legs[] = array('notifications_nav_btn',t('System notifications will arrive here'));
	$legs[] = array('nav-search-text',t('Search for content and users'));
	$legs[] = array('directory_nav_btn',t('Browse for new contacts'));
	$legs[] = array('apps_nav_btn',t('Launch installed apps'));
	$legs[] = array('help_nav_btn',t('Looking for help? Click here.'));

	$content .= "<ol id='tourlegs' class='tourbus-legs'>";

	$steps = 0;
	if(!in_array('tourintro', $seen)) {
		$content .= "<li data-orientation='centered' data-tourid='tourintro'><p>".t('Welcome to Red Matrix! Would you like to see a tour of the UI?</p> <p>You can pause it at any time and continue where you left off by reloading the page, or navigting to another page.</p><p>You can also advance by pressing the return key')."</p><button href='javascript:void(0);' class='tourbus-next btn btn-primary'>Start tour <span class='icon-forward'/></button><button href='javascript:void()' class='tourbus-stop btn btn-warning'>Show tour later <span class='icon-pause'/></button><button href='javascript:void();' onclick='notour()' class='tourbus-stop btn btn-danger' onclick='notour();'>Never show tour <span class='icon-remove'></span></button></li>";
		$steps = $steps + 1;
	}

	foreach($legs as $leg) {
		if(in_array($leg[0],$seen)) {
			continue;
		}
		$click='';
		if(count($leg) > 2)
			$click="data-click='$leg[2]'";
		$content .= "<li data-el='#$leg[0]' data-tourid='$leg[0]' $click><p>$leg[1]</p><button href='javascript:void(0);' class='tourbus-next btn btn-primary'>Continue <span class='icon-forward'/></button><button href='javascript:void()' class='tourbus-stop btn btn-warning'>Pause tour <span class='icon-pause'/></button><button href='javascript:void();' onclick='notour();' class='tourbus-stop btn btn-danger'>Don't show tour again <span class='icon-remove'></span></button></li>";
		$steps = $steps + 1;
	}

	if($steps > 1) {
		$content .= "<li data-orientation='centered' data-tourid='tourend'><p>That's it for now! Continue to explore, and you'll get more help along the way.</p><button href='javascript:void()' class='tourbus-stop btn btn-primary'>OK <span class='icon-ok'/></button></li>";

	}

	$content .= '</ol>';
if($steps > 1) {
$content .= <<<'EOD'
<script>
$(window).load(function() {
	// Clean up tour by removing unknown elements
	$('#tourlegs li').each( function() {
		var leg = $(this);
		var targetSelector = leg.data('el');
		if( targetSelector && $(targetSelector).length == 0 ) leg.remove();
	});


	var tour = $("#tourlegs").tourbus({
		leg: { align:'left', arrow:15},
		onLegStart: function(leg, bus) {if(leg.rawData.click) { eval(leg.rawData.click); } bus.repositionLegs(); $.post('tour',{'seen':leg.rawData.tourid}); },
		/* Options will go here */
	});

	tour.trigger('depart.tourbus');
});

function notour() {
	$.post('tour',{'notour':'1'});
}
</script>
EOD;
}

	$navHtml .= $content;
}
?>
