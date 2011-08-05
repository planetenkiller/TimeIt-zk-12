/*********************************************************************
* No onMouseOut event if the mouse pointer hovers a child element 
* *** Please do not remove this header. ***
* This code is working on my IE7, IE6, FireFox, Opera and Safari
* 
* Usage: 
* <div onMouseOut="fixOnMouseOut(this, event, 'JavaScript Code');"> 
*		So many childs 
*	</div>
*
* @Author Hamid Alipour Codehead @ webmaster-forums.code-head.com		
**/
function is_child_of(parent, child) {
      if( child != null ) {			
              while( child.parentNode ) {
                      if( (child = child.parentNode) == parent ) {
                              return true;
                      }
              }
      }
      return false;
}
function fixOnMouseOut(element, event, JavaScript_code) {
      var current_mouse_target = null;
      if( event.toElement ) {				
              current_mouse_target 			 = event.toElement;
      } else if( event.relatedTarget ) {				
              current_mouse_target 			 = event.relatedTarget;
      }

      if(current_mouse_target != null && !is_child_of(element, current_mouse_target) && element != current_mouse_target ) {
          eval(JavaScript_code);
      }
}
/*********************************************************************/


function eventEditInit2()
{
    $('LocationTimeIt_name').focus();
    Event.observe($('timeit_address_box'), 'mouseover',myfunc);
}

function eventEditInit()
{
    // observe mouseout 
    myfunc = function(event) 
    {
        Event.stopObserving($('timeit_address_box'), 'mouseover', myfunc);        
        Event.observe($('timeit_address_box'), 'mouseout',function(event) 
        {
            fixOnMouseOut($('timeit_address_box'), event, 'eventEditInit2()');
        }, false);
    }
    // observe mouseover
    Event.observe($('timeit_address_box'), 'mouseover',myfunc);
          
    
    
    // observe any changes on the address
     Event.observe($('LocationTimeIt_street'), 'focus',function(event)
                                         {
                                                 checkAddress($('LocationTimeIt_street').value);
                                         }, false);
     Event.observe($('LocationTimeIt_street'), 'blur',function(event)
                                         {
                                                 checkAddressTwo($('LocationTimeIt_street').value);
                                         }, false);
     Event.observe($('LocationTimeIt_houseNumber'), 'focus',function(event)
                                         {
                                                 checkAddress($('LocationTimeIt_houseNumber').value);
                                         }, false);
     Event.observe($('LocationTimeIt_houseNumber'), 'blur',function(event)
                                         {
                                                 checkAddressTwo($('LocationTimeIt_houseNumber').value);
                                         }, false);
     Event.observe($('LocationTimeIt_zip'), 'focus',function(event)
                                         {
                                                 checkAddress($('LocationTimeIt_zip').value);
                                         }, false);
     Event.observe($('LocationTimeIt_zip'), 'blur',function(event)
                                         {
                                                 checkAddressTwo($('LocationTimeIt_zip').value);
                                         }, false);
     Event.observe($('LocationTimeIt_city'), 'focus',function(event)
                                         {
                                                 checkAddress($('LocationTimeIt_city').value);
                                         }, false);
     Event.observe($('LocationTimeIt_city'), 'blur',function(event)
                                         {
                                                 checkAddressTwo($('LocationTimeIt_city').value);
                                         }, false);
     Event.observe($('LocationTimeIt_country'), 'focus',function(event)
                                         {
                                                 checkAddress($('LocationTimeIt_country').value);
                                         }, false);
     Event.observe($('LocationTimeIt_country'), 'blur',function(event)
                                         {
                                                 checkAddressTwo($('LocationTimeIt_country').value);
                                         }, false);
     
     
     
}

function checkAddress(value)
{
    timeit_address_changed = true;
    timeit_address_changed2 = value;
}

function checkAddressTwo(value)
{
    if(timeit_address_changed2 != value)
    {
        window.setTimeout("checkAddressThree()", 1000);
    }
    timeit_address_changed = false;
}

function checkAddressThree()
{
    if(!timeit_address_changed)
    {
        showGoogleMapsMap(false);
    }
}

function eventEditInitBoxes()
{
    Event.observe($('formicula_contact_choose'), 'click',function(event) 
                                                         {
                                                            $('formicula_contact_choose_content').show();
                                                            $('formicula_contact_add_content').hide();
                                                         }, false);
    Event.observe($('formicula_contact_add'), 'click',function(event) 
                                                         {
                                                            $('formicula_contact_choose_content').hide();
                                                            $('formicula_contact_add_content').show();
                                                         }, false);

    Event.observe($('formicula_contact_add'), 'click',function(event)
                                                         {
                                                            $('formicula_contact_choose_content').hide();
                                                            $('formicula_contact_add_content').show();
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

var timeit_address_changed = false;
var timeit_address_changed2 = false;

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

        var address = $('LocationTimeIt_street').value+' '+$('LocationTimeIt_houseNumber').value+' '+$('LocationTimeIt_zip').value+' '+$('LocationTimeIt_city').value+' '+$('LocationTimeIt_country').value;
        var geocoder = new GClientGeocoder();
        $('timeit_geocode_indicator').show();
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

                    $('LocationTimeIt_lat').value = point.lat();
                    $('LocationTimeIt_lng').value = point.lng();
                }

                $('timeit_geocode_indicator').hide();

                if(typeof callback == 'function')
                {
                    callback();
                }
            }
        );
    }

    return false;
}


function sendRecurrenceToApplet()
{
    $('sendRecurrenceToApplet_error_date').hide();
    
    var type = parseInt(radioWert($('pnFormForm').repeat));
    var startDate = $('pnFormForm').startDate.value;
    var endDate = $('pnFormForm').endDate.value;
    var frec = "";
    var spec = "";
    if(type == "1")
    {
        frec = $('pnFormForm').repeatFrec.value;
        spec = selectWert($('pnFormForm').repeatFrec1);
    } else if(type == "2")
    {
        spec = $('pnFormForm');
        spec = get_check_value(spec['repeat21[]']);
        spec = spec +" " + selectWert($('pnFormForm').repeat22);
        frec = $('pnFormForm').repeatFrec2.value;
    } else if(type == "3")
    {
        spec = $('pnFormForm').repeat3Dates.value;
    }
    
    if(startDate && endDate)
    {
        $('tiapplet').setRecurrence(type, spec, frec, startDate, endDate);
    } else
    {
        $('sendRecurrenceToApplet_error_date').show();
    }
}

function addIgnoreDate(d)
{
    var d2 = d.split("-");
    var year = parseInt(d2[0]);
    var month = parseInt(d2[1]);
    var day = parseInt(d2[2]);
    
    
    if( window.calendar != null)
    {
         window.calendar.hide();
    }
    window.calendar = null;
    
    $('noReapeats').value += d+",";
    MA_noReapeats.push(new Date(year, month-1, day));
    $('noReapeats_img').onclick();
    
    window.calendar.hide();
}

function radioWert(rObj) 
{
    for (var i=0; i<rObj.length; i++) if (rObj[i].checked) return rObj[i].value;
    return false;

}

function selectWert(sObj) 
{
    with (sObj) return options[selectedIndex].value;
}

function get_check_value(obj)
{
    var c_value = "";
    for (var i=0; i < obj.length; i++)
    {
        if (obj[i].checked)
        {
            c_value = c_value + obj[i].value+",";
        }
    }
    
    return c_value.substring(0, c_value.length-1);
}

