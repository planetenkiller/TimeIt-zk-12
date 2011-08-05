<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) TimeIt Development Team
 * @link http://code.zikula.org/timeit
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package TimeIt
 * @subpackage Template Plugins Spezial
 */

/** Make sure to use require_once() instead of Loader::requireOnce() since "function.pnformtextinput.php"
 is loaded by Smarty (the base render class) with the use of require_once(). We do not want to
 get in conflict with that.*/
if(file_exists('system/pnForm/plugins/function.pnformtextinput.php'))
    require_once('system/pnForm/plugins/function.pnformtextinput.php');


/**
 * Date input for pnForms
 *
 * The date input plugin is a text input plugin that only allows dates to be posted. The value
 * returned from {@link pnForm::pnFormGetValues()} is although a string of the format 'YYYY-MM-DD'
 * since this is the standard internal Zikula format for dates.
 *
 * You can also use all of the features from the pnFormTextInput plugin since the date input
 * inherits from it.
 *
 * @package pnForm
 * @subpackage Plugins
 */
class tiFormDateInput extends pnFormTextInput
{
    /**
     * Enable or disable input of time in addition to the date
     * @var bool
     */
    var $includeTime;
    var $initDate;
    var $multipleTime;

    /**
     * Default date value
     *
     * This parameter enables the input to be pre-filled with the current date or similar other well defined
     * default values.
     * You can set the default value to be one of the following:
     * - now: current date and time
     * - today: current date
     * - monthstart: first day in current month
     * - monthend: last day in current month
     * - yearstart: first day in the year
     * - yearend: last day in the year
     * - custom: inital Date
     */
    var $defaultValue;


    function getFilename()
    {
        return __FILE__; // FIXME: may be found in smarty's data???
    }


    function create(&$render, &$params)
                {
        $this->includeTime = (array_key_exists('includeTime', $params) ? $params['includeTime'] : 0);
        $this->daFormat = (array_key_exists('daFormat', $params) ? $params['daFormat'] : ($this->includeTime ? __('%A, %B %d, %Y - %I:%M %p') : __('%A, %B %d, %Y')));
        $this->defaultValue = (array_key_exists('defaultValue', $params) ? $params['defaultValue'] : null);
        $this->initDate = (array_key_exists('initDate', $params) ? $params['initDate'] : 0);
        $this->useSelectionMode = (array_key_exists('useSelectionMode', $params) ? $params['useSelectionMode'] : 0);
        $this->multipleTime = (array_key_exists('multipleTime', $params) ? $params['multipleTime'] : 0);
        
        if($this->multipleTime){
            $this->maxLength = 1600;
        } else {
            $this->maxLength = ($this->includeTime ? 18 : 12);
        }
        $params['width'] = ($this->includeTime ? '10em' : '8em');

        parent::create($render, $params);

        $this->cssClass .= ' date';
    }


    function render(&$render)
    {
        static $firstTime = true;

        $i18n = & ZI18n::getInstance();

        if (!empty($this->defaultValue) && !$render->pnFormIsPostBack())
        {
            $d = strtolower($this->defaultValue);
            $now = getdate();
            $date = null;

            if ($d == 'now') {
                $date = time();
            } else if ($d == 'today') {
                $date = mktime(0, 0, 0, $now['mon'], $now['mday'], $now['year']);
            } else if ($d == 'monthstart') {
                $date = mktime(0, 0, 0, $now['mon'], 1, $now['year']);
            } else if ($d == 'monthend') {
                $daysInMonth = date('t');
                $date = mktime(0, 0, 0, $now['mon'], $daysInMonth, $now['year']);
            } else if ($d == 'yearstart') {
                $date = mktime(0, 0, 0, 1, 1, $now['year']);
            } else if ($d == 'yearend') {
                $date = mktime(0, 0, 0, 12, 31, $now['year']);
            } else if ($d == 'custom') {
                $date = strtotime($this->initDate);
            } else if ($d == 'multiple') {
                $date = "more";
            }

            if ($date != null)
                $this->text = DateUtil::getDatetime($date, ($this->includeTime ? '%Y-%m-%d %H:%M' : '%Y-%m-%d'));
            /*else
                $this->text = 'Unknown default date';*/
        }

        $result = '<span class="date" style="white-space: nowrap">';

        // Bugfix: after a postback and an error $this->text contains a date with the format yyyy-mm-dd, we call formatValue to get a language specific date
        if(!empty($this->text)) {
            $this->text = $this->formatValue($render, $this->text);
        }

        $result .= parent::render($render);

        $txt = _PNFORM_SELECTDATE;
        $result .= " <img id=\"{$this->id}_img\" src=\"javascript/jscalendar/img.gif\" style=\"vertical-align: middle\" class=\"clickable\" alt=\"$txt\" /></span>";

        if ($firstTime) {
            $headers[] = 'javascript/jscalendar/calendar.js';
            $lang = ZLanguage::transformFS(ZLanguage::getLanguageCode());
            // map of the jscalendar supported languages
            $map = array('ca' => 'ca_ES', 'cz' => 'cs_CZ', 'da' => 'da_DK', 'de' => 'de_DE', 'el' => 'el_GR', 'en-us' => 'en_US', 'es' => 'es_ES', 'fi' => 'fi_FI', 'fr' => 'fr_FR', 'he' => 'he_IL', 'hr' => 'hr_HR', 'hu' => 'hu_HU', 'it' => 'it_IT', 'ja' => 'ja_JP',
                         'ko' => 'ko_KR', 'lt' => 'lt_LT', 'lv' => 'lv_LV', 'nl' => 'nl_NL', 'no' => 'no_NO', 'pl' => 'pl_PL', 'pt' => 'pt_BR', 'ro' => 'ro_RO', 'ru' => 'ru_RU', 'si' => 'si_SL', 'sk' => 'sk_SK', 'sv' => 'sv_SE', 'tr' => 'tr_TR');

            if ($map[$lang]) {
                $lang = $map[$lang];
            }



            $headers[] = 'javascript/jscalendar/calendar.js';
            if (file_exists("javascript/jscalendar/lang/calendar-$lang.utf8.js")) {
                $headers[] = "javascript/jscalendar/lang/calendar-$lang.utf8.js";
            } else {
                $headers[] = "javascript/jscalendar/lang/calendar-$lang.js";
            }
            $headers[] = 'javascript/jscalendar/calendar-setup.js';
            PageUtil::addVar('stylesheet', 'javascript/jscalendar/calendar-win2k-cold-2.css');
            PageUtil::addVar('javascript', $headers);
        }
        $firstTime = false;

        if($this->multipleTime) {
            $result .= "<script type=\"text/javascript\">//<![CDATA[
                    // the default multiple dates selected, first time the calendar is instantiated
                    var MA_".$this->id." = [];";
            
            if($this->text) {
                $dates = explode(',', $this->text);
                foreach($dates AS $date) {
                    if($date) {
                        $date = DateUtil::transformInternalDateTime(DateUtil::parseUIDate($date));
                        $dateExplode = explode('-', $date);
                        $result .= "MA_".$this->id.".push(new Date(".((int)$dateExplode[0]).",".(((int)$dateExplode[1])-1).",".((int)$dateExplode[2])."));\n";
                    }
                }
            }

            $result .= "
                    function closed(cal) {

                        // here we'll write the output; this is only for example.  You
                        // will normally fill an input field or something with the dates.
                        var el = document.getElementById(\"".$this->id."\");
                        
                        // reset initial content.
                        el.value = \"\";

                        // Reset the \"MA\", in case one triggers the calendar again.
                        // CAREFUL!  You don't want to do \"MA = [];\".  We need to modify
                        // the value of the current array, instead of creating a new one.
                        // Calendar.setup is called only once! :-)  So be careful.
                        MA_".$this->id.".length = 0;

                        // walk the calendars multiple dates selection hash
                        for (var i in cal.multiple) {
                        var d = cal.multiple[i];
                        // sometimes the date is not actually selected, thats why we need to check.
                        if (d) {
                            // OK, selected.  Fill an input field.  Or something.  Just for example,
                            // we will display all selected dates in the element having the id \"output\".
                            document.getElementById(\"".$this->id."\").value += d.print(\"" . __('%Y-%m-%d') . "\") + \",\";

                            // and push it in the \"MA\", in case one triggers the calendar again.
                            MA_".$this->id."[MA_".$this->id.".length] = d;
                        }
                        }
                        cal.hide();
                        return true;
                    };

                    Calendar.setup({
                        showOthers : true,
                        multiple   : MA_".$this->id.", // pass the initial or computed array of multiple dates to be initially selected
                        onClose    : closed,
                        ifFormat : \"" . __('%Y-%m-%d') . "\",
                        button : \"{$this->id}_img\",
                        firstDay: " . $i18n->locale->getFirstweekday() . "
                    }); 

                      //]]>
                      </script>";
        } else if($this->includeTime) {
            $this->initDate = str_replace('-',',', $this->initDate);
            $result .= "<script type=\"text/javascript\">
            Calendar.setup(
                {
                    inputField : \"{$this->id}\",
                    ifFormat : \"" . __('%Y-%m-%d %H:%M') . "\",
                    showsTime      :    true,
                    timeFormat     :    \"".$i18n->locale->getTimeformat()."\",
                    button : \"{$this->id}_img\",
                    singleClick    :    false,
                    firstDay: " . $i18n->locale->getFirstweekday() . "
                });
                </script>";
        } else {
            $result .= "<script type=\"text/javascript\">
            Calendar.setup(
                {
                    inputField : \"{$this->id}\",
                    ifFormat : \"" . __('%Y-%m-%d') . "\",
                    button : \"{$this->id}_img\",
                    firstDay: " . $i18n->locale->getFirstweekday() . "
                }
            );
            </script>";
        }
        return $result;
    }


    function parseValue(&$render, $text)
    {
      if (empty($text))
          return null;
      return $text;
    }


    function validate(&$render)
    {
        parent::validate($render);
        if (!$this->isValid)
            return;

        if (strlen($this->text) > 0) {
            $error = false;
            if ($this->includeTime) {
                $dateValue = DateUtil::transformInternalDateTime(DateUtil::parseUIDate($this->text));
            } else {
                if($this->multipleTime)  {
                    $dateValue = "";
                    $dates = explode(',', $this->text);
                    foreach($dates AS $date) {
                        if($date) {
                            $timestamp = DateUtil::parseUIDate($date);
                            if($timestamp == NULL) {
                                $error = true;
                                break;
                            } else {
                                $dateValue .= ",".DateUtil::transformInternalDate($timestamp);
                            }
                        }
                    }
                    $dateValue = substr($dateValue, 1); // remove first ,
                } else {
                    $dateValue = DateUtil::transformInternalDate(DateUtil::parseUIDate($this->text));
                }
            }

            if ($dateValue == null || $error)
                $this->setError(__('Error! Invalid date.'));
            else
                $this->text = $dateValue;
        }
    }


    function formatValue(&$render, $value)
    {
        if(!$this->multipleTime) {
            return DateUtil::formatDatetime($value, ($this->includeTime ? __('%Y-%m-%d %H:%M') : __('%Y-%m-%d')), false);
        } else {
            $dateValue = '';
            $dates = explode(',', $value);
            foreach($dates AS $date) {
                if($date)
                    $dateValue .= ','.DateUtil::formatDatetime($date,  __('%Y-%m-%d'));
            }
            return substr($dateValue, 1); // remove first , and return
        }
        
    }
}


function smarty_function_tiformdateinput($params, &$render)
{
    return $render->pnFormRegisterPlugin('tiFormDateInput', $params);
}
