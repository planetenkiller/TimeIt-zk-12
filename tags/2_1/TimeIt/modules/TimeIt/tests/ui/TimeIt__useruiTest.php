<?php
/**
 * TimeIt Calendar Module
 *
 * @copyright (c) TimeIt Development Team
 * @link http://code.zikula.org/timeit
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package TimeIt
 * @subpackage Tests
 */

/**
 * Tests for pnuser.php file.
 */
class TimeIt__useruiTest extends ZkUnitUITestCase
{
    public function __construct($name)
    {
        parent::__construct($name);
    }

    public function testSubscribtion()
    {
        // login
        $this->login('admin','admin');
        $this->open("index.php?module=TimeIt&func=event&id=3&date=2009-01-02");
        $this->assertEquals("allday subscribe", $this->getText("//div[@id='pn-maincontent']/div[2]/table/tbody/tr[1]/td/span[1]/strong/span"));
        $this->assertEquals("Description allday event subscribeable\n \n You are already registered. View registered user \n \n Register | unsubscribe", $this->getTable("//div[@id='pn-maincontent']/div[2]/table.1.0"));
        $this->assertFalse($this->isVisible("//div[@id='pn-maincontent']/div[2]/table/tbody/tr[2]/td/a[@id='subscribeLink']"));
        $this->click("unsubscribeLink");
        $this->assertEquals("Description allday event subscribeable\n \n You are already registered. View registered user \n \n Register | unsubscribe", $this->getTable("//div[@id='pn-maincontent']/div[2]/table.1.0"));
        $this->click("subscribeLink");
        $this->click("viewUserLink");
        $this->assertTrue($this->isTextPresent("admin"));
        $this->click("viewUserLink");
        $this->assertTrue($this->isVisible("//div[@id='pn-maincontent']/div[2]/table/tbody/tr[2]/td/a[@id='unsubscribeLink']"));
        $this->click("unsubscribeLink");
    }

    public function testMonthViewJan()
    {
        $this->open("index.php?module=TimeIt&year=2009&month=1");
        $this->assertEquals("<< January 2009 >>", $this->getText("//div[@id='pn-maincontent']/table[2]/tbody/tr[1]/td/div/strong"));
        $this->assertEquals("01 \n » allday\n » 10:00 day with time", $this->getTable("//div[@id='pn-maincontent']/table[2].2.3"));
        $this->assertEquals("02 \n » allday subscribe", $this->getTable("//div[@id='pn-maincontent']/table[2].2.4"));
        $this->assertEquals("03 \n » multiday", $this->getTable("//div[@id='pn-maincontent']/table[2].2.5"));
        $this->assertEquals("04 \n » multiday", $this->getTable("//div[@id='pn-maincontent']/table[2].2.6"));
        $this->assertEquals("05 \n [week] » multiday", $this->getTable("//div[@id='pn-maincontent']/table[2].3.0"));
    }

    public function testMonthViewFeb()
    {
        $this->open("index.php?module=TimeIt&year=2009&month=2");
        $this->assertEquals("<< February 2009 >>", $this->getTable("//div[@id='pn-maincontent']/table[2].0.0"));
        $this->assertEquals("02 \n [week] » rep 1 day\n » rep 1 week\n » rep 1 year", $this->getTable("//div[@id='pn-maincontent']/table[2].3.0"));
        $this->assertEquals("04 \n » rep 1 day", $this->getTable("//div[@id='pn-maincontent']/table[2].3.2"));
        $this->assertEquals("06 \n » rep 1 day", $this->getTable("//div[@id='pn-maincontent']/table[2].3.4"));
        $this->assertEquals("09 \n [week] » rep 1 week", $this->getTable("//div[@id='pn-maincontent']/table[2].4.0"));
        $this->assertEquals("10 \n » rep 1 month", $this->getTable("//div[@id='pn-maincontent']/table[2].4.1"));
        $this->assertEquals("16 \n [week] » rep 1 week", $this->getTable("//div[@id='pn-maincontent']/table[2].5.0"));
    }

    public function testMonthViewMar()
    {
        $this->open("index.php?module=TimeIt&year=2009&month=3");
        $this->assertEquals("<< March 2009 >>", $this->getTable("//div[@id='pn-maincontent']/table[2].0.0"));
        $this->assertEquals("02 \n [week] » rep 2 first mo", $this->getTable("//div[@id='pn-maincontent']/table[2].3.0"));
        $this->assertEquals("09 \n [week] » rep 2 secound mo", $this->getTable("//div[@id='pn-maincontent']/table[2].4.0"));
        $this->assertEquals("10 \n » rep 1 month", $this->getTable("//div[@id='pn-maincontent']/table[2].4.1"));
        $this->assertEquals("16 \n [week] » rep 2 third mo", $this->getTable("//div[@id='pn-maincontent']/table[2].5.0"));
        $this->assertEquals("23 \n [week] » rep 2 fourth mo", $this->getTable("//div[@id='pn-maincontent']/table[2].6.0"));
        $this->assertEquals("30 \n [week] » rep 2 last mo", $this->getTable("//div[@id='pn-maincontent']/table[2].7.0"));
    
        $this->open("index.php?module=TimeIt&year=2010&month=2");
        $this->assertEquals("<< February 2010 >>", $this->getTable("//div[@id='pn-maincontent']/table[2].0.0"));
        $this->assertEquals("02 \n » rep 1 year", $this->getTable("//div[@id='pn-maincontent']/table[2].2.1"));
    }

    public function testCreateEvent()
    {
        // login
        $this->login('admin','admin');
        $this->open("index.php?module=TimeIt&func=new");
        $this->assertEquals("Add an Event", $this->getText("//div[@id='pn-maincontent']/div/h2"));

        // create event
        $this->open("index.php?module=TimeIt&func=new");
        $this->type("title", "selenium event");
        $this->type("startDate", "2009-07-01");
        $this->click("allDay2");
        $this->select("allDayStart_h", "label=8");
        $this->type("allDayDur", "2");
        $this->type("text", "my selenium event");
        $this->click("link=show/hide");
        $this->click("repeat1");
        $this->type("repeatFrec", "1");
        $this->type("endDate", "2009-07-06");
        $this->click("link=show/hide");
        $this->click("mybtn_create_create");
        $this->waitForPageToLoad("30000");

        // check creation
        $this->select("day", "label=01");
        $this->select("month", "label=07");
        $this->select("year", "label=2009");
        $this->click("//input[@name='submit' and @value='jump']");
        $this->waitForPageToLoad("30000");
        $this->assertEquals("01 + \n » 8:00 selenium event", $this->getTable("//div[@id='pn-maincontent']/table[2].2.2"));
        $this->assertEquals("02 + \n » 8:00 selenium event", $this->getTable("//div[@id='pn-maincontent']/table[2].2.3"));
        $this->assertEquals("03 + \n » 8:00 selenium event", $this->getTable("//div[@id='pn-maincontent']/table[2].2.4"));
        $this->assertEquals("04 + \n » 8:00 selenium event", $this->getTable("//div[@id='pn-maincontent']/table[2].2.5"));
        $this->assertEquals("05 + \n » 8:00 selenium event", $this->getTable("//div[@id='pn-maincontent']/table[2].2.6"));
        $this->assertEquals("06 + \n [week] » 8:00 selenium event", $this->getTable("//div[@id='pn-maincontent']/table[2].3.0"));
    }

    public function testDetailView()
    {
        $this->open("index.php?module=TimeIt&func=event&id=7&date=2009-02-02");
        $this->assertEquals("Test Calendar", $this->getText("//div[@id='pn-maincontent']/h2"));
        $this->assertEquals("rep 1 day", $this->getText("//div[@id='pn-maincontent']/div[2]/table/tbody/tr[1]/td/span[1]/strong/span"));
        $this->assertEquals("Repeat: every 2 days", $this->getText("//div[@id='pn-maincontent']/div[2]/table/tbody/tr[1]/td/div[2]"));
        $this->assertEquals("rep day event", $this->getText("//div[@id='pn-maincontent']/div[2]/table/tbody/tr[2]/td[1]/fieldset/div"));
        $this->assertEquals("global Event", $this->getText("//div[@id='pn-maincontent']/div[2]/table/tbody/tr[2]/td[2]/fieldset[1]/strong[1]"));
        $this->assertEquals("Feb 02, 2009", $this->getText("//div[@id='pn-maincontent']/div[2]/table/tbody/tr[2]/td[2]/fieldset[1]/abbr[1]"));
        $this->assertEquals("Feb 06, 2009", $this->getText("//div[@id='pn-maincontent']/div[2]/table/tbody/tr[2]/td[2]/fieldset[1]/abbr[2]"));
    }


    function testChangeRecurrence()
    {
         // login
        $this->login('admin','admin');
        $this->open("index.php?module=TimeIt&year=2009&month=2");
        $this->click("link=rep 1 day");
        $this->waitForPageToLoad("30000");
        $this->click("//img[@alt='Edit']");
        $this->waitForPageToLoad("30000");
        $this->click("link=show/hide");
        $this->type("repeatFrec", "1");
        $this->click("link=show/hide");
        $this->click("mybtn_create_create");
        $this->waitForPageToLoad("30000");
        $this->open("index.php?module=TimeIt&year=2009&month=2");
        $this->assertEquals("02 + \n [week] » rep 1 day\n » rep 1 week\n » rep 1 year", $this->getTable("//div[@id='pn-maincontent']/table[2].3.0"));
        $this->assertEquals("03 + \n » rep 1 day", $this->getTable("//div[@id='pn-maincontent']/table[2].3.1"));
        $this->assertEquals("04 + \n » rep 1 day", $this->getTable("//div[@id='pn-maincontent']/table[2].3.2"));
        $this->assertEquals("05 + \n » rep 1 day", $this->getTable("//div[@id='pn-maincontent']/table[2].3.3"));
        $this->assertEquals("06 + \n » rep 1 day", $this->getTable("//div[@id='pn-maincontent']/table[2].3.4"));
    }
}
