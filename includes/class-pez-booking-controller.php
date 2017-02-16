<?php

/**
 * Paddle EZ Booking Controller
 *
 * @package    Pez
 * @subpackage Pez/includes
 * @author     AJWells <ajwells99@gmail.com>
 */

class Pez_Booking_Controller {

  private $plugin_name;
	private $version;
  private $pez_booking;
  private $product;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
    $this->pez_booking = new Pez_Booking_Functions( null, null );
	}

	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'assets/css/pez-bookingform.css', array( 'wc-bookings-styles' ), $this->version, 'all' );
	}
  public function enqueue_scripts() {}

  private function get_script_on_demand( $context ) {
    if ( 'form_fields' === $context ) {
        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'assets/js/pez-bookingform.js', array( 'jquery' ), $this->version, false );
    } elseif ( 'cart' === $context ) {
        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'assets/js/pez-cart.js', array( 'jquery' ), $this->version, false );
    }
  }

  /*
  * Modify product pricing html
  */
  public function pez_modify_price_html( $price_html ) {
    $price_html = str_replace( 'From: ', '', $price_html );
    return $price_html . ' per hour';
  }

  /*
  * Get time block availability
  */
  public function pez_get_blocks() {
    $block_html = $this->pez_booking->get_blocks();
    die( $block_html );
  }

  /*
  * Add custom booking form fields
  */
  function pez_add_custom_form_fields( $fields ) {
    $this->get_script_on_demand('form_fields');
    return $this->pez_booking->get_custom_form_fields( $fields );
  }

  /*
  * Modify product page tab list
  */
  function pez_modify_product_tabs( $tabs ) {
    unset( $tabs['description'] );

    $tabs['product_details'] = array(
    		'title' 	=> __( 'Product Details', 'woocommerce' ),
    		'priority' 	=> 9,
    		'callback' 	=> ( array($this,'pez_product_details_tab_content') )
    	);
  	return $tabs;
  }

  /*
  * Callback for product_details tab
  */
  function pez_product_details_tab_content() {
    global $product;

    echo '<span class="label">Price:</span>';
    echo '<p>'. $product->get_price_html() . '</p>';
    echo '<span class="label">Description:</span>';
    echo '<p>'. get_post($product->id)->post_content .'</p>';
  }

  /*
  * Booking cost quantity adjustment
  */
  function pez_adjust_booking_cost( $cost, $booking_form, $posted ) {
    $pez_book = new Pez_Booking_Functions( $posted, null );
    $cost     = ($pez_book->get_quantity()) * $cost;
    return $cost;
  }

  /*
  * Store custom field in Cart
  */
  function pez_update_cart_item_data( $cart_item_meta, $product_id ) {
    $this->product = wc_get_product( $product_id );
		if ( ! is_wc_booking_product( $this->product ) ) { return $cart_item_data; }

    $i_meta = $cart_item_meta;
    $bk     = $i_meta['booking'];
    $pez_book = new Pez_Booking_Functions( $_POST, $product_id );
    $i_meta['booking']['_qty'] =  $quantity   = $pez_book->get_quantity();
    $i_meta['booking']['Quantity']            = $quantity;
    $i_meta['booking']['_selected_resources'] = $pez_book->get_selected_resources( $bk['_start_date'], $bk['_end_date'], $bk['_resource_id'] );
    $i_meta['booking']['type'] = $pez_book->get_resource_name_string( $i_meta['booking']['_selected_resources'] );

    $duration = absint($i_meta['booking']['_duration']) / 60;
    $i_meta['booking']['_duration_unit'] = 'hour';
    $i_meta['booking']['_duration']      = absint( $duration );
    $i_meta['booking']['duration']       = $duration . (($quantity > 1) ? ' hours' : ' hour');

    $cost = $i_meta['booking']['_cost'] / $quantity;
    $i_meta['booking']['_cost'] = $cost;

    if ( empty($i_meta['booking']['is_held']) ) {
      $i_meta['booking']['_is_held'] = false;
      $i_meta['booking']['_is_booked'] = false;
    }

		return $i_meta;
  }

  /*
  * Create additional resource bookings
  */
  function pez_auto_create_resource_booking( $item_meta, $product_id ) {
    $step = is_checkout() ? 'checkout' : 'cart';
    if ( $step == 'cart' ) {
      $child_bookings = $this->pez_booking->hold_child_resources( $item_meta, $product_id );
      $item_meta['booking']['_child_bookings'] = $child_bookings;
      $item_meta['booking']['_is_held'] = true;
    }

    return $item_meta;
  }

  /*
  * Update item quantity on add to cart
  */
  public function pez_update_cart_item_qty( $cart_item_key, $product_id, $quantity ) {
    $cart       = WC()->cart->get_cart();
		$cart_item  = $cart[ $cart_item_key ];
		$booking_id = isset( $cart_item['booking'] ) && ! empty( $cart_item['booking']['_booking_id'] ) ? absint( $cart_item['booking']['_booking_id'] ) : '';

    WC()->cart->cart_contents[ $cart_item_key ]['quantity'] = $cart_item['booking']['_qty'];
  }

  /*
  * Add cart js
  */
  public function pez_add_cart_scripts( $cart_data, $cart_item ) {
    $step = is_checkout() ? 'checkout' : 'cart';
    $this->get_script_on_demand( $step );
    return $cart_data;
  }

  /*
  * Remove child resource bookings
  */
  public function pez_remove_cart_resource_bookings() {
    $wc_cart = WC()->cart->removed_cart_contents;
    foreach( $wc_cart as $cart_item ) {

      $child_bookings = isset( $cart_item['booking'] ) && ! empty( $cart_item['booking']['_child_bookings'] ) ? $cart_item['booking']['_child_bookings'] : array();
      foreach( $child_bookings as $delete_id ) {
        $booking = get_wc_booking( $delete_id );

        $booking->update_status( 'was-in-cart' );
        WC_Cache_Helper::get_transient_version( 'bookings', true );
        wp_delete_post( $delete_id );
        wp_clear_scheduled_hook( 'wc-booking-remove-inactive-cart', array( $delete_id ) );
      }
    }
  }

 /*
 * Mark child resource bookings paid
 */
  public function pez_publish_bookings( $order_id ) {
    foreach( WC()->cart->get_cart() as $cart_item ) {
      $child_bookings = isset( $cart_item['booking'] ) && ! empty( $cart_item['booking']['_child_bookings'] ) ? $cart_item['booking']['_child_bookings'] : array();
      foreach( $child_bookings as $delete_id ) {
        $booking = get_wc_booking( $delete_id );
        if ( $booking->get_status( true ) == 'in-cart') {
            $booking->update_status( 'paid' );
        }
      }
    }
  }

}
