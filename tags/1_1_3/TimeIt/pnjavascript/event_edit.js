function eventEditInit()
{
	Event.observe($('buttonsBox'), 'mouseover',function(event) 
											{
												showGoogleMapsMap(false);
											}, false);
}

function myWaitFunc(endwait)
{
	if(endwait == true)
	{
		return;
	} else 
	{
		window.setTimeout("myWaitFunc(true)", 1000);
	}
}

function showGoogleMapsMap(showMap, callback)
{
	if (GBrowserIsCompatible()) 
	{
		if(showMap)
		{
			$('gmap').show();
			var map = new GMap2(document.getElementById("gmap"));
			map.addControl(new GSmallMapControl());
			map.addControl(new GMapTypeControl());
		}
	
		var address = $('streat').value+' '+$('houseNumber').value+' '+$('zip').value+' '+$('city').value+' '+$('country').value;
		var geocoder = new GClientGeocoder();
		geocoder.getLatLng(address,
			function(point) 
			{
				if(point)
				{
					if(showMap)
					{
						map.setCenter(point, 13);
						var marker = new GMarker(point);
						map.addOverlay(marker);
					}
					
					$('lat').value = point.lat();
					$('lng').value = point.lng();
				}
				
				if(typeof callback == 'function')
				{ 
					callback();
				}	
			}
		);
	}
	return false;
}