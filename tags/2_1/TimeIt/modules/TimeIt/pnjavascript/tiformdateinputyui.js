function tiformdateinputyuiSetup(id, selectedDates) 
{
    var Event = YAHOO.util.Event,
        Dom = YAHOO.util.Dom;	
    var oCalendarMenu;

    var onButtonClick = function () 
    {
        // Create a Calendar instance and render it into the body 
        // element of the Overlay.
        var oCalendar = new YAHOO.widget.Calendar(id+"_buttoncalendar", oCalendarMenu.body.id, { selected:selectedDates });
        oCalendar.render();

        // Subscribe to the Calendar instance's "select" event to 
        // update the Button instance's label when the user
        // selects a date.
        oCalendar.selectEvent.subscribe(function (p_sType, p_aArgs) 
        {
            var aDate,
                nMonth,
                nDay,
                nYear;

            if (p_aArgs) 
            {
                aDate = p_aArgs[0][0];

                nMonth = aDate[1];
                nDay = aDate[2];
                nYear = aDate[0];

                oButton.set("label", (nYear+"-"+(nMonth<10?"0"+nMonth:nMonth)+"-"+(nDay<10?"0"+nDay:nDay)));


                // Sync the Calendar instance's selected date with the date form fields
                Dom.get(id).value = nYear+"-"+(nMonth<10?"0"+nMonth:nMonth)+"-"+(nDay<10?"0"+nDay:nDay);
            }

            oCalendarMenu.hide();
        });

        // Pressing the Esc key will hide the Calendar Menu and send focus back to 
        // its parent Button
        Event.on(oCalendarMenu.element, "keydown", function (p_oEvent) {

                if (Event.getCharCode(p_oEvent) === 27) {
                        oCalendarMenu.hide();
                        this.focus();
                }

        }, null, this);

        var focusDay = function () 
        {
            var oCalendarTBody = Dom.get(id+"_buttoncalendar").tBodies[0],
                aElements = oCalendarTBody.getElementsByTagName("a"),
                oAnchor;

            if (aElements.length > 0) 
            {
                Dom.batch(aElements,  function (element) 
                                      {			
                                          if (Dom.hasClass(element.parentNode, "today")) {
                                              oAnchor = element;
                                          }
                                      });

                if (!oAnchor) 
                {
                    oAnchor = aElements[0];
                }


                // Focus the anchor element using a timer since Calendar will try 
                // to set focus to its next button by default				
                YAHOO.lang.later(0, oAnchor, function () {
                        try {
                                oAnchor.focus();
                        }
                        catch(e) {}
                });			
            }			
        };

        // Set focus to either the current day, or first day of the month in 
        // the Calendar	when it is made visible or the month changes
        oCalendarMenu.subscribe("show", focusDay);
        oCalendar.renderEvent.subscribe(focusDay, oCalendar, true);


        // Give the Calendar an initial focus
        focusDay.call(oCalendar);

        // Re-align the CalendarMenu to the Button to ensure that it is in the correct
        // position when it is initial made visible
        oCalendarMenu.align();


        // Unsubscribe from the "click" event so that this code is 
        // only executed once
        this.unsubscribe("click", onButtonClick);		
    };


    var oDateField = Dom.get(id);


    // Hide the form field used for the date so that they can be replaced by the 
    // calendar button.
    oDateField.style.display = "none";

    // Create a Overlay instance to house the Calendar instance
    oCalendarMenu = new YAHOO.widget.Overlay(id+"_calendarmenu", { visible: false });

    // Create a Button instance of type "menu"
    var oButton = new YAHOO.widget.Button({ type: "menu", 
                                            id: id+"_calendarpicker", 
                                            label: selectedDates, 
                                            menu: oCalendarMenu, 
                                            container: id+"_dateinput" });


    oButton.on("appendTo", function () 
    {
        // Create an empty body element for the Overlay instance in order 
        // to reserve space to render the Calendar instance into.
        oCalendarMenu.setBody("&#32;");
        oCalendarMenu.body.id = id+"calendarcontainer";
    });
    
    // Add a listener for the "click" event.  This listener will be
    // used to defer the creation the Calendar instance until the 
    // first time the Button's Overlay instance is requested to be displayed
    // by the user.

    oButton.on("click", onButtonClick);
}

