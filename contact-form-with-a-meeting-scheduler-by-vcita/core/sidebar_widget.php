<?php

class ls_sidebar_widget {

	/**
	 * Sets up helpers as global
	 * @since 0.1.1
	 */
	public $ls_helpers;

	/**
	 * Sets up helpers as global
	 * @since 0.1.1
	 */
	public $ls_embed;

	function __construct(){

		$this->ls_helpers = new ls_helpers();
		$this->ls_embed = new livesite_embed_code();

    add_action( 'admin_enqueue_scripts', array($this,'enqueue_scripts') );

    wp_register_sidebar_widget('vcita_widget_id', 'vCita Sidebar Widget', array($this,'vcita_widget_content'));
    wp_register_widget_control('vcita_widget_id', 'vCita Sidebar Widget', array($this,'vcita_widget_admin'));

	}

  /**
	 * Load Scripts
	 * @since 0.1.0
	 */
	function enqueue_scripts( $hook_suffix ){

		$ls_helpers = $this->ls_helpers;

		$path = plugin_dir_url( __FILE__ );

    // Remove core from url
    $path = str_replace( '/core', '', $path );

		$ls_helpers->register_scripts(array(
			'livesite' => array(
				'path'		=> $path . "js/livesite.js",
				'deps'		=> array('jquery'),
				'params' => array(
					'ls_admin_url'  	=> get_admin_url(),
					'ls_locale'				=> get_locale(),
					'ls_site_url'	 		=> get_site_url()
				)
			)
		));

		$page_hook_id = $this->setings_page_id();

		if ( $hook_suffix == $page_hook_id ){
			wp_enqueue_script( 'livesite' );
		}
	}

  /**
	 * Utility: Page Hook
	 * The Settings Page Hook, it's the same with global $hook_suffix.
	 * @since 0.1.0
	 */
	function setings_page_id(){
		return 'widgets.php';
	}

  /**
   * Use the current settings and create the vCita widget. - simply call the main vcita_add_contact function with the required parameters
   */
  function vcita_widget_content($args) {

    $ls_embed = $this->ls_embed;

    $settings = ls_get_settings();
		$uid = $settings['vcita_params']['uid'];

    $atts = array('type' => 'widget', 'height' => '430px', 'width' => '100%');

    $html = $ls_embed->create_embed_code( 'widget', $uid, '100%', '430px' );

    echo $html;

  }

  /**
   * Create the vCita floatting widget Settings form content.
   *
   * This is based on Wordpress guidelines for creating a single widget.
   */
  function vcita_widget_admin() { ?>
      <script type="text/javascript">
        jQuery(function ($) {
          $('.vcita-email')
            .on('keypress', function( e ){
              if (e.keyCode == 13)
              	$('.js-vcita-reconnect').click();
            });
        });
      </script>
      <div id="vcita_config">
      <?php

       $is_vcita_connected = ls_is_vcita_connected();

       if( !$is_vcita_connected ) {

         $admin_email = get_option('admin_email','');

         ?>
      		<h3>Contact requests will be sent to this email:</h3>
      		<input id="connect-email" type="text" value="<?php echo $admin_email; ?>">
      		<a href="#" class="js-vcita-connect button button-primary">OK</a>
      	<?php }
      		else {
            $vcita_params = ls_get_vcita_params();
      		?>
      		<h3>Contact requests will be sent to this email:</h3>
      		<label class="checked" for="user-email"></label>
      		<input class="vcita-email" type="text" disabled="disabled" value="<?php echo $vcita_params['email']; ?>"/>
      		<br><br>
      		<a href="#" class="js-vcita-reconnect button button-primary">Change Email</a>
      		<br><br>
      		<a data-open-popup class="button button-primary" href="https://www.vcita.com/widget_implementations?platform=wordpress&widget=sidebar&key=<?php $vcita_params['uid'] ?>">Edit</a>
      	<?php } ?>
      </div>
  <?php }

}

new ls_sidebar_widget();

?>
