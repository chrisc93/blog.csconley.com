<?php
/*
	Plugin Name: WP Cerber
	Plugin URI: http://wpcerber.com
	Description: Protects site against brute force attacks. Comprehensive control of user activity. Restrict login by IP access lists. Limit login attempts. Feel free to contact developer via wpcerber@gmail.com or at the site <a href="http://wpcerber.com">wpcerber.com</a>.
	Author: Gregory M
	Author URI: http://wpcerber.com
	Version: 1.8.1
	Text Domain: cerber
	Domain Path: /languages
	Network: true
*/


define('CERBER_LOG_TABLE','cerber_log');
define('CERBER_ACL_TABLE','cerber_acl');
define('CERBER_BLOCKS_TABLE','cerber_blocks');
define('WP_LOGIN_SCRIPT','wp-login.php');
define('CERBER_OPT','cerber-main');
define('CERBER_REQ_PHP','5.2.0');
define('CERBER_REQ_WP','3.3');
define('CERBER_FILE',__FILE__);


if (defined('WP_ADMIN') || defined('WP_NETWORK_ADMIN')) {
	// Load admin stuff
	require_once('dashboard.php');
}

/*
	Initialize global Cerbers variables
*/
add_action('init', 'cerber_init');
add_action('login_head', 'cerber_init');
function cerber_init(){
	global $cerber_status;
	if ( ! wp_next_scheduled( 'cerber_hourly' ) ) {
  	wp_schedule_event( time(), 'hourly', 'cerber_hourly');
	}
	//if (is_admin()) return;
	$cerber_status=0; // Default: OK!
	if (cerber_is_citadel()) $cerber_status=3;
	elseif (!cerber_is_allowed(cerber_get_ip())) {
		if (cerber_acl_check(cerber_get_ip(),'B')) $cerber_status=1;
		else $cerber_status=2;
	}
}
/*
	Display login form if Custom login URL was requested.
*/
//add_action('after_setup_theme', 'cerber_login_screen'); // @since 1.0
add_action('init', 'cerber_login_screen'); // @since 1.6
function cerber_login_screen(){
	if ($path = cerber_get_options('loginpath')) {
		$request = explode('/',$_SERVER['REQUEST_URI']);
		array_pop($request);
		if ($path == end($request)) {
			require(ABSPATH.WP_LOGIN_SCRIPT); // load default wp-login form
			exit;
		}
	}
}
/*
	Create message to show it above login form for any simply GET
*/
add_action('login_head', 'cerber_login_head'); // hook on GET page with login form
function cerber_login_head(){
	global $error,$cerber_status;
	if ($_SERVER['REQUEST_METHOD']!='GET') return;
	if (!cerber_can_msg()) return;
	if (!cerber_is_allowed(cerber_get_ip())) $error = cerber_get_error_msg();
	elseif ($msg = cerber_get_remain_msg()) $error = $msg;
}
/*
	Replace ANY system messages or add notify above login form if IP not allowed (blocked or locked out)
*/
add_filter('login_errors','cerber_login_errors'); // hook on POST if credentials was wrong
function cerber_login_errors($errors) {
	global $error,$cerber_status;
	if (cerber_can_msg()) {
		if (!cerber_is_allowed(cerber_get_ip())) $errors = cerber_get_error_msg(); // replace for error msg
		elseif (($msg = cerber_get_remain_msg()) && !$error) $errors.='<p>'.$msg; // add for informative msg
	}
	return $errors;
}
/*
	Block authentication for existing user if IP not allowed (blocked or locked out)
*/
add_filter('wp_authenticate_user','cerber_stop_authentication',9999,2);
function cerber_stop_authentication($user, $password){
	cerber_init();
	if (!cerber_is_allowed()) {
		status_header(403);
		$error = new WP_Error();
		$error->add('cerber_wp_error', cerber_get_error_msg());
		return $error;
	}
	return $user;
}
add_filter('shake_error_codes', 'cerber_login_failure_shake'); // Shake it, baby!
function cerber_login_failure_shake($shake_error_codes) {
	$shake_error_codes[] = 'cerber_wp_error';
	return $shake_error_codes;
}
/*
	Replace default login/logout URL for Custom login page URL
*/
add_filter('site_url','cerber_login_logout',9999,4);
add_filter('network_site_url','cerber_login_logout',9999,3);
function cerber_login_logout($url, $path, $scheme , $blog_id = 0){ // $blog_id only for 'site_url'
	if ($login_path = cerber_get_options('loginpath')) {
		$url=str_replace(WP_LOGIN_SCRIPT,$login_path.'/',$url);
	}
	return $url;
}
/*
	Replace default logout redirect URL with Custom login page URL 
*/
add_filter('wp_redirect','cerber_redirect',9999,2);
function cerber_redirect($location,$status){
	if ((0 === strpos($location,WP_LOGIN_SCRIPT.'?')) && $path=cerber_get_options('loginpath')) {
		$loc = explode('?',$location);
		$location=get_home_url().'/'.$path.'/?'.$loc[1];
	}
	return $location;
}
/*
	Direct access to wp-login.php - what will we do?
*/
add_action('init', 'cerber_access_control');
function cerber_access_control(){
	global $current_user,$wp_query;
	if (is_admin()) return;
	if (cerber_acl_check(cerber_get_ip(),'W')) return; // @since 1.7. It's useful for some users
	if ($_SERVER['SCRIPT_NAME'] == '/'.WP_LOGIN_SCRIPT) { // no direct access
		if (cerber_get_options('wplogin')) cerber_block_add(cerber_get_ip());
		if (cerber_get_options('loginnowp')) cerber_404_page();
	}
}
/*
	Redirection control
*/
add_filter( 'wp_redirect', 'cerber_no_redirect',10,2);
function cerber_no_redirect($location, $status){
	global $current_user;
	if ($current_user->ID == 0 && cerber_get_options('noredirect')) {
		$str ='redirect_to='.urlencode(admin_url());
		if (strpos($location,$str)) cerber_404_page();
	}
	return $location;
}

remove_action( 'template_redirect', 'wp_redirect_admin_locations', 1000 ); // don't give intruders a chance to find entrance

/*
	Return Error message in context
*/
function cerber_get_error_msg(){
	global $cerber_status,$wpdb;
	switch ($cerber_status ) {
		case 1:
		case 3: return __('You are not allowed to log in. Ask your administrator for assistance.','cerber');
		case 2:
			$block = cerber_get_block();
			$min = 1 + ($block->block_until - time()) / 60;
			return sprintf( __('You have reached the login attempts limit. Please try again in %d minutes.','cerber'),$min);
			break;
		default: return '';
	}
}
/*
	Return Remain message in context
*/
function cerber_get_remain_msg(){
	global $cerber_status;
	$remain = cerber_get_remain_count();
	if ($remain < cerber_get_options('attempts')) {
		if ($remain == 0) $remain = 1; // with some settings or when lockout was manualy removed, still 1 attempts exists.
		return sprintf(_n('You have only one attempt remaining.', 'You have %d attempts remaining.', $remain, 'cerber'), $remain);
	}
	return false;
}
/*
	Can be message showed?
*/
function cerber_can_msg(){
	if (!isset($_REQUEST['action'])) return true;
	if ($_REQUEST['action'] == 'login') return true;
	return false;
	//if ( !in_array( $action, array( 'postpass', 'logout', 'lostpassword', 'retrievepassword', 'resetpass', 'rp', 'register', 'login' );
}


// Cookies ---------------------------------------------------------------------------------
/*
	Mark user with groove
	@since 1.3
*/
add_action('auth_cookie_valid','cerber_cookie1',10,2);
function cerber_cookie1($cookie_elements = null, $user = null){
	global $current_user;
	if (!$user) {
		get_currentuserinfo();
		$user = $current_user;
	}
	$expire = time() + apply_filters( 'auth_cookie_expiration', 14 * 24 * 3600, $user->ID, true ) + ( 12 * 3600 );
	cerber_set_cookie($expire);
}
/*
	Mark switched user with groove
	@since 1.6
*/
add_action('set_logged_in_cookie', 'cerber_cookie2' ,10 ,5);
function cerber_cookie2($logged_in_cookie, $expire, $expiration, $user_id, $logged_in){
	cerber_set_cookie($expire);
}
function cerber_set_cookie($expire){
	if (!headers_sent()) setcookie('cerber_groove', cerber_get_groove() , $expire + 1, COOKIEPATH);
}

/*
	Mark current user when they logged out
	@since 1.0
*/
add_action('wp_logout', 'cerber_clear_cookie');
function cerber_clear_cookie(){
	if (!headers_sent()) setcookie('cerber_logout', 'ok', time() + 24 * 3600, COOKIEPATH);
}
/*
	Track BAD cookies with non-existence user or bad password (hash)
*/
add_action('auth_cookie_bad_username', 'cerber_cookie_bad');
add_action('auth_cookie_bad_hash', 'cerber_cookie_bad');
function cerber_cookie_bad($cookie_elements) {
	cerber_login_failed($cookie_elements['username']);
	wp_clear_auth_cookie();
}
/*
	Block authentication by cookie if IP not allowed (blocked or locked out)
*/
add_action('plugins_loaded','cerber_stop_cookies');
function cerber_stop_cookies($cookie_elements) {
	if (cerber_check_groove()) return; // keep already logged in users
	if (!cerber_is_allowed(cerber_get_ip())) wp_clear_auth_cookie();
}
/*
	Get special Cerber Sign for using with cookies
*/
function cerber_get_groove(){
	$groove = get_site_option('cerber-groove');
	if (empty($groove)) {
		$groove = wp_generate_password(16,false);
		update_site_option('cerber-groove',$groove);
	}
	return md5($groove);
}
/*
	Check if special Cerber Sign valid
*/
function cerber_check_groove($hash = ''){
	if (!$hash) {
		if (!isset($_COOKIE['cerber_groove'])) return false;
		$hash = $_COOKIE['cerber_groove'];
	}
	$groove = get_site_option('cerber-groove');
	if ($hash == md5($groove)) return true;
	return false;
}

//  Track login/logout activity -------------------------------------------------------------------------

add_action('wp_login', 'cerber_log_login',10,2);
function cerber_log_login($login, $user){
	global $wpdb,$blog_id;
	if (!$wpdb->query($wpdb->prepare('INSERT INTO '. CERBER_LOG_TABLE . ' (ip,user_id,user_login,stamp,activity) VALUES (%s,%d,%s,%d,%d)',cerber_get_ip(),$user->ID,$login,time(),5)));
	update_site_option('cerber_last_error',"Unable to write log! Table: " . CERBER_LOG_TABLE);
}
add_action( 'wp_logout', 'cerber_log_logout');
function cerber_log_logout(){
	global $wpdb,$blog_id,$user_ID;
	get_currentuserinfo();
	if (!$wpdb->query($wpdb->prepare('INSERT INTO '. CERBER_LOG_TABLE . ' (ip,user_id,stamp,activity) VALUES (%s,%d,%d,%d)',cerber_get_ip(),$user_ID,time(),6)));
	update_site_option('cerber_last_error',"Unable to write log! Table: " . CERBER_LOG_TABLE);
}
add_action('wp_login_failed', 'cerber_login_failed');
function cerber_login_failed($user_login){
	global $wpdb,$blog_id;

	$black = false;
	$ip = cerber_get_ip();

	if (cerber_is_allowed($ip)) $ac=7;
	elseif (cerber_acl_check($ip,'B')) {
		$black = true;
		$ac=14;
	}
	else $ac=13;

	if (!$wpdb->query($wpdb->prepare('INSERT INTO '. CERBER_LOG_TABLE . ' (ip,user_login,stamp,activity) VALUES (%s,%s,%d,%d)',$ip,$user_login,time(),$ac)));
	update_site_option('cerber_last_error',"Unable to write log! Table: " . CERBER_LOG_TABLE);

	// White? Stop further actions.
	if (!$black && cerber_acl_check($ip,'W')) return;

	if (cerber_get_options('usefile')) {
		cerber_logger($user_login,$ip);
	}

	if(!defined('DOING_AJAX') || !DOING_AJAX){ // Needs additional researching and, maybe, refactoring
		status_header(403);
	}

	// Blacklisted? No more actions is needed. @since 1.5
	if ($black) return;

	// Citadel mode?
	if (cerber_get_options('ciperiod') && !cerber_is_citadel()) {
		$range = time() - cerber_get_options('ciperiod') * 60;
		$lockouts = $wpdb->get_var($wpdb->prepare('SELECT count(ip) FROM '. CERBER_LOG_TABLE . ' WHERE activity = 7 AND stamp > %d',$range));
		if ($lockouts >= cerber_get_options('cilimit')) {
			cerber_enable_citadel();
		}
	}

	if (cerber_get_remain_count() < 1) cerber_block_add($ip);
	elseif (cerber_get_options('nonusers')) {
		if (!get_user_by('login',$user_login)) cerber_block_add($ip);
	}
}
add_action( 'password_reset', 'cerber_password_reset');
function cerber_password_reset($user){
	global $wpdb;
	if (!$wpdb->query($wpdb->prepare('INSERT INTO '. CERBER_LOG_TABLE . ' (ip,user_login,user_id,stamp,activity) VALUES (%s,%s,%d,%d,%d)',cerber_get_ip(),$user->user_login,$user->ID,time(),20)));
	update_site_option('cerber_last_error',"Unable to write log! Table: " . CERBER_LOG_TABLE);
}

// Block list (lockouts) routines ---------------------------------------------------------------------

function cerber_block_add($ip,$duration = null, $reason = ''){
	global $wpdb;
	if (!$ip) $ip = cerber_get_ip();

	if (cerber_acl_check($ip)) return false; // Protection from conflict between lockout and ACL

	if (cerber_get_options('subnet')) {
		$ip = cerber_get_subnet($ip);
		$activity = 11;
	}
	else $activity = 10;

	if ($wpdb->get_var($wpdb->prepare('SELECT count(ip) FROM '. CERBER_BLOCKS_TABLE . ' WHERE ip = %s',$ip))) return;

	if (!$duration) $duration = cerber_calc_duration($ip);
	$until = time() + $duration;
	$result = $wpdb->query($wpdb->prepare('INSERT INTO '. CERBER_BLOCKS_TABLE . ' (ip,block_until,reason) VALUES (%s,%d,%s)',$ip,$until,$reason));
	if ($result) {
		$wpdb->query($wpdb->prepare('INSERT INTO '. CERBER_LOG_TABLE . ' (ip,stamp,activity) VALUES (%s,%d,%d)',cerber_get_ip(),time(),$activity));
	}
	if (cerber_get_options('notify')) {
		$count = $wpdb->get_var('SELECT count(ip) FROM '. CERBER_BLOCKS_TABLE );
		if ($count > cerber_get_options('above')) cerber_send_notify('lockout');
	}
	return $result;
}
function cerber_block_delete($ip){
	global $wpdb;
	return $wpdb->query($wpdb->prepare('DELETE FROM '. CERBER_BLOCKS_TABLE . ' WHERE ip = %s',$ip));
}
function cerber_block_garbage_collector(){
	global $wpdb;
	$wpdb->query('DELETE FROM '. CERBER_BLOCKS_TABLE . ' WHERE block_until < ' . time());
}
/*
	Check if IP is blocked. With C subnet also.
*/
function cerber_block_check($ip){
	global $wpdb;
	cerber_block_garbage_collector();
	if ($wpdb->get_var($wpdb->prepare('SELECT count(ip) FROM '. CERBER_BLOCKS_TABLE . ' WHERE ip = %s',$ip))) return true;
	else {
		$subnet = cerber_get_subnet($ip); // try subnet
		if ($wpdb->get_var($wpdb->prepare('SELECT count(ip) FROM '. CERBER_BLOCKS_TABLE . ' WHERE ip = %s',$subnet))) return true;
	}
	return false;
}
/*
	Check remain time for IP if it is blocked. With C subnet also.
*/
function cerber_get_block($ip=''){
	global $wpdb;
	if (!$ip) $ip = cerber_get_ip();
	cerber_block_garbage_collector();
	if ($ret = $wpdb->get_row($wpdb->prepare('SELECT * FROM '. CERBER_BLOCKS_TABLE . ' WHERE ip = %s',$ip))) return $ret;
	else {
		$subnet = cerber_get_subnet($ip); // try subnet
		if ($ret = $wpdb->get_row($wpdb->prepare('SELECT * FROM '. CERBER_BLOCKS_TABLE . ' WHERE ip = %s',$subnet))) return $ret;
	}
	return false;
}
/*
	Calculation duration of blocking (lockout) IP address based on settings & rules.
*/
function cerber_calc_duration($ip){
	global $wpdb;
	$range = time() - cerber_get_options('aglast') * 3600;
	$lockouts = $wpdb->get_var($wpdb->prepare('SELECT count(ip) FROM '. CERBER_LOG_TABLE . ' WHERE ip = %s AND activity IN (10,11) AND stamp > %d',$ip,$range));
	if ($lockouts >= cerber_get_options('aglocks')) return cerber_get_options('agperiod') * 3600;
	return cerber_get_options('lockout') * 60;
}
/*
	Calculation remaining attempts based on context
*/
function cerber_get_remain_count($ip='') {
	global $wpdb;
	if (!$ip) $ip = cerber_get_ip();
	$allowed = cerber_get_options('attempts');
	if (cerber_acl_check($ip,'W')) return $allowed; // whitelist = infinty attempts
	$range = time() - cerber_get_options('period') * 60;
	$attempts = $wpdb->get_var($wpdb->prepare('SELECT count(ip) FROM '. CERBER_LOG_TABLE . ' WHERE ip = %s AND activity = 7 AND stamp > %d',$ip,$range));
	if (!$attempts) return $allowed;
	else $ret = $allowed - $attempts;
	$ret = $ret < 0 ? 0 : $ret;
	return $ret;
}

/*
	Is given IP allowed to login?
*/
function cerber_is_allowed($ip=''){
	global $wpdb;
	if (!$ip) $ip = cerber_get_ip();
	if (cerber_is_citadel()) {
		if (cerber_get_options('ciwhite') && cerber_acl_check($ip,'W')) return true;
		return false;
	}
	$tag = cerber_acl_check($ip);
	if ($tag == 'W') return true;
	if ($tag == 'B') return false;
	if (cerber_block_check($ip)) return false;
	return true;
}

// Access lists (ACL) routines --------------------------------------------------------------------------------

function cerber_acl_add($ip,$tag){
	global $wpdb;
	if ($wpdb->get_var($wpdb->prepare('SELECT COUNT(ip) FROM '. CERBER_ACL_TABLE . ' WHERE ip = %s',$ip))) return false;
	return $wpdb->query($wpdb->prepare('INSERT INTO '. CERBER_ACL_TABLE . ' (ip,tag) VALUES (%s,%s)',$ip,$tag));
}
function cerber_add_white($ip){
	return cerber_acl_add($ip,'W');
}
function cerber_add_black($ip){
	return cerber_acl_add($ip,'B');
}
function cerber_acl_remove($ip){
	global $wpdb;
	return $wpdb->query($wpdb->prepare('DELETE FROM '. CERBER_ACL_TABLE . ' WHERE ip = %s ',$ip));
}

/*
	Check ACL for IP. With C subnet also. Some extra lines for perfomance reason.
*/
function cerber_acl_check($ip,$tag=''){
	global $wpdb;
	if (!$ip) $ip = cerber_get_ip();
	$ret = false;
	if ($tag) {
		if ($wpdb->get_var($wpdb->prepare('SELECT count(ip) FROM '. CERBER_ACL_TABLE . ' WHERE ip = %s AND tag = %s',$ip,$tag))) $ret = true;
		else {
			$subnet = cerber_get_subnet($ip);
			if ($wpdb->get_var($wpdb->prepare('SELECT count(ip) FROM '. CERBER_ACL_TABLE . ' WHERE ip = %s AND tag = %s',$subnet,$tag))) $ret = true;
			else $ret = false;
		}
	}
	else {
		if (!$ret = $wpdb->get_var($wpdb->prepare('SELECT tag FROM '. CERBER_ACL_TABLE . ' WHERE ip = %s',$ip))) {
			$subnet = cerber_get_subnet($ip);
			if (!$ret = $wpdb->get_var($wpdb->prepare('SELECT tag FROM '. CERBER_ACL_TABLE . ' WHERE ip = %s',$subnet))) $ret = false;
		}
	}
	return $ret;
}
/*
 * Logging directly to file
 *
 * CERBER_FAIL_LOG optional full path to the file including name
 * CERBER_LOG_FACILITY optional is used to specify what type of program is logging the message
 *
 * */
function cerber_logger($user_login,$ip){
	if (defined('CERBER_FAIL_LOG')) {
		if ($log = @fopen(CERBER_FAIL_LOG,'a')){
			$pid = absint(@posix_getpid());
			@fwrite($log,date('M j H:i:s ').$_SERVER['SERVER_NAME'].' Cerber('.$_SERVER['HTTP_HOST'].')['.$pid.']: Authentication failure for '.$user_login.' from '.$ip."\n");
			@fclose($log);
		}
	}
	else {
		@openlog('Cerber('.$_SERVER['HTTP_HOST'].')',LOG_NDELAY|LOG_PID, defined('CERBER_LOG_FACILITY') ? CERBER_LOG_FACILITY : LOG_AUTH);
		@syslog(LOG_NOTICE,'Authentication failure for '.$user_login.' from '.$ip);
		@closelog();
	}
}

/*
	Return string like subnet Class C wildcard
*/
function cerber_get_subnet($ip){
	return preg_replace('/\.\d{1,3}$/', '.*', $ip);
}
/*
	Return IP address of remote side (user)
*/
function cerber_get_ip(){
	if (cerber_get_options('proxy')) return $_SERVER['HTTP_X_FORWARDED_FOR'];
	return $_SERVER['REMOTE_ADDR'];
}
/*
	Check if IP address or wildcard is valid
*/
function cerber_is_ip($ip){
	if (inet_pton($ip)) return true;
	return preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.(\d{1,3}|\*)$/',$ip);
}
/*
	Check for given IP address or subnet belong to this session.
*/
function cerber_is_myip($ip){
	if ($ip == cerber_get_ip()) return true;
	if ($ip == cerber_get_subnet(cerber_get_ip())) return true;
	return false;
}

/*
	Display 404 page with message.
*/
function cerber_404_page(){
	global $wp_query;
	status_header('404');
	$wp_query->set_404();
	if (file_exists(TEMPLATEPATH.'/404.php')) include(TEMPLATEPATH.'/404.php');
	//get_template_part('404');
  else { // wow, theme does not have 404.php file?
   	header('HTTP/1.0 404 Not Found', true, 404);
   	echo '<html><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL '.$_SERVER['REQUEST_URI'].' was not found on this server.</p></body></html>';
  }
  exit;
}

// Citadel mode -------------------------------------------------------------------------------------

function cerber_enable_citadel(){
	global $wpdb;
	if (get_transient('cerber_citadel')) return;
	set_transient('cerber_citadel',true,cerber_get_options('ciduration') * 60);
	$wpdb->query($wpdb->prepare('INSERT INTO '. CERBER_LOG_TABLE . ' (ip,stamp,activity) VALUES (%s,%d,%d)',cerber_get_ip(),time(),12));
	// Notify admin
	if (cerber_get_options('cinotify')) cerber_send_notify('citadel');
}
function cerber_disable_citadel(){
	delete_transient('cerber_citadel');
}
function cerber_is_citadel(){
	if (get_transient('cerber_citadel')) return true;
	return false;
}

/*

	Send notifications

*/
function cerber_send_notify($type = '',$msg = ''){
	global $wpdb;
	if (!$type) return;
	$to = get_option('admin_email');
	$gmt_offset=get_option('gmt_offset')*3600;
	$tf=get_option('time_format');
	$df=get_option('date_format');

	switch ($type) {
		case 'citadel':
			$max = $wpdb->get_var('SELECT MAX(stamp) FROM '.CERBER_LOG_TABLE.' WHERE  activity = 7');
			$last_date = date($df.' '.$tf, $gmt_offset + $max);
			$last = $wpdb->get_row('SELECT * FROM '.CERBER_LOG_TABLE.' WHERE stamp = '.$max.' AND activity = 7');

			//$subj = '['.get_option('blogname').'] '.__('WP Cerber notify: Citadel mode is activated','cerber');
			$subj = '['.get_option('blogname').'] '.__('WP Cerber notify','cerber').': '.__('Citadel mode is activated','cerber');

			$body = sprintf(__('Citadel mode is activated after %d failed login attempts in %d minutes.','cerber'),cerber_get_options('cilimit'),cerber_get_options('ciperiod'))."\n\n";
			$body .= sprintf(__('Last failed attempt was at %s from IP %s with user login: %s.','cerber'),$last_date,$last->ip,$last->user_login)."\n\n";
			$body .= __('View activity in dashboard','cerber').': '.admin_url(cerber_get_opage('activity'))."\n\n";
			$body .= __('Change notification settings','cerber').': '.admin_url(cerber_get_opage());
		break;
		case 'lockout':
			$max = $wpdb->get_var('SELECT MAX(stamp) FROM '.CERBER_LOG_TABLE.' WHERE  activity IN (10,11)');
			$last_date = date($df.' '.$tf, $gmt_offset + $max);
			$last = $wpdb->get_row('SELECT * FROM '.CERBER_LOG_TABLE.' WHERE stamp = '.$max.' AND activity IN (10,11)');
			if (!$active = $wpdb->get_var('SELECT count(ip) FROM '.CERBER_BLOCKS_TABLE)) $active = 0;

			//$subj = '['.get_option('blogname').'] '.__('WP Cerber notify: Number of lockouts is growing up','cerber');
			$subj = '['.get_option('blogname').'] '.__('WP Cerber notify','cerber').': '.__('Number of lockouts is growing up','cerber');

			$body = __('Number of active lockouts','cerber').': '.$active."\n\n";
			$body .= __('Last lockout was added','cerber').': '.$last_date."\n\n";
			$body .= __('View lockouts in dashboard','cerber').': '.admin_url(cerber_get_opage('lockouts'))."\n\n";
			$body .= __('Change notification settings','cerber').': '.admin_url(cerber_get_opage());
		break;
		case 'newlurl':
			//$subj = '['.get_option('blogname').'] '.__('WP Cerber notify: New Custom login URL','cerber');
			$subj = '['.get_option('blogname').'] '.__('WP Cerber notify','cerber').': '.__('New Custom login URL','cerber');
			$body .= $msg;
		break;
	}
	//$body .= __('This message was sent by','cerber').' <a href="http://wpcerber.com">WP Cerber security plugin</a>.'."\n";
	$body .= "\n\n\n" . __('This message was sent by','cerber')." WP Cerber.\n";
	$body .= 'http://wpcerber.com';
	if ($to && $subj && $body) wp_mail($to,$subj,$body);
}


/*
	TODO: Return themed page with message instead of login form.
*/
/*
function cerber_info_page(){
	global $wp_query;
	$wp_query->is_page = true;
	add_filter('the_content', 'cerber_info_page_content');
  if(!include(TEMPLATEPATH.'/page.php')) { // wow, theme does not have page.php file?
   	echo '<html><head><title>Login not permited</title></head><body><h1>Login not permited</h1><p>You not allowed to login to this site.</p></body></html>';
  }
  exit;
}
function cerber_info_page_content(){
	return 'Login not permited.';
}
*/

/*
	Hide login form from user
*/
add_action('login_head', 'cerber_lohead');
function cerber_lohead(){
	if (!cerber_is_allowed(cerber_get_ip()))  : ?>
	<style type="text/css" media="all">
		#loginform { display:none; }
	</style>
	<?php
	endif;
}

// Auxiliary routines ----------------------------------------------------------------

add_action('cerber_hourly', 'cerber_do_hourly');
function cerber_do_hourly(){
	global $wpdb;
	$days = absint(cerber_get_options('keeplog'));
	if ($days > 0) {
		$wpdb->query('DELETE FROM '. CERBER_LOG_TABLE . ' WHERE stamp < ' . (time() - $days * 24 * 3600 ));
	}
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
	Right and convenient way to access to the Cerber settings
*/
function cerber_get_options($option=''){
	//$options = (array)get_option('cerber-main');
	$options = (array)get_site_option(CERBER_OPT); // network wide settings for multisite
	if (!empty($option)) {
		if (isset($options[$option])) return $options[$option];
		else return false;
	}
	return $options;
}
/*
	Localization
*/
add_action( 'plugins_loaded', 'cerber_load_lang' );
function cerber_load_lang() {
  load_plugin_textdomain( 'cerber', false, basename( dirname( __FILE__ ) ) . '/languages' );
}
/*
	Return system ID of WP Cerber plugin
*/
function cerber_plug_in(){
	return plugin_basename( __FILE__ );
}
/*
	Return plugin info
*/
function cerber_plugin_data(){
	return get_plugin_data(__FILE__);
}
/*
	Format date
*/
function cerber_date($time){
	$gmt_offset = get_option('gmt_offset') * 3600;
	$tf = get_option('time_format');
	$df = get_option('date_format');
	return date($df.', '.$tf, $gmt_offset + $time);
}
/*
	Defaults settings
*/
function cerber_get_defaults(){
	return array_merge(cerber_get_options(),
	array(
		'attempts' 		=> 3, 'period' => 60,
		'lockout' 		=> 60,
		'agperiod' 		=> 24, 'aglocks' => 2, 'aglast' => 4,
		'notify' 			=> 1,
		'above' 			=> 5,

		'proxy' 			=> 0,

		'subnet' 			=> 0,
		'nonusers'		=> 1,
		'wplogin'			=> 0,
		'noredirect' 	=> 1,

		'cilimit'			=> 200, 'ciperiod' => 30,
		'ciduration' 	=> 60,

		'ciwhite' 		=> 1,
		'cinotify' 		=> 1,

		'keeplog' 		=> 90,
		'usefile' 		=> 0,
	));
}
/*
	Plugin activation
*/
register_activation_hook( __FILE__, 'cerber_activate' );
function cerber_activate(){
	global $wpdb,$wp_version;

	cerber_load_lang();

	if ( version_compare( CERBER_REQ_PHP, phpversion(), '>' ) ) {
		cerber_stop_activating('<h3>'.sprintf(__('The WP Cerber requires PHP %s or higher. You are running','cerber'),CERBER_REQ_PHP).' '.phpversion().'</h3>');
	}

	if ( version_compare( CERBER_REQ_WP, $wp_version, '>' ) ) {
		cerber_stop_activating('<h3>'.sprintf(__('The WP Cerber requires WordPress %s or higher. You are running','cerber'),CERBER_REQ_WP).' '.$wp_version.'</h3>');
	}

	// Tables
	$db_errors = array();

	if (!$wpdb->get_row("SHOW TABLES LIKE '".CERBER_LOG_TABLE."'")) {
		if (!$wpdb->query("

	CREATE TABLE IF NOT EXISTS ".CERBER_LOG_TABLE." (
  `ip` varchar(39) CHARACTER SET ascii NOT NULL COMMENT 'Remote IP',
  `user_login` varchar(60) NOT NULL COMMENT 'Login from POST request',
  `user_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `stamp` bigint(20) unsigned NOT NULL COMMENT 'Unix timestamp',
  `activity` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'What''s happen?',
  KEY `ip` (`ip`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Cerber actions log';


				")
				) $db_errors[]= $wpdb->last_error;
	}
	if (!$wpdb->get_row("SHOW TABLES LIKE '".CERBER_ACL_TABLE."'")) {
		if (!$wpdb->query("

	CREATE TABLE IF NOT EXISTS ".CERBER_ACL_TABLE." (
  `ip` varchar(39) CHARACTER SET ascii NOT NULL COMMENT 'IP',
  `tag` char(1) NOT NULL COMMENT 'Type: B or W',
  `comments` varchar(250) NOT NULL,
  UNIQUE KEY `ip` (`ip`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Cerber IP access lists';

				")
				) $db_errors[]= $wpdb->last_error;
	}
	if (!$wpdb->get_row("SHOW TABLES LIKE '".CERBER_BLOCKS_TABLE."'")) {
		if (!$wpdb->query("

	CREATE TABLE IF NOT EXISTS ".CERBER_BLOCKS_TABLE." (
  `ip` varchar(39) CHARACTER SET ascii NOT NULL COMMENT 'Remote IP',
  `block_until` bigint(20) unsigned NOT NULL COMMENT 'Unix timestamp',
  `reason` varchar(250) NOT NULL COMMENT 'Why was blocked',
  UNIQUE KEY `ip` (`ip`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='List of blocked IP';

				")
				) $db_errors[]= $wpdb->last_error;
	}

	if ($db_errors) {
		cerber_stop_activating('<h3>'.__("Can't activate WP Cerber due to a database error.",'cerber').'</h3><p>'.implode('<p>',$db_errors));
	}

	cerber_cookie1();
	cerber_disable_citadel();
	cerber_get_groove();

	cerber_add_white(cerber_get_subnet(cerber_get_ip())); // Protection for non-experienced user
	
	update_site_option('cerber_admin_message',__('WP Cerber is now active and has started protecting your site.','cerber').
	' <a href="'.admin_url(cerber_get_opage()).'">'.__('Check settings here','cerber').'.</a>');

	$opt = cerber_get_options();
	$opt = array_filter($opt);
	if (!empty($opt))	return;

	update_site_option(CERBER_OPT,cerber_get_defaults());
}
/*
	Plugin deactivation, some cleanup
*/
register_deactivation_hook( __FILE__, 'cerber_deactivate' );
function cerber_deactivate(){
	wp_clear_scheduled_hook('cerber_hourly');
}
/*
	Stop activating plugin!
*/
function cerber_stop_activating($msg){
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die($msg);
}
/*
	Fix issue with empty user_id field in comments table.
*/
add_filter( 'preprocess_comment' ,'cerber_add_uid');
function cerber_add_uid($commentdata) {
	global $current_user;
	if (!$current_user->ID) get_currentuserinfo();
	$commentdata['user_ID'] = $current_user->ID;
	return $commentdata;
}

?>