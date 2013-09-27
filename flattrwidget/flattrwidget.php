<?php
/* Name: Flattr Widget
 * Description: Add a Flattr Button to the left/right aside are to allow the flattring of one thing (e.g. the for a blog)
 * Version: 0.1
 * Screenshot: img/red-flattr-widget.png
 * Depends: Core
 * Recommends: None
 * Category: Widget, flattr, Payment
 * Author: Tobias Diekershoff <https://diekershoff.de/channel/bavatar>
 * Maintainer: Tobias Diekershoff <https://diekershoff.de/channel/bavatar>
 */

function flattrwidget_load() {
	register_hook('channel_mod_aside', 'addon/flattrwidget/flattrwidget.php', 'flattrwidget_channel_mod_aside');
	register_hook('feature_settings', 'addon/flattrwidget/flattrwidget.php', 'flattrwidget_settings');
	register_hook('feature_settings_post', 'addon/flattrwidget/flattrwidget.php', 'flattrwidget_settings_post');
}

function flattrwidget_unload() {
	unregister_hook('channel_mod_aside', 'addon/flattrwidget/flattrwidget.php', 'flattrwidget_channel_mod_aside');
	unregister_hook('feature_settings', 'addon/flattrwidget/flattrwidget.php', 'flattrwidget_settings');
	unregister_hook('feature_settings_post', 'addon/flattrwidget/flattrwidget.php', 'flattrwidget_settings_post');
}

function flattrwidget_channel_mod_aside(&$a,&$b) {
    $id = $a->profile['profile_uid'];
    $enable = intval(get_pconfig($id,'flattrwidget','enable'));
    if (! $enable)
	return;
    $a->page['htmlhead'] .= '<link rel="stylesheet" href="'.$a->get_baseurl().'/addon/flattrwidget/style.css'.'" media="all" />';
    //  get alignment and static/dynamic from the settings
    //  align is either "aside" or "right_aside"
    //  sd is either static or dynamic
    $lr = get_pconfig( $id, 'flattrwidget', 'align');
    $sd = get_pconfig( $id, 'flattrwidget', 'sd');
    //  title of the thing for the things page on flattr
    $ftitle = get_pconfig( $id, 'flattrwidget', 'title');
    //  URL of the thing
    $thing = get_pconfig( $id, 'flattrwidget', 'thing');
    //  flattr user the thing belongs to
    $user = get_pconfig( $id, 'flattrwidget', 'user');
    //  title for the flattr button itself
    $title = t('Flattr this!');
    //  construct the link for the button
    $link = 'https://flattr.com/submit/auto?user_id='.$user.'&url=' . rawurlencode($thing).'&title='.rawurlencode($ftitle);
    if ($sd == 'static') {
	//  static button graphic from the img folder
	$img = $a->get_baseurl() .'/addon/flattrwidget/img/flattr-badge-large.png';
	$code = '<a href="'.$link.'" target="_blank"><img src="'.$img.'" alt="'.$title.'" title="'.$title.'" border="0"></a>';
    } else {
	$code = '<script id=\'fbdu5zs\'>(function(i){var f,s=document.getElementById(i);f=document.createElement(\'iframe\');f.src=\'//api.flattr.com/button/view/?uid='.$user.'&url='.rawurlencode($thing).'&title='.rawurlencode($ftitle).'\';f.title=\''.$title.'\';f.height=72;f.width=65;f.style.borderWidth=0;s.parentNode.insertBefore(f,s);})(\'fbdu5zs\');</script>';
	//  dynamic button from flattr API
    }
    //  put the widget content together
    $flattrwidget = '<div id="flattr-widget">'.$code.'</div>';
    //  place the widget into the selected aside area
    $a->set_widget( $title, $flattrwidget, $lr);
}
function flattrwidget_settings_post($a,$s) {
    if(! local_user() || (! x($_POST,'flattrwidget-settings-submit')))
	return;
    $c = $a->get_channel();
    set_pconfig( local_user(), 'flattrwidget', 'align', $_POST['flattrwidget-align'] );
    set_pconfig( local_user(), 'flattrwidget', 'sd', $_POST['flattrwidget-static'] );
    $thing = $_POST['flattrwidget-thing'];
    if ($thing == '') {
	$thing = $a->get_baseurl().'/channel/'.$c['channel_address'];
    }
    set_pconfig( local_user(), 'flattrwidget', 'thing', $thing);
    set_pconfig( local_user(), 'flattrwidget', 'user', $_POST['flattrwidget-user']);
    $ftitle = $_POST['flattrwidget-thingtitle'];
    if ($ftitle == '') {
	$ftitle = $c['channel_name'].' on The Red Matrix';
    }
    set_pconfig( local_user(), 'flattrwidget', 'title', $ftitle);
    set_pconfig( local_user(), 'flattrwidget', 'enable', intval($_POST['flattrwidget-enable']));
    info(t('Flattr widget settings updated.').EOL);
}
function flattrwidget_settings(&$a,&$s) {
    $id = local_user();
    if (! $id)
	return;

    $a->page['htmlhead'] .= '<link rel="stylesheet" href="'.$a->get_baseurl().'/addon/flattrwidget/style.css'.'" media="all" />';
    $lr = get_pconfig( $id, 'flattrwidget', 'align');
    $sd = get_pconfig( $id, 'flattrwidget', 'sd');
    $thing = get_pconfig( $id, 'flattrwidget', 'thing');
    $user = get_pconfig( $id, 'flattrwidget', 'user');
    $ftitle = get_pconfig( $id, 'flattrwidget', 'title');
    $enable = intval(get_pconfig(local_user(),'flattrwidget','enable'));
    $enable_checked = (($enable) ? ' checked="checked" ' : '');

    $s .= '<div class="settings-block">';
    $s .= '<h3>Flattr Widget '.t('Settings').'</h3>';
    $s .= '<div id="flattrwidget-settings-wrapper">';
    $s .= '<label id="flattrwidget-user-label" for="flattrwidget-user">' . t('flattr user'). '</label>';
    $s .= '<input id="flattrwidget-user" type="text" name="flattrwidget-user" value="'.$user.'" />';
    $s .= '<div class="clear"></div>';
    $s .= '<label id="flattrwidget-thing-label" for="flattrwidget-thing">' . t('URL of the Thing to flattr (if empty channel URL is used)'). '</label>';
    $s .= '<input id="flattrwidget-thing" type="text" name="flattrwidget-thing" value="'.$thing.'" />';
    $s .= '<div class="clear"></div>';
    $s .= '<label id="flattrwidget-thingtitle-label" for="flattrwidget-thingtitle">' . t('Title of the Thing (if empty "channel name on The Red Matrix" will be used)'). '</label>';
    $s .= '<input id="flattrwidget-thingtitle" type="text" name="flattrwidget-thingtitle" value="'.$ftitle.'" />';
    $s .= '<div class="clear"></div>';
    $s .= '<label id="flattrwidget-static-label" for="flattrwidget-static">' . t('Static or dynamic flattr button'). '</label>';
    $s .= '<select name="flattrwidget-static" id="flattrwidget-static">';
    if ($sd=='static') {
	$s .= '<option value="static" selected>'.t('static').'</option>';
	$s .= '<option value)"dynamic">'.t('dynamic').'</option>';
    } else {
	$s .= '<option value="static">'.t('static').'</option>';
	$s .= '<option value)"dynamic" selected>'.t('dynamic').'</option>';
    }
    $s .= '</select>';
    $s .= '<div class="clear"></div>';
    $s .= '<label id="flattrwidget-align-label" for="flattrwidget-align">' . t('Alignment of the widget'). '</label>';
    $s .= '<select name="flattrwidget-align" id="flattrwidget-align">';
    if ($lr=='aside') {
	$s .= '<option value="aside" selected>'.t('left').'</option>';
	$s .= '<option value="right_aside">'.t('right').'</option>';
    } else {
	$s .= '<option value="aside">'.t('left').'</option>';
	$s .= '<option value="right_aside" selected>'.t('right').'</option>';
    }
    $s .= '</select>';
    $s .= '<div class="clear"></div>';
    $s .= '<label id="flattrwidget-enable-label" for="flattrwidget-enable">' . t('Enable Flattr widget') . '</label>';
    $s .= '<input id="flattrwidget-enable" type="checkbox" name="flattrwidget-enable" value="1" ' . $enable_checked . '/>';
    
    $s .= '<div class="clear"></div>';
    $s .= '</div>';
    $s .= '<div class="settings-submit-wrapper" ><input type="submit" name="flattrwidget-settings-submit" class="settings-submit" value="' . t('Submit') . '" /></div>';
    $s .= '</div>';
}
