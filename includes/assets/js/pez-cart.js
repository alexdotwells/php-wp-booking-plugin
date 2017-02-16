(function( $ ) {
	'use strict';


	var pezCart = (function () {

		var cartModule;
		var pezCart = [];
		var qtyCells
		var qtyVals;
		var priceCells;

		var controller = {
			init: function() {
				cartModule = $("#main");
        qtyVals = cartModule.find('dd.variation-Quantity p');
        qtyCells = cartModule.find('td.product-quantity');
        priceCells = cartModule.find('td.product-price span.woocommerce-Price-amount');

				controller.addProduct();
        controller.updateQuantity();
        controller.updatePrice();
			},
			addProduct: function() {
				var cartwrap = cartModule.find('div.entry-content div.woocommerce');
				cartwrap.prepend('<div class="woocommerce-info">Want to add another product? <a href="#" class="showProduct">Click here</a></div>');
			},
      updatePrice: function() {
        priceCells.each(function() {
            var span = $(this).find('span');
            var currhtml = $(this).html();
            $(this).html( currhtml );
        });
      },
      updateQuantity: function() {
        qtyVals.each(function() {
            pezCart.push( $(this).html() );
        });
        var i = 0;
        qtyCells.each(function() {
            var inpt = $(this).find('input');
            inpt.val(pezCart[i]);
            $(this).html(pezCart[i]);
            $(this).append(inpt);
            i += 1;
        });
      }
		};

		return controller;
	}());


	$(document).ready(pezCart.init);


})( jQuery );
