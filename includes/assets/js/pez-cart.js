
var pez_cartModule;
var pezCart = [];
var qtyCells
var qtyVals;

(function( $ ) {
	'use strict';

    initPezCart();
    
    function initPezCart() {    
        pez_cartModule = $('table.cart');
        qtyVals = pez_cartModule.find('dd.variation-Quantity p');
        qtyCells = pez_cartModule.find('td.product-quantity');
        
        var priceCells = pez_cartModule.find('td.product-price span.woocommerce-Price-amount');

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
         
        priceCells.each(function() {   
            var span = $(this).find('span');
            var currhtml = $(this).html();
            $(this).html( currhtml );
        });
    }
        
})( jQuery );