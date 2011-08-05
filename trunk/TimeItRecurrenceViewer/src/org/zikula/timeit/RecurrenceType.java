package org.zikula.timeit;

import java.util.ArrayList;
import java.util.Date;

/**
 *
 * @author Planetenkiler
 */
public interface RecurrenceType 
{
    public ArrayList<SimpleDate> getDates(SimpleDate start, SimpleDate end);
}
