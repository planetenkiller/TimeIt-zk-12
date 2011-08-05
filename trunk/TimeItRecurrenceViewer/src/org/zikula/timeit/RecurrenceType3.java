package org.zikula.timeit;

import java.util.ArrayList;

/**
 *
 * @author Planetenkiller
 */
public class RecurrenceType3 implements RecurrenceType
{
    private String spec;
    private String frec;
    private SimpleDate startDate;
    private SimpleDate endDate;
    
    public RecurrenceType3(String spec, String frec, SimpleDate startDate, SimpleDate endDate)
    {
        this.spec = spec;
        this.frec = frec;
        this.startDate = startDate;
        this.endDate = endDate;
    }

    public ArrayList<SimpleDate> getDates(SimpleDate start, SimpleDate end)
    {
        ArrayList<SimpleDate> dates = new ArrayList<SimpleDate>();
        
        dates.add(startDate);
        String[] datesSplitt = spec.split(",");
        
        for(String s : datesSplitt)
        {
            dates.add(SimpleDate.fromString(s));
        }
        
        return dates;
    }
}