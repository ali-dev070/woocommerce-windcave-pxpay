jQuery(document).ready(function(){


    jQuery("#woocommerce_ilaa_windcave_pxpay2_method_test_button").click(function() {

		var data = {
			action: 'GenerateRequest',
		};
		jQuery.post(ajaxurl, data, function(response) {
			if(response=="ERROR"){
				alert(response);
				console.log("ajax call returned 'ERROR'");
			}else{
				alert(response);
				//window.location.replace(response);				
			}
		});
		return false;
	});

    

});
