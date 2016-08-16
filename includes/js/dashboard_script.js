/**
 * 
 */

var $ =jQuery.noConflict();
			  

jQuery(document).ready(function($) {
    $( "#slider" ).slider({
    	      range: true,
    	      min: 0,
    	      max: 500,
    	      values: [ 75, 300 ]
    }
    	    );
  } );