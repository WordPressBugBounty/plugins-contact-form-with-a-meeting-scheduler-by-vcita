<?php
/*
Plugin Name: Contact Form Builder
Plugin URI: https://www.vcita.com
Description: Contact form by vCita proves to increase the number of contact form requests
Version: 4.10.2
Author: vCita.com
Author URI: https://www.vcita.com
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

/*
*  livesite_main_cf
*
*  @description: controller for main init of LiveSite Plugin
*  @since: 4.0.2
*  @created: 01/10/15
*/

class livesite_main_cf {

    /**
     * Defines the plugin settings for the init instance
     * Is only used in this class for settings relaying
     * @since 0.1.0
     */
    public $old_plugin_settings;
	
	function __construct() {
		// Retrieve old plugin settings
		$this->old_plugin_settings = get_option('livesite_plugin_settings', false);
		$run_plugin = true; // Flag to determine if the plugin should run
		
		// Check if this is not a fresh install
		if ($this->old_plugin_settings) {
			// Check if the active module is not the expected one
			if ($this->old_plugin_settings['main_module'] != 'form_builder') {
				// Display an admin notice
				add_action('admin_notices', array($this, 'other_plugin_installed'));
				// Deactivate the current plugin
				add_action('admin_init', array($this, 'deactivate_plugin'));
				
				$run_plugin = false; // Set flag to false to prevent further execution
			}
		}
		
		// Proceed with plugin initialization if the flag is true
		if ($run_plugin) {
			$path = plugin_dir_path(__FILE__); // Get the plugin directory path
			
			// Include the plugin initialization file
			require_once($path . 'plugin_init.php');
			
			// Initialize the main plugin class
			new ls_plugin_init();
		}
	}

    // Shows message that another plugin is installed
	function other_plugin_installed() {
		$settings = $this->old_plugin_settings;
		
		if (isset($settings['main_module'], $settings['modules'][$settings['main_module']]['title'])) {
			$main_module = $settings['main_module'];
			$module_title = $settings['modules'][$main_module]['title'];
		} else {
			$module_title = __('Unknown Module', 'livesite');
		}
		
		?>
        <div id="message" class="error notice is-dismissible">
            <p>
				<?php
				echo sprintf(__('vCita LiveSite Pack is already installed. Please use <a href="%s">%s</a>', 'livesite'),
					esc_url(get_admin_url('', '', 'admin').'plugins.php'),
					esc_html($module_title));
				?>
            </p>
        </div>
		<?php
	}
	
	function deactivate_plugin() {
		deactivate_plugins(plugin_basename(__FILE__));
	}

}

new livesite_main_cf();
?>
