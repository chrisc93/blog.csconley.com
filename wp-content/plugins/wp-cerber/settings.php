<?php
/*
 	Copyright (C) 2015-16 Gregory Markov, http://wpcerber.com

    Licenced under the GNU GPL

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

define('CERBER_OPT','cerber-main'); // tab 1
define('CERBER_OPT_H','cerber-hardening'); // tab 2
//define('CERBER_OPTIONS','cerberus');


/*
	WP Settings API
*/
add_action('admin_init', 'cerber_settings_init');
function cerber_settings_init(){

	// Main Settings tab

	$tab='main'; // 'cerber-main' settings
	register_setting( 'cerberus-'.$tab, 'cerber-'.$tab );

	add_settings_section('cerber', __('Limit login attempts','cerber'), 'cerberus_section_main', 'cerber-'.$tab);
	add_settings_field('attempts',__('Attempts','cerber'),'cerberus_field_show','cerber-'.$tab,'cerber',array('group'=>$tab,'option'=>'attempts','type'=>'attempts'));
	add_settings_field('lockout',__('Lockout duration','cerber'),'cerberus_field_show','cerber-'.$tab,'cerber',array('group'=>$tab,'option'=>'lockout','type'=>'text','label'=>__('minutes','cerber'),'size'=>3));
	add_settings_field('aggressive',__('Aggressive lockout','cerber'),'cerberus_field_show','cerber-'.$tab,'cerber',array('group'=>$tab,'type'=>'aggressive'));
	add_settings_field('notify',__('Notifications','cerber'),'cerberus_field_show','cerber-'.$tab,'cerber',array('group'=>$tab,'type'=>'notify','option'=>'notify'));
	add_settings_field('proxy',__('Site connection','cerber'),'cerberus_field_show','cerber-'.$tab,'cerber',array('group'=>$tab,'option'=>'proxy','type'=>'checkbox','label'=>__('My site is behind a reverse proxy','cerber')));

	add_settings_section('proactive', __('Proactive security rules','cerber'), 'cerberus_section_proactive', 'cerber-'.$tab);
	add_settings_field('subnet',__('Block subnet','cerber'),'cerberus_field_show','cerber-'.$tab,'proactive',array('group'=>$tab,'option'=>'subnet','type'=>'checkbox','label'=>__('Always block entire subnet Class C of intruders IP','cerber')));
	add_settings_field('nonusers',__('Non-existent users','cerber'),'cerberus_field_show','cerber-'.$tab,'proactive',array('group'=>$tab,'option'=>'nonusers','type'=>'checkbox','label'=>__('Immediately block IP when attempting to login with a non-existent username','cerber')));
	add_settings_field('noredirect',__('Redirect dashboard requests','cerber'),'cerberus_field_show','cerber-'.$tab,'proactive',array('group'=>$tab,'option'=>'noredirect','type'=>'checkbox','label'=>__('Disable automatic redirecting to the login page when /wp-admin/ is requested by an unauthorized request','cerber')));
	add_settings_field('wplogin',__('Request wp-login.php','cerber'),'cerberus_field_show','cerber-'.$tab,'proactive',array('group'=>$tab,'option'=>'wplogin','type'=>'checkbox','label'=>__('Immediately block IP after any request to wp-login.php','cerber')));

	add_settings_section('custom', __('Custom login page','cerber'), 'cerberus_section_custom', 'cerber-'.$tab);
	add_settings_field('loginpath',__('Custom login URL','cerber'),'cerberus_field_show','cerber-'.$tab,'custom',array('group'=>$tab,'option'=>'loginpath','type'=>'text','label'=>__('must not overlap with the existing pages or posts slug','cerber')));
	add_settings_field('loginnowp',__('Disable wp-login.php','cerber'),'cerberus_field_show','cerber-'.$tab,'custom',array('group'=>$tab,'option'=>'loginnowp','type'=>'checkbox','label'=>__('Block direct access to wp-login.php and return HTTP 404 Not Found Error','cerber')));

	add_settings_section('citadel', __('Citadel mode','cerber'), 'cerberus_section_citadel', 'cerber-'.$tab);
	add_settings_field('citadel',__('Threshold','cerber'),'cerberus_field_show','cerber-'.$tab,'citadel',array('group'=>$tab,'type'=>'citadel'));
	add_settings_field('ciduration',__('Duration','cerber'),'cerberus_field_show','cerber-'.$tab,'citadel',array('group'=>$tab,'option'=>'ciduration','type'=>'text','label'=>__('minutes','cerber'),'size'=>3));
	//add_settings_field('ciwhite',__('Whitelist','cerber'),'cerberus_field_show','cerber-'.$tab,'citadel',array('group'=>$tab,'option'=>'ciwhite','type'=>'checkbox','label'=>__('Allow whitelist in Citadel mode','cerber')));
	add_settings_field('cinotify',__('Notifications','cerber'),'cerberus_field_show','cerber-'.$tab,'citadel',array('group'=>$tab,'option'=>'cinotify','type'=>'checkbox','label'=>__('Send notification to admin email','cerber').' (<a href="'.wp_nonce_url(add_query_arg(array('testnotify'=>'citadel')),'control','cerber_nonce').'">'.__('Click to send test','cerber').'</a>)'));

	add_settings_section('notify', __('Notifications','cerber'), 'cerberus_section_activity', 'cerber-'.$tab);
	$def_email = '<b>'.get_site_option('admin_email').'</b>';
	add_settings_field('email',__('Email Address'),'cerberus_field_show','cerber-'.$tab,'notify',array('group'=>$tab,'option'=>'email','type'=>'text','label'=>sprintf(__('if empty, the admin email %s will be used','cerber'),$def_email)));

	add_settings_section('activity', __('Activity','cerber'), 'cerberus_section_activity', 'cerber-'.$tab);
	add_settings_field('keeplog',__('Keep records for','cerber'),'cerberus_field_show','cerber-'.$tab,'activity',array('group'=>$tab,'option'=>'keeplog','type'=>'text','label'=>__('days','cerber'),'size'=>3));
	//$http = _wp_http_get_object();
	//if ($http->block_request(RIPE_HOST)) {}
	add_settings_field('ip_extra',__('Drill down IP','cerber'),'cerberus_field_show','cerber-'.$tab,'activity',array('group'=>$tab,'option'=>'ip_extra','type'=>'checkbox','label'=>__('Retrieve extra WHOIS information for IP','cerber').' <a href="'.admin_url(cerber_get_opage('help')).'">Know more</a>'));
	add_settings_field('usefile',__('Use file','cerber'),'cerberus_field_show','cerber-'.$tab,'activity',array('group'=>$tab,'option'=>'usefile','type'=>'checkbox','label'=>__('Write failed login attempts to the file','cerber')));


	// Hardening tab

	$tab='hardening'; // 'cerber-hardening' settings
	register_setting( 'cerberus-'.$tab, 'cerber-'.$tab );
	add_settings_section('hwp', __('Hardening WordPress','cerber'), 'cerberus_section_hardening', 'cerber-'.$tab);
	add_settings_field('stopenum',__('Stop user enumeration','cerber'),'cerberus_field_show','cerber-'.$tab,'hwp',array('group'=>$tab,'option'=>'stopenum','type'=>'checkbox','label'=>__('Block access to the pages like /?author=n','cerber')));
	add_settings_field('xmlrpc',__('Disable XML-RPC','cerber'),'cerberus_field_show','cerber-'.$tab,'hwp',array('group'=>$tab,'option'=>'xmlrpc','type'=>'checkbox','label'=>__('Block access to the XML-RPC server (including Pingbacks and Trackbacks)','cerber')));
	add_settings_field('nofeeds',__('Disable feeds','cerber'),'cerberus_field_show','cerber-'.$tab,'hwp',array('group'=>$tab,'option'=>'nofeeds','type'=>'checkbox','label'=>__('Block access to the RSS, Atom and RDF feeds','cerber')));
	add_settings_field('norest',__('Disable REST API','cerber'),'cerberus_field_show','cerber-'.$tab,'hwp',array('group'=>$tab,'option'=>'norest','type'=>'checkbox','label'=>__('Block access to the WordPress REST API','cerber')));
	//add_settings_field('cleanhead',__('Clean up HEAD','cerber'),'cerberus_field_show','cerber-'.$tab,'hwp',array('group'=>$tab,'option'=>'cleanhead','type'=>'checkbox','label'=>__('Remove generator and version tags from HEAD section','cerber')));
	//add_settings_field('ping',__('Disable Pingback','cerber'),'cerberus_field_show','cerber-'.$tab,'hwp',array('group'=>$tab,'option'=>'ping','type'=>'checkbox','label'=>__('Block access to ping functional','cerber')));

}
/*
	Generate HTML for every sections on settings pages
*/
function cerberus_section_main($args){
}
function cerberus_section_proactive($args){
	_e('Make your protection smarter!','cerber');
}
function cerberus_section_custom($args){
	if (!get_option('permalink_structure')) {
		echo '<span style="color:#DF0000;">'.__('Please enable Permalinks to use this feature. Set Permalink Settings to something other than Default.','cerber').'</span>';
	}
	else {
		_e('Be careful when enabling this options. If you forget the custom login URL you will not be able to login.','cerber');
	}
}
function cerberus_section_citadel($args){
	_e("In Citadel mode nobody is able to login. Active users' sessions will not be affected.",'cerber');
}
function cerberus_section_activity($args){
}
function cerberus_section_hardening($args){
	echo __("These settings do not affect hosts from the ",'cerber').' '.__('White IP Access List','cerber');
}
/*
 *
 * Generate HTML for settings page with tabs
 * @since 1.0
 *
 */
function cerber_settings_page(){
	global $wpdb;
	$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'main';
	if (!in_array($active_tab,array('main','acl','activity','lockouts','messages','tools','help','hardening'))) $active_tab = 'main';
	?>
	<div class="wrap">

		<h2><?php _e('Cerber Settings','cerber') ?></h2>
		<h2 class="nav-tab-wrapper cerber-tabs">
			<?php
			echo '<a href="'.admin_url(cerber_get_opage('main')).'" class="nav-tab '. ($active_tab == 'main' ? 'nav-tab-active' : '') .'"><span class="dashicons dashicons-admin-settings"></span> '. __('Main Settings','cerber') .'</a>';

			$total = $wpdb->get_var('SELECT count(ip) FROM '. CERBER_ACL_TABLE);
			echo '<a href="'.admin_url(cerber_get_opage('acl')).'" class="nav-tab '. ($active_tab == 'acl' ? 'nav-tab-active' : '') .'"><span class="dashicons dashicons-admin-network"></span> '. __('Access Lists','cerber').' <sup class="acltotal">'.$total.'</sup></a>';

			echo '<a href="'.admin_url(cerber_get_opage('activity')).'" class="nav-tab '. ($active_tab == 'activity' ? 'nav-tab-active' : '') .'"><span class="dashicons dashicons-welcome-view-site"></span> '. __('Activity','cerber').'</a>';

			$total = $wpdb->get_var('SELECT count(ip) FROM '. CERBER_BLOCKS_TABLE);
			echo '<a href="'.admin_url(cerber_get_opage('lockouts')).'" class="nav-tab '. ($active_tab == 'lockouts' ? 'nav-tab-active' : '') .'"><span class="dashicons dashicons-shield"></span> '. __('Lockouts','cerber').' <sup class="loctotal">'.$total.'</sup></a>';

			echo '<a href="'.admin_url(cerber_get_opage('hardening')).'" class="nav-tab '. ($active_tab == 'hardening' ? 'nav-tab-active' : '') .'"><span class="dashicons dashicons-shield-alt"></span> '. __('Hardening','cerber').'</a>';
			//echo '<a href="'.admin_url(cerber_get_opage().'&tab=messages"').'" class="nav-tab '. ($active_tab == 'messages' ? 'nav-tab-active' : '') .'">'. __('Messages','cerber').'</a>';

			echo '<a href="'.admin_url(cerber_get_opage('tools')).'" class="nav-tab '. ($active_tab == 'tools' ? 'nav-tab-active' : '') .'"><span class="dashicons dashicons-admin-tools"></span> '. __('Tools','cerber').'</a>';

			echo '<a href="'.admin_url(cerber_get_opage('help')).'" class="nav-tab '. ($active_tab == 'help' ? 'nav-tab-active' : '') .'"><span class="dashicons dashicons-editor-help"></span> '. __('Help','cerber').'</a>';
			?>
		</h2>
		<?php

		cerber_show_aside($active_tab);

		echo '<div class="crb-main">';
		if ($active_tab == 'acl') cerber_acl_form();
		elseif ($active_tab == 'activity') cerber_show_activity();
		elseif ($active_tab == 'lockouts') cerber_show_lockouts();
		elseif ($active_tab == 'tools') cerber_show_tools();
		elseif ($active_tab == 'help') cerber_show_help();
		else cerber_show_settings($active_tab);
		echo '</div>';

		$pi = get_file_data(cerber_plugin_file(),array('Version' => 'Version'),'plugin');
		$pi ['time'] = time();
		$pi ['user'] = get_current_user_id();
		update_site_option('_cp_tabs_'.$active_tab,serialize($pi));

		?>
	</div>
	<?php
}
/*
 * Display settings screen (one tab)
 *
 */
function cerber_show_settings($active_tab = null){
	if (is_multisite()) $action =  ''; // Settings API doesn't work in multisite. Post data will be handled in the cerber_ms_update()
	else $action ='options.php';
	// Display form with settings fields via Settings API
	echo '<form method="post" action="'.$action.'">';

	settings_fields( 'cerberus-'.$active_tab ); // option group name, the same as used in register_setting().
	do_settings_sections( 'cerber-'.$active_tab ); // the same as used in add_settings_section()	$page

	submit_button();
	echo '</form>';
}
/*
	Generate HTML for one field on the settings page.
*/
function cerberus_field_show($args){
	$settings = get_site_option('cerber-'.$args['group']);
	if (is_array($settings)) $settings = array_map('esc_html',$settings); // yes, that's it, API settings is a nightmare!
	$value = null;
	if (isset($args['option']) && isset($settings[$args['option']])) $value = $settings[$args['option']];
	$pre='';
	if (isset($args['option']) && ($args['option'] == 'loginnowp' || $args['option'] == 'loginpath') && !get_option('permalink_structure')) $disabled=' disabled="disabled" '; else $disabled='';
	if (isset($args['option']) && $args['option'] == 'loginpath') {
		$pre = rtrim(get_home_url(),'/').'/';
		$value =	urldecode($value);
	}
	switch ($args['type']) {
		case 'attempts':
			$html=sprintf(__('%s allowed retries in %s minutes','cerber'),
				'<input type="text" id="attempts" name="cerber-'.$args['group'].'[attempts]" value="'.$settings['attempts'].'" size="3" maxlength="3" />',
				'<input type="text" id="period" name="cerber-'.$args['group'].'[period]" value="'.$settings['period'].'" size="3" maxlength="3" />');
			break;
		case 'aggressive':
			$html=sprintf(__('Increase lockout duration to %s hours after %s lockouts in the last %s hours','cerber'),
				'<input type="text" id="agperiod" name="cerber-'.$args['group'].'[agperiod]" value="'.$settings['agperiod'].'" size="3" maxlength="3" />',
				'<input type="text" id="aglocks" name="cerber-'.$args['group'].'[aglocks]" value="'.$settings['aglocks'].'" size="3" maxlength="3" />',
				'<input type="text" id="aglast" name="cerber-'.$args['group'].'[aglast]" value="'.$settings['aglast'].'" size="3" maxlength="3" />');
			break;
		case 'notify':
			$html= '<input type="checkbox" id="'.$args['option'].'" name="cerber-'.$args['group'].'['.$args['option'].']" value="1" '.checked(1,$value,false).$disabled.' /> '
			       .__('Notify admin if the number of active lockouts above','cerber').
			       ' <input type="text" id="above" name="cerber-'.$args['group'].'[above]" value="'.$settings['above'].'" size="3" maxlength="3" />'.
			       ' (<a href="'.wp_nonce_url(add_query_arg(array('testnotify'=>'lockout')),'control','cerber_nonce').'">'.__('Click to send test','cerber').'</a>)';
			break;
		case 'citadel':
			$html=sprintf(__('Enable after %s failed login attempts in last %s minutes','cerber'),
				'<input type="text" id="cilimit" name="cerber-'.$args['group'].'[cilimit]" value="'.$settings['cilimit'].'" size="3" maxlength="3" />',
				'<input type="text" id="ciperiod" name="cerber-'.$args['group'].'[ciperiod]" value="'.$settings['ciperiod'].'" size="3" maxlength="3" />');
			break;
		case 'checkbox':
			$html='<input type="checkbox" id="'.$args['option'].'" name="cerber-'.$args['group'].'['.$args['option'].']" value="1" '.checked(1,$value,false).$disabled.' />';
			$html.= ' <label for="'.$args['option'].'">'.$args['label'].'</label>';
			break;
		default:
			if (isset($args['size'])) $size=' size="'.$args['size'].'" maxlength="'.$args['size'].'" '; else $size='';
			$html=$pre.'<input type="text" id="'.$args['option'].'" name="cerber-'.$args['group'].'['.$args['option'].']" value="'.$value.'"'.$disabled.$size.'/>';
			$html.= ' <label for="'.$args['option'].'">'.$args['label'].'</label>';
			break;
	}
	echo $html;
}

/*
	Sanitizing users input for settings
*/
add_filter( 'pre_update_option_'.CERBER_OPT, 'cerber_sanitize_options', 10, 3 );
function cerber_sanitize_options($new,$old,$option){ // $option added in WP 4.4.0

	$new['attempts']=absint($new['attempts']);
	$new['period']=absint($new['period']);
	$new['lockout']=absint($new['lockout']);

	$new['agperiod']=absint($new['agperiod']);
	$new['aglocks']=absint($new['aglocks']);
	$new['aglast']=absint($new['aglast']);

	if (get_option('permalink_structure')) {
		$new['loginpath']=urlencode(str_replace('/','',$new['loginpath']));
		if ($new['loginpath'] && $new['loginpath']!=$old['loginpath']) {
			$href=get_home_url().'/'.$new['loginpath'].'/';
			$url=urldecode($href);
			$msg = __('Attention! You have changed the login URL! The new login URL is','cerber');
			update_site_option('cerber_admin_notice',$msg.': <a href="'.$href.'">'.$url.'</a>');
			cerber_send_notify('newlurl',$msg.': '.$url);
		}
	}
	else {
		$new['loginpath']='';
		$new['loginnowp']=0;
	}

	$new['ciduration']=absint($new['ciduration']);
	$new['cilimit']=absint($new['cilimit']);
	$new['cilimit']= $new['cilimit'] == 0 ? '' : $new['cilimit'];
	$new['ciperiod']=absint($new['ciperiod']);
	$new['ciperiod']= $new['ciperiod'] == 0 ? '' : $new['ciperiod'];
	if (!$new['cilimit']) $new['ciperiod']='';
	if (!$new['ciperiod']) $new['cilimit']='';

	if (!empty($new['email']) && !is_email($new['email'])) {
		$new['email']=$old['email'];
		update_site_option('cerber_admin_notice',__('<strong>ERROR</strong>: please enter a valid email address.'));
	}

	if (absint($new['keeplog']) == 0) $new['keeplog']='';
	return $new;
}

/*
 *
 * Process POST Form for settings screens in multisite mode.
 * Because of Settigns API doesn't work in multisite mode!
 *
 */
if (is_multisite())  add_action('admin_init', 'cerber_ms_update'); // allowed only for network
function cerber_ms_update() {
	if ($_SERVER['REQUEST_METHOD']!='POST' || !isset($_POST['action']) || $_POST['action'] != 'update') {
		return;
	}
	if (!isset($_POST['option_page']) || false === strpos($_POST['option_page'],'cerberus-')) {
		return;
	}
	if (!current_user_can('manage_options')) {
		return;
	}

	$opt_name = 'cerber-'.substr($_POST['option_page'],9); // 8 = length of 'cerberus-'

	$old = (array)get_site_option($opt_name);
	$new = $_POST[$opt_name];
	//$new = cerber_sanitize_options($new,$old);
	$new = apply_filters('pre_update_option_'.$opt_name,$new,$old,$opt_name);
	update_site_option($opt_name,$new);
}
/*
 * 	Default settings
 *
 */
function cerber_get_defaults($field = null) {
	$all_defaults = array(
		CERBER_OPT   => array(
			'attempts' => 3,
			'period'   => 60,
			'lockout'  => 60,
			'agperiod' => 24,
			'aglocks'  => 2,
			'aglast'   => 4,
			'notify'   => 1,
			'above'    => 5,

			'proxy' => 0,

			'subnet'     => 0,
			'nonusers'   => 1,
			'wplogin'    => 0,
			'noredirect' => 1,

			'loginpath' => '',
			'loginnowp' => 0,

			'cilimit'    => 200,
			'ciperiod'   => 30,
			'ciduration' => 60,
			'ciwhite'    => 1,
			'cinotify'   => 1,
			'email'      => '',

			'keeplog' => 30,
			'ip_extra' => 1,
			'usefile' => 0,

		),
		CERBER_OPT_H => array(
			'stopenum' => 1,
			'xmlrpc'   => 1,
			'nofeeds'  => 0,
			'norest'  => 1,
			'cleanhead'  => 1,
		),
	);
	if ( $field ) {
		foreach ( $all_defaults as $option ) {
			if ( isset( $option[ $field ] ) ) {
				return $option[ $field ];
			}
		}
		return false;
	} else {
		return $all_defaults;
	}
}

/*
 *
 * Right way to save Cerber settings outside of wp-admin settings page
 * @since 2.0
 *
 */
function cerber_save_options($options){
	foreach ( cerber_get_defaults() as $option_name => $fields ) {
		$save=array();
		foreach ( $fields as $field_name => $def ) {
			if (isset($options[$field_name])) $save[$field_name]=$options[$field_name];
		}
		if (!empty($save)) {
			$result = update_site_option($option_name,$save);
		}
	}
}

/*
	Right way to access to the Cerber settings
*/
function cerber_get_options($option='') {
	$options = array( CERBER_OPT, CERBER_OPT_H );
	$united  = array();
	foreach ( $options as $opt ) {
		$o = get_site_option( $opt );
		if (!is_array($o)) continue;
		$united = array_merge( $united, $o );
	}
	$options = $united;
	//$options = get_site_option( CERBER_OPTIONS );
	if ( ! empty( $option ) ) {
		if ( isset( $options[ $option ] ) ) {
			return $options[ $option ];
		} else {
			return false;
		}
	}
	return $options;
}
/*
	Email for notification
*/
function cerber_get_email() {
	if (!$email = cerber_get_options('email'))	$email = get_site_option('admin_email');
	return $email;
}
/*
	Right way to access to the Cerber settings
*/
function cerber_load_defaults() {
	$save = array();
	foreach ( cerber_get_defaults() as $option_name => $fields ) {
		foreach ( $fields as $field_name => $def ) {
			$save[ $field_name ] = $def;
		}
	}
	$old = cerber_get_options();
	$save['loginpath'] = $old['loginpath'];
	cerber_save_options( $save );
}
/*
	Return link to the admin pages
*/
function cerber_get_opage($tag=''){
	if (!is_multisite()) $target = 'options-general.php'; // must use admin_url();
	else $target = 'network/settings.php';	 // must use network_admin_url();
	$opage = $target . '?page=cerber-settings';
	if ($tag) $opage .= '&tab='.$tag;
	return $opage;
}

/*
 * Add per screen settings
 * @since 2.1
 *
 */
//add_action("load-$page_hook_suffix); $page_hook_suffix = add_menu_page();
add_action("load-settings_page_cerber-settings", "cerber_screen_options");
function cerber_screen_options() {
	if (!in_array($_GET['tab'],array('lockouts','activity'))) return;
	$args = array(
		'label' => __( 'Number of items per page:' ),
		'default' => 50,
		'option' => 'cerber_screen_'.$_GET['tab'],
	);
	add_screen_option( 'per_page', $args );
}
/*
 * Allows to save options to the user meta
 * @since 2.1
 *
 */
add_filter('set-screen-option', 'cerber_save_screen_option', 10, 3);
function cerber_save_screen_option($status, $option, $value) {
	if ( 'cerber_pp_activity' == $option ) return $value;
	if ( 'cerber_pp_lockouts' == $option ) return $value;
}
/*
 * Retrieve option for current screen
 * @since 2.1
 *
 */
function cerber_get_pp(){
	$screen = get_current_screen();
	$screen_option = $screen->get_option('per_page', 'option');
	$per_page = get_user_meta(get_current_user_id(), $screen_option, true);
	if ( empty ( $per_page) || $per_page < 1 ) {
		$per_page = $screen->get_option( 'per_page', 'default' );
	}
	return $per_page;
}
