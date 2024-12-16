<?php

class ls_shortcodes {

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

		add_shortcode( 'livesite-pay', array( $this, 'livesite_pay_shortcode' ));
		add_shortcode( 'vcita_pay_now', array( $this, 'livesite_pay_shortcode' ));
		add_shortcode( 'livesite-contact', array( $this, 'livesite_contact_shortcode' ));
		add_shortcode( 'vCitaContact', array( $this, 'livesite_contact_shortcode' ));
		add_shortcode( 'livesite-schedule', array( $this, 'livesite_scheduler_shortcode' ));

		add_action( 'wp_head', array($this, 'enqueue_front_end_stylesheet') );

	}

	/**
	 * Load front facing stylesheet
	 * @since 0.1.0
	 */
	function enqueue_front_end_stylesheet(){

		$ls_helpers = $this->ls_helpers;

		$path = $ls_helpers->get_dir( __FILE__ );

		wp_enqueue_style( 'livesite', $path . '../css/livesite-frontend.css' );

	}

	/**
	 * Add livesite payment button shortcode
	 * @since 0.1.0
	 */
	function livesite_pay_shortcode( $atts ) {
		// Attributes
		$atts = shortcode_atts(array(
			'label'          => 'PAY NOW',
			'show_icons'     => 'false',
			'payment_amount' => '',
			'title'          => '',
			'class'          => ''
		), $atts, 'livesite');
		
		$show_icons = filter_var($atts['show_icons'], FILTER_VALIDATE_BOOLEAN);
		$payment_icons = $show_icons ? '<div class="ls-payment-button-icons"></div>' : '';
		
		$options = 'data-options="';
		
		if ( !empty($atts['payment_amount']) ) {
			$options .= 'amount:' . esc_attr($atts['payment_amount']) . ';';
		}
		
		if ( !empty($atts['title']) ) {
			$options .= 'title:' . esc_attr($atts['title']) . ';';
		}
		
		$options .= '"';
		
		$html = '<div class="ls-payment-button-wrapper">
                <div class="ls-payment-button livesite-pay ' . esc_attr($atts['class']) . '" ' . $options . '>' . esc_html($atts['label']) . '</div>
                ' . $payment_icons . '
            </div>';
		
		return $html;
	}
	
	
	/**
	 * Add livesite contact form shortcode
	 * @since 0.1.0
	 */
	function livesite_contact_shortcode( $atts ) {
		$ls_embed = $this->ls_embed;
		
		// Attributes
		$atts = shortcode_atts(array(
			'title'   => 'Contact request',
			'class'   => '',
			'type'    => 'contact',
			'width'   => '100%',
			'height'  => '450px',
		), $atts, 'livesite');
		
		$title = 'title=' . esc_attr($atts['title']);
		
		$class = !empty($atts['class']) ? 'class="' . esc_attr($atts['class']) . '"' : '';
		
		// Получаем UID
		$settings = ls_get_settings();
		$uid = isset($settings['vcita_params']['uid']) ? $settings['vcita_params']['uid'] : '';
		
		// Генерируем HTML
		$html = $ls_embed->create_embed_code($atts['type'], $uid, esc_attr($atts['width']), esc_attr($atts['height']));
		
		return $html;
	}

	/**
	 * Add livesite scheduler shortcode
	 * @since 0.1.0
	 */
	function livesite_scheduler_shortcode( $atts ) {
		// Attributes
		$atts = shortcode_atts(array(
			'title' => 'Contact request',
			'class' => ''
		), $atts, 'livesite');
		
		$title = 'title=' . esc_attr($atts['title']);
		
		$class = !empty($atts['class']) ? 'class="' . esc_attr($atts['class']) . '"' : '';
		
		// UID
		$settings = ls_get_settings();
		$uid = isset($settings['vcita_params']['uid']) ? $settings['vcita_params']['uid'] : '';
		
		// HTML
		$html = '<iframe frameborder="0" ' . $class . ' src="//www.vcita.com/widgets/scheduler/' . esc_url($uid) . '?ver=2" width="100%" height="470"></iframe>';
		
		return $html;
	}


}

new ls_shortcodes();

?>
