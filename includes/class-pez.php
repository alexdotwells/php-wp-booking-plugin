<?php

/**
 *  Main Plugin Controller
 *
 * @package    Pez
 * @subpackage Pez/includes
 * @author     AJWells <ajwells99@gmail.com>
 */

class Pez {

	protected $loader;
	protected $plugin_name;
	protected $version;

	public function __construct() {
		$this->plugin_name = 'pez';
		$this->version = '2.5.0';
		$this->load_dependencies();
		$this->define_public_hooks();
	}

	private function load_dependencies() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-pez-loader.php';
    require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-pez-booking.php';
    require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-pez-booking-controller.php';

		$this->loader = new Pez_Loader();
	}

	private function define_public_hooks() {
		$booking_controller = new Pez_Booking_Controller( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $booking_controller, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $booking_controller, 'enqueue_scripts' );
		$this->loader->add_filter( 'woocommerce_get_price_html', $booking_controller, 'pez_modify_price_html' );
		$this->loader->add_filter( 'booking_form_fields', $booking_controller, 'pez_add_custom_form_fields', 20 );
		$this->loader->add_filter( 'woocommerce_get_item_data', $booking_controller, 'pez_add_cart_scripts', 11, 2 );
		$this->loader->add_filter( 'woocommerce_product_tabs', $booking_controller, 'pez_modify_product_tabs', 98 );
		$this->loader->add_action( 'wp_ajax_wc_bookings_get_blocks', $booking_controller, 'pez_get_blocks' );
		$this->loader->add_action( 'wp_ajax_nopriv_wc_bookings_get_blocks', $booking_controller, 'pez_get_blocks' );
		$this->loader->add_action( 'woocommerce_add_cart_item_data', $booking_controller, 'pez_update_cart_item_data', 11, 2 );
		$this->loader->add_action( 'woocommerce_add_cart_item_data', $booking_controller, 'pez_auto_create_resource_booking', 21, 2 );
		$this->loader->add_action( 'woocommerce_cart_item_removed', $booking_controller, 'pez_remove_cart_resource_bookings' );
		$this->loader->add_action( 'woocommerce_add_to_cart', $booking_controller, 'pez_update_cart_item_qty', 11, 3 );
		$this->loader->add_action( 'booking_form_calculated_booking_cost', $booking_controller, 'pez_adjust_booking_cost', 11, 3 );
		$this->loader->add_action( 'woocommerce_order_status_processing', $booking_controller, 'pez_publish_bookings', 11 );
		$this->loader->add_action( 'woocommerce_order_status_completed', $booking_controller, 'pez_publish_bookings', 11 );
	}

	public function run() {
		$this->loader->run();
  }

  public function get_loader() {
		return $this->loader;
	}

	public function get_plugin_name() {
		return $this->plugin_name;
	}

	public function get_version() {
		return $this->version;
	}

}
