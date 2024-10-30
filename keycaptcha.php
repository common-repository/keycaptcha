<?php
/*
Plugin Name: KeyCAPTCHA
Plugin URI: https://www.keycaptcha.com
Description: Adds KeyCAPTCHA anti-spam solution to WordPress on the comment form and registration form.
Version: 2.5.1
Author: Mersane, Ltd
Author URI: https://www.keycaptcha.com
License: GNU GPL2
*/
/*
Copyright (C) 2011-2013 Mersane, Ltd (www.keycaptcha.com). All rights reserved.

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License,
version 2, as published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

if ( !class_exists('KeyCAPTCHA_CLASS') )
{
class KeyCAPTCHA_CLASS
{
		private $c_kc_keyword = "accept";
		private $p_kc_visitor_ip = "";
		private $p_kc_session_id = "";
		private $p_kc_web_server_sign = "";
		private $p_kc_web_server_sign2 = "";
		private $p_kc_js_code = "";
		private $p_kc_private_key = "";
		private $p_kc_userID = "";

		private function get_web_server_sign($use_visitor_ip = 0)
		{
			return md5($this->p_kc_session_id . (($use_visitor_ip) ? ($this->p_kc_visitor_ip) :("")) . $this->p_kc_private_key);
		}

		function __construct($a_private_key='')	
		{
			if ( $a_private_key != '' )
			{
				$set = explode("0",trim($a_private_key),2);
				if (sizeof($set)>1){
					$this->p_kc_private_key = trim($set[0]);
					$this->p_kc_userID = (int)$set[1];
					$this->p_kc_js_code = 
"<!-- KeyCAPTCHA code (www.keycaptcha.com)-->
<script type=\"text/javascript\">
	var s_s_c_user_id = '".$this->p_kc_userID."';
	var s_s_c_session_id = '#KC_SESSION_ID#';
	var s_s_c_captcha_field_id = 'capcode';
	var s_s_c_submit_button_id = 'submit,wp-submit';
	var s_s_c_web_server_sign = '#KC_WSIGN#';
	var s_s_c_web_server_sign2 = '#KC_WSIGN2#';
</script>
<script type=\"text/javascript\" src=\"http://backs.keycaptcha.com/swfs/cap.js\"></script>
<!-- end of KeyCAPTCHA code-->";
				}
			}
			$this->p_kc_session_id = uniqid() . '-2.5.1.014';
			$this->p_kc_visitor_ip = $_SERVER["REMOTE_ADDR"];
		}

		function http_get($path)
		{
			$arr = parse_url($path);
			$host = $arr['host'];
			$page = $arr['path'];
			if ( $page=='' ) {
				$page='/';
			}
			if ( isset( $arr['query'] ) ) {
				$page.='?'.$arr['query'];
			}
			$errno = 0;
			$errstr = '';
			$fp = fsockopen ($host, 80, $errno, $errstr, 30);
			if (!$fp){ return ""; }
			$request = "GET $page HTTP/1.0\r\n";
			$request .= "Host: $host\r\n";
			$request .= "Connection: close\r\n";
			$request .= "Cache-Control: no-store, no-cache\r\n";
			$request .= "Pragma: no-cache\r\n";
			$request .= "User-Agent: KeyCAPTCHA\r\n";
			$request .= "\r\n";

			fwrite ($fp,$request);
			$out = '';

			while (!feof($fp)) $out .= fgets($fp, 250);
			fclose($fp);
			$ov = explode("close\r\n\r\n", $out);

			return $ov[1];
		}

		public function check_result($response)
		{
			$kc_vars = explode("|", $response);
			if ( count( $kc_vars ) < 4 )
			{
				return false;
			}
			if ($kc_vars[0] == md5($this->c_kc_keyword . $kc_vars[1] . $this->p_kc_private_key . $kc_vars[2]))
			{
				if (stripos($kc_vars[2], "http://") !== 0)
				{
					$kc_current_time = time();
					$kc_var_time = split('[/ :]', $kc_vars[2]);
					$kc_submit_time = gmmktime($kc_var_time[3], $kc_var_time[4], $kc_var_time[5], $kc_var_time[1], $kc_var_time[2], $kc_var_time[0]);
					if (($kc_current_time - $kc_submit_time) < 15)
					{
						return true;
					}
				}
				else
				{
					if ($this->http_get($kc_vars[2]) == "1")
					{
						return true;
					}
				}
			}
			return false;
		}

		public function render_js ()
		{
			if ( isset($_SERVER['HTTPS']) && ( $_SERVER['HTTPS'] == 'on' ) )
			{
				$this->p_kc_js_code = str_replace ("http://","https://", $this->p_kc_js_code);
			}
			$this->p_kc_js_code = str_replace ("#KC_SESSION_ID#", $this->p_kc_session_id, $this->p_kc_js_code);
			$this->p_kc_js_code = str_replace ("#KC_WSIGN#", $this->get_web_server_sign(1), $this->p_kc_js_code);
			$this->p_kc_js_code = str_replace ("#KC_WSIGN2#", $this->get_web_server_sign(), $this->p_kc_js_code);
			return $this->p_kc_js_code;
		}
	}
}

function keycaptcha_uninstall()
{
   if (basename(dirname(__FILE__)) != "mu-plugins")
      delete_option('keycaptcha_vars_db');
}


class KeyCAPTCHA_WP
{
	private $keycaptcha_vars_default = array
		(
			'keycaptcha_site_private_key' => '',
			'keycaptcha_code' => '',
			'keycaptcha_on_register' => 'true',
			'keycaptcha_on_login' => 'false',
			'keycaptcha_on_lostpwd' => 'true',
			'keycaptcha_on_cf7' => 'true',
			'keycaptcha_posts_cnt' => 0,
			'keycaptcha_link'	=> '',
		);
	public $keycaptcha_vars = array
		(
			'keycaptcha_site_private_key' => '',
			'keycaptcha_code' => '',
			'keycaptcha_on_register' => '',
			'keycaptcha_on_login' => '',
			'keycaptcha_on_lostpwd' => '',
			'keycaptcha_on_cf7' => '',
			'keycaptcha_posts_cnt' => 0,
			'keycaptcha_link'	=> '',
		);
	public $kc_o;


	function __construct ()
	{
		$this->keycaptcha_get_settings();
		$this->kc_o = new KeyCAPTCHA_CLASS($this->keycaptcha_vars['keycaptcha_site_private_key']);
		if (function_exists('load_plugin_textdomain'))
		{
			load_plugin_textdomain('keycaptcha', false, dirname(plugin_basename(__FILE__)).'/languages' );
		}
	}

	function keycaptcha_get_settings()
	{		
		if ( file_exists(  WP_PLUGIN_DIR.'/keycaptcha/keycaptcha_multisite.php' ) ) {
			require_once( WP_PLUGIN_DIR.'/keycaptcha/keycaptcha_multisite.php' );
			global $keycaptcha_multisite_settings;
			$this->keycaptcha_vars_default = $keycaptcha_multisite_settings;
			$this->keycaptcha_vars = $keycaptcha_multisite_settings;
		} else {
			add_option('keycaptcha_vars_db', $this->keycaptcha_vars_default, '', 'yes');
			$this->keycaptcha_vars = get_option('keycaptcha_vars_db');
			foreach($this->keycaptcha_vars as $key => $val)
			{
				$this->keycaptcha_vars[$key] = str_replace('\\','',$val);
			}
			$pos = strpos($this->keycaptcha_vars['keycaptcha_site_private_key'],"0");
			if ( ( $pos === false ) && ( !empty( $this->keycaptcha_vars['keycaptcha_site_private_key'] ) ) && ( !empty( $this->keycaptcha_vars['keycaptcha_code'] ) ) ) {
				$a = $this->keycaptcha_vars['keycaptcha_code'];
				$uid = substr($a,strpos($a,"user_id = '")+11, strpos($a,"'",strpos($a,"user_id = '")+11)-strpos($a,"user_id = '")-11);
				$this->keycaptcha_vars['keycaptcha_site_private_key'] = $this->keycaptcha_vars['keycaptcha_site_private_key']."0".$uid;
				update_option('keycaptcha_vars_db', $this->keycaptcha_vars);
				$this->keycaptcha_vars = get_option('keycaptcha_vars_db');
				foreach($this->keycaptcha_vars as $key => $val)
				{
					$this->keycaptcha_vars[$key] = str_replace('\\','',$val);
				}
			}
			if (($this->keycaptcha_vars['keycaptcha_link']!='true')&&($this->keycaptcha_vars['keycaptcha_link']!='false')){
				$this->keycaptcha_vars['keycaptcha_link'] = 'true';
			}
			

		}
	}

	function keycaptcha_settings()
	{
		global $keycaptcha_path;
		require_once($keycaptcha_path.'/keycaptcha_settings.php');
	}

	function keycaptcha_add_tabs ()
	{		
		add_submenu_page('plugins.php', 'KeyCAPTCHA', 'KeyCAPTCHA', 'manage_options', __FILE__,array ($this, 'keycaptcha_settings'));		
	}

	function keycaptcha_plugin_action_links($links, $file)
	{
		static $this_plugin;
		if (!$this_plugin)
		{
			$this_plugin = plugin_basename(__FILE__);
		}
		if ($file == $this_plugin)
		{
			$settings_link = '<a href="plugins.php?page=keycaptcha/keycaptcha.php">'. __('Settings', 'keycaptcha') . '</a>';
			array_unshift( $links, $settings_link );
		}
		return $links;
	}	

	function keycaptcha_wp_move_subbmit_button()
	{
		echo( '<div id="keycaptcha-submit-button-area" style="margin-top:0px;" ><br /></div>
			<script type="text/javascript">
				var kc_subs = document.getElementsByClassName( "form-submit" );
				var fmoved = false;
				for(var i in kc_subs) {
					var sub = kc_subs[i];
					if ( sub.parentNode != undefined ) {
						sub.parentNode.removeChild(kc_subs[i]);
						document.getElementById("keycaptcha-submit-button-area").appendChild(sub);
						fmoved = true;
					}
				}
				if (!fmoved) {
					var kc_sub = document.getElementById("submit");
					if (kc_sub!=undefined) {
						kc_sub.parentNode.removeChild(kc_sub);
						document.getElementById("keycaptcha-submit-button-area").appendChild(kc_sub);
						kc_sub.tabIndex = 6;
					}
				}
			</script>' );
	}

	function keycaptcha_get_html( $akc_code, $mode )
	{
		$kc_text = __('Anti-spam: complete the task','keycaptcha');
		if (isset($this->keycaptcha_vars['keycaptcha_custom_text']))
		{
			if (strlen($this->keycaptcha_vars['keycaptcha_custom_text']) > 0)
			{
				$kc_text = $this->keycaptcha_vars['keycaptcha_custom_text'];
			}
		}
		if ($this->keycaptcha_vars['keycaptcha_link']=='true'){
			$kc_link = "<a target='_blank' href='https://www.keycaptcha.com/wordpress-captcha/' style='margin-left:100px; font-size:8px;float:right;'>WordPress CAPTCHA</a>";
		} else {
			$kc_link = "";
		}
		
		$kc_text .= $kc_link;
		switch ($mode)
		{
			case 0:
				return '<input type="hidden" value="false" id="capcode" name="capcode"><p><small>'.
				$kc_text.'</small></p> <noscript><p><small style="color:red;">'.
				__('You should turn on JavaScript on your browser. After that please reload the page. Otherwise you won&#39;t be able to post any information on this site.', 'keycaptcha').
				'</small></p></noscript>'.$akc_code;
				break;
			case 1:
				return '<div class="register-section" style="clear:left; margin-top:-10px;"><input type="hidden" value="false" id="capcode" name="capcode"><small>'.
				$kc_text.'</small> <noscript><p><small style="color:red;">'.
				__('You should turn on JavaScript on your browser. After that please reload the page. Otherwise you won&#39;t be able to post any information on this site.', 'keycaptcha').
				'</small></p></noscript></div>'.$akc_code;
				break;
			default:
				return '<input type="hidden" value="false" id="capcode" name="capcode"><p><small>'.
				$kc_text.'</small></p> <noscript><p><small style="color:red;">'.
				__('You should turn on JavaScript on your browser. After that please reload the page. Otherwise you won&#39;t be able to post any information on this site.', 'keycaptcha').
				'</small></p></noscript>'.$akc_code;
		}
	}

	function cnt_comments_kc($userid) {
		global $wpdb;		
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->comments where comment_approved = 1 and user_id = $userid", 0, 0 );
		return $count;
	}	

	function keycaptcha_comment_form()
	{
		global $user_ID;
		if ( (isset($user_ID) && intval($user_ID) > 0 && ( intval( $this->keycaptcha_vars['keycaptcha_posts_cnt'] ) <= $this->cnt_comments_kc( $user_ID ) ) )
			|| empty($this->keycaptcha_vars['keycaptcha_site_private_key']))
		{
			
			return true;
		}
		echo( $this->keycaptcha_get_html( $this->kc_o->render_js (), 0 ) );
		$this->keycaptcha_wp_move_subbmit_button();
		return true;
	}


	function keycaptcha_comment_form_check($comment)
	{		
		global $user_ID;
		if ( (isset($user_ID) && intval($user_ID) > 0 && ( intval( $this->keycaptcha_vars['keycaptcha_posts_cnt'] ) <= $this->cnt_comments_kc( $user_ID ) ) )
			|| empty($this->keycaptcha_vars['keycaptcha_site_private_key']))
		{
				return $comment;
		}
		if ( ( $comment['comment_type'] != '' ) && ( $comment['comment_type'] != 'comment' ) ) {
			return $comment;
		}
		if ($this->kc_o->check_result($_POST['capcode'])) {
			return $comment;
		} else {
			wp_die('<strong>' . __('ERROR', 'keycaptcha') . '</strong>: ' . __('The solution of task you submitted was incorrect. Please read the instruction and try again.','keycaptcha'));
		}
	}

	function keycaptcha_register_form()
	{
		if (empty($this->keycaptcha_vars['keycaptcha_site_private_key']))
		{
			return true;
		}
		echo( $this->keycaptcha_get_html( $this->kc_o->render_js (), 0 ) );
		echo ('<script language="JavaScript">document.getElementById("login").style.width = "582px";</script><br>');
		return true;
	}

	function keycaptcha_register_bp_form()
	{
		if (empty($this->keycaptcha_vars['keycaptcha_site_private_key']))
		{
			return true;
		}
		echo( $this->keycaptcha_get_html( $this->kc_o->render_js (), 1 ) );
		return true;
	}


	function keycaptcha_register_form_check($errors)
	{
		if (empty($this->keycaptcha_vars['keycaptcha_site_private_key']))
		{
			return $errors;
		}
		if (!$this->kc_o->check_result($_POST['capcode']))
		{
			$errors->add('captcha_wrong', '<strong>' . __('ERROR', 'keycaptcha') . '</strong>: ' . __('The solution of task you submitted was incorrect. Please read the instruction and try again.','keycaptcha'));			
		}
		return $errors;
	}

	function keycaptcha_register_bp_form_check()
	{
		global $bp;
		if (empty($this->keycaptcha_vars['keycaptcha_site_private_key']))
		{
			return;
		}
		if (!$this->kc_o->check_result($_POST['capcode']))
		{
			$bp->signup->errors['signup_username'] = __('The solution of task you submitted was incorrect. Please read the instruction and try again.','keycaptcha');
		}
	}


	function keycaptcha_lost_password_check()
	{
		if (empty($this->keycaptcha_vars['keycaptcha_site_private_key']))
		{
			return $errors;
		}
		if (!$this->kc_o->check_result($_POST['capcode']))
		{
			return false;
		}
		return true;
	}

	function keycaptcha_auth_check( $user )
	{
		if 	(( !isset( $_POST['log'] ) )
			|| empty($this->keycaptcha_vars['keycaptcha_site_private_key']))
		{
			return $user;
		}
		if (!$this->kc_o->check_result($_POST['capcode']))
		{
			return new WP_Error( 'captcha_wrong', '<strong>' . __('ERROR', 'keycaptcha') . '</strong>: ' . __('The solution of task you submitted was incorrect. Please read the instruction and try again.','keycaptcha') );
		}
		return $user;
	}

	function keycaptcha_register_net_form($errors)
	{
		if (empty($this->keycaptcha_vars['keycaptcha_site_private_key']))
		{
			return true;
		}
		$e_captcha_wrong = $errors->get_error_message("captcha_wrong");
		echo( $this->keycaptcha_get_html( $this->kc_o->render_js (), 0 ) );
		if( isset($e_captcha_wrong) && $e_captcha_wrong != "")
		{
			echo '<p class="error">' . $e_captcha_wrong . '</p>';
		}
		echo ($this->kc_o->render_js());
		return true;
	}

	function keycaptcha_register_net_form_check($errors)
	{
		if (empty($this->keycaptcha_vars['keycaptcha_site_private_key']))
		{
			return $errors;
		}
		if ($_POST['stage'] == 'validate-user-signup')
		{
			if (!$this->kc_o->check_result($_POST['capcode']))
			{
				$errors['errors']->add('captcha_wrong', '<strong>' . __('ERROR', 'keycaptcha') . '</strong>: ' . __('The solution of task you submitted was incorrect. Please read the instruction and try again.','keycaptcha'));
			}
		}
		return $errors;
	}

	function keycaptcha_process_custom_forms($errors, $tag)
	{		
		if ( isset( $_POST['your-message'] ) ) {
			if (!$this->kc_o->check_result($_POST['capcode']))
			{
				if ( $tag != '' ) { // if CF7
					$errors['valid'] = false;
					$errors['reason']['keycaptcha'] = __('The solution of task you submitted was incorrect. Please read the instruction and try again.','keycaptcha');

				} else {
					$errors['errors']->add('captcha_wrong', '<strong>' . __('ERROR', 'keycaptcha') . '</strong>: ' . __('The solution of task you submitted was incorrect. Please read the instruction and try again.','keycaptcha'));
				}
			}
		}
		return $errors;
	}
}

global $keycaptcha_wp_o;

$keycaptcha_wp_o = new KeyCAPTCHA_WP();

function keycaptcha_shortcode( $atts ) {
	global $keycaptcha_wp_o;
	return $keycaptcha_wp_o->keycaptcha_get_html( $keycaptcha_wp_o->kc_o->render_js (), 0 );
}

$keycaptcha_path = WP_PLUGIN_DIR.'/keycaptcha';

add_action( 'init', 'kcinit' );

function kcinit() {
  global $wp_version, $keycaptcha_wp_o;

// initialize admin panel
if ( ! file_exists(  WP_PLUGIN_DIR.'/keycaptcha/keycaptcha_multisite.php' ) ) {
	add_action('admin_menu', array($keycaptcha_wp_o, 'keycaptcha_add_tabs'), 10);
	add_filter('plugin_action_links', array($keycaptcha_wp_o, 'keycaptcha_plugin_action_links'), 10, 2);
}

// adding KeyCAPTCHA to comment form
add_action('comment_form', array($keycaptcha_wp_o, 'keycaptcha_comment_form'), 1);
add_filter('preprocess_comment', array($keycaptcha_wp_o, 'keycaptcha_comment_form_check'), 1);

$kc_vexp = explode( '.', $wp_version );

// adding KeyCAPTCHA to signup MU form
if ( ( $kc_vexp[0] > 2 ) && ( $keycaptcha_wp_o->keycaptcha_vars['keycaptcha_on_register'] == 'true' ) )
{
	add_action('signup_extra_fields', array($keycaptcha_wp_o, 'keycaptcha_register_net_form'));
	add_filter('wpmu_validate_user_signup', array($keycaptcha_wp_o, 'keycaptcha_register_net_form_check'));
}

// adding KeyCAPTCHA to registration form
if ( $keycaptcha_wp_o->keycaptcha_vars['keycaptcha_on_register'] == 'true' ) {
	add_action('register_form', array($keycaptcha_wp_o, 'keycaptcha_register_form'), 1);
	add_filter('registration_errors', array($keycaptcha_wp_o, 'keycaptcha_register_form_check'), 1);
	add_action('bp_before_registration_submit_buttons', array($keycaptcha_wp_o, 'keycaptcha_register_bp_form' ));
	add_action('bp_signup_validate', array($keycaptcha_wp_o, 'keycaptcha_register_bp_form_check' ));

}

// adding KeyCAPTCHA to login form only for 2.8 and above
if ( ( ( $kc_vexp[0] >= 2 ) || ( $kc_vexp[1] > 7 ) ) && ( $keycaptcha_wp_o->keycaptcha_vars['keycaptcha_on_login'] == 'true' ) )
{	
	add_action('login_form', array($keycaptcha_wp_o, 'keycaptcha_register_form'), 1);
	add_filter('authenticate', array($keycaptcha_wp_o, 'keycaptcha_auth_check'), 40, 3);
}

// adding KeyCAPTCHA to lost password form
if ( $keycaptcha_wp_o->keycaptcha_vars['keycaptcha_on_lostpwd'] == 'true' ) {
	add_action('lostpassword_form', array($keycaptcha_wp_o, 'keycaptcha_register_form'), 1);
	add_filter('allow_password_reset', array($keycaptcha_wp_o, 'keycaptcha_lost_password_check'), 1);
}

//add_action('bp_sidebar_login_form', array( $keycaptcha_wp_o, 'keycaptcha_register_form' ) );

if ( $keycaptcha_wp_o->keycaptcha_vars['keycaptcha_on_cf7'] == 'true' ) {
	// adding KeyCAPTCHA to CF7
	if (function_exists('wpcf7_add_shortcode')) {
		wpcf7_add_shortcode('keycaptcha', 'keycaptcha_shortcode', true );
		add_filter( 'wpcf7_validate_keycaptcha', array($keycaptcha_wp_o, 'keycaptcha_process_custom_forms'), 10, 2 );
	}
}

// reserved for custom hooks 
//add_shortcode('keycaptcha', 'keycaptcha_shortcode', true );
//add_filter('keycaptcha_process_custom_forms', array($keycaptcha_wp_o, 'keycaptcha_process_custom_forms'), 0);
}

// uninstall KeyCAPTCHA
if (function_exists('register_uninstall_hook'))
{
	register_uninstall_hook(__FILE__, 'keycaptcha_uninstall');
}
?>
