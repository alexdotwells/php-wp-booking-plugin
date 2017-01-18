
//var module;
//var fields;

(function( $ ) {
	'use strict';

  initPezDatepicker();

  function initPezDatepicker() {
      module = $("#wc-bookings-booking-form");
      fields = module.find("fieldset.wc-bookings-date-picker");

			$(document).on('click', '#wc-bookings-booking-form td.bookable', doBookingScroll );
			doBookingScroll();
  }

  function doBookingScroll() {
		console.log( $(this) );          //debug
		console.log('------here-------');  //debug

		$('html, body').animate({scrollTop: $("fieldset.wc-bookings-date-picker").offset().top }, 1000);
  }

})( jQuery );
