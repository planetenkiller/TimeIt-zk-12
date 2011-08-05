package org.zikula.timeit;

import java.util.ArrayList;
import java.util.Calendar;
import java.util.TimeZone;

/**
 *
 * @author Planetenkiller
 */
public class RecurrenceType1 implements RecurrenceType
{
    private String spec;
    private String frec;
    private SimpleDate startDate;
    private SimpleDate endDate;
    
    public RecurrenceType1(String spec, String frec, SimpleDate startDate, SimpleDate endDate)
    {
        this.spec = spec;
        this.frec = frec;
        this.startDate = startDate;
        this.endDate = endDate;
    }
    
    public ArrayList<SimpleDate> getDates(SimpleDate start, SimpleDate end)
    {
        ArrayList<SimpleDate> dates = new ArrayList<SimpleDate>();
        int days = (int)Main.diffInDays(startDate, start);
        
        
        // repeat daily
        if(spec.equalsIgnoreCase("day"))
        {
            int repeats = days / Integer.parseInt(frec);
            repeats--;
            
            int daysToLastUnusedRepeat;
            if(repeats < 0)
            {
                daysToLastUnusedRepeat = Integer.parseInt(frec) * -1;
            } else 
            {
                daysToLastUnusedRepeat = repeats * Integer.parseInt(frec);
            }
            
            Calendar startT = start.toCalendar();
            Calendar endT = end.toCalendar();
            
            int counter = Integer.parseInt(frec);
            System.out.println("daysToLastUnusedRepeat:"+daysToLastUnusedRepeat);
            System.out.println("repeats:"+repeats);
            System.out.println("days:"+days);
            while(true)
            {
                Calendar date = start.toCalendar();
                date.add(Calendar.DAY_OF_MONTH, daysToLastUnusedRepeat+counter);
                counter += Integer.parseInt(frec);
                
                System.out.println("while:"+date);
                
                if(date.after(endDate.toCalendar()) || date.after(endT))
                {
                    break;
                }
                
                dates.add(SimpleDate.fromCalendar(date));
            }
        } else if(spec.equalsIgnoreCase("week")) // repeat weekly
        {
            int weeks = start.getWeek() - startDate.getWeek();
            weeks = weeks / Integer.parseInt(frec);
            if(weeks < 0) weeks = 0;
            
             Calendar date = Calendar.getInstance(TimeZone.getTimeZone("GMT"));
             date.set( start.getYear(), start.getMonth()-1, start.getDay(), 0, 0, 0 );
             date.set(Calendar.WEEK_OF_YEAR, date.get(Calendar.WEEK_OF_YEAR)+weeks*Integer.parseInt(frec));
             
             while(date.compareTo(end.toCalendar()) <= 0 && date.compareTo(endDate.toCalendar()) <= 0)
             {
                 if(date.compareTo(start.toCalendar()) >= 0 && date.compareTo(startDate.toCalendar()) >= 0)
                 {
                     dates.add(SimpleDate.fromCalendar(date));
                     System.out.println("while:"+date);
                 }
                 
                 date.add(Calendar.WEEK_OF_YEAR, Integer.parseInt(frec));
             }
        } else if(spec.equalsIgnoreCase("month")) // repeat monthly
        {
            int years = start.getWeek() - startDate.getYear();
            int months = years * 12;
            int monthsTemp = start.getMonth() - startDate.getMonth();
            months += monthsTemp;
            months = months / Integer.parseInt(frec);
            
            Calendar date = Calendar.getInstance(TimeZone.getTimeZone("GMT"));
            date.set( start.getYear(), start.getMonth()-1, start.getDay(), 0, 0, 0 );
            date.set(Calendar.MONTH, date.get(Calendar.MONTH)+months*Integer.parseInt(frec));
            while(date.compareTo(end.toCalendar()) <= 0 && date.compareTo(endDate.toCalendar()) <= 0)
            {
                if(date.compareTo(start.toCalendar()) >= 0 && date.compareTo(startDate.toCalendar()) >= 0)
                {
                    dates.add(SimpleDate.fromCalendar(date));
                    System.out.println("while:"+date);
                }
                
                date.add(Calendar.MONTH, Integer.parseInt(frec));
            }
        } else if(spec.equalsIgnoreCase("year")) // repeat yearly
        {
            int years = start.getYear() - startDate.getYear();
            years = years / Integer.parseInt(frec);
            
            Calendar date = start.toCalendar();
            date.set(Calendar.YEAR, date.get(Calendar.YEAR)+years*Integer.parseInt(frec));
            while(date.compareTo(end.toCalendar()) <= 0 && date.compareTo(endDate.toCalendar()) <= 0)
            {
                if(date.compareTo(start.toCalendar()) >= 0 && date.compareTo(startDate.toCalendar()) >= 0)
                {
                    dates.add(SimpleDate.fromCalendar(date));
                    
                }
                
                date.add(Calendar.YEAR, Integer.parseInt(frec));
            }
        }
        
        return dates;
    }
}
