//// Adjust height of non season category menu

jQuery(document).ready(function ($) {
	$('.menu_non_season').each(function(){
      var parentHeight = $(this).parent().height();
      $(this).height(parentHeight - 35);    
	});
	$('.menu_brands').each(function(){
      var parentHeight = $(this).parent().height();
      $(this).height(parentHeight - 35);    
	});
});


//// Smooth scroll on #

jQuery(document).ready(function ($) {
	$( '.anchorfix' ).click(function(evt){ 
		evt.preventDefault(); 
		var target = "#om";
	    var $target = $(target);

	    $('html, body').stop().animate({
	        'scrollTop': $target.offset().top
	    },  'swing', function () {
	        window.location.hash = target;
	    });
		//alert('clicked');
		
	});
	
});



//// Static menu

jQuery(document).ready(function ($) {
	needle = '.toggled_menu';
	
	$( needle + '_div' ).each(function( index ) {
		//console.log( index + ": " + $( this ).text() + ": " + $( this ).attr('id') );
		//console.log( index + $( "." + $( this ).attr('id')).attr('class') );
		if ( $('#wrapper-navbar').width() > 1024 ){
			if ( $( "." + $( this ).attr('id')).hasClass('current-menu-item') || $( "." + $( this ).attr('id')).hasClass('current-product_cat-ancestor') || $( "." + $( this ).attr('id')).hasClass('current-product-ancestor')){
				if( $( this ).attr('id') != 't_md_stores'){
					$( this ).contents().appendTo( '#s_menu');
					$( '#s_menu' ).show();
				}
				$( '#jsDebug' ).html(  $('#wrapper-navbar').width() );
				console.log( );
			};
		};
	}); 
});


//// Toggled menus

jQuery(document).ready(function ($) {

	allMenus = '.toggled_menu_div';
	allLinks = '.t_link';
	
	function getSelect( added, prepend ){
		return prepend + added;	
	};

	function resetLinks(menuID, overrideCurrent){
		if (typeof(overrideCurrent)==='undefined'){
			overrideCurrent = false;
		}
		$( '.current-menu-item').removeClass('old');
		$( '.current-product_cat-ancestor').removeClass('old'); 
		$( '.current-product-ancestor').removeClass('old');
		$( allLinks ).removeClass('new');
		if(overrideCurrent == true){	
			$( getSelect(menuID,'.')).removeClass('old');
		}
		console.log( "this" + getSelect(menuID,'.') )
	};
	
	function setLinks(menuID, overrideCurrent){
		if (typeof(overrideCurrent)==='undefined'){
			overrideCurrent = false;
		}
		$( '.current-menu-item').addClass('old');
		$( '.current-product_cat-ancestor').addClass('old'); 
		$( '.current-product-ancestor').addClass('old');
		$( getSelect(menuID,'.')).addClass('new');
		if(overrideCurrent == true){	
			$( getSelect(menuID,'.')).removeClass('old');
		}
	};
	
	function toggleMenu(menuID, overrideCurrent){
		
		if (typeof(overrideCurrent)==='undefined'){
			overrideCurrent = false;
		}
		
		if(overrideCurrent == true){
			
		}		
	
		if ($( getSelect(menuID,'.')).hasClass('current-menu-item') || $( getSelect(menuID,'.')).hasClass('current-product_cat-ancestor') || $( getSelect(menuID,'.')).hasClass('current-product-ancestor')){

			$( '#jsDebug' ).html('current. not visible');
			if( $( getSelect(menuID,'#') + ':visible' ).length <= 0 && overrideCurrent == true ){
				$( allMenus + ":not(" + getSelect(menuID, "#") + ")").not(":animated").slideUp(300);
				$( allMenus + ":not(" + getSelect(menuID, "#") + ")").not(":animated").css('z-index', 10);
			
				$( getSelect(menuID,'#')).not(":animated").css('z-index', 15);
				$( getSelect(menuID,'#')).not(":animated").slideDown(300);
						
				resetLinks(menuID, overrideCurrent);			
				setLinks(menuID, overrideCurrent);
				$( '#jsDebug' ).html( "current, was not not visible. Hiding others" );
			}
			else if(overrideCurrent == true){
				$( allMenus ).not(":animated").slideUp(300);
				$( allMenus ).not(":animated").css('z-index', 10);

				resetLinks(menuID);			
				setLinks(menuID);
				$( '#jsDebug' ).html( "Current, probably visible, hiding" );
			
			
			}
			else{
				$( allMenus ).not(":animated").slideUp(300);
				$( allMenus ).not(":animated").css('z-index', 10);
				resetLinks(menuID);	
				console.log( 'got here: Is it this one?' )
				$('html, body').animate({ scrollTop: 0 }, 'fast');
				$( '#jsDebug' ).html('Current. others may have been visible. Hiding all');
			}
		
		}
		else if( $( getSelect(menuID,'#') + ':visible' ).length > 0){
			
			$( getSelect(menuID,'#')).not(":animated").css('z-index', 10);
			$( getSelect(menuID,'#')).not(":animated").slideUp(300);
			
			resetLinks(menuID);
			
			$( '#jsDebug' ).html('Target was visible. Hiding all');
		}
		else if ($( allMenus + ':visible' ).length > 0){
			
			$( allMenus ).not(":animated").slideUp(300);
			$( allMenus ).not(":animated").css('z-index', 10);
			$( getSelect(menuID,'#')).not(":animated").css('z-index', 15);
			$( getSelect(menuID,'#')).not(":animated").slideDown(300);
						
			resetLinks(menuID);
			setLinks(menuID);
			
			$( '#jsDebug' ).html('Target was not visible and not current. Other was visible; hide all and show target.');
		}
		
		else {
			
			$( getSelect(menuID,'#')).not(":animated").css('z-index', 15);
			$( getSelect(menuID,'#')).not(":animated").slideDown(200);
			
			setLinks(menuID);
			
			$( '#jsDebug' ).html('None were visible.');
		}
	};


	if ( $('#wrapper-navbar').width() > 1024 ){
		$( getSelect('t_sm_equipment', '.') + ' a' ).click(function(evt){ evt.preventDefault(); toggleMenu('t_sm_equipment' ); });
		$( getSelect('t_sm_products', '.') ).click(function(evt){ evt.preventDefault(); toggleMenu('t_sm_products'); });
	}
	else{
		$( getSelect('t_sm_equipment', '.') + ' a' ).click(function(evt){ evt.preventDefault(); toggleMenu('t_sm_equipment', true ); });
		$( getSelect('t_sm_products', '.') ).click(function(evt){ evt.preventDefault(); toggleMenu('t_sm_products', true ); });
	}
	$( getSelect('t_md_nav', '.') ).click(function(evt){ evt.preventDefault(); toggleMenu('t_md_nav' ); });
	$( getSelect('t_md_stores', '.') ).click(function(evt){ evt.preventDefault(); toggleMenu('t_md_stores',true ); }); 
	$( getSelect('t_sm_nav', '.') ).click(function(evt){ evt.preventDefault(); toggleMenu('t_sm_nav'); });
	$( getSelect('t_xs_nav', '.') ).click(function(evt){ evt.preventDefault(); toggleMenu('t_xs_nav'); });
				
	//toggleMenu( getSelect('#t_md_equipment'))
  	$( "" ).css( "border", "3px solid red" );
		$( '#jsDebug' ).html('Debug active');
	
	//alert('lool');
	$( getSelect('t_sm_equipment_force', '.') ).click(function(evt){ evt.preventDefault(); toggleMenu('t_sm_equipment',true ); }); 
	$('#toggle_close_t_sm_equipment').click(function(evt){ evt.preventDefault(); toggleMenu('t_sm_equipment', true ); });

});



/////// Fonts

/*

 MyFonts Webfont Build ID 3307464, 2016-11-11T12:35:36-0500

 The fonts listed in this notice are subject to the End User License
 Agreement(s) entered into by the website owner. All other parties are 
 explicitly restricted from using the Licensed Webfonts(s).

 You may obtain a valid license at the URLs below.

 Webfont: Nexa-BlackItalic by Fontfabric
 URL: http://www.myfonts.com/fonts/font-fabric/nexa/black-italic/
 Copyright: Modern and elegant sans serif font.

 Webfont: Nexa-Black by Fontfabric
 URL: http://www.myfonts.com/fonts/font-fabric/nexa/black/
 Copyright: Modern and elegant sans serif font.

 Webfont: Nexa-Heavy by Fontfabric
 URL: http://www.myfonts.com/fonts/font-fabric/nexa/heavy/
 Copyright: Modern and elegant sans serif font.

 Webfont: Nexa-Regular by Fontfabric
 URL: http://www.myfonts.com/fonts/font-fabric/nexa/regular/
 Copyright: Copyright \(c\) 2012 by Svet Simov. All rights reserved.


 License: http://www.myfonts.com/viewlicense?type=web&buildid=3307464
 Licensed pageviews: 10,000

 ? 2016 MyFonts Inc
*/
var protocol=document.location.protocol;"https:"!=protocol&&(protocol="http:");var count=document.createElement("script");count.type="text/javascript";count.async=!0;count.src=protocol+"//hello.myfonts.net/count/3277c8";var s=document.getElementsByTagName("script")[0];s.parentNode.insertBefore(count,s);var browserName,browserVersion,webfontType;if("undefined"==typeof woffEnabled)var woffEnabled=!0;var svgEnabled=1,woff2Enabled=1;
if("undefined"!=typeof customPath)var path=customPath;else{var scripts=document.getElementsByTagName("SCRIPT"),script=scripts[scripts.length-1].src;script.match("://")||"/"==script.charAt(0)||(script="./"+script);path=script.replace(/\\/g,"/").replace(/\/[^\/]*\/?$/,"")}
var wfpath=path+"/webfonts/",browsers=[{regex:"MSIE (\\d+\\.\\d+)",versionRegex:"new Number(RegExp.$1)",type:[{version:9,type:"woff"},{version:5,type:"eot"}]},{regex:"Trident/(\\d+\\.\\d+); (.+)?rv:(\\d+\\.\\d+)",versionRegex:"new Number(RegExp.$3)",type:[{version:11,type:"woff"}]},{regex:"Firefox[/s](\\d+\\.\\d+)",versionRegex:"new Number(RegExp.$1)",type:[{version:3.6,type:"woff"},{version:3.5,type:"ttf"}]},{regex:"Edge/(\\d+\\.\\d+)",versionRegex:"new Number(RegExp.$1)",type:[{version:12,type:"woff"}]},
{regex:"Chrome/(\\d+\\.\\d+)",versionRegex:"new Number(RegExp.$1)",type:[{version:36,type:"woff2"},{version:6,type:"woff"},{version:4,type:"ttf"}]},{regex:"Mozilla.*Android (\\d+\\.\\d+).*AppleWebKit.*Safari",versionRegex:"new Number(RegExp.$1)",type:[{version:4.1,type:"woff"},{version:3.1,type:"svg#wf"},{version:2.2,type:"ttf"}]},{regex:"Mozilla.*(iPhone|iPad).* OS (\\d+)_(\\d+).* AppleWebKit.*Safari",versionRegex:"new Number(RegExp.$2) + (new Number(RegExp.$3) / 10)",unhinted:!0,type:[{version:5,
type:"woff"},{version:4.2,type:"ttf"},{version:1,type:"svg#wf"}]},{regex:"Mozilla.*(iPhone|iPad|BlackBerry).*AppleWebKit.*Safari",versionRegex:"1.0",type:[{version:1,type:"svg#wf"}]},{regex:"Version/(\\d+\\.\\d+)(\\.\\d+)? Safari/(\\d+\\.\\d+)",versionRegex:"new Number(RegExp.$1)",type:[{version:5.1,type:"woff"},{version:3.1,type:"ttf"}]},{regex:"Opera/(\\d+\\.\\d+)(.+)Version/(\\d+\\.\\d+)(\\.\\d+)?",versionRegex:"new Number(RegExp.$3)",type:[{version:24,type:"woff2"},{version:11.1,type:"woff"},
{version:10.1,type:"ttf"}]}],browLen=browsers.length,suffix="",i=0;
a:for(;i<browLen;i++){var regex=new RegExp(browsers[i].regex);if(regex.test(navigator.userAgent)){browserVersion=eval(browsers[i].versionRegex);var typeLen=browsers[i].type.length;for(j=0;j<typeLen;j++)if(browserVersion>=browsers[i].type[j].version&&(1==browsers[i].unhinted&&(suffix="_unhinted"),webfontType=browsers[i].type[j].type,"woff"!=webfontType||woffEnabled)&&("woff2"!=webfontType||woff2Enabled)&&("svg#wf"!=webfontType||svgEnabled))break a}else webfontType="woff"}
/(Macintosh|Android)/.test(navigator.userAgent)&&"svg#wf"!=webfontType&&(suffix="_unhinted");var head=document.getElementsByTagName("head")[0],stylesheet=document.createElement("style");stylesheet.setAttribute("type","text/css");head.appendChild(stylesheet);
for(var fonts=[{fontFamily:"Nexa-BlackItalic",url:wfpath+"3277C8_0"+suffix+"_0."+webfontType},{fontFamily:"Nexa-Black",url:wfpath+"3277C8_1"+suffix+"_0."+webfontType},{fontFamily:"Nexa-Heavy",url:wfpath+"3277C8_2"+suffix+"_0."+webfontType},{fontFamily:"Nexa-Regular",url:wfpath+"3277C8_3"+suffix+"_0."+webfontType}],len=fonts.length,css="",i=0;i<len;i++){var format="svg#wf"==webfontType?'format("svg")':"ttf"==webfontType?'format("truetype")':"eot"==webfontType?"":'format("'+webfontType+'")',css=css+
("@font-face{font-family: "+fonts[i].fontFamily+";src:url("+fonts[i].url+")"+format+";");fonts[i].fontWeight&&(css+="font-weight: "+fonts[i].fontWeight+";");fonts[i].fontStyle&&(css+="font-style: "+fonts[i].fontStyle+";");css+="}"}stylesheet.styleSheet?stylesheet.styleSheet.cssText=css:stylesheet.innerHTML=css;


///// Payment fixes

jQuery(document).ready(function ($) {
	console.log('loaded');
	function show_billing(){
		$('.logged_in_hidden').show();
		$('.saved_billing').hide();;
	
	}
	function show_billing_disclaimer(){
		$('.billing_disclaimer').slideDown();
	}	
	function hide_billing_disclaimer(){
		$('.billing_disclaimer').slideUp();
	}	
	function fetch_billing(){
			
	}

	function update_pnr($value){
		console.log($value);
		$('#pp_billing_ssn').val($value);
		$('#iv_billing_ssn').val($value);
		$('#billing_personnr').val($value);
	}	
	
	$(document).on("change", '#pp_billing_ssn', function(){
		$value = $( this ).val();
		update_pnr( $value ); 
	});
	$(document).on("change", '#iv_billing_ssn', function(){
		$value = $( this ).val();
		update_pnr( $value ); 
	});
	$(document).on("change", '#billing_personnr', function(){
		$value = $( this ).val();
		update_pnr( $value ); 
	});



	$(document).on('click', '.payment_method_sveawebpay_part_pay', function(){
		show_billing_disclaimer();
	});
	$(document).on('click', '#payment_method_sveawebpay_part_pay', function(){
		show_billing_disclaimer();
	});
	
	$(document).on('click', '.payment_method_sveawebpay_invoice', function(){
		show_billing_disclaimer();
	});
	$(document).on('click', '#payment_method_sveawebpay_invoice', function(){
		show_billing_disclaimer();
	});

	$(document).on('click', '.payment_method_stripe', function(){
		hide_billing_disclaimer();
	});
	$(document).on('click', '#payment_method_stripe', function(){
		hide_billing_disclaimer();
	});


	$(document).on('click', '.payment_method_redlight_swish-ecommerce', function(){
		hide_billing_disclaimer();
	});	
	$(document).on('click', '#payment_method_redlight_swish-ecommerce', function(){
		hide_billing_disclaimer();
	});

	
	$(document).on('click', '.update_billing', function(evt){
		evt.preventDefault();
		show_billing();
	});

});

////// Delivery dates

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
			if(variations.indexOf('Sommarsäsong') != -1){
				date = ($("#sommar").html());
				$('#order_return_rent-season-summer').val(date);
				var info = info + "Artiklar hyrda för sommarsäsongen återlämnas senast " + date + ".<br />";
			
			}
			
			// Vintersäsong
			if(variations.indexOf('Vintersäsong') != -1){
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
