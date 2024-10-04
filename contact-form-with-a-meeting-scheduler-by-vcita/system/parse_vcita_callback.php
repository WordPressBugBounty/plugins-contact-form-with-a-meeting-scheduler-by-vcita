<?php
/*
*  livesite_parse_callback
*
*  @description: Parses the return callback once the user logged in to vCita
*  @since: 3.6
*  @created: 25/01/13
*/

class livesite_parse_callback
{

	/**
	 * Sets up helpers as global
	 * @since 0.1.1
	 */
	public $ls_helpers;

	/*
	*  __construct
	*
	*  @description:
	*  @since 3.1.8
	*  @created: 23/06/12
	*/

	function __construct(){

		$this->ls_helpers = new ls_helpers();

        // Uses priority 20 to laod after plugin init
        add_action( 'admin_menu', array($this, 'add_parse_vcita_callback_page'), 20 );

    }

    /**
     * Adds a hidden page to allow reseting the plugin (mainly used for degbugging but not exclusive)
     * @since 0.1.0
     */
    function add_parse_vcita_callback_page(){
        add_submenu_page(
            null,
            __('', 'livesite'),
            __('', 'livesite'),
            'edit_posts',
            'live-site-parse-vcita-callback',
            array($this, 'ls_parse_vcita_callback')
        );
    }

    /**
     * Parses the return values from vcita connection
     * @since 0.1.0
     */
    function ls_parse_vcita_callback(){
        if(current_user_can('install_plugins')) {
            $success = filter_var($_GET['success'], FILTER_VALIDATE_BOOLEAN);
            $uid = filter_var($_GET['uid'], FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_ENCODE_HIGH);
            $first_name = filter_var($_GET['first_name'], FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_ENCODE_HIGH);
            $last_name = filter_var($_GET['last_name'], FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_ENCODE_HIGH);
            $title = filter_var($_GET['title'], FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_ENCODE_HIGH);
            $confirmation_token = filter_var($_GET['confirmation_token'], FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_ENCODE_HIGH);
            $confirmed = filter_var($_GET['confirmed'], FILTER_VALIDATE_BOOLEAN);
            $engage_delay = filter_var($_GET['engage_delay'], FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_ENCODE_HIGH);
            $implementation_key = filter_var($_GET['implementation_key'], FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_ENCODE_HIGH);
            $email = filter_var($_GET['email'], FILTER_VALIDATE_EMAIL);

            ls_set_settings( array(
                'vcita_connected' => true,
                'vcita_params' => array(
                  'success'              => $success,
                    'uid'                  => $uid,
                    'first_name'           => $first_name,
                    'last_name'            => $last_name,
                    'title'                => $title,
                    'confirmation_token'   => $confirmation_token,
                    'confirmed'            => $confirmed,
                    'engage_delay'         => $engage_delay,
                    'implementation_key'   => $implementation_key,
                    'email'                => $email
                )
            ));

            $ls_helpers = $this->ls_helpers;

            // Replace curly brace tags inside of html code
            // $ls_helpers->ls_replace_default_tags();

            $redirect_url = $ls_helpers->get_plugin_path();
        }
    ?>
    <script type="text/javascript">
        window.location = "<?php echo $redirect_url; ?>";
    </script>
    <?php }

}

new livesite_parse_callback();

?>
