// Adjust height of non season category menu
jQuery(document).ready(function ($) {
	$('.menu_non_season').each(function(){
      var parentHeight = $(this).parent().height();
      $(this).height(parentHeight);    
	});
});


// Smooth scroll on #
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


// Static menu
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


