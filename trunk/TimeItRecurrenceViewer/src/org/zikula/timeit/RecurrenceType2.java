package org.zikula.timeit;

import java.util.ArrayList;
import java.util.Calendar;

/**
 *
 * @author Planetenkiller
 */
public class RecurrenceType2 implements RecurrenceType
{
    private String spec;
    private String frec;
    private SimpleDate startDate;
    private SimpleDate endDate;
    
    public RecurrenceType2(String spec, String frec, SimpleDate startDate, SimpleDate endDate)
    {
        this.spec = spec;
        this.frec = frec;
        this.startDate = startDate;
        this.endDate = endDate;
    }

    public ArrayList<SimpleDate> getDates(SimpleDate start, SimpleDate end)
    {
        ArrayList<SimpleDate> dates = new ArrayList<SimpleDate>();
        int weekdayMap[] = new int[] {
         Calendar.SUNDAY,Calendar.MONDAY,Calendar.TUESDAY,Calendar.WEDNESDAY,Calendar.THURSDAY,Calendar.FRIDAY,Calendar.SATURDAY
        };

        String[] specSpitt = this.spec.split(" ");

        String[] days = specSpitt[0].split(",");
        for(String spec : days)
        {
            int a = Integer.parseInt(spec);
            int b = weekdayMap[Integer.parseInt(specSpitt[1])];

            if(a == 5)
            {
                a = -1;
            }

            Calendar date = start.toCalendar();
            date.set(Calendar.DAY_OF_WEEK, b);
            date.set(Calendar.DAY_OF_WEEK_IN_MONTH, a);

            while(date.compareTo(end.toCalendar()) <= 0 && date.compareTo(endDate.toCalendar()) <= 0)
            {
                if(date.compareTo(start.toCalendar()) >= 0 && date.compareTo(startDate.toCalendar()) >= 0)
                {
                    dates.add(SimpleDate.fromCalendar(date));
                }

                
                date.add(Calendar.MONTH, 1);
                date.set(Calendar.DAY_OF_WEEK, b);
                date.set(Calendar.DAY_OF_WEEK_IN_MONTH, a);
            }
        }
         
         
         
         return dates;
    }
}