function initSubscriptionLink()
{
    Event.observe($('timeit_delete_button'), 'click', function(){
        if(confirm(timeit_lang_delete)) {
            $('timeit_delete_form').submit();
        }
    }, false);

    var obj = $('viewUserLink');
    Event.observe(obj, 'click', showSubscribedUser, false);

    obj = $('subscribeLink');
    Event.observe(obj, 'click', function(event) {
        if(confirm(timeit_lang_register)) {
            subscribeEvent(event);
        }

        Event.stop(event);
    }, false);

    obj = $('unsubscribeLink');
    Event.observe(obj, 'click', function(event) {
        if(confirm(timeit_lang_deregister)) {
            unsubscribeEvent(event);
        }

        Event.stop(event);
    }, false);

    
}


function showSubscribedUser(event)
{
    var obj = $('viewUserList');
    if(!obj.visible())
    {
        var pars = "module=TimeIt&func=viewUserOfSubscribedEvent&id="+timeIt_dheobj_id;

        var myAjax = new Ajax.Request(
            document.location.pnbaseURL+"ajax.php",
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
    var pars = "module=TimeIt&func=subscribe&id="+timeIt_dheobj_id+"&eid="+timeIt_event_id;
    var myAjax = new Ajax.Request(
        document.location.pnbaseURL+"ajax.php",
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
        $('timeit_ajax_content').innerHTML = '<div class="pn-statusmsg">'+json.statusmsg+'</div>';
        $('timeit_ajax_content').show();
        $('subscribeLink').hide();
        $('unsubscribeLink').show();
    }
}

function unsubscribeEvent(event)
{
    var pars = "module=TimeIt&func=unsubscribe&id="+timeIt_dheobj_id+"&eid="+timeIt_event_id;
    var myAjax = new Ajax.Request(
        document.location.pnbaseURL+"ajax.php", 
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

function showGoogleMapsMap(lat, lng, zoom)
{
    if(!zoom) {
        zoom = 13;
    }

    if (GBrowserIsCompatible())
    {
        var map = new GMap2(document.getElementById("gmap"));
        map.addControl(new GSmallMapControl());
        map.addControl(new GMapTypeControl());

        var point = new GLatLng(lat, lng);
        map.setCenter(point, zoom);
        var marker = new GMarker(point);
        map.addOverlay(marker);
    }
}