jQuery(document).ready(function($) {

var redlight_swish_ecommerce_payment_check = {

	order_access_token: '',
	check_payment_status: true,
	showPopUp: function () {
		if (!$('redlight-swish-popup_wraper').hasClass('active')) {
			$('redlight-swish-popup_wraper').addClass('active').fadeIn('slow');
		}
	},

	randomText: function (length = 15) {
		var text = "";
		var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

		for (var i = 0; i < length; i++) {
			text += possible.charAt(Math.floor(Math.random() * possible.length));
		}
		return text;
	},

	checkPayment: function () {
		redlight_swish_ecommerce_payment_check.order_access_token = redlight_swish_ecommerce_payment_check.randomText();
		$.ajax({
			type: 'POST',
			url: redlight_script_vars.swish_ajax,
			data: {
				action: 'swish_ajax_payment_check',
				order_access_token: redlight_swish_ecommerce_payment_check.order_access_token,
				order_id: window.redlight_script_vars.swish_order_id,
			},
			success: function (data) {
				//console.log(order_details);
				data = JSON.parse(data);
				console.log(data)
				if (data.result == "success") {
					$('.swish-button').hide()
					redlight_swish_ecommerce_payment_check.check_payment_status = false;
					var html = '<div class="swish-info">'+ redlight_script_vars.payment_succesful + '</div>';
					html += '<div class="woocommerce-info">'+ redlight_script_vars.succesful_payment_redirect_message +'</div>';
					$('#redlight-swish-popup_wraper .redlight-swish-popup_message').html(html);
					setTimeout(function () {
						window.location.href = data.redirect;
					}, 5000);
				} else if (data.result == "error") {
					$('.swish-button').hide()
					if (!$('#redlight-swish-popup_wraper .woocommerce-info').length) {
						redlight_swish_ecommerce_payment_check.check_payment_status = false;
						var html = '<div class="swish-info">'+redlight_script_vars.payment_failed + '</div> <a class="button" href="'+ redlight_script_vars.swish_shop_url +'">' + redlight_script_vars.return_to_checkout + '</a>';
						html += '<div class="woocommerce-info">'+ redlight_script_vars.redirect_message + ' <span class="count">10</span> '+ redlight_script_vars.seconds +'</div>';
						$('#redlight-swish-popup_wraper .redlight-swish-popup_message').html(html);
						redlight_swish_ecommerce_payment_check.count_start();
					} else {
						console.log('Something happend', data);
					}
				} else {
					if (data.errorCode && data.errorMessage) {

						redlight_swish_ecommerce_payment_check.check_payment_status = false;
						if ($('#redlight-swish-popup_wraper .woocommerce-error').length) {
							$('#redlight-swish-popup_wraper .woocommerce-error').text(order_details.body.errorMessage);
						} else {
							var html = '<div class="woocommerce-error">' + order_details.body.errorMessage + '</div>';
							$('#redlight-swish-popup_wraper .redlight-swish-popup_message').html(html);
						}

						var html = '<h5 class="return-to-shop"><a class="button" href="' + redlight_script_vars.swish_shop_url + '">' + redlight_script_vars.return_to_checkout + '</a></h5>';
						html += '<div class="woocommerce-info">' + redlight_script_vars.redirect_message + ' <span class="count">10</span> ' + redlight_script_vars.seconds + '</div>';
						$('#redlight-swish-popup_wraper .woocommerce-error').after(html);
						redlight_swish_ecommerce_payment_check.count_start();

						/*setTimeout(function(){
							$('#popup_wraper').removeClass('active').css('display', 'none');
						}, 20000)*/
					} else {
						$('#redlight-swish-popup_wraper .woocommerce-error').remove();
						redlight_swish_ecommerce_payment_check.check_payment_status = true;
						$('#redlight-swish-popup_wraper').addClass('active').css('display', 'block');
					}
				}
			},
		});

	},

	count_start: function () {

		redlight_swish_ecommerce_payment_check.showPopUp();

		var start_from = 10,
			ends_to = 0,
			decrement = 1,
			redirect_url = redlight_script_vars.swish_shop_url;

		var interval_id = setInterval(function () {
			if (start_from <= ends_to) {
				if (redirect_url) {
					window.location.href = redirect_url;
					clearInterval(interval_id);
				}
				return;
			}
			start_from -= decrement;
			$('#redlight-swish-popup_wraper .woocommerce-info .count').text(start_from);
			console.log(start_from);
		}, 1000);

	},

	managePopup: function () {
		if(redlight_swish_ecommerce_payment_check.errorTextMatched()){
			redlight_swish_ecommerce_payment_check.showPopUp();
			redlight_swish_ecommerce_payment_check.checkPayment();
		}
	},

	swish_event_list: function () {
		/*updated checkout*/
		jQuery(document).on('updated_checkout', function () {
			redlight_swish_ecommerce_payment_check.managePopup();
		});

		/*popupbtn Click*/
		$('#redlight-swish-popup_wraper .button').on('click', function (evt) {
			evt.preventDefault();
			redlight_swish_ecommerce_payment_check.checkPayment();
		});
	},

	errorTextMatched: function () {

		var retVal = false;

		if (!redlight_swish_ecommerce_payment_check.check_payment_status) {
			return retVal;
		}

		if (typeof redlight_script_vars.swish_order_id === "string" && redlight_script_vars.swish_order_id != "0") {
			retVal = true;
		}
		return retVal;
	},

	payment_check: function () {
		setInterval(function () {
			if (redlight_swish_ecommerce_payment_check.errorTextMatched()) {
				redlight_swish_ecommerce_payment_check.managePopup();
			}
		}, 3000);
	},

	init: function () {
		redlight_swish_ecommerce_payment_check.swish_event_list();
		redlight_swish_ecommerce_payment_check.managePopup();
		redlight_swish_ecommerce_payment_check.payment_check();
	},
}

redlight_swish_ecommerce_payment_check.init();
});
