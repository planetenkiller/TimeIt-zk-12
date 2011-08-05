function initSubscriptionLink()
{
	var obj = $('viewUserLink');
	Event.observe(obj, 'click', showSubscribedUser, false);
	
	var obj = $('subscribeLink');
	Event.observe(obj, 'click', subscribeEvent, false);
	
	var obj = $('unsubscribeLink');
	Event.observe(obj, 'click', unsubscribeEvent, false);
}


function showSubscribedUser(event)
{
	var obj = $('viewUserList');
	if(!obj.visible())
	{
		var pars = "module=TimeIt&func=viewUserOfSubscribedEvent&id="+timeIt_event_id;
	
    	var myAjax = new Ajax.Request(
        	"ajax.php", 
        	{
            	method: 'get', 
            	parameters: pars, 
            	onComplete: showSubscribedUser_response
        	});
	} else
	{
		obj.hide();
	}
	
	Event.stop(event);
}

function showSubscribedUser_response(req)
{
	if (req.status != 200 ) { 
        pnshowajaxerror(req.responseText);
        return;
    }
    
    var json = pndejsonize(req.responseText);
    
    var obj = $('viewUserList');
    obj.innerHTML = json.html;
    obj.show();
}

function subscribeEvent(event)
{
	var pars = "module=TimeIt&func=subscribe&id="+timeIt_event_id;
	var myAjax = new Ajax.Request(
        "ajax.php", 
        {
            method: 'get', 
            parameters: pars, 
            onComplete: subscribeEvent_response
        });

	
	Event.stop(event);
}

function subscribeEvent_response(req)
{
	if (req.status != 200 ) { 
        pnshowajaxerror(req.responseText);
        return;
    }
    
    var json = pndejsonize(req.responseText);
    
    if(json.result)
    {
    	$('subscribeLink').hide();
    	$('unsubscribeLink').show();
    }
}

function unsubscribeEvent(event)
{
	var pars = "module=TimeIt&func=unsubscribe&id="+timeIt_event_id;
	var myAjax = new Ajax.Request(
        "ajax.php", 
        {
            method: 'get', 
            parameters: pars, 
            onComplete: unsubscribeEvent_response
        });

	
	Event.stop(event);
}

function unsubscribeEvent_response(req)
{
	if (req.status != 200 ) { 
        pnshowajaxerror(req.responseText);
        return;
    }
    
    var json = pndejsonize(req.responseText);
    
    if(json.result)
    {
    	$('subscribeLink').show();
    	$('unsubscribeLink').hide();
    }
}

function showGoogleMapsMap(lat, lng)
{
	if (GBrowserIsCompatible()) 
	{
		var map = new GMap2(document.getElementById("gmap"));
		map.addControl(new GSmallMapControl());
		map.addControl(new GMapTypeControl());
	
		var point = new GLatLng(lat, lng);
		map.setCenter(point, 13);
		var marker = new GMarker(point);
		map.addOverlay(marker);
	}
}