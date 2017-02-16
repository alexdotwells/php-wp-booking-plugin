(function($) {
  'use strict';

  var pezBookingForm = (function() {

    var formModule;
    var boxProduct;
    var boxTabs;
    var boxTabDetails;
    var inpPezDurration;
    var inpPezQuantity;
    var inpWcDurration;
    var selHours;
    var fields;
    var tabAction;

    var controller = {
      init: function() {
        formModule = $("#main");
        inpPezDurration = formModule.find("#pez_field_duration");
        inpWcDurration = formModule.find("input[name='wc_bookings_field_duration']");
        inpPezQuantity = formModule.find("input[name='pez_field_quantity']");
        fields = formModule.find("fieldset.wc-bookings-date-picker");
        boxProduct = formModule.find(".summary .cart");
        boxTabs = formModule.find(".woocommerce-tabs.wc-tabs-wrapper");
        boxTabDetails = formModule.find("#tab-product_details");
        selHours = 2;
        inpPezQuantity.val(1);
        inpPezDurration.val(2);
        inpWcDurration.val(selHours * 60);

        controller.prepTabList();

        tabAction = formModule.find("li.product_details_tab.active");
        tabAction.addClass("closed");

        formModule.on('change', '#pez_field_duration', controller.setDuration);
        formModule.on('change', '#pez_field_quantity', controller.getTimeBlocks);
        formModule.on('click', '.product_details_tab.active', controller.toggleTabDetails);
      },
      prepTabList: function(e) {
        boxTabs.prependTo(boxProduct);
        boxTabDetails.addClass("pezHidden");
        $('.product_title.entry-title').hide();
        $('.single-product div.product .summary p.price').hide();
      },
      setDuration: function(e) {
        selHours = inpPezDurration.val();
        inpWcDurration.val(selHours * 60);
        controller.updateTimes();
      },
      getTimeBlocks: function(e) {
        controller.updateTimes();
      },
      updateTimes: function(e) {
        $('td.ui-datepicker-current-day').click();
      },
      toggleTabDetails: function(e) {
        e.stopPropagation();
        if (boxTabDetails.hasClass("pezHidden")) {
          boxTabDetails.removeClass("pezHidden");
        } else {
          boxTabDetails.toggle();
        }
        if ($(this).hasClass("open")) {
          $(this).removeClass("open").addClass("closed");
        } else {
          $(this).addClass("open").removeClass("closed");
        }

        return false;
      },
    };

    return controller;
  }());

  $(document).ready(pezBookingForm.init);

})(jQuery);
