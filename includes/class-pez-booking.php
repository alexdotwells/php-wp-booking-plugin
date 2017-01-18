<?php

/**
 * WordPress Custom Booking Methods
 *
 * @package    Pez
 * @subpackage Pez/includes
 * @author     AJWells <ajwells99@gmail.com>
 */


/**
* Booking Methods
*/
class Pez_Booking_Functions{

  private $posted;
  private $product;
  private $filter_interval;
  private $is_form_built;
  private $is_open;
  private $is_today;
  private $options;
  private $time_format;
  private $timezone;

	public function __construct( $post, $product_id ) {
    $this->options         = get_option( 'pez_settings' );
    $this->filter_interval = ( isset($this->options['pez_filter_interval']) && is_numeric($this->options['pez_filter_interval']) ) ? $this->options['pez_filter_interval'] : 30;
    $this->is_form_built   = false;
    $this->is_open         = false;
    $this->is_today        = false;
    $this->time_format     = get_option('time_format');
    $this->timezone        = get_option('timezone_string');
    if ( !is_null($post) ) {
      $this->posted = $post;
    }
    if ( !is_null($product_id) ) {
      $this->product = wc_get_product( $product_id );
    }
	}

  /*
  * Get time blocks
  */
  function get_blocks() {
    $posted = array();
    parse_str( $_POST['form'], $posted );

    if ( empty( $posted['add-to-cart'] ) ) {
    	return false;
    }

    $booking_id   = $posted['add-to-cart'];
    $product      = get_product( $booking_id );
    $booking_form = new WC_Booking_Form( $product );

    if ( ! empty( $posted['wc_bookings_field_start_date_year'] ) && ! empty( $posted['wc_bookings_field_start_date_month'] ) && ! empty( $posted['wc_bookings_field_start_date_day'] ) ) {
    	$year      = max( date('Y'), absint( $posted['wc_bookings_field_start_date_year'] ) );
    	$month     = absint( $posted['wc_bookings_field_start_date_month'] );
    	$day       = absint( $posted['wc_bookings_field_start_date_day'] );
    	$timestamp = strtotime( "{$year}-{$month}-{$day}" );
    }

    if ( ! $product ) {
      return false;
    }

    if ( empty( $timestamp ) ) {
    	die( '<li>' . esc_html__( 'Please enter a valid date.', 'woocommerce-bookings' ) . '</li>' );
    }

    if ( ! empty( $posted['wc_bookings_field_duration'] ) ) {
    	$interval = $posted['wc_bookings_field_duration'] * $product->wc_booking_duration;
    } else {
    	$interval = $product->wc_booking_duration;
    }

    $base_interval = $product->wc_booking_duration;
    if ( 'hour' === $product->get_duration_unit() ) {
    	$interval      = $interval * 60;
    	$base_interval = $base_interval * 60;
    }

    $first_block_time = $product->wc_booking_first_block_time;
    $from             = $time_from = strtotime( $first_block_time ? $first_block_time : 'midnight', $timestamp );
    $to               = strtotime( "tomorrow midnight", $timestamp ) + $interval;

    $resource_id_to_check = ( ! empty( $posted['wc_bookings_field_resource'] ) ? $posted['wc_bookings_field_resource'] : 0 );
    if ( $resource_id_to_check && $resource = $product->get_resource( absint( $resource_id_to_check ) ) ) {
    	$resource_id_to_check = $resource->ID;
    } elseif ( $product->has_resources() && ( $resources = $product->get_resources() ) && sizeof( $resources ) === 1 ) {
    	$resource_id_to_check = current( $resources )->ID;
    } else {
    	$resource_id_to_check = 0;
    }

    $quantity   = isset( $posted['pez_field_quantity'] ) ? max( 0, absint( $posted['pez_field_quantity'] ) ) : 1;
    $blocks     = $product->get_blocks_in_range( $from, $to, array( $interval, $base_interval ), $resource_id_to_check );
    $block_html = $this->get_available_blocks_html( $blocks, array( $interval, $base_interval ), $resource_id_to_check, $from, $product, $first_block_time, $quantity );

    if ( empty( $block_html ) ) {
    	$block_html .= '<li>' . __( 'No blocks available.', 'woocommerce-bookings' ) . '</li>';
    } else {
      $html_current = $this->get_current_time_button( $year.'-'.$month.'-'.$day, $booking_form );
      $block_html =  $html_current . $block_html;
    }

    echo(  "<script>(function( $ ) { $('html, body').animate({scrollTop: $('fieldset.wc-bookings-date-picker').offset().top },1000); })( jQuery );</script>" );

    return $block_html;
	}

  /*
  * Get available time block html
  */
  public function get_available_blocks_html( $blocks, $intervals = array(), $resource_id = 0, $from = '', $product, &$first_block_time, $quantity ) {
		if ( empty( $intervals ) ) {
			$default_interval = 'hour' === $product->get_duration_unit() ? $product->wc_booking_duration * 60 : $product->wc_booking_duration;
			$intervals        = array( $default_interval, $default_interval );
		}

		list( $interval, $base_interval ) = $intervals;

		$blocks            = $product->get_available_blocks( $blocks, $intervals, $resource_id, $from );
		$existing_bookings = $product->get_bookings_in_date_range( current( $blocks ), ( end( $blocks ) + ( $base_interval * 60 ) ), $resource_id );
		$booking_resource  = $resource_id ? $product->get_resource( $resource_id ) : null;
		$block_html        = '';
    $firstloop         = true;

		foreach ( $blocks as $block ) {
			if ( $product->has_resources() && ( is_null( $booking_resource ) || ! $booking_resource->has_qty() ) ) {
      	$available_qty = 0;
				foreach ( $product->get_resources() as $resource ) {
					if ( ! $product->check_availability_rules_against_date( $from, $resource->get_id() ) ) {
						continue;
					}
					$available_qty += $resource->get_qty();
				}
			} else if ( $product->has_resources() && $booking_resource && $booking_resource->has_qty() ) {
				$available_qty = $booking_resource->get_qty();
			} else {
				$available_qty = $product->get_qty();
			}

			$qty_booked_in_block = 0;
			foreach ( $existing_bookings as $existing_booking ) {
				if ( $existing_booking->is_within_block( $block, strtotime( "+{$interval} minutes", $block ) ) ) {
					$qty_to_add = $product->has_person_qty_multiplier() ? max( 1, array_sum( $existing_booking->get_persons() ) ) : 1;
					if ( $product->has_resources() ) {
						if ( $existing_booking->get_resource_id() === absint( $resource_id ) ) {
							$qty_booked_in_block += $qty_to_add;
						} else if ( ( is_null( $booking_resource ) || ! $booking_resource->has_qty() ) && $existing_booking->get_resource() ) {
							$qty_booked_in_block += $qty_to_add;
						}
					} else {
						$qty_booked_in_block += $qty_to_add;
					}
				}
			}

			$available_qty = $available_qty - $qty_booked_in_block;
      $time_is_okay = $this->is_time_okay( date( 'Hi', $block ) );

			if ( $available_qty >= $quantity && $time_is_okay ) {
        if ( $qty_booked_in_block ) {
					$block_html .= '<li class="block" data-block="' . esc_attr( date( 'Hi', $block ) ) . '" data-qty="' . $available_qty . '" ><a href="#" data-value="' . date( 'G:i', $block ) . '">' . date_i18n( get_option( 'time_format' ), $block ) . ' <small class="booking-spaces-left">(' . sprintf( _n( '%d left', '%d left', $available_qty, 'woocommerce-bookings' ), absint( $available_qty ) ) . ')</small></a></li>';
				} else {
					$block_html .= '<li class="block" data-block="' . esc_attr( date( 'Hi', $block ) ) . '" data-qty="' . $available_qty . '" ><a href="#" data-value="' . date( 'G:i', $block ) . '">' . date_i18n( get_option( 'time_format' ), $block ) . '</a></li>';
				}
			}
      $firstloop = false;
		}

		return $block_html;
	}

  /*
  * Get book 'NOW' button
  */
  function get_current_time_button( $sel_date, $booking_form ) {
    date_default_timezone_set($this->timezone);

    $selected = new DateTime($sel_date);
    $selected = $selected->format('Y-m-d');
    $today    = new DateTime();
    $today    = $today->format('Y-m-d');
    $result_str = '';

    if ($selected == $today) {
      $this->is_today = true;

      $_date = $sel_date;
      $date  = date_i18n( wc_date_format(), strtotime($_date) );
      $_time = wc_clean( date( 'G:i') );
      $time  = date_i18n( get_option( 'time_format' ), strtotime( "{$_date} {$_time}" ) );
      $_sel  = date('YmdHi', strtotime( $_date . ' ' . $_time));
      $_now  = date('YmdHi', current_time('timestamp'));

      $min_date = $booking_form->product->get_min_timestamp_for_date( strtotime( $date ) );

      if ( ! empty( $_time ) ) {
          $_start_date = strtotime( $_date . ' ' . $_time );
          $_end_date   = strtotime( "+10 Minutes", $_start_date );
          $_all_day    = 0;
      }

      //check resource availability
      $available_bookings = $booking_form->product->get_available_bookings( $_start_date, $_end_date, 0, 1 );
      $isOk = false;
      if ( is_wp_error( $available_bookings ) ) {;
          $isOk = false;
      } elseif ( ! $available_bookings ) {
          $isOk = false;
      } else {
          $isOk = true;
      }

      $this->is_open = $isOk;
    }

    if ( $this->is_today && $this->is_open ) {
      $total_resources = count($booking_form->product->get_resources());
      $avail_resources = count($available_bookings);

      if ( $total_resources == $avail_resources ) {
        $result_str = '<li class="block pez" data-block="' . date('Hi', strtotime('+2 Minutes')) . '"><a href="#" id="pez_curr" class="pez" data-value="' . date('G:i', strtotime('+2 Minutes')) . '">NOW' . '</a></li>';
      } else {
        $result_str = '<li class="block pez" data-block="' . date('Hi', strtotime('+2 Minutes')) . '"><a href="#" id="pez_curr" class="pez" data-value="' . date('G:i', strtotime('+2 Minutes')) . '">NOW' . ' <small class="booking-spaces-left">(' . sprintf( _n( '%d left', '%d left', $avail_resources, 'woocommerce-bookings' ), absint( $avail_resources ) ) . ')</small></a></li>';
      }
    }

    return $result_str;
  }

  /*
  * Check block for 30min interval
  */
  function is_time_okay( $time_str ) {
      $okay = false;
      $mins = substr($time_str, -2);
      if ( $mins == 30 || $mins == 0) {
          $okay = true;
      }
      return $okay;
  }


  /**
  * Get qty input value from booking form
  */
  public function get_quantity() {
    $quantity = isset( $this->posted['pez_field_quantity'] ) ? max( 0, absint( $this->posted['pez_field_quantity'] ) ) : 1;
    return $quantity;
  }

   /*
  * Add custom booking form fields
  */
  function get_custom_form_fields( $fields ) {
    global $product;

    $j = 0;
    $imdone = false;

    foreach ($fields as $key => $field) {
      if ($key == 'wc_bookings_field_duration' && $imdone == false) {
        $fields[$key]['class'] = array('wc_bookings_field_duration pezHidden');
        $imdone = true;
      }
      $j++;
    }

    $new_duration =  array(
        'pez_duration_field' =>
            array(
                'type'    => 'select',
                'name'    => 'pez_field_duration',
                'options' => array( '1' => __( '1 hour' ), '2' => __( '2 hours' ), '3' => __( '3 hours' ), '4' => __( '4 hours' ) ),
                'class'   => array('pez_field'),
                'label'   => __( 'Duration', 'woocommerce-bookings' )
            )
    );

    $qty_ops_arr = array();
    for ($x = 1; $x <= count($product->get_resources()); $x++) {
      $qty_ops_arr[ $x ] = __( $x );
    }

    $new_quantity =  array(
      'pez_quantity_field' =>
          array(
              'type'        => 'select',
              'id'          => 'pez_field_quantity',
              'options'     => $qty_ops_arr,
              'name'        => 'pez_field_quantity',
              'class'       => array('pez_field'),
              'label'       => __( 'Quantity', 'woocommerce' ),
              'description' => __( 'Enter the quantity here.', 'woocommerce' )
          )
    );
    $fields = $new_quantity +  $new_duration + $fields;

    return $fields;
  }

  /**
  * Find available resources for booking
  */
  public function get_selected_resources( $start_date, $end_date, $parent_resource_id ) {
    if ( ! is_wc_booking_product( $this->product ) ) { return array(); }

    $qty                = $this->get_quantity();
    $selected_resources = array();
    $parent_resource    = $this->product->get_resource( absint($parent_resource_id) );
    $avail_resources    = $this->product->get_available_bookings( $start_date, $end_date, 0, 1 );
    $qty               -= 1;
    $selected_resources[ $parent_resource->ID ] = $parent_resource->post_title;

    foreach ( $avail_resources as $id => $q ) {
      $found = false;
      foreach ( $this->product->get_resources() as $resource ) {
          if ( ! $found  &&  $qty > 0  &&  $id == $resource->ID ) {
              $selected_resources[$resource->ID] = $resource->post_title;
              $found = true;
              $qty -= 1;
          }
      }
    }
    return $selected_resources;
}

  /**
  * Get selected resource names
  */
  public function get_resource_name_string( $resources ) {
    $resource_name_str = '';
    foreach ( $resources as $res_id => $res_val ) {
        $resource_name_str .= ( $res_val . ', ');
    }
    return trim( $resource_name_str, ', ' );
  }

  /*
  * Create child resource bookings
  */
  function hold_child_resources( $item_meta, $product_id ) {
    $is_onhold      = $item_meta['booking']['_is_held'];
    $quantity       = $item_meta['booking']['_qty'];
    $resources      = $item_meta['booking']['_selected_resources'];
    $booking_id     = $item_meta['booking']['_booking_id'];
    $prev_booking   = get_wc_booking( $booking_id );
    $child_bookings = array();

    $i = 0;
    if ( ! $is_onhold ) {
        foreach ( $resources as $res_id => $res_val ) {
            if ( $i > 0 && $prev_booking->parent_id <= 0 ) {

                $this_booking   =  create_wc_booking(
                        $prev_booking->product_id,
                        $new_booking_data = array(
                            'start_date'  => $prev_booking->start,
                            'end_date'    => $prev_booking->end,
                            'resource_id' => $res_id,
                            'parent_id'   => $booking_id
                        ),
                        $prev_booking->get_status(),
                        true
                    );

                array_push( $child_bookings, $this_booking->id );
                $this->schedule_cart_removal( $this_booking->id );
            }

            $i += 1;
        }
    }
    return $child_bookings;
  }

  /*
  * Schedule hold/booking to be deleted if inactive
  */
	public function schedule_cart_removal( $booking_id ) {
		wp_clear_scheduled_hook( 'wc-booking-remove-inactive-cart', array( $booking_id ) );
		wp_schedule_single_event( apply_filters( 'woocommerce_bookings_remove_inactive_cart_time', time() + ( 60 * 15 ) ), 'wc-booking-remove-inactive-cart', array( $booking_id ) );
	}

}
