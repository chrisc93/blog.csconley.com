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

// If this file is called directly, abort executing.
if ( ! defined( 'WPINC' ) ) { exit; }

require_once(dirname(__FILE__).'/whois.php');

/*
	Display lockouts in dashboard for admins
*/
function cerber_show_lockouts(){
	global $wpdb;
	cerber_block_garbage_collector();

	$per_page = cerber_get_pp();

	//$per_page = absint($per_page);
	$limit = (cerber_get_pn() - 1) * $per_page.','.$per_page;

	if ($rows = $wpdb->get_results('SELECT * FROM '. CERBER_BLOCKS_TABLE . ' ORDER BY block_until DESC LIMIT '.$limit)) {
		$total=$wpdb->get_var('SELECT count(ip) FROM '. CERBER_BLOCKS_TABLE);
		$list=array();
		$base_url = admin_url(cerber_get_opage('activity'));
		$assets_url = plugin_dir_url(CERBER_FILE).'assets/';
		foreach ($rows as $row) {
			$ip = '<a href="'.$base_url.'&filter_ip='.$row->ip.'">'.$row->ip.'</a>';

			//$ip_id = str_replace('.','-',$row->ip);
			//$ip_id = str_replace(':','_',$ip_id); // IPv6
			//if (($ip_info = unserialize(get_transient($ip_id))) && isset($ip_info['hostname'])) $hostname = $ip_info['hostname'];
			//else $hostname = '<img data-ip-id="'.$ip_id .'" class="crb-no-hn" src="'.$assets_url.'ajax-loader-ip.gif" />'."\n";

			$ip_info = cerber_get_ip_info($row->ip,true);
			if (isset($ip_info['hostname'])) $hostname = $ip_info['hostname'];
			else {
				$ip_id = cerber_get_id_ip($row->ip);
				$hostname = '<img data-ip-id="'.$ip_id .'" class="crb-no-hn" src="'.$assets_url.'ajax-loader-ip.gif" />'."\n";
			}

			$list[]='<td>'.$ip.'</td><td>'.$hostname.'</td><td>'.cerber_date($row->block_until).'</td><td>'.$row->reason.'</td><td><a href="'.wp_nonce_url(add_query_arg(array('lockdelete'=>$row->ip)),'control','cerber_nonce').'">'.__('Remove','cerber').'</a></td>';

		}
		$titles = '<tr><th>'.__('IP','cerber').'</th><th>'.__('Hostname','cerber').'</th><th>'.__('Expires','cerber').'</th><th>'.__('Reason','cerber').'</th><th></th></tr>';
		$table = '<table class="widefat crb-table"><thead>'.$titles.'</thead><tfoot>'.$titles.'</tfoot>'.implode('</tr><tr>',$list).'</tr></table>';
		$table .= cerber_page_navi($total,$per_page);

		//echo '<h3>'.sprintf(__('Showing last %d records from %d','cerber'),count($rows),$total).'</h3>';
		$showing = '<h3>'.sprintf(__('Showing last %d records from %d','cerber'),count($rows),$total).'</h3>';
		echo '<p>'.$table;

		$view = '<p><b>'.__('Hint','cerber').':</b> ' . __('To view activity, click on the IP','cerber').'</p>';
	}
	else $view = '<p>'.sprintf(__('No lockouts at the moment. The sky is clear.','cerber')).'</p>';
	echo '<div class="cerber-margin">'.$view.'</div>';
}

/*
	ACL management form in dashboard
*/
function cerber_acl_form(){
	global $wp_cerber;
	echo '<h3>'.__('White IP Access List','cerber').'</h3><p>'.__('These IPs will never be locked out','cerber').'</p>'.cerber_acl_get_table('W');
	echo '<h3>'.__('Black IP Access List','cerber').'</h3><p>'.__('Nobody can log in from these IPs','cerber').'</p>'.cerber_acl_get_table('B');
	echo '<p><b>'.__('Your IP','cerber').': '.$wp_cerber->getRemoteIp().'</b></p>';
	echo '<p>'.__('Note: You can add a subnet Class C with the format like this: xxx.xxx.xxx.*','cerber').'</p>';
}
/*
	Create HTML to display ACL area: table + form
*/
function cerber_acl_get_table($tag){
	global $wpdb;
	$activity_url = admin_url(cerber_get_opage('activity'));
	if ($rows = $wpdb->get_results('SELECT * FROM '. CERBER_ACL_TABLE . " WHERE tag = '".$tag."' ORDER BY ip")) {
		foreach ($rows as $row) $list[]='<td>'.$row->ip.'</td><td><a class="delete_entry" href="javascript:void(0)" data-ip="'.$row->ip.'">'.__('Remove','cerber').'</a></td><td><a href="'.$activity_url.'&filter_ip='.$row->ip.'">'.__('Check for activity','cerber').'</a></td>';
		$ret = '<table id="acl_'.$tag.'" class="acl_table"><tr>'.implode('</tr><tr>',$list).'</tr></table>';
	}
	else $ret='<p><i>'.__('List is empty','cerber').'</i></p>';
	$ret = '<div class="acl_wrapper"><div class="acl_manager">'.$ret.'</div><form action="" method="post"><p><input type="text" name="add_acl_'.$tag.'"> <input type="submit" class="button button-primary" value="'.__('Add IP to the list','cerber').'" ></p>'.wp_nonce_field('cerber_dashboard','cerber_nonce').'</form></div>';
	return $ret;
}
/*
	Handle actions with items in ACLs in the dashboard
*/
add_action('admin_init','cerber_acl_form_process');
function cerber_acl_form_process(){
	if (!current_user_can('manage_options')) return;
	if (!isset($_POST['cerber_nonce']) || !wp_verify_nonce($_POST['cerber_nonce'],'cerber_dashboard')) return;
	if ($_SERVER['REQUEST_METHOD']=='POST') {
		if (isset($_POST['add_acl_W']) && $ip = trim($_POST['add_acl_W'])) {
			if ( cerber_is_ip_or_net($ip) && cerber_add_white($ip)) update_site_option('cerber_admin_message',sprintf(__('Address %s was added to White IP Access List','cerber'),$ip));
		}
		if (isset($_POST['add_acl_B']) && $ip = trim($_POST['add_acl_B'])) {
			if (cerber_is_ip_or_net($ip)) {
				if (!cerber_is_myip($ip)) { // Protection from adding IP of current user
					if (cerber_add_black($ip)) update_site_option('cerber_admin_message',sprintf(__('Address %s was added to Black IP Access List','cerber'),$ip));
				}
				else update_site_option('cerber_admin_notice',__("You can't add your IP address",'cerber').' '.$ip);
			}
		}
	}
}
/*
	Get all entries from access lists
*/
function cerber_acl_all($fields='*'){
	global $wpdb;
	return $wpdb->get_results('SELECT '.$fields.' FROM '. CERBER_ACL_TABLE , ARRAY_N);
}

/*
	AJAX admin requests is landing here
*/
add_action('wp_ajax_cerber_ajax', 'cerber_admin_ajax');
function cerber_admin_ajax() {
	global $wpdb;
	if (!current_user_can('manage_options')) return;
	$response = array();
	if (isset($_REQUEST['acl_delete'])){
		check_ajax_referer('delete-ip','ajax_nonce');
		$ip = $_REQUEST['acl_delete'];
		if (!cerber_is_ip_or_net($ip)) wp_die();
		if (cerber_acl_remove($ip)) $response['deleted_ip'] = $ip;
	}
	elseif (isset($_REQUEST['get_hostnames'])){
		$list = array_unique($_REQUEST['get_hostnames']);
		foreach ($list as $ip_id) {
			$ip = cerber_get_ip_id($ip_id);
			$ip_info = cerber_get_ip_info($ip);
			$response[$ip_id] = $ip_info['hostname'];

			/*if (($ip_info = unserialize(get_transient($ip_id))) && isset($ip_info['hostname'])) $response[$ip_id] = $ip_info['hostname'];
			else {
				$ip = str_replace('-','.',$ip_id);
				$ip = str_replace('_',':',$ip); // IPv6
				$hostname = @gethostbyaddr($ip);
				if ($hostname) {
					set_transient($ip_id, serialize(array('hostname' => $hostname)), 24 * 3600);
					$response[$ip_id] = $hostname;
				}
				else $response[$ip_id] = __('unknown','cerber');
			}
			*/
		}
	}
	echo json_encode($response);
	wp_die();
}
/*
 * Retrieve extended IP information
 * @since 2.2
 *
 */
function cerber_get_ip_info($ip, $use_cache = false){
	$ip_id = str_replace('.','-',$ip);
	$ip_id = str_replace(':','_',$ip_id); // IPv6
	$ip_info = @unserialize(get_transient($ip_id)); // lazy way
	if ($use_cache) return $ip_info;
	if (!isset($ip_info['hostname'])) {
		$ip_info = array();
		$hostname = @gethostbyaddr( $ip );
		if ( $hostname ) {
			$ip_info['hostname'] = $hostname;
		} else {
			$ip_info['hostname'] = __( 'unknown', 'cerber' );
		}
		set_transient( $ip_id, serialize( array( 'hostname' => $hostname ) ), 24 * 3600 );
	}
	return $ip_info;
}
/*
 * Get IP from ip_id
 * @since 2.2
 *
 */
function cerber_get_ip_id($ip_id){
	$ip = str_replace('-','.',$ip_id,$count);
	if (!$count) $ip = str_replace('_',':',$ip); // IPv6
	return $ip;
}
/*
 * Get ip_id from IP
 * @since 2.2
 *
 */
function cerber_get_id_ip($ip){
	$ip_id = str_replace('.','-',$ip,$count);
	if (!$count) $ip_id = str_replace(':','_',$ip_id); // IPv6
	return $ip_id;
}
/*
	Admin's actions with GET requests is handled here
*/
add_action('admin_init','cerber_admin_request');
function cerber_admin_request(){
	global $wpdb;
	if (!current_user_can('manage_options')) return;
	if ($_SERVER['REQUEST_METHOD']!='GET' || !isset($_GET['cerber_nonce']) || !wp_verify_nonce($_GET['cerber_nonce'],'control')) return;

	if (isset($_GET['testnotify'])) {
		cerber_send_notify($_GET['testnotify']);
		update_site_option('cerber_admin_message',__('Message has been sent to ','cerber').' '.cerber_get_email());
		wp_safe_redirect(remove_query_arg('testnotify')); // mandatory!
		exit; // mandatory!
	}
	if (isset($_GET['lockdelete'])) {
		$ip = $_GET['lockdelete'];
		if (cerber_block_delete($ip)) update_site_option('cerber_admin_message',sprintf(__('Lockout for %s was removed','cerber'),$ip));
	}
	if (isset($_GET['citadel']) && $_GET['citadel']=='deactivate') {
		cerber_disable_citadel();
	}
	if (isset($_GET['load_settings']) && $_GET['load_settings']=='default') {
		cerber_load_defaults();
		update_site_option('cerber_admin_message',__('Settings saved.'));
		wp_safe_redirect(remove_query_arg('load_settings')); // mandatory!
		exit; // mandatory!
	}
}

/*
 * Display activities in dashboard for admins
 * @since 1.0
 *
 */
function cerber_show_activity(){
	global $wpdb,$activity_msg,$blog_id;
	$labels = cerber_get_labels('activity');
	$base_url = admin_url(cerber_get_opage('activity'));
	$per_page = cerber_get_pp();
	$where = array();
	$falist = array();

	if (isset($_GET['filter_activity'])) { // Multiple activities can be requested this way: &filter_activity[]=11&filter_activity[]=7
		$filter = $_GET['filter_activity'];
		if (is_array($filter)) {
			$falist = array_filter(array_map('absint',$filter));
			$filter = implode(',',$falist);
		}
		else {
			$filter = absint($filter);
			$falist = array($filter); // for further using in links
		}
		$where[] = 'activity IN ('.$filter.')';
	}

	$ip_text='';
	if (isset($_GET['filter_ip'])) {
		$filter = $_GET['filter_ip'];
		if (strrchr($filter,'*')) $where[] = $wpdb->prepare('ip LIKE %s',str_replace('*','%',$filter)); // * means subnet, so we need LIKE
		else $where[] = $wpdb->prepare('ip = %s',$filter);
		$ip_text = cerber_ip_extra_view($filter);
	}

	if (isset($_GET['filter_login'])) {
		$where[] = $wpdb->prepare('user_login = %s',$_GET['filter_login']);
	}
	if (isset($_GET['filter_user'])) {
		$where[] = $wpdb->prepare('user_id= %d',$_GET['filter_user']);
	}
	if (!empty($where)) $where = 'WHERE '.implode(' AND ',$where); 
	else $where = '';

	$per_page = absint($per_page);
	$limit = (cerber_get_pn() - 1) * $per_page.','.$per_page;

	if ($rows = $wpdb->get_results('SELECT SQL_CALC_FOUND_ROWS * FROM '. CERBER_LOG_TABLE . " $where ORDER BY stamp DESC LIMIT $limit")) {
		$total=$wpdb->get_var("SELECT FOUND_ROWS()");
		$assets_url = plugin_dir_url(CERBER_FILE).'assets/';
		$list=array();
		foreach ($rows as $row) {
			if ($row->user_id) {
				$u=get_userdata($row->user_id);
				$name = '<a href="'.$base_url.'&filter_user='.$row->user_id.'">'.$u->display_name.'</a>';
			}
			else $name='';

			$ip = '<a href="'.$base_url.'&filter_ip='.$row->ip.'">'.$row->ip.'</a>';
			$username = '<a href="'.$base_url.'&filter_login='.urlencode($row->user_login).'">'.$row->user_login.'</a>';

			$ip_info = cerber_get_ip_info($row->ip,true);
			if (isset($ip_info['hostname'])) $hostname = $ip_info['hostname'];
			else {
				$ip_id = cerber_get_id_ip($row->ip);
				$hostname = '<img data-ip-id="'.$ip_id .'" class="crb-no-hn" src="'.$assets_url.'ajax-loader-ip.gif" />'."\n";
			}

			$tip='';
			$acl = cerber_acl_check($row->ip);
			if ($acl == 'W') $tip = __('White IP Access List','cerber');
			elseif ($acl == 'B') $tip = __('Black IP Access List','cerber');
			if (cerber_block_check($row->ip)) {
				$block='ip-blocked';
				$tip .= ' '.__('Locked out','cerber');
			}
			else $block='';

			$list[]='<td><div class="act-icon ip-acl'.$acl.' '.$block.'" title="'.$tip.' now"></div>'.$ip.'</td><td>'.$hostname.'</td><td>'.cerber_date($row->stamp).'</td><td><span class="actv'.$row->activity.'">'.$labels[$row->activity].'</span></td><td>'.$name.'</td><td>'.$username.'</td>';
		}
		$titles = '<tr><th><div class="act-icon"></div>'.__('IP','cerber').'</th><th>'.__('Hostname','cerber').'</th><th>'.__('Date','cerber').'</th><th>'.__('Activity','cerber').'</th><th>'.__('Local User','cerber').'</th><th>'.__('Username used','cerber').'</th></tr>';
		$table='<table id="crb-activity" class="widefat crb-table"><thead>'.$titles.'</thead><tfoot>'.$titles.'</tfoot><tbody><tr>'.implode('</tr><tr>',$list).'</tr></tbody></table>';

		$table .= cerber_page_navi($total,$per_page);
		//$legend  = '<p>'.sprintf(__('Showing last %d records from %d','cerber'),count($rows),$total);
		$info = $ip_text;
	}
	else {
		$info = '<p>'.__('No activity has been logged.','cerber').'</p>';
		$table = '';
	}

	// Filter activity by ...
	foreach ($labels as $tag => $label) {
		if (in_array($tag,$falist)) $links[] = '<b>'.$label.'</b>';
		else $links[] = '<a href="'.$base_url.'&filter_activity='.$tag.'">'.$label.'</a>';
	}
	$filters = '<p>'.__('Show only','cerber').': '.implode(' | ',$links).'</p>';

	echo '<div class="cerber-margin">'.$filters.$info.'</div>';

	echo $table;

}
/*
 * Detailed information about IP address
 * @since 2.7
 *
 */
function cerber_ip_extra_view($ip){
	global $wp_cerber;
	if (!cerber_is_ip_or_net($ip)) return '';
	$tip = '';
	$acl = cerber_acl_check($ip);
	if ($acl == 'W') $tip = __('White IP Access List','cerber');
	elseif ($acl == 'B') $tip = __('Black IP Access List','cerber');
	if (cerber_block_check($ip)) {
		$tip .= ' '.__('Locked out','cerber');
	}
	if ($tip) $tip = ' - '.$tip;

	// Filter activity by ...

	/*$labels = cerber_get_labels('activity');
	foreach ($labels as $tag => $label) {
		//if (in_array($tag,$falist)) $links[] = '<b>'.$label.'</b>';
		$links[] = '<a href="'.$base_url.'&filter_activity='.$tag.'">'.$label.'</a>';
	}
	$filters = implode(' | ',$links);*/

	$form = '';
	if (!cerber_is_myip($ip) && !cerber_acl_check($ip)) $form = '<form action="" method="post"><input type="hidden" name="add_acl_B" value="'.$ip.'"><input type="submit" class="button button-primary" value="'.__('Add IP to the Black List','cerber').'" >'.wp_nonce_field('cerber_dashboard','cerber_nonce').'</form>';

	$whois = '';
	$country = '';
	if (cerber_get_options('ip_extra')) {
		$ip_info = ip_readable_info($ip);
		if (isset($ip_info['whois'])) $whois = '<div id="whois">' . $ip_info['whois'] . '</div>';
		if (isset($ip_info['error'])) $whois = '<div id="whois">' . $ip_info['error'] . '</div>';
		if (isset($ip_info['country'])) $country = $ip_info['country'];
	}

	$ret = '<div id="ip-extra"><div style="line-height: 28px; font-size: 110%; float: left;"><span style="font-weight: bold;">IP: '.$ip.' '.$country.'</span> '.$tip.'</div>'.$filters.'<div style="float:right;">'.$form.'</div></div>';

	return $ret.$whois;
}
/*
 * Sets of human readable labels for vary activity/logs events
 * @since 1.0
 *
 */
function cerber_get_labels($type){
	$labels = array();
	if ($type == 'activity') {
		$labels[5]=__('Logged in','cerber');
		$labels[6]=__('Logged out','cerber');
		$labels[7]=__('Login failed','cerber');
		$labels[10]=__('IP blocked','cerber');
		$labels[11]=__('Subnet blocked','cerber');
		$labels[12]=__('Citadel activated!','cerber');
		$labels[13]=__('Locked out','cerber');
		$labels[14]=__('IP blacklisted','cerber');
		$labels[20]=__('Password changed','cerber');
	}
	return $labels;
}

/*
	Add admin menu & network admin bar link
*/
if (!is_multisite()) add_action('admin_menu', 'cerber_admin_menu');
else add_action('network_admin_menu', 'cerber_admin_menu'); // only network wide menu allowed in multisite mode
function cerber_admin_menu(){
	global $cerber_screen;
	if (!is_multisite()) $target = 'options-general.php';
	else $target = 'settings.php';
	$cerber_screen = add_submenu_page($target,__('WP Cerber Settings','cerber'),__('WP Cerber','cerber'),'manage_options','cerber-settings', 'cerber_settings_page');
}
add_action( 'admin_bar_menu', 'cerber_admin_bar' );
function cerber_admin_bar( $wp_admin_bar ) {
	if (!is_multisite()) return;
	$args = array(
		'parent' => 'network-admin',
		'id'    => 'cerber_admin',
		'title' => __('WP Cerber','cerber'),
		'href'  => admin_url(cerber_get_opage()),
	);
	$wp_admin_bar->add_node( $args );
}
/*
	Check if on the WP Cerber dashboard page
*/
function cerber_is_my_page(){
	$screen = get_current_screen();
	if ($screen->parent_base == 'plugins') return true;
	if ($screen->parent_base == 'options-general') return true;
	if ($screen->parent_base == 'settings') return true;
	return false;
}

/*
 *
 * United options 2.0. Looks crazy? Because of Settings API! :-(
 * @since 2.0
 *
 *
 */
//add_action('admin_init', 'cerber_united_update');
/*
function cerber_united_update() {
	if ($_SERVER['REQUEST_METHOD']!='POST' || !isset($_POST['action']) || $_POST['action'] != 'update') return;
	if (false === strpos($_POST['option_page'],'cerberus-')) return;
	if (!current_user_can('manage_options')) return;

	// preparing some data...
	//$options=array('cerber-main','cerber-hardening'); // list of tabs (sections/groups of Settings API used with register_setting())
	$options=array(CERBER_OPT,CERBER_OPT_H); // list of tabs (sections/groups of Settings API used with register_setting())
	$opt_name = 'cerber-'.substr($_POST['option_page'],9); // 8 = length of 'cerberus-'
	unset($options[array_search($opt_name,$options)]);
	$default = cerber_get_defaults();
	$version = $default['version'];
	$default = array_map(function(){ return false; },$default);
	$default['version'] = $version; // preserve version

	// preparing is finished, we are ready to go
    $united = array_merge($default,$_POST[$opt_name]);

    foreach ($options as $opt) {
        $o = get_site_option($opt);
        if (!is_array($o)) continue;
        $united = array_merge($united,$o); // add old values from other sections/tabs to preserve it
    }
    if (is_multisite()) { // Settings API doesn't work here!
        $old = (array)get_site_option(CERBER_OPTIONS);
        $united = cerber_sanitize_options($united,$old);
        update_site_option($opt_name,$_POST[$opt_name]);
    }
    update_site_option(CERBER_OPTIONS,$united);
}
*/
/*
	Add custom columns to the Users screen
*/
add_filter('manage_users_columns' , 'cerber_u_columns');
function cerber_u_columns($columns) {
	return array_merge( $columns,
          	array('cbcc' => __('Comments','cerber'),
          	'cbla' => __('Last login','cerber') ,
          	'cbfl' => __('Failed attempts in last 24 hours','cerber'),
          	'cbdr' => __('Date of registration','cerber')) );
}
add_filter( 'manage_users_sortable_columns','cerber_u_sortable');
function cerber_u_sortable($sortable_columns) {
	$sortable_columns['cbdr']='user_registered';
	return $sortable_columns;
}
/*
	Display custom columns on the Users screen
*/
add_filter( 'manage_users_custom_column' , 'cerber_show_users_columns', 10, 3 );
function cerber_show_users_columns($value, $column, $user_id) {
	global $wpdb,$current_screen,$user_login;
	$ret = $value;
	switch ($column) {
		case 'cbcc' : // to get this work we need add filter 'preprocess_comment'
			if ($com = get_comments(array('author__in' => $user_id)))	$ret = count($com);
			else $ret = 0;
		break;
		case 'cbla' :
			$ret = $wpdb->get_var('SELECT MAX(stamp) FROM '.CERBER_LOG_TABLE.' WHERE user_id = '.$user_id);
			if ($ret) {
				$act_link = admin_url(cerber_get_opage().'&tab=activity');
				$gmt_offset=get_option('gmt_offset')*3600;
				$tf=get_option('time_format');
				$df=get_option('date_format');
				$ret = '<a href="'.$act_link.'&filter_user='.$user_id.'">'.date($df.' '.$tf, $gmt_offset + $ret).'</a>';
			}
			else $ret=__('Never','cerber');
		break;
		case 'cbfl' :
			$act_link = admin_url(cerber_get_opage().'&tab=activity');
			$u=get_userdata($user_id);
			$failed = $wpdb->get_var('SELECT count(user_id) FROM '.CERBER_LOG_TABLE.' WHERE user_login = \''.$u->user_login.'\' AND activity = 7 AND stamp > ' . (time() - 24 * 3600));
			$ret = '<a href="'.$act_link.'&filter_login='.$u->user_login.'&filter_activity=7">'.$failed.'</a>';
		break;
		case 'cbdr' :
			$time=strtotime($wpdb->get_var("SELECT user_registered FROM  $wpdb->users WHERE id = ".$user_id));
			$gmt_offset=get_option('gmt_offset')*3600;
			$tf=get_option('time_format');
			$df=get_option('date_format');
			$ret = date($df.' '.$tf, $gmt_offset + $time);
		break;
	}
	return $ret;
}

/*
	Show Tools screen
*/
function cerber_show_tools(){
	global $wpdb;
	$form = '<h3>'.__('Export settings to the file','cerber').'</h3>';
	$form .= '<p>'.__('When you click the button below you will get a configuration file, which you can upload on another site.','cerber').'</p>';
	$form .= '<p>'.__('What do you want to export?','cerber').'</p><form action="" method="get">';
	$form .= '<input id="exportset" name="exportset" value="1" type="checkbox" checked> <label for="exportset">'.__('Settings','cerber').'</label>';
	$form .= '<p><input id="exportacl" name="exportacl" value="1" type="checkbox" checked> <label for="exportacl">'.__('Access Lists','cerber').'</label>';
	$form .= '<p><input type="submit" name="cerber_export" id="submit" class="button button-primary" value="'.__('Download file','cerber').'"></form>';

	$form .= '<h3 style="margin-top:2em;">'.__('Import settings from the file','cerber').'</h3>';
	$form .= '<p>'.__('When you click the button below, file will be uploaded and all existing settings will be overridden.','cerber').'</p>';
	$form .= '<p>'.__('Select file to import.','cerber').' '. sprintf( __( 'Maximum upload file size: %s.'), esc_html(size_format(wp_max_upload_size())));
	$form .= '<form action="" method="post" enctype="multipart/form-data">';
	$form .= '<p><input type="file" name="ifile" id="ifile">';
	$form .= '<p>'.__('What do you want to import?','cerber').'</p><p><input id="importset" name="importset" value="1" type="checkbox" checked> <label for="importset">'.__('Settings','cerber').'</label>';
	$form .= '<p><input id="importacl" name="importacl" value="1" type="checkbox" checked> <label for="importacl">'.__('Access Lists','cerber').'</label>';
	$form .= '<p><input type="submit" name="cerber_import" id="submit" class="button button-primary" value="'.__('Upload file').'"></form>';
	echo $form;

	?>
	<h3 style="margin-top: 2em;">Diagnostic</h3>
    <input type="button" class="button button-primary" value="Show diagnostic information"
           onclick="toggle_visibility('diagnostic'); return false;" /><p>
		<form id="diagnostic" style="display: none; margin-top: 2em;">
            <?php echo cerber_check_itself(); ?>
            <h4>System info</h4>
			<textarea style="width: 100%; height: 400px;" name="dia"><?php
                echo 'PHP version: ' . phpversion()."\n";
                foreach ($_SERVER as $key => $value) {
                    if ($key == 'HTTP_COOKIE') continue;
                    //echo '['.$key.'] => '. htmlspecialchars($value)."\n";
                    echo '['.$key.'] => '. strip_tags($value)."\n";
                }
                ?>
			</textarea>
		</form>
	<script type="text/javascript">
	function toggle_visibility(id) {
       var e = document.getElementById(id);
       if(e.style.display == 'block')
          e.style.display = 'none';
       else
          e.style.display = 'block';
    }
	</script>
	<?php
}
/*
	Create export file
*/
add_action('admin_init','cerber_export');
function cerber_export(){
	global $wpdb;
	if ($_SERVER['REQUEST_METHOD']!='GET' || !isset($_GET['cerber_export'])) return;
	if (!current_user_can('manage_options')) wp_die('Error!');
	$p = cerber_plugin_data();
	$data = array('cerber_version' => $p['Version'],'home'=> get_home_url(),'date'=>date('d M Y H:i:s'));
	//if ($_GET['exportset']) $data ['options'] = (array)get_site_option(CERBER_OPTIONS);
	if ($_GET['exportset']) $data ['options'] = cerber_get_options(); // @since 2.0
	if ($_GET['exportacl'])	$data ['acl'] = cerber_acl_all('ip,tag,comments');
	$file = json_encode($data);
	$file .= '==/'.strlen($file).'/'.crc32($file).'/EOF';
	header($_SERVER["SERVER_PROTOCOL"].' 200 OK');
	header("Content-type: application/force-download");
	header("Content-Type: application/octet-stream");
	header("Content-Disposition: attachment; filename=wpcerber.config");
	echo $file;
	exit;
}
/*
	Load and Parse file and then import settings
*/
add_action('admin_init','cerber_import');
function cerber_import(){
	global $wpdb;
	if ($_SERVER['REQUEST_METHOD']!='POST' || !isset($_POST['cerber_import'])) return;
	if (!current_user_can('manage_options')) wp_die('Upload failed.');
	$ok = true;
	if (!is_uploaded_file($_FILES['ifile']['tmp_name'])) {
		update_site_option('cerber_admin_notice',__('No file was uploaded or file is corrupted','cerber'));
		return;
	}
	elseif ($file = file_get_contents($_FILES['ifile']['tmp_name'])) {
		$p = strrpos($file,'==/');
		$data = substr($file,0,$p);
		$sys = explode('/',substr($file,$p));
		if ($sys[3] == 'EOF' && crc32($data) == $sys[2] && $data = json_decode($data, true)) {

			if ($_POST['importset'] && $data['options'] && is_array($data['options']) && !empty($data['options'])) {
				$data['options']['loginpath'] = urldecode($data['options']['loginpath']); // needed to work filter cerber_sanitize_options()
				cerber_save_options($data['options']); // @since 2.0
			}

			if ($_POST['importacl'] && $data['acl'] && is_array($data['acl']) && !empty($data['acl'])) {
				$acl_ok = true;
				if (false === $wpdb->query("DELETE FROM ".CERBER_ACL_TABLE)) $acl_ok = false;
				foreach($data['acl'] as $row) {
					// if (!$wpdb->query($wpdb->prepare('INSERT INTO '.CERBER_ACL_TABLE.' (ip,tag,comments) VALUES (%s,%s,%s)',$row[0],$row[1],$row[2]))) $acl_ok = false;
					if (!$wpdb->insert(CERBER_ACL_TABLE,array('ip'=>$row[0],'tag'=>$row[1],'comments'=>$row[2]),array('%s','%s','%s'))) $acl_ok = false;
				}
				if (!$acl_ok) update_site_option('cerber_admin_notice',__('Error while updating','cerber').' '.__('Access Lists','cerber'));
			}

			update_site_option('cerber_admin_message',__('Settings has imported successfully from','cerber').' '.$_FILES['ifile']['name']);
		}
		else $ok = false;
	}
	if (!$ok) update_site_option('cerber_admin_notice',__('Error while parsing file','cerber'));
}

/*
 	Registering widgets
*/
if (!is_multisite()) add_action( 'wp_dashboard_setup', 'cerber_widgets' );
else add_action( 'wp_network_dashboard_setup', 'cerber_widgets' );
function cerber_widgets() {
	if (!current_user_can('manage_options')) return;
	if (current_user_can( 'manage_options')) {
		wp_add_dashboard_widget( 'cerber_quick', __('Cerber Quick View','cerber'), 'cerber_quick_w');
	}
}
/*
	Cerber Quick View widget
*/
function cerber_quick_w(){
	global $current_user,$wpdb;
	$set = admin_url(cerber_get_opage());
	$act = admin_url(cerber_get_opage('activity'));
	$acl = admin_url(cerber_get_opage('acl'));
	$loc = admin_url(cerber_get_opage('lockouts'));
	//$midnight = strtotime('today');
	$opt = cerber_get_options();
	$failed = $wpdb->get_var('SELECT count(ip) FROM '. CERBER_LOG_TABLE .' WHERE activity IN (7) AND stamp > '.(time() - 24 * 3600));
	$failed_prev = $wpdb->get_var('SELECT count(ip) FROM '. CERBER_LOG_TABLE .' WHERE activity IN (7) AND stamp > '.(time() - 48 * 3600).' AND stamp < '.(time() - 24 * 3600));

	$failed_ch = cerber_percent($failed_prev,$failed);

	$locked = $wpdb->get_var('SELECT count(ip) FROM '. CERBER_LOG_TABLE .' WHERE activity IN (10,11) AND stamp > '.(time() - 24 * 3600));
	$locked_prev = $wpdb->get_var('SELECT count(ip) FROM '. CERBER_LOG_TABLE .' WHERE activity IN (10,11) AND stamp > '.(time() - 48 * 3600).' AND stamp < '.(time() - 24 * 3600));

	$locked_ch = cerber_percent($locked_prev,$locked);

	$lockouts = $wpdb->get_var('SELECT count(ip) FROM '. CERBER_BLOCKS_TABLE);
	if ($last = $wpdb->get_var('SELECT MAX(stamp) FROM '.CERBER_LOG_TABLE.' WHERE  activity IN (10,11)')) {
		$last = cerber_date($last);
	}
	else $last = __('Never','cerber');
	$w_count = $wpdb->get_var('SELECT count(ip) FROM '. CERBER_ACL_TABLE .' WHERE tag ="W"' );
	$b_count = $wpdb->get_var('SELECT count(ip) FROM '. CERBER_ACL_TABLE .' WHERE tag ="B"' );

	if (cerber_is_citadel()) $citadel = '<span style="color:#FF0000;">'.__('active','cerber').'</span> (<a href="'.wp_nonce_url(add_query_arg(array('citadel' => 'deactivate')),'control','cerber_nonce').'">'.__('deactivate','cerber').'</a>)';
	else {
		if (cerber_get_options('ciperiod')) $citadel = __('not active','cerber');
		else $citadel = __('disabled','cerber');
	}

	echo '<div class="cerber-widget">';

	echo '<table style="width:100%;"><tr><td style="width:50%; vertical-align:top;"><table><tr><td class="bigdig">'.$failed.'</td><td class="per">'.$failed_ch.'</td></tr></table><p>'.__('failed attempts','cerber').' '.__('in 24 hours','cerber').'<br/>(<a href="'.$act.'&filter_activity=7">'.__('view all','cerber').'</a>)</p></td>';
	echo '<td style="width:50%; vertical-align:top;"><table><tr><td class="bigdig">'.$locked.'</td><td class="per">'.$locked_ch.'</td></tr></table><p>'.__('lockouts','cerber').' '.__('in 24 hours','cerber').'<br/>(<a href="'.$act.'&filter_activity[]=10&filter_activity[]=11">'.__('view all','cerber').'</a>)</p></td></tr></table>';

	echo '<table id="quick-info"><tr><td>'.__('Lockouts at the moment','cerber').'</td><td>'.$lockouts.'</td></tr>';
	echo '<tr><td>'.__('Last lockout','cerber').'</td><td>'.$last.'</td></tr>';
	echo '<tr><td style="padding-top:15px;">'.__('White IP Access List','cerber').'</td><td style="padding-top:15px;"><b>'.$w_count.' '._n('entry','entries',$w_count,'cerber').'</b></td></tr>';
	echo '<tr><td>'.__('Black IP Access List','cerber').'</td><td><b>'.$b_count.' '._n('entry','entries',$b_count,'cerber').'</b></td></tr>';
	echo '<tr><td style="padding-top:15px;">'.__('Citadel mode','cerber').'</td><td style="padding-top:15px;"><b>'.$citadel.'</b></td></tr>';
	echo '</table></div>';

	echo '<div class="wilinks">
	<a href="'.$act.'"><span class="dashicons dashicons-welcome-view-site"></span> ' . __('Activity','cerber').'</a> |
	<a href="'.$loc.'"><span class="dashicons dashicons-shield"></span> ' . __('Lockouts','cerber').'</a> |
	<a href="'.$acl.'"><span class="dashicons dashicons-admin-network"></span> ' . __('Access Lists','cerber').'</a> |
	<a href="'.$set.'"><span class="dashicons dashicons-admin-settings"></span> ' . __('Settings','cerber').'</a>
	</div>';
	if ($msg = cerber_update_check())	echo '<div class="up-cerber">'.$msg.'</div>';
}

function cerber_percent($one,$two){
	if ($one == 0) {
		if ($two > 0) $ret = '100';
		else $ret = '0';
	}
	else {
		$ret = round (((($two - $one)/$one)) * 100);
	}
	$style='';
	if ($ret < 0) $style='color:#008000';
	elseif ($ret > 0) $style='color:#FF0000';
	if ($ret > 0)	$ret = '+'.$ret;
	return '<span style="'.$style.'">'.$ret.' %</span>';
}

/*
	Show Help tab screen
*/
function cerber_show_help() {
	if ( in_array( get_locale(), array( 'uk', 'ru_RU' ) ) ) {
		$help = '<h3>Поддержка на русском языке</h3>Если вам нужна помощь на русском, напишите на электронную почту <a href="mailto:wpcerber@gmail.com?subject=WP Cerber Russian Support">wpcerber@gmail.com</a>.';
	} else {
		$help = '';
	}
	$assets_url = plugin_dir_url( CERBER_FILE ) . 'assets';
	?>
	<div style="margin: 10px;">

		<?php echo $help; ?>

		<h3>What is Drill down IP?</h3>

		<p>
			To get extra information like country, company, network info and abuse contact for IP the WP Cerber uses requests to the limited set of external WHOIS servers which are maintained by appropriate Registry. All Registry are accredited by ICANN,  so there are no reasons for security concerns. Retrieved information isn't storing in the database, but it is caching for 24 hours to avoid excessive requests and get fast response.
		</p>
		<p><a href="http://wpcerber.com?p=194">Read more in the Security Blog</a></p>

		<h3>Do you have a question or need help?</h3>

		<p>Support is provided on the WordPress forums for free, though please note that it is free support hence it is
			not always possible to answer all questions on a timely manner, although we do try.</p>

		<p><a href="http://wordpress.org/support/plugin/wp-cerber">Get answer on support forum</a>.</p>

		<h3>Do you have an idea for a cool new feature you would love to see in WP Cerber?</h3>

		<p>
			Feel free to submit your ideas here: <a href="http://wpcerber.com/new-feature-request/">New Feature Request</a>.
		</p>

		<h3>Are you ready to translate this plugin into your language?</h3>

		<p>We would appreciate that! Please, <a href="http://wpcerber.com/support/">notify us</a> or use Loco Translate
			plugin.</p>

		<h3 style="margin: 40px 0 40px 0;">Check out other plugins from the trusted author</h3>

		<div>

			<a href="https://wordpress.org/plugins/plugin-inspector/">

				<img src="<?php echo $assets_url . '/inspector.png' ?>"
				     style="float: left; width: 128px; margin-right: 20px;"/>
			</a>
			<h3>Plugin for inspecting code of plugins on your site: <a href="https://wordpress.org/plugins/plugin-inspector/">Plugin Inspector</a></h3>
			<p style="font-size: 110%">The Plugin Inspector plugin is an easy way to check plugins installed on your WordPress and make sure
				that plugins does not use deprecated WordPress functions and some unsafe functions like eval,
				base64_decode, system, exec etc. Some of those functions may be used to load malicious code (malware)
				from the external source directly to the site or WordPress database.
			</p>
			<p style="font-size: 110%">Plugin Inspector allows you to view all the deprecated functions complete with path, line number,
				deprecation function name, and the new recommended function to use. The checks are run through a simple
				admin page and all results are displayed at once. This is very handy for plugin developers or anybody
				who want to know more about installed plugins.
			</p>
		</div>

		<div style="margin: 40px 0 40px 0;">
			<a href="https://wordpress.org/plugins/goo-translate-widget/">
				<img src="<?php echo $assets_url . '/goo-translate.png' ?>"
				     style="float: left; width: 128px; margin-right: 20px;"/>
			</a>

			<h3>Plugin to quick translate site: <a href="https://wordpress.org/plugins/goo-translate-widget/">Google
					Translate Widget</a></h3>
			<p style="font-size: 110%">Google Translate Widget expands your global reach quickly and easily. Google Translate is a free
				multilingual machine translation service provided by Google to translate websites. And now you can allow
				visitors around of the world to get your site in their native language. Just put widget on the sidebar
				with one click.</p>

		</div>

	</div>
	<?php
}


/*
	Admin aside bar
*/
function cerber_show_aside($page){

	$aside = array();
	if (!in_array($page,array('main','acl','messages','tools','help','hardening'))) return;
	if (in_array($page,array('main','hardening'))) {
		$aside[]='<div class="crb-box">
			<h3>'.__('Confused about some settings?','cerber').'</h3>'
			.__('You can easily load default recommended settings using button below','cerber').'
			<p style="text-align:center;">
				<input type="button" class="button button-primary" value="'.__('Load default settings','cerber').'" onclick="button_default_settings()" />
				<script type="text/javascript">function button_default_settings(){
					if (confirm("'.__('Are you sure?','cerber').'")) {
						click_url = "'.wp_nonce_url(add_query_arg(array('load_settings'=>'default')),'control','cerber_nonce').'";
						window.location = click_url.replace(/&amp;/g,"&");
					}
				}</script>
			</p>
			<p><i>* '.__("doesn't affect Custom login URL and Access Lists",'cerber').'</i></p>
		</div>';
	}
	if (in_array($page,array('main','acl','messages','tools','help','hardening'))) {
		$aside[]='<div class="crb-box">
			<h3><span class="dashicons-before dashicons-lightbulb"></span> '.__('Read our blog','cerber').'</h3>
			<p><a href="http://wpcerber.com/hardening-wordpress-with-wp-cerber/" target="_blank">Hardening WordPress with WP Cerber</a>
			<p><a href="http://wpcerber.com/know-more-about-intruder-ip-address/" target="_blank">Know more about intruder IP address</a>
			<p><a href="http://wpcerber.com/how-to-protect-wordpress-with-fail2ban/" target="_blank">How to protect WordPress with Fail2Ban</a>
			<p><a href="http://wpcerber.com/hardening-wordpress-with-wp-cerber-and-nginx/" target="_blank">Hardening WordPress with WP Cerber and NGINX</a>
			<p><a href="http://wpcerber.com/wordpress-website-has-been-hacked/" target="_blank">What to do if your WordPress site has been hacked</a>
		</div>';
		$aside[]='<div class="crb-box">
			<h3>'.__('Donate','cerber').'</h3>
			<p>Please consider making a donation to support the continued development and support of this plugin. Any help is greatly appreciated. Thanks!</p>
			<div style="text-align:center;">
			<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
			<input type="hidden" name="cmd" value="_s-xclick">
			<input type="hidden" name="hosted_button_id" value="SR8RJXFU35EW8">
			<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG_global.gif" border="0" name="submit" alt="PayPal – The safer, easier way to pay online.">
			<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
			</form>
			</div>
		</div>';
	}
	echo '<div id="crb-aside">'.implode(' ',$aside).'</div>';
}

/*
	Just notices in dashboard
*/
add_action( 'admin_notices', 'cerber_admin_notice' , 9999 );
add_action( 'network_admin_notices', 'cerber_admin_notice' , 9999 );
function cerber_admin_notice(){
	if (cerber_is_citadel() && current_user_can('manage_options')) {
		echo '<div class="update-nag crb-alarm"><p>'.
		__('Attention! Citadel mode is now active. Nobody is able to login.','cerber').
		' &nbsp; <a href="'.wp_nonce_url(add_query_arg(array('citadel' => 'deactivate')),'control','cerber_nonce').'">'.__('Deactivate','cerber').'</a>'.
		' | <a href="'.admin_url(cerber_get_opage().'&tab=activity').'">'.__('View Activity','cerber').'</a>'.
		'</p></div>';
	}
	if (!cerber_is_my_page()) return;
	cerber_update_check();
	if ($notices = get_site_option('cerber_admin_notice'))
		echo '<div class="update-nag crb-note"><p>'.$notices.'</p></div>'; // class="updated" - green, class="update-nag" - yellow and above the page title,
	if ($notices = get_site_option('cerber_admin_message'))
		echo '<div class="updated crb-msg" style="overflow: auto;"><p>'.$notices.'</p></div>'; // class="updated" - green, class="update-nag" - yellow and above the page title,
	update_site_option('cerber_admin_notice','');
	update_site_option('cerber_admin_message','');
}

/*
	Check for new version of plugin and create message if needed
*/
function cerber_update_check() {
	$ret = false;
	if ( $updates = get_site_transient( 'update_plugins' ) ) {
		$key = cerber_plug_in();
		if ( isset( $updates->checked[ $key ] ) && isset( $updates->response[ $key ] ) ) {
			$old = $updates->checked[ $key ];
			$new = $updates->response[ $key ]->new_version;
			if ( 1 === version_compare( $new, $old ) ) {
				// current version is lower than latest
				$ret = __( 'New version is available', 'cerber' ) . ' <span class="dashicons dashicons-arrow-right"></span>';
				if ( is_multisite() ) {
					$href = network_admin_url( 'plugins.php?plugin_status=upgrade' );
				} else {
					$href = admin_url( 'plugins.php?plugin_status=upgrade' );
				}
				$msg = '<b>' . $ret . '</b> <a href="' . $href . '">' . sprintf( __( 'Update to version %s of WP Cerber', 'cerber' ), $new ) . '</a>';
				update_site_option( 'cerber_admin_message', $msg );
				$ret = '<a href="' . $href . '">' . $ret . '</a>';
			}
		}
	}
	return $ret;
}

/*
	Pagination
*/
function cerber_page_navi($total,$per_page = 20){
	$max_links = 10;
	$page = cerber_get_pn();
	$last_page = ceil($total / $per_page);
	$ret = '';
	if($last_page > 1){
		$start =1 + $max_links * intval(($page-1)/$max_links);
		$end = $start + $max_links - 1;
		if ($end > $last_page) $end = $last_page;
		if ($start > $max_links) $links[]='<a disabled="disabled" href="'.esc_url(add_query_arg('pagen',$start - 1)).'" >&laquo;</a>';
		for ($i=$start; $i <= $end; $i++) {
			if($page!=$i) $links[]='<a href="'.esc_url(add_query_arg('pagen',$i)).'" >'.$i.'</a>';
			else $links[]='<span class="cupage">'.$i.'</span> ';
		}
		if($end < $last_page) $links[]='<a href="'.esc_url(add_query_arg('pagen',$i)).'" >&raquo;</a>';
		$ret = '<div class="tablenav"><div class="tablenav-pages cerber-margin" style="float:left;">'.$total.' '._n('entry','entries',$total,'cerber').' &nbsp; '.implode(' ',$links).'</div></div>';
	}
	return $ret;
}
function cerber_get_pn(){
	$page = 1;
	if (isset($_GET['pagen'])) {
		$page = (int)$_GET['pagen'];
		if(!$page) $page = 1;
	}
	return $page;
}
/*
	Plugins screen links
*/
add_filter('plugin_action_links','cerber_action_links',10,4);
function cerber_action_links($actions, $plugin_file, $plugin_data, $context){
	if($plugin_file == cerber_plug_in()){
		$link[] = '<a href="'.admin_url(cerber_get_opage()).'">' . __('Settings') . '</a>';
		$link[] = '<a href="'.admin_url(cerber_get_opage().'&tab=acl').'">' . __('Access Lists','cerber') . '</a>';
		$actions = array_merge ($link,$actions);
	}
	return $actions;
}
/*
 * Checks state of the art
 * @since 2.7.2
 *
 */
function cerber_check_itself(){
    global $wpdb,$wp_cerber;
    $list = array();
    if (!$wpdb->get_row("SHOW TABLES LIKE '".CERBER_LOG_TABLE."'")) $list[] = 'Table '.CERBER_LOG_TABLE.' not found!';
	/*else {
        //show table status like 'cerber_blocks' 
		$columns = $wpdb->get_results("SHOW FULL COLUMNS FROM ".CERBER_LOG_TABLE);
		foreach ($columns as $column) {
            foreach ($column as $key => $value) {
                $field_data[] = '<tr><td>'.$key.'</td><td>'.$value.'</td>';
            }
		}
        echo '<table>'.implode('',$field_data).'</table>';
	}*/
    if (!$wpdb->get_row("SHOW TABLES LIKE '".CERBER_ACL_TABLE."'")) $list[] = 'Table '.CERBER_ACL_TABLE.' not found!';
    if (!$wpdb->get_row("SHOW TABLES LIKE '".CERBER_BLOCKS_TABLE."'")) $list[] = 'Table '.CERBER_BLOCKS_TABLE.' not found!';
    if ($wp_cerber->getRemoteIp() == '127.0.0.1') $list[] = 'It seems that we are unable to get IP addresses.';
    if ($list) return '<h4>Below are some problems with Cerber plugin</h4>'.implode('<p>',$list);
}

/*
function add_some_pointers() {
	?>
	<script type="text/javascript">
		jQuery(document).ready( function($) {
			var options = {'content':'<h3>Info</h3><p>Cerber will require RIPE database for extra information when you will click on IP.</p>','position':{'edge':'left','align':'center'}};
			if ( ! options ) return;
			options = $.extend( options, {
				close: function() {
					//to do
				}
			});
			$("#ip_extra").click(function(){
				$(this).pointer( options ).pointer('open');
			});
		});
	</script>
	<?php
}
add_action('admin_enqueue_scripts', 'cerber_admin_enqueue');
function cerber_admin_enqueue($hook) {
	wp_enqueue_style( 'wp-pointer' );
	wp_enqueue_script( 'wp-pointer' );
}
*/

/*
	Some admin styles & JS
*/
add_action('admin_head','cerber_admin_head');
function cerber_admin_head(){
	$assets_url = plugin_dir_url(CERBER_FILE).'assets';
	?>
	<style type="text/css" media="all">
	/* Common */
	.crb-main {
		width: auto;
		overflow: hidden;
	}

	.cerber-margin {
		margin-left: 10px;
	}
	.cupage {
		padding:10pt;
		font-weight:bold;
	}
	
	#crb-aside {
		float:right;
		width:290px;
		margin: 1em 0;
	}
	@media (max-width: 1000px) {
		#crb-aside {
			display:none;
		}
	}
	#crb-aside .crb-box {
		background-color:#fff;
		border: 1px solid #E5E5E5;
		box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
		padding:0 1em 2em 1em;
		margin-bottom:1em;
	}

	/* Messages */
	.crb-alarm {
		display:block;  
		border-left: 6px solid #ff0000;
		/*
		background-color:#FF5E3C; 
		color:#fff;*/
	}	
	.crb-alarm a {
		font-weight:bold;
	}	
	.crb-note > p::before {  /*  content: "\e62e";  content: "\f339";  */ }
	
	/* Tables */
	.crb-table tr:nth-child(even) {background: #f9f9f9}
	.crb-table tr:nth-child(odd) {background: #FFF}
	.crb-table td {
		vertical-align: middle;
	}

	/* Activity */

	.act-icon {
		display: inline-block;
		vertical-align: middle;
		width: 1em;
		height: 1em;
		margin-right: 1em;
		background-color: inherit;
	}
	.ip-aclB {
		background-color: #000;
	}
	.ip-aclW {
		background-color: #83CE77;
	}
	.ip-blocked {
		background-color: #FF5733;
	}

	.green_label, .actv5 {
		display:inline-block;
		padding:3px 5px 3px 5px;
		margin:1px;
		background-color:#83CE77;
		color:#000;
		/*border-radius: 5px;*/
		border-left: 4px solid rgba(0, 0, 0, 0);
		border-right: 4px solid rgba(0, 0, 0, 0);
	}
	.red_label, .actv10, .actv11, .actv12 {
		display:inline-block;
		padding:3px 5px 3px 5px;
		margin:1px;
		background-color:#FF5733;
		color:#000;
		/*border-radius: 5px;*/
		border-left: 4px solid rgba(0, 0, 0, 0);
		border-right: 4px solid rgba(0, 0, 0, 0);
	}
	.yellow_label, .actv13, .actv14 {
		display:inline-block;
		padding:3px 5px 3px 5px;
		margin:1px;
		background-color:#FFFF80;
		color:#000;
		/*border-radius: 5px;*/
		border-left: 4px solid rgba(0, 0, 0, 0);
		border-right: 4px solid rgba(0, 0, 0, 0);
	}
	.actv10, .actv11, .actv12 {
		border-left: 4px solid rgba(0, 0, 0, .25);
	}
	/* ACL */
	.acl_wrapper {
		margin-bottom:30px;
	}
	.acl_manager {
		/*
		max-height:500px;
		min-width:30%;
		overflow: auto;
		display:inline-block;
		*/
	}
	.acl_table {
		border: 1px solid #aaa;
		background-color:#fff;
		min-width:30%;
	}
	.acl_table td {
		padding:6px;
		background-color:#eee;
	}
	.acl_table tr td:nth-child(1) {
		width:60%;
	}
	.acl_table tr td:nth-child(2) {
		width:20%;
		text-align:center;
	}
	.acl_table tr td:nth-child(2) {
		width:20%;
		text-align:center;
	}
	/* Tabs */
	.cerber-tabs span.dashicons {
		display: inline-block;
		vertical-align: middle;
		line-height: 18px;
	}
	.cerber-tabs .nav-tab-active {
		color: #804040;
		color: rgb(0, 103, 153);
	}

	/* Preserve line-height for tabs */
	.cerber-tabs sup,
	.cerber-tabs sub {
		height: 0;
		line-height: 1;
		vertical-align: baseline;
		_vertical-align: bottom;
		position: relative;
	}
	.cerber-tabs sup {
		bottom: 1ex;
	}
	.cerber-tabs sub {
		top: .5ex;
	}
	/* Users */
	#cbcc, .cbcc, #cbfl, .cbfl {
		text-align:center;
	}

	/* Widgets */
	#cerber_quick .inside {
		padding:0;
 		background-image: url("<?php echo $assets_url; ?>/bgwidget.png");
		background-repeat: no-repeat;
		background-position: right top;
	}
	.cerber-widget {
  	border-bottom-width: 1px;
  	border-bottom-style: solid;
  	border-bottom-color: #eeeeee;
  	padding: 4px 12px 12px;

  }
	.cerber-widget .bigdig {
		font-size: 250%;
	}
	#quick-info td {
		padding: 0 8px 6px 0;
		font-size:110%;
	}
	.cerber-widget td.per {
		vertical-align:middle;
		padding-left:5px;
	}
	.wilinks, .up-cerber {
		padding: 12px;
		text-align: center;
	}
	.wilinks a {
		white-space: nowrap;
	}
	.up-cerber {
		background-color: #804040;
		font-size:110%;
		color: #fff;
	}
	.up-cerber a {
		color: #fff;
		display:block;
	}
	/* Ip extra, whois */
	#ip-extra{
		overflow: auto;
		/*margin-bottom: 10px;*/
		padding: 15px 25px;
		background-color: #f8f8f8;
		border: solid 1px #ddd;
		border-bottom: none;
	}
	div#whois {
		border: solid 1px #ddd;
		background-color: #fff;
		margin-bottom: 50px;
		padding: 15px 25px;
		overflow: auto;
		max-height: 200px;
		transition: max-height 0.2s ease-in;
	}
	div#whois:hover{
		max-height: 500px;
		transition: max-height 0.2s ease-out;
	}
	.whois-object{
		min-width: 20%;
		max-width: 50%;
		float: left;
		margin-right: 20px;
		background-color: #fff;
		border-left: #ddd solid 2px;
	}
	.whois-object tr td:first-of-type{
		padding-left:10px;
	}
	.whois-object tr td:last-child{
		padding-right:10px;
	}
	.raw pre{
		white-space: pre-wrap;       /* css-3 */
	}

	</style>
	<?php
}
/*
 * Stuff for footer
 *
 */
add_action('admin_footer','cerber_basement');
function cerber_basement(){
	//add_some_pointers();
	$assets_url = plugin_dir_url(CERBER_FILE).'assets';
	$ajax_nonce = wp_create_nonce('delete-ip');
	?>
	<script type="text/javascript">
		jQuery(document).ready(function($) {

			$(".delete_entry").click(function() {
				/* if (!confirm('<?php _e('Are you sure?','cerber') ?>')) return; */
				$.post(ajaxurl,{
						action: 'cerber_ajax',
						acl_delete: $(this).data('ip'),
						ajax_nonce: '<?php echo $ajax_nonce; ?>'
					},
					onDeleteSuccess
				);
				/*$(this).parent().parent().fadeOut(500);*/
				/* $(this).closest("tr").FadeOut(500); */
			});
			function onDeleteSuccess(server_data) {
				var cerber_response =  $.parseJSON(server_data);
				$('.delete_entry[data-ip="'+cerber_response['deleted_ip']+'"]').parent().parent().fadeOut(300);
			}

			if ($(".crb-table").length) {
				function setHostNames(server_data) {
					var hostnames =  $.parseJSON(server_data);
					$(".crb-table .crb-no-hn").each(function(index) {
						$(this).replaceWith(hostnames[$(this).data('ip-id')]);
					});
				}
				var ip_list = $(".crb-table .crb-no-hn").map(
					function () {
						return $(this).data('ip-id');
					}
				);
				if (ip_list.length != 0) $.post(ajaxurl,{ action:'cerber_ajax', get_hostnames:ip_list.toArray() }, setHostNames);

			}

		});
	</script>
	<?php
}
