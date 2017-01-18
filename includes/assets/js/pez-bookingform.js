
var module;
var inpPezDurration;
var inpPezQuantity;
var inpWcDurration;
var selHours;
var maxQty;
var fields;

(function( $ ) {
	'use strict';

  initPez();

  function initPez() {
    module = $("#wc-bookings-booking-form");
    inpPezDurration = module.find("#pez_field_duration");
    inpWcDurration = module.find("input[name='wc_bookings_field_duration']");
    inpPezQuantity = module.find("input[name='pez_field_quantity']");
    fields = module.find("fieldset.wc-bookings-date-picker");
    selHours = 2;

    inpPezQuantity.val(1);
    inpPezDurration.val(2);
    inpWcDurration.val(selHours * 60);

    $(document).on('change', '#pez_field_duration', setDuration );
    $(document).on('change', '#pez_field_quantity', getTimeBlocks );
		$(document).on('click', "td.ui-date-picker", doBookingScroll );
  }

  function setDuration() {
    selHours = inpPezDurration.val();
    inpWcDurration.val(selHours * 60);
    updateTimes();
  }

  function getTimeBlocks() {
    updateTimes();
  }

  function updateTimes() {
    $('td.ui-datepicker-current-day').click();
  }

  function doBookingScroll() {
		$('html, body').animate({
      scrollTop: fields.offset().top
    }, 1000 );
  }

})( jQuery );
