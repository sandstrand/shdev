
function initilize(x, y, url) {
	var latlng = new google.maps.LatLng(x, y);
	var settings = {
		zoom: 15,
		center: latlng,
		mapTypeControl: true,
		mapTypeControlOptions: {style: 
google.maps.MapTypeControlStyle.DROPDOWN_MENU},
		navigationControl: true,
		navigationControlOptions: {style: 
google.maps.NavigationControlStyle.SMALL},
		mapTypeId: google.maps.MapTypeId.ROADMAP
	};
    var map = new google.maps.Map(document.getElementById("map_canvas"), 
settings);

    var companyLogo = new google.maps.MarkerImage(url,
  		new google.maps.Size(100,50),
		new google.maps.Point(0,0),
		new google.maps.Point(50,50)
	);
	
	var companyPos = new google.maps.LatLng(x, y);
	var companyMarker = new google.maps.Marker({
		position: companyPos,
		map: map,
		icon: companyLogo,
		title:"Sporthyra",
		zIndex:4
	});
	var styles = [
	  {
	    "stylers": [
	      { "saturation": -100 }
	    ]
	  },{
	  }
	]

	map.setOptions({styles: styles});

}


