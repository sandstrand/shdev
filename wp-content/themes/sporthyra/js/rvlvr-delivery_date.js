jQuery(document).ready(function ($) {
	
	update_notes();
	$("#order_delivery_date").on('change', update_notes);
	
	function update_notes() {
	   	if ($('#order_review .product-name .variation').length) {
			var variations = $('#order_review .product-name .variation').text();
			var end = new Date($("#start_date").attr('value'));
			var info = "";
			
			// 1 Day
			if(variations.indexOf('1 dag') != -1){
				var date = new Date($("#order_delivery_date").val()),
				days = parseInt("4");
			
				if(!isNaN(date.getTime())){
					date.setDate(date.getDate() + days);
					console.log(date);
					$('#order_return_rent-1_day').val(formatdatenoday(date));
					
					var info = info + "Artiklar hyrda i 1 dag återlämnas senast " + formatdate(date) + ".<br />";
				} else {
					console.log('Bad date');  
				}		
			}
			
			// 2 days
			if(variations.indexOf('2 dag') != -1){
				var date = new Date($("#order_delivery_date").val()),
				days = parseInt("5");
			
				if(!isNaN(date.getTime())){
					date.setDate(date.getDate() + days);
					console.log(date);
					$('#order_return_rent-2_days').val(formatdatenoday(date));
					var info = info + "Artiklar hyrda i 2 dagar återlämnas senast " + formatdate(date) + ".<br />";
				} else {
					console.log('Bad date');  
				}		
			}
			
			// 3 days
			if(variations.indexOf('3 dag') != -1){
				var date = new Date($("#order_delivery_date").val()),
				days = parseInt("6");
			
				if(!isNaN(date.getTime())){
					date.setDate(date.getDate() + days);
					console.log(date);
					$('#order_return_rent-3_days').val(formatdatenoday(date));
					var info = info + "Artiklar hyrda i 3 dagar återlämnas senast " + formatdate(date) + ".<br />";
				} else {
					console.log('Bad date');  
				}		
			}
			
			// 4 days
			if(variations.indexOf('4 dag') != -1){
				var date = new Date($("#order_delivery_date").val()),
				days = parseInt("7");
			
				if(!isNaN(date.getTime())){
					date.setDate(date.getDate() + days);
					console.log(date);
					$('#order_return_rent-4_days').val(formatdatenoday(date));
					var info = info + "Artiklar hyrda i 4 dagar återlämnas senast " + formatdate(date) + ".<br />";
				} else {
					console.log('Bad date');  
				}		
			}
			
			// 5-8 days
			if(variations.indexOf('5-8 dag') != -1){
				var date = new Date($("#order_delivery_date").val()),
				days = parseInt("11");
			
				if(!isNaN(date.getTime())){
					date.setDate(date.getDate() + days);
					console.log(date);
					$('#order_return_rent-week').val(formatdatenoday(date));
					var info = info + "Artiklar hyrda i 5-8 dagar återlämnas senast " + formatdate(date) + ".<br />";
				} else {
					console.log('Bad date');  
				}		
			}
			
			// 1 Month
			if(variations.indexOf('1 må') != -1){
				var date = new Date($("#order_delivery_date").val()),
				days = parseInt("3");
				months = parseInt("1");
					
				if(!isNaN(date.getTime())){
					date.setMonth(date.getMonth() + months);
					date.setDate(date.getDate() + days);
					console.log(date);
					$('#order_return_rent-1_month').val(formatdatenoday(date));
					var info = info + "Artiklar hyrda i 1 månad återlämnas senast " + formatdate(date) + ".<br />";
				} else {
					console.log('Bad date');  
				}		
			}

			// 3 Months
			if(variations.indexOf('3 må') != -1){
				var date = new Date($("#order_delivery_date").val()),
				days = parseInt("3");
				months = parseInt("3");
					
				if(!isNaN(date.getTime())){
					date.setMonth(date.getMonth() + months);
					date.setDate(date.getDate() + days);
					console.log(date);
					$('#order_return_rent-3_months').val(formatdatenoday(date));
					var info = info + "Artiklar hyrda i 3 månadader återlämnas senast " + formatdate(date) + ".<br />";
				} else {
					console.log('Bad date');  
				}		
			}		
			
			// Sommarsäsong
			if(variations.indexOf('Hela som') != -1){
				date = ($("#sommar").html());
				$('#order_return_rent-season-summer').val(date);
				var info = info + "Artiklar hyrda för sommarsäsongen återlämnas senast " + date + ".<br />";
			
			}
			
			// Vintersäsong
			if(variations.indexOf('Hela vintern') != -1){
				date = ($("#vinter").html());
				$('#order_return_rent-season-winter').val(date);
				console.log (date);
				var info = info + "Artiklar hyrda för vintersäsongen återlämnas senast " + date + ".<br />";
			
			}
			
			$('#order_delivery_note').val(info);
			$('.order_delivery_note').html(info);
			$('.order_delivery_notes').slideDown(300);
		}
  
	}
	$('.order_delivery_notes').hide();
    function formatdate(thedate){
		var weekday=new Array(7);
		weekday[0]="söndag";
		weekday[1]="måndag";
		weekday[2]="tisdag";
		weekday[3]="onsdag";
		weekday[4]="torsdag";
		weekday[5]="fredag";
		weekday[6]="lördag";

		
		endout = weekday[thedate.getDay()] + " " + thedate.getFullYear() + "-" +("00" + (thedate.getMonth() + 1)).slice(-2) + "-" + ("00" + thedate.getDate()).slice(-2)
		//var endout = thedate.getFullYear() + "-" + thedate.getMonth() + "-" + thedate.getDate();
		return endout;
	}
	 function formatdatenoday(thedate){
	
		endout = thedate.getFullYear() + "-" +("00" + (thedate.getMonth() + 1)).slice(-2) + "-" + ("00" + thedate.getDate()).slice(-2)
		//var endout = thedate.getFullYear() + "-" + thedate.getMonth() + "-" + thedate.getDate();
		return endout;
	}
	
    Date.prototype.toInputFormat = function() {
       var yyyy = this.getFullYear().toString();
       var mm = (this.getMonth()+1).toString(); // getMonth() is zero-based
       var dd  = this.getDate().toString();
       return yyyy + "-" + (mm[1]?mm:"0"+mm[0]) + "-" + (dd[1]?dd:"0"+dd[0]); // padding
    };

})