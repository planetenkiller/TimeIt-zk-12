<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) 2008, TimeIt Development Team
 * @link http://www.assembla.com/spaces/TimeIt
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 */

define('_TIMEIT',           	'TimeIt');
define('_TIMEIT_ACCOUNT_PRVATECALENDAR',    'Show private calendar');
define('_TIMEIT_ACCOUNT_SUBSCRIBED_EVENTS',	'Calendar: view subscribed events');
define('_TIMEIT_ADDITIONALINFO','Additional Infos');
define('_TIMEIT_ALLDAY',    	'All Day');
define('_TIMEIT_ALLDAY2',   	'Start Time');
define('_TIMEIT_ALLOWSUBSCRIBE', 'allow user to subscribe events');
define('_TIMEIT_ALLOWSUBSCRIBEDELETE','allow event creator to delete subscribed user');
define('_TIMEIT_ATOMREDIRECT',	'Atom is available under ');
define('_TIMEIT_CITY', 			'city');
define('_TIMEIT_COUNTRY', 		'country');
define('_TIMEIT_CONTACTPERSON', 'contact person');
define('_TIMEIT_CONFIG',    	'Settings');
define('_TIMEIT_CONTACT', 		'Contact');
define('_TIMEIT_DEFAULTVIEW', 	'Default calendar view');
define('_TIMEIT_DEFAULTTEMPLATE','default template');
define('_TIMEIT_DURATION', 		'Duration');
define('_TIMEIT_EVERY',     	'every');
define('_TIMEIT_EVENTDATE', 	'Event/Start Date');
define('_TIMEIT_EVENTTYPE', 	'Event Type');
define('_TIMEIT_ENDDATE',   	'End Date');
define('_TIMEIT_EVENTDETAILS', 	'Event Details');
define('_TIMEIT_EVENTADDED', 	'event added');
define('_TIMEIT_FEE', 			'fee');
define('_TIMEIT_FULL',			'full');
define('_TIMEIT_FILTERBYPERMISSION','Filter Events by the Category Permission');
define('_TIMEIT_FRIENDCALENDAR','Allow events for friends only');
define('_TIMEIT_FRIENDEVENT',   'event for friends only');
define('_TIMEIT_GLOBALCALENDAR','Allow global events');
define('_TIMEIT_GROUP', 		'group');
define('_TIMEIT_GLOBALEVENT', 	'global Event');
define('_TIMEIT_HOUSENUMBER', 	'house number');
define('_TIMEIT_HIDDEN', 		'Offline');
define('_TIMEIT_HOURS', 		'hours');
define('_TIMEIT_IMPORT', 		'import');
define('_TIMEIT_IMPORT_PC',		'Import form PostCalendar');
define('_TIMEIT_IMPORT_ICAL',	'Import from iCalendar');
define('_TIMEIT_IMPORT_PCINFO',	'PostCalendar does not have to be installed. Existing tables are sufficient. Prefix: Used in table name, {prefix}_postcalendar_events');
define('_TIMEIT_ITMESPERPAGE', 	'Items per page');
define('_TIMEIT_IMPORTSUCESS',	'import succesfull');
define('_TIMEIT_LIMIT',			'Limit(0=no subscribe)');
define('_TIMEIT_LOCATION', 		'Location');
define('_TIMEIT_MORE', 			'more');
define('_TIMEIT_MONTHTODAY', 	'Border color today');
define('_TIMEIT_MONTHON', 		'Background color current month');
define('_TIMEIT_MONTHOFF', 		'Background color other month');
define('_TIMEIT_NOREPEAT',  	'No Repeat');
define('_TIMEIT_NEW',       	_ADD);
define('_TIMEIT_NEW_EVENT', 	'Add an Event');
define('_TIMEIT_ADMIN_COUNTPENDING','There are %i% new pending events.');
define('_TIMEIT_NOTIFYEVENTS', 	'Notify admin about event submission?');
define('_TIMEIT_NOTIFYEVENTSEMAIL', '(Admin) email Address');
define('_TIMEIT_NOEVENTS', 		'no events found');
define('_TIMEIT_NOTIFYEVENTS_MESSAGE','Hello,\n\nUser %user% added a new event.\n\nTitle: %title%\nLink:%link%\n%pending%');
define('_TIMEIT_OCLOCK',    	'O\'clock');
define('_TIMEIT_OFMONTHEVERY',  'of the Month '._TIMEIT_EVERY);
define('_TIMEIT_ON',        	'on');
define('_TIMEIT_PENDING',   	'Pending');
define('_TIMEIT_POSTEDBYANDON', 'Posted by %username% on %date%');
define('_TIMEIT_PRVATEEVENT', 	'private Event');
define('_TIMEIT_PUBLICEVENT', 	'public Event');
define('_TIMEIT_PHONE', 		'phone number');
define('_TIMEIT_RSSREDIRECT',	'Rss is available under ');
define('_TIMEIT_PRVATECALENDAR','Allow private calendar');
define('_TIMEIT_REPEAT',    	'Repeat');
define('_TIMEIT_RSSATOMITEMS', 	'Rss/Atom: number of (newest) events');
define('_TIMEIT_STREAT', 		'streat');
define('_TIMEIT_SCRIBITEEDITOR','scribite editor("-"=>none)');
define('_TIMEIT_SHARING',   	'Sharing');
define('_TIMEIT_SUBSCRIBE',		'subscribe');
define('_TIMEIT_SUBSCRIBED_EVENTS',    	 'view subscribed events');
define('_TIMEIT_SUBSCRIBED_EVENTS_TITLE','Subscribed events');
define('_TIMEIT_SUBSCRIBED_USER',	     'view subscribed user');
define('_TIMEIT_SUBSCRIBED_USER_TITLE',  'Subscribed user');
define('_TIMEIT_SUBSCRIBEMODERATE',      'Moderate Subscribed user');
define('_TIMEIT_TICKET_PRICE', 	'Ticket / Price');
define('_TIMEIT_TEMPLATE',		'Template');
define('_TIMEIT_UNSUBSCRIBE',	'unsubscribe');
define('_TIMEIT_UN_SUBSCRIBE',	'(un-) subscribe');
define('_TIMEIT_UNLOCK',		'Unlock');
define('_TIMEIT_WEBSITE', 		'website');
define('_TIMEIT_WORKFLOW', 		'Workflow');
define('_TIMEIT_ZIP', 			'zip');

// errors
define('_TIMEIT_ERROR_1', 		_TIMEIT_EVENTDATE.' bigger than '._TIMEIT_ENDDATE);
define('_TIMEIT_ERROR_2', 		'It isnt allowed to disable global events, event for friends only and private calendar!');
define('_TIMEIT_ERROR_PCTABLE', 'PostCalendar tables not found.');
define('_TIMEIT_INVALIDDATE', 	'Invalid date given.');
define('_TIMEIT_ERROR_UPLOADINVALID', 'Uploaded File is invalid.');
define('_TIMEIT_ERROR_USERNOFOUND',   'User %s% not found.');
define('_TIMEIT_IDNOTEXIST',	'Error, the Event does not exist.');
define('_TIMEIT_NOCONTACTLIST',	'Module ContactList isn\'t installed.');
define('_TIMEIT_NOSCRBITE',	    'Module Scribite isn\'t installed.');
define('_TIMEIT_NOIDPATAM', 	'Error, The Parameter "id" is not  set.');

// blocks
define('_TIMEIT_BLOCK_TODAYEVENTS', 			'Today events');
define('_TIMEIT_BLOCK_UPCOMINGEVENTS', 			'Upcoming events');
define('_TIMEIT_BLOCK_VIEWTYPE', 				'Display type of block');
define('_TIMEIT_BLOCK_CONFIGFORVEWTYPE', 		'Settings for display type "'._TIMEIT_BLOCK_UPCOMINGEVENTS.'"');
define('_TIMEIT_BLOCK_EVENTS_DISPLAY_LIMIT', 	'Display how many events?');
define('_TIMEIT_BLOCK_EVENTS_DISPLAY_RANGE', 	'How many months ahead to query for upcoming events?');
define('_TIMEIT_DISPLAY_IF_THERE_EVENTS', 		'highlight days with events?');
define('_TIMEIT_BLOCK_FOR_USER', 				'Display private/public events from logged in user?');
define('_TIMEIT_BLOCK_HIDE_UNREG', 				'hide block on unregestred user(with above option only)');
define('_TIMEIT_NONE', 							'none');

