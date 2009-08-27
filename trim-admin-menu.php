<?php
/*
	Plugin Name: Trim Admin Menu
	Plugin URI: 
	Description: Hide menu items in the admin interface from non-admin users.
	Version: 1.0
	Author: Severin Heiniger <severinheiniger@gmail.com>
	Author URI: http://claimid.com/severinheiniger
*/

/*
	Copyright 2009  Severin Heiniger (severinheiniger@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if (!class_exists('TrimAdminMenuPlugin')):

class TrimAdminMenuPlugin {
	var $name = 'Trim Admin Menu';
	var $basename = '';
	var $domain = 'trim-admin-menu';
	var $nonce_field = 'explain_nonce_admin_trim_menu';
	
	var $options = null; // Don't use this directly
	var $options_saved = false;
	
	var $default_options = array('menu' => array(), 'submenu' => array());
	var $default_menu = null;
	var $default_submenu = null;
	
	function TrimAdminMenuPlugin() {
		if (!is_admin())
			return;
		
		$this->name = __($this->name, $this->domain);
		$this->basename = plugin_basename(__FILE__);
		
		register_activation_hook(__FILE__, array(&$this, 'install'));
		register_uninstall_hook(__FILE__, array(&$this, 'uninstall'));
		
		add_action('init', array(&$this, 'text_domain'));
		add_action('admin_menu', array(&$this, 'admin_menu'));
		add_action('load-appearance_page_trim-admin-menu/trim-admin-menu',
			array(&$this, 'maybe_save_options'));
		
		add_filter($this->nonce_field, array(&$this, 'explain_nonce'));
	}
	
	function explain_nonce($msg) {
		return __('Unable to perform action: Your WordPress session has ' .
			'expired.  Please login and try again.');
	}
	
	function text_domain() {
		$plugin_dir = basename(dirname(__FILE__));
		$abs_plugin_dir = 'wp-content/plugins/' . $plugin_dir;
		$lang_dir = $plugin_dir . '/lang';
		
		load_plugin_textdomain($this->domain, $abs_plugin_dir, $lang_dir);
	}
	
	function install() {
		add_option($this->domain, $this->default_options);
	}
	
	function uninstall() {
		delete_option($this->domain);
	}
	
	function plugin_action_links($action_links) {
		$label = __('Settings');
		$link = "<a href='themes.php?page={$this->basename}'>{$label}</a>";
		
		array_unshift($action_links, $link);
		
		return $action_links;
	}
	
	function get_options() {
		if (!empty($this->options))
			return $this->options;
		
		$this->options = get_option($this->domain);
		
		if (!isset($this->options['menu']))
			$this->options = $this->default_options;
		
		return $this->options;
	}
	
	function admin_menu() {
		global $wp_version, $menu, $submenu;
		
		if (current_user_can('manage_options')) {
			if (version_compare($wp_version, '2.6.999', '>'))
				add_filter('plugin_action_links_' . $this->basename,
					array(&$this, 'plugin_action_links'));
			
			add_theme_page($this->name, $this->name, 9, $this->basename,
				array(&$this, 'options_page'));
		}
		
		$this->default_menu = $menu;
		$this->default_submenu = $submenu;
		
		// For non-admin users the 'Users' menu is replaced by a simple
		// 'Profile' menu. Fake the existence of such a 'Profile' menu so that
		// it listed in the option page.
		$this->default_menu[70] = array(__('Profile'), 'read', 'profile.php',
			'', 'menu-top', 'menu-users', 'div');
		
		if (!current_user_can('level_10')) {
			$options = $this->get_options();
			
			foreach ($menu as $index => $item) {
				if (in_array($item[2], $options['menu']))
					unset($menu[$index]);
				else if (!empty($submenu[$item[2]])) {
					foreach ($submenu[$item[2]] as $subindex => $subitem) {
						if (in_array($subitem[2], $options['submenu']))
							unset($submenu[$item[2]][$subindex]);
					}
				}
			}
		}
	}
	
	function maybe_save_options() {
		$options = $this->get_options();
		
		if (isset($_POST['submitted'])) {
			check_admin_referer($this->nonce_field);
			
			$options['menu'] = array();
			$options['submenu'] = array();
			
			foreach ($this->default_menu as $index => $item) {
				if ($this->check_hidden($item[2]))
					$options['menu'][] = $item[2];
				else if (!empty($this->default_submenu[$item[2]])) {
					foreach ($this->default_submenu[$item[2]] as $subindex => $subitem) {
						if ($this->check_hidden($subitem[2]))
							$options['submenu'][] = $subitem[2];
					}
				}
			}
			
			update_option($this->domain, $options);
			$this->options = $options;
			$this->options_saved = true;
		}
	}
	
	function check_hidden($target) {
		$target_hash = md5($target);
		
		return isset($_POST[$target_hash]) and $_POST[$target_hash] == '1';
	}
	
	function only_admin_can($cap) {
		$capabilities = array(
			'install_themes', 'update_themes','switch_themes', 'edit_themes',
			'install_plugins', 'activate_plugins', 'edit_plugins',
			'update_plugins', 'delete_plugins', 'create_users', 'edit_users',
			'delete_users', 'edit_files', 'manage_options', 'import',
			'unfiltered_upload', 'edit_dashboard');
		
		return in_array($cap, $capabilities);
	}
	
	function options_page() {
		$options = $this->get_options();
		$action_url = $_SERVER[PHP_SELF] . '?page=' . $this->basename;
		$help = __('Select the menu items that should be hidden from ' .
			'non-administrator users.', $this->domain);
		
		if ($this->options_saved)
			echo "<div id='message' class='updated fade'><p><strong>" . 
				__('Settings saved.') . '</strong></p></div>';
		
		echo <<<END
		<div class='wrap'>
			<h2>{$this->name}</h2>
			<p>$help</p>
			<form name='trim_admin_menu' action='$action_url' method='post'>
END;
		wp_nonce_field($this->nonce_field);
		
		echo "<ul id='menu-selection'>";
		
		foreach ($this->default_menu as $index => $item) {
			$label = $item[0];
			$cap = $item[1];
			$target = $item[2];
			$target_hash = md5($target);
			
			if ($item[4] == 'wp-menu-separator' or
				$item[5] == 'menu-dashboard' or
				empty($label) or $this->only_admin_can($cap))
				continue;
			
			if (in_array($target, $options['menu']))
				$checked = 'checked=checked ';
			else
				$checked = '';
			
			echo "<li><input name='$target_hash' type='checkbox' " .
				"id='$target_hash' value='1' $checked/><strong>$label</strong>";
			
			if (!empty($this->default_submenu[$item[2]])) {
				echo '<ul>';
				
				foreach ($this->default_submenu[$item[2]] as $subindex => $subitem) {
					$sublabel = $subitem[0];
					$subcap = $subitem[1];
					$subtarget = $subitem[2];
					$subtarget_hash = md5($subtarget);
					
					if ($target == $subtarget or $this->only_admin_can($subcap))
						continue;
					
					if (in_array($subtarget, $options['submenu']) or 
						!empty($checked))
						$subchecked = 'checked=checked ';
					else
						$subchecked = '';
					
					echo "<li><input name='$subtarget_hash' type='checkbox' " .
						"id='$subtarget_hash' value='1' $subchecked />" .
						"$sublabel</li>";
				}
				
				echo '</ul>';
			}
			
			echo '</li>';
		}
		
		$save_label = __('Save Changes');
		echo <<<END
				</ul>
				<input type='hidden' name='submitted' value='1' />
				<div class='submit'>
					<input type='submit' name='Submit' class='button-primary'
						value='$save_label' />
				</div>
			</form>
		</div>
		<style type="text/css">
			#menu-selection li ul {
				margin-left: 1.5em;
			}
			#menu-selection li ul li {
				display: inline;
				margin-right: 1em;
			}
			#menu-selection input {
				margin-right: 0.4em;
			}
			#menu-selection li span {
				display: none;
			}
		</style>
END;
	}
}

endif; // end if !class_exists()

if ( class_exists('TrimAdminMenuPlugin') )
	new TrimAdminMenuPlugin();
?>
