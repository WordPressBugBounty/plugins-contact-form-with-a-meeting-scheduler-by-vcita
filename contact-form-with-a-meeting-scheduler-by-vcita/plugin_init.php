<?php

class ls_plugin_init {

	public $ls_helpers;

	function __construct(){

		require_once('core/helpers.php');

		$this->ls_helpers = new ls_helpers();

		require_once('core/settings.php');
		require_once('core/ajax_api.php');
		require_once('system/parse_vcita_callback.php');
		require_once('system/reset_plugin.php');
		require_once('core/sdk.php');
		require_once('core/embed_code.php');
		require_once('core/shortcodes.php');

		// For legacy wordpress sidebar widget on contact form
		require_once('core/sidebar_widget.php');

		// UI Items that are used on every page
		require_once('includes/sidebar.php');
		require_once('includes/footer.php');
		require_once('includes/pre_header.php');

		/* Add Settings Page */
		add_action( 'admin_menu', array($this,'ls_settings_setup') );

		add_action( 'upgrader_process_complete', array($this,'redirect_after_upgrade'), 10, 2 );

	}

	/**
	 * Create Settings Page
	 * @since 0.1.0
	 */
	function ls_settings_setup() {
		$ls_helpers = $this->ls_helpers;
		
		// Setup small link on WordPress plugins page that directs user to settings page
		add_filter('plugin_action_links', array($this, 'plugin_action_links'), 10, 2);
		
		/* Add settings menu page */
		$settings_page = add_menu_page(
			'LiveSite Pack',             /* Page Title */
			'LiveSite',                  /* Menu Title */
			'manage_options',            /* Capability */
			'live-site',                 /* Page Slug */
			array($this, 'ls_settings_page'), /* Settings Page Function Callback */
			plugin_dir_url(__FILE__) . 'images/vcita-icon.png', /* Menu icon */
			'2.3489'
		);
		
		$os = $ls_helpers->get_os();
		
		// Define directory separator based on OS
		if ($os !== 'win32') {
			define('LS_SLASH', '/');
		} else {
			define('LS_SLASH', '\\');
		}
		
		// If settings don't exist in db set them up from defaults
		if (!ls_get_settings()) {
			ls_init_default_settings();
		}
		
		// Check if vcita is not connected
		// If old connection details are in place use them
		if (!ls_is_vcita_connected()) {
			$old_plugin_db_key = $ls_helpers->get_old_plugin_db_key();
			
			// Get old plugin connection details
			$old_connection_options = get_option($old_plugin_db_key);
			
			// Setup connection details
			if ($old_connection_options) {
				$ls_helpers->parse_old_plugin_params($old_connection_options);
				
				// Flag plugin as upgraded plugin so we know not to auto-install module pages
				ls_set_settings(array(
					'plugin_upgraded' => true
				));
				
				$vcita_params = ls_get_vcita_params();
				
				// Set value of active engage widget to the same value as the old plugin
				if (isset($vcita_params['engage_active'])) {
					ls_set_module_data('livesite_widget', array('show_livesite' => $vcita_params['engage_active']));
				}
				
				$redirect_url = esc_url($ls_helpers->get_plugin_path()); // Escape the redirect URL
				wp_redirect($redirect_url);
				exit;
			}
		}
		
		// Only show modules and settings if we're connected to vcita
		if (ls_is_vcita_connected()) {
			require_once('system/settings_page.php');
			require_once('system/backoffice_page.php');
			
			$this->init_modules();
			
			$main_module = ls_get_main_module();
			$settings = ls_get_settings();
			
			// For a one-time redirect after vcita connect
			if (!$settings['plugin_initially_activated']) {
				// Set plugin as already activated
				ls_set_settings(array(
					'plugin_initially_activated' => true
				));
				
				// Get main module data
				$module_data = ls_get_module_data($main_module);
				
				// Redirect to main module page
				$plugin_page_url = esc_url($ls_helpers->get_plugin_page_url($module_data['slug'])); // Escape URL
				wp_redirect($plugin_page_url);
				exit;
			}
		}
		
		/* Vars */
		$page_hook_id = $this->setings_page_id();
		
		/* Do stuff in settings page, such as adding scripts, etc. */
		if (!empty($settings_page)) {
			/* Load the JavaScript needed for the settings screen. */
			add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
			add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
		}
	}


	/**
	 * Setup small settings link on wordpress plugins page
	 * @since 0.1.0
	 */
	function plugin_action_links( $links, $file ) {
		$ls_helpers = $this->ls_helpers;
		
		// Check if the current plugin matches our plugin
		if ( $file !== plugin_basename( plugin_dir_path( __FILE__ ) ) . '/Livesite.php' ) {
			return $links; // Return the original links if not matching
		}
		
		// Get the redirect URL safely
		$redirect_url = esc_url( $ls_helpers->get_plugin_path() );
		
		// Create the settings link with escaped text
		$settings_link = '<a href="' . $redirect_url . '">' . esc_html__( 'Settings', 'livesite' ) . '</a>';
		
		// Prepend the settings link to the existing links
		array_unshift( $links, $settings_link );
		
		return $links; // Return the modified links
	}

	/**
	 * Redirect user to main plugin page after update
	 * @since 0.1.0
	 */
	function redirect_after_upgrade( $upgrader_object, $options ) {
		// Ensure this runs only in admin area
		if ( is_admin() ) {
			$contactform_plugin = plugin_basename( __FILE__ );
			
			// Check if the action is an update for a plugin
			if ( isset($options['action']) && $options['action'] == 'update'
				&& isset($options['type']) && $options['type'] == 'plugin'
				&& isset($options['plugins']) && is_array($options['plugins'])) {
				
				foreach ( $options['plugins'] as $plugin ) {
					// Validate the plugin basename
					if ( $plugin === $contactform_plugin ) {
						$ls_helpers = $this->ls_helpers;
						
						// Get the redirect URL safely
						$redirect_url = esc_url($ls_helpers->get_plugin_path());
						
						// Redirect to the plugin path
						wp_redirect( $redirect_url );
						exit; // Ensure no further code runs after redirect
					}
				}
			}
		}
	}

	/**
	 * Utility: Page Hook
	 * The Settings Page Hook, it's the same with global $hook_suffix.
	 * @since 0.1.0
	 */
	function setings_page_id(){
		return 'toplevel_page_live-site';
	}

	/**
	 * Load Scripts
	 * @since 0.1.0
	 */
	function enqueue_scripts($hook_suffix) {
		$ls_helpers = $this->ls_helpers;
		
		// Get the plugin directory path
		$path = $ls_helpers->get_dir(__FILE__);
		
		// Register scripts with proper escaping
		$ls_helpers->register_scripts(array(
			'livesite' => array(
				'path' => esc_url($path . 'js/livesite.js'), // Escape URL for safety
				'deps' => array('jquery'),
				'params' => array(
					'ls_admin_url' => esc_url(get_admin_url()), // Escape URL for safety
					'ls_locale' => get_locale(),
					'ls_module_nonce' => wp_create_nonce( 'activate-module' ), // Nonce for security
					'ls_site_url' => esc_url(get_site_url()) // Escape URL for safety
				)
			),
			'custom_page' => array(
				'path' => esc_url($path . 'js/custom-page.js') // Escape URL for safety
			)
		));
		
		// Get the settings page ID
		$page_hook_id = $this->setings_page_id();
		
		// Enqueue scripts if the current hook suffix matches the settings page ID
		if ($hook_suffix === $page_hook_id) {
			wp_enqueue_script('common');
			wp_enqueue_script('wp-lists');
			wp_enqueue_script('livesite');
		}
	}


	/**
	 * Load Styles
	 * @since 0.1.0
	 */
	function enqueue_styles($hook_suffix) {
		$ls_helpers = $this->ls_helpers;
		
		// Get the plugin directory path and version
		$path = $ls_helpers->get_dir(__FILE__);
		$version = $ls_helpers->get_plugin_version();
		
		// Register ACF styles
		$styles = array(
			'livesite' => esc_url($path . 'css/livesite.css'), // Escape URL for safety
			'livesite_icon_font' => esc_url($path . 'css/icon-font.css'), // Escape URL for safety
			'toggle_switch' => esc_url($path . 'css/toggles-full.css'), // Escape URL for safety
		);
		
		// Register each style
		foreach ($styles as $k => $v) {
			wp_register_style($k, $v, array(), $version); // Ensure dependencies are handled (set to array())
		}
		
		// Get the settings page ID
		$page_hook_id = $this->setings_page_id();
		
		// Enqueue styles if the current hook suffix matches the settings page ID
		if ($hook_suffix === $page_hook_id) {
			wp_enqueue_style('livesite');
			wp_enqueue_style('livesite_icon_font');
		}
	}

	/**
	 * Settings Page Callback
	 * used in ls_settings_setup().
	 * @since 0.1.0
	 */
	function ls_settings_page(){

		/* global vars */
		global $hook_suffix; // Access the global hook suffix
		
		$ls_helpers = $this->ls_helpers; // Access helper methods
		$modules = ls_get_modules(); // Get the list of modules

        // When plugin is not connected to vcita get admin email to populate email signup field
		$admin_email = sanitize_email(get_option('admin_email', '')); // Sanitize admin email to prevent potential issues

        // Check if user has connected their account to vCita
		$is_vcita_connected = ls_is_vcita_connected(); // Presume this function is secure
		
		$plugin_page_url = esc_url($ls_helpers->get_plugin_page_url('live-site-backoffice')); // Escape URL for safety
		
		$main_module = ls_get_main_module(); // Get the main module
		$main_title = '';

        // Partner URL
		$partner_url = esc_url('https://www.vcita.com/partners?' . $ls_helpers->get_plugin_identifier()); // Escape URL for safety
		
		
		if ( $main_module ){
			
			$module_data = ls_get_module_data($main_module); // Get module data
			
			// Check if module data is set and not empty
			if (isset($module_data['main_title'], $module_data['text'])) {
				$module_main_title = sanitize_text_field($module_data['main_title']); // Sanitize module title
				$module_text = wp_kses_post($module_data['text']); // Sanitize module text for safe HTML output
			}

		}

	?>

	<div class="wrap ls-wrap">

		<?php settings_errors(); ?>

		<?php ls_render_pre_header(); ?>

		<div class="ls-header">

			<div class="ls-header__decoration"></div>
			<div class="ls-header__main-decoration"></div>

			<div class="ls-header__wrap">
				<div class="ls-header__title"><?php _e( $module_main_title,'livesite'); ?></div>
				<div class="ls-header__text-wrap">
					<span class="ls-header__text-title"><?php _e('Part of','livesite'); ?></span>
					<span class="ls-header__text-icon icon-Livesite"></span>
					<span class="ls-header__text"><?php _e('vCita LiveSite Pack','livesite'); ?></span>
				</div>
			</div>

		</div>

		<?php if ( !$is_vcita_connected ): ?>
		<div class="ls-section text-center">
			<input class="connect-email-input"
				   placeholder="your@email.com"
				   type="text"
				   name="connect-email"
				   id="connect-email"
				   value="<?php echo $admin_email; ?>">
			<a class="ls-button--central js-vcita-connect"><?php _e('Connect to Get Started', 'livesite'); ?></a>
		</div>
		<?php endif; ?>

		<div class="ls-section text-center">
			<div class="ls-small-text push-down-1"><?php echo $module_main_title . ' ' . __('is part of vCita LiveSite Pack','livesite'); ?></div>
			<strong class="ls-section__title"><?php _e('Your LiveSite Modules:','livesite'); ?></strong>

			<ul class="ls-modules">

				<?php foreach ( $modules as $module_key => $module ):

					$module_classes = '';

					$module_classes .= $is_vcita_connected ? '' : ' ls-modules__module--disabled';
					$module_classes .= $module['active'] ? ' ls-modules__module--active' : '';
					$module_classes .= $module['active'] && $is_vcita_connected ? ' js-ls-modules__module' : '';

				?><li class="ls-modules__module <?php echo $module_classes; ?>">
					<span class="ls-modules__module-icon <?php echo $module['icon']; ?>"></span>

					<div class="ls-modules__module-content">
						<strong class="ls-modules__module-title"><?php echo $module['title']; ?></strong>
						<div class="ls-modules__module-text"><?php echo $module['text']; ?></div>
						<?php if ( ! $module['active'] ){ ?>
						<button class="button button-primary <?php echo $is_vcita_connected ? 'js-ls-modules__module-button' : 'button-disabled'; ?>"
							    data-module-name="<?php echo $module_key; ?>">
							<?php _e('Add','livesite'); ?>
						</button>
						<?php } else if ( $is_vcita_connected ) { ?>
							<a href="<?php echo $ls_helpers->get_plugin_page_url( $module['slug'] ); ?>" class="button button-primary js-ls-modules__module-button--edit <?php echo $is_vcita_connected ? '' : 'button-disabled'; ?>">
								<?php _e('Edit','livesite'); ?>
							</a>
						<?php }	?>

					</div>
				</li><?php
				endforeach; ?>
			</ul>

		</div>

		<div class="ls-section ls-section--last text-center">
			<strong class="ls-section__title push-down-3"><?php _e('One Platform which enables all modules','livesite'); ?></strong>

			<div class="ls-promotions">
				<div class="ls-promotions__promotion<?php echo $is_vcita_connected ? '' : ' ls-promotions__promotion--disabled' ?>">
					<div class="ls-promotions__icon ls-promotions__promotion--color-1">
						<span class="icon-Optimization"></span>
					</div>
					<div class="ls-promotions__title"><?php _e('Backoffice','livesite'); ?></div>
					<div class="ls-promotions__text"><?php _e('All livesite modules plug into a single business management dashboard','livesite'); ?></div>
					<a <?php echo $is_vcita_connected ? 'href="'. $plugin_page_url .'"' : ''; ?> class="ls-promotions__url"><?php _e('Go to Backoffice','livesite'); ?></a>
				</div><!--
				--><div class="ls-promotions__promotion">
					<div class="ls-promotions__icon ls-promotions__promotion--color-2">
						<span class="icon-Code-Window"></span>
					</div>
					<div class="ls-promotions__title"><?php _e('SDK for Developers','livesite'); ?></div>
					<div class="ls-promotions__text"><?php _e('To achieve maximum flexibility use our LiveSite SDK','livesite'); ?></div>
					<a href="//developers.vcita.com/" target="_blank" class="ls-promotions__url"><?php _e('Go to SDK Documentation','livesite'); ?></a>
				</div><!--
				--><div class="ls-promotions__promotion">
					<div class="ls-promotions__icon ls-promotions__promotion--color-3">
						<span class="icon-Partners"></span>
					</div>
					<div class="ls-promotions__title"><?php _e('Partner Program','livesite'); ?></div>
					<div class="ls-promotions__text"><?php _e('Join over 8500 partners who leverage the vCita web engagement solution to extend their brand','livesite'); ?></div>
					<a href="<?php echo $partner_url; ?>" target="_blank" class="ls-promotions__url"><?php _e('Learn More','livesite'); ?></a>
				</div>
			</div>
		</div>

		<?php ls_render_footer(); ?>

	</div>

	<?php }

	/**
	 * Settings
	 * Initiates modules based on their active definition in the $ls_settings
	 * @since 0.1.0
	 */
	function init_modules() {
		$ls_helpers = $this->ls_helpers; // Access the helper methods
		$modules = ls_get_modules(); // Retrieve the list of modules
		$dir = $ls_helpers->get_path(); // Get the base directory path
		
		// Iterate through all modules and initialize the active ones
		foreach ($modules as $module_name => $module) {
			if ($module['active']) {
				// Clean up the directory path
				$base_dir = str_replace('/core', '', $dir); // Remove '/core' from the path
				$base_dir = str_replace('\\core', '', $base_dir); // Remove '\core' (Windows compatibility)
				$base_dir = str_replace('/', LS_SLASH, $base_dir); // Normalize the directory separator
				
				// Attempt to require the module file, handle potential errors
				$module_path = $base_dir . $module['path'];
				if (file_exists($module_path)) {
					require_once($module_path); // Include the module file
				} else {
					error_log("Module file not found: " . $module_path); // Log an error if the file does not exist
				}
			}
		}
	}
}

?>
