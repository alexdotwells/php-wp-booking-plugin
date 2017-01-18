/**
 * @wordpress-plugin
 * Plugin Name:       WordPress Custom Booking 
 * Version:           2.0.1
 * Author:            AWells <ajwells99@gmail.com>
 * Description:       WordPress Custom Booking Plugin. Style, script, and markup changes to extend the Woocommerce Bookings process.
 * Text Domain:       pez
 */
 

FILE:  pez.php
-------------------------------
* Plugin Main
* Identifies, Initializes, & Runs the plugin



FILE:  includes/class-pez.php
-------------------------------
* PEZ Plugin Controller
* Loads supporting plugin files/classes
* Defines actions and filters
* Initializes & runs Loader



FILE:  includes/class-pez-loader.php
-------------------------------
* Removes any necessary actions
* Adds plugin hooks



FILE:  includes/class-pez-booking-controller.php
-------------------------------
* Methods called by plugin actions/filters



FILE:  includes/class-pez-booking.php
-------------------------------
* Helper methods to get/build data for the main booking controller



FILE:  includes/assets/css/pez-bookingform.css
-------------------------------
* Shop/Booking Form styles



FILE:  includes/assets/js/pez-bookingform.js
-------------------------------
* Shop/Booking Form JavaScript



FILE:  includes/assets/js/pez-cart.js
-------------------------------
* Cart/Checkout JavaScript


