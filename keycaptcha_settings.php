<?php
/*
KeyCAPTCHA plugin for WordPress
Version		2.5.1
Author		Mersane, Ltd
Author URI	https://www.keycaptcha.com
License		GNU GPL2
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

if (isset($_POST['submit']))
{
	$optionarray_update = array
		(
			'keycaptcha_site_private_key' 	=> (isset( $_POST['keycaptcha_site_private_key'] ) ) ? $_POST['keycaptcha_site_private_key'] : '',
			'keycaptcha_code' 		=> '',
			'keycaptcha_on_register' 	=> (isset( $_POST['keycaptcha_on_register'] ) ) ? 'true' : 'false',
			'keycaptcha_on_login' 		=> (isset( $_POST['keycaptcha_on_login'] ) ) ? 'true' : 'false',
			'keycaptcha_on_lostpwd' 	=> (isset( $_POST['keycaptcha_on_lostpwd'] ) ) ? 'true' : 'false',
			'keycaptcha_on_cf7' 		=> (isset( $_POST['keycaptcha_on_cf7'] ) ) ? 'true' : 'false',
			'keycaptcha_custom_text' 	=> (isset( $_POST['keycaptcha_custom_text'] ) ) ? $_POST['keycaptcha_custom_text'] : '',
			'keycaptcha_posts_cnt'  	=> (isset( $_POST['keycaptcha_posts_cnt'] ) ) ? $_POST['keycaptcha_posts_cnt'] : '',
			'keycaptcha_link'		=> (isset( $_POST['keycaptcha_link'] ) ) ? 'true' : 'false' ,
		);
	foreach ($optionarray_update as $key => $val)
	{
		$optionarray_update[$key] = str_replace('&quot;','"',trim($val));
	}
	update_option('keycaptcha_vars_db', $optionarray_update);
	$this->keycaptcha_vars = get_option('keycaptcha_vars_db');
	foreach($this->keycaptcha_vars as $key => $val)
	{
		$this->keycaptcha_vars[$key] = str_replace('\\','',$val);
	}
	if (function_exists('wp_cache_flush'))
	{
		wp_cache_flush();
	}
}
?>

<?php if ( !empty($_POST ) ) : ?>
<div id="message" class="updated fade"><p><strong><?php _e('Settings saved.', 'keycaptcha') ?></strong></p></div>
<?php endif; ?>
<div class="kc_div_settings">
	<h2><?php _e('KeyCAPTCHA Settings', 'keycaptcha') ?></h2>
	<?php if ( file_exists(  WP_PLUGIN_DIR.'/keycaptcha/keycaptcha_multisite.php' ) ) : ?>
	<h3><?php _e('settings not available', 'keycaptcha') ?></h3>
	<?php else: ?>
	<form name="formsettings" action="<?php echo admin_url( 'plugins.php?page=keycaptcha/keycaptcha.php' );?>" method="post">
		<fieldset class="kc_fs_settings">
			<h3><?php _e('General settings', 'keycaptcha') ?></h3>
			<table width="100%" cellspacing="2" cellpadding="5" class="form-table">
				<tr>
					<th scope="row"><?php _e('Private key:', 'keycaptcha') ?></th>
					<td>
						<input name="keycaptcha_site_private_key" id="keycaptcha_site_private_key" type="text" style="width:540px;" value="<?php echo($this->keycaptcha_vars['keycaptcha_site_private_key']); ?>" />
					</td>
				</tr>
						<?php
							if ((empty($this->keycaptcha_vars['keycaptcha_site_private_key']))||(strpos($this->keycaptcha_vars['keycaptcha_site_private_key'],'0'))==0)
							{
echo '				<tr>
					<th scope="row"></th>
					<td>
';
								echo ('<b style="color:red;">'.__('Wrong KeyCAPTCHA private key. To get the correct private key please register your site on ', 'keycaptcha').'<a href="https://www.keycaptcha.com" target="_blank">www.keycaptcha.com</a></b>');
echo '					</td>
				</tr>';								
							}
							
						?>
				
				<tr>
					<th scope="row"><?php _e('Enable KeyCAPTCHA for users who have less than ', 'keycaptcha') ?></th>
					<td>
						<select name="keycaptcha_posts_cnt" id="keycaptcha_posts_cnt" type="checkbox" style="width:80px;">
							<option value="5" <?php if ( $this->keycaptcha_vars['keycaptcha_posts_cnt'] == '5' ) echo ' selected=selected '; ?> >5</option>
							<option value="10" <?php if ( $this->keycaptcha_vars['keycaptcha_posts_cnt'] == '10' ) echo ' selected=selected '; ?> >10</option>
							<option value="25" <?php if ( $this->keycaptcha_vars['keycaptcha_posts_cnt'] == '25' ) echo ' selected=selected '; ?> >25</option>
							<option value="50" <?php if ( $this->keycaptcha_vars['keycaptcha_posts_cnt'] == '50' ) echo ' selected=selected '; ?> >50</option>
							<option value="100000000" <?php if ( $this->keycaptcha_vars['keycaptcha_posts_cnt'] == '100000000' ) echo ' selected=selected '; ?> >Always</option>
							<option value="0" <?php if ( $this->keycaptcha_vars['keycaptcha_posts_cnt'] == '0' ) echo ' selected=selected '; ?> >Disable</option>
						</select> posts
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e('KeyCAPTCHA on registration', 'keycaptcha') ?></th>
					<td>
						<input name="keycaptcha_on_register" id="keycaptcha_on_register" type="checkbox" <?php if ( $this->keycaptcha_vars['keycaptcha_on_register'] == 'true' ) echo ' checked="checked" '; ?> />
						<label for="keycaptcha_on_register">
							<?php _e('Check this box to enable KeyCAPTCHA protection on the register form.', 'keycaptcha') ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e('KeyCAPTCHA on login', 'keycaptcha') ?></th>
					<td>
						<input name="keycaptcha_on_login" id="keycaptcha_on_login" type="checkbox" <?php if ( $this->keycaptcha_vars['keycaptcha_on_login'] == 'true' ) echo ' checked="checked" '; ?> />
						<label for="keycaptcha_on_register">
							<?php _e('Check this box to enable KeyCAPTCHA protection on the login form. This option is supported by WordPress ver. 2.8 or above.', 'keycaptcha') ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e('KeyCAPTCHA on lost password', 'keycaptcha') ?></th>
					<td>
						<input name="keycaptcha_on_lostpwd" id="keycaptcha_on_lostpwd" type="checkbox" <?php if ( $this->keycaptcha_vars['keycaptcha_on_lostpwd'] == 'true' ) echo ' checked="checked" '; ?> />
						<label for="keycaptcha_on_register">
							<?php _e('Check this box to enable KeyCAPTCHA protection on "Lost your password" form. This option is supported by WordPress ver. 2.7 or above.', 'keycaptcha') ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e('KeyCAPTCHA on Contact Form 7', 'keycaptcha') ?></th>
					<td>
						<input name="keycaptcha_on_cf7" id="keycaptcha_on_cf7" type="checkbox" <?php if ( $this->keycaptcha_vars['keycaptcha_on_cf7'] == 'true' ) echo ' checked="checked" '; ?> />
						<label for="keycaptcha_on_register">
							<?php _e(	'Check this box to enable KeyCAPTCHA protection on Contact Form 7.
									<br>
									<div style="margin-left:50px;">
									<p>To integarte KeyCAPTCHA with <b>Contact Form 7</b> please use the following instuctions:
									<br>
									<ol>
										<li>Copy the following tag with square brackets <b>[keycaptcha]</b></li>
										<li>Open the page with settings of Contact Form 7</li>
										<li>Paste the copied tag into "Form" section above the line which contains "&lt;p&gt;[submit "Send"]&lt;/p&gt;"</li>
									</ol></p></div>', 'keycaptcha') ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e('Custom text above KeyCAPTCHA:', 'keycaptcha') ?></th>
					<td>
						<input name="keycaptcha_custom_text" id="keycaptcha_custom_text" type="text" style="width:540px;" value="<?php echo($this->keycaptcha_vars['keycaptcha_custom_text']); ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e ('KeyCAPTCHA link', 'keycaptcha')?></th>
					<td>
						<input name='keycaptcha_link' type='checkbox' <?php if($this->keycaptcha_vars['keycaptcha_link']=='true') echo ' checked="checked" ' ?>> 
					</td>
				</tr>
			</table>
		</fieldset>
		<p class="submit">
			<input type="submit" name="submit" value="<?php _e('Save', 'keycaptcha') ?> &raquo;" />
		</p>
	</form>
	<?php endif; ?>
</div>
