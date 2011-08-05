package org.zikula.timeit;

import java.util.Calendar;
import java.util.Date;
import java.util.Locale;
import java.util.TimeZone;

/**
 *
 * @author Planetenkiller
 */
public class SimpleDate 
{
    private int year;
    private int month;
    private int day;
    private int week;

    
    public SimpleDate(int year, int month, int day)
    {
        this.year = year;
        this.month = month;
        this.day = day;
        week = toCalendar().get(Calendar.WEEK_OF_YEAR);
    }
    
    public int getDay()
    {
        return day;
    }

    public int getMonth()
    {
        return month;
    }

    public int getYear()
    {
        return year;
    }

    public int getWeek()
    {
        return week;
    }

    @Override
    public boolean equals(Object a)
    {
        if(a instanceof SimpleDate)
        {
            SimpleDate b = (SimpleDate)a;
            if(year == b.year && month == b.month && day == b.day)
            {
                return true;
            }
        } 
            
        return false;
    }
    
    
    
    public Calendar toCalendar()
    {
        Calendar start = Calendar.getInstance();
        start.set( year, month-1, day, 0, 0, 0 );
        start.set(Calendar.MILLISECOND, 0);
        
        return start;
    }
    
    public Calendar toCalendar2()
    {
        Calendar start = Calendar.getInstance();
        start.set( year, month-1, day);
        
        return start;
    }
    
    public Date toDate()
    {
        return toCalendar2().getTime();
    }

    @Override
    public String toString()
    {
        return year+"-"+((month<10)?"0"+month:month)+"-"+((day<10)?"0"+day:day);
    }
    
    
    public static SimpleDate fromCalendar(Calendar cal)
    {
        return new SimpleDate(cal.get(Calendar.YEAR), cal.get(Calendar.MONTH)+1, cal.get(Calendar.DAY_OF_MONTH));
    }
    
    public static SimpleDate fromDate(Date d)
    {
        Calendar cal = Calendar.getInstance();
        cal.setTime(d);
        return fromCalendar(cal);
    }
    
    /**
     * Converts a string to a SimpleDate
     * @param d format: yyyy-mm-dd
     * @return object of class SimpleDate
     */
    public static SimpleDate fromString(String d)
    {
        String[] splitt = d.split("-");
        
        return new SimpleDate(Integer.parseInt(splitt[0]), Integer.parseInt(splitt[1]), Integer.parseInt(splitt[2]));
    }
}
