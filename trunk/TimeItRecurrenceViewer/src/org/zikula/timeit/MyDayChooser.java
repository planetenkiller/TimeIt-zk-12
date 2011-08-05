package org.zikula.timeit;

import com.toedter.calendar.JDayChooser;
import java.awt.Color;
import java.awt.Font;
import java.awt.event.MouseAdapter;
import java.awt.event.MouseEvent;
import java.util.ArrayList;
import java.util.Calendar;
import javax.swing.JButton;
import javax.swing.JPopupMenu;

/**
 * This class extends the default day chooser with flagged days and a popup menu for the days.
 * @author Planetenkiller
 */
public class MyDayChooser extends JDayChooser
{
    private ArrayList<SimpleDate> flaggedDates;
    private Color flaggedDatesColor = Color.BLUE;
    private Font falggedDatesFont;
    private Font defaultDaysFont;
    private JPopupMenu dayPopup;

    public MyDayChooser()
    {
        super();
        flaggedDates = new ArrayList<SimpleDate>();
        
        JButton temp = new JButton();
        defaultDaysFont = days[1].getFont();
        
        falggedDatesFont = new Font(defaultDaysFont.getFamily(), Font.BOLD, defaultDaysFont.getSize());
        initPopup();
    }

    public MyDayChooser(boolean weekOfYearVisible)
    {
        super(weekOfYearVisible);
        flaggedDates = new ArrayList<SimpleDate>();
        
        JButton temp = new JButton();
        defaultDaysFont = days[1].getFont();
        
        falggedDatesFont = new Font(defaultDaysFont.getFamily(), Font.BOLD, defaultDaysFont.getSize());
        initPopup();
    }

    public JPopupMenu getDayPopup()
    {
        return dayPopup;
    }

    public void setDayPopup(JPopupMenu dayPopup)
    {
        this.dayPopup = dayPopup;
    }
    
    private void initPopup()
    {
        for (int i = 7; i < 49; i++) 
        {
            // has this button got text?
            if(days[i].getText().length() > 0)
            {
                days[i].addMouseListener(new DayMouseListener(days[i]));
            }
        }
    }
    
    public void addFlaggedDate(SimpleDate d)
    {
        if(!flaggedDates.contains(d))
        {
            this.flaggedDates.add(d);
            drawDays();
        }
    }

    public ArrayList<SimpleDate> getFlaggedDates()
    {
        return flaggedDates;
    }
    
    public void clearFlaggedDates()
    {
        flaggedDates.clear();
    }

    public Color getFlaggedDatesColor()
    {
        return flaggedDatesColor;
    }

    public void setFlaggedDatesColor(Color flaggedDatesColor)
    {
        this.flaggedDatesColor = flaggedDatesColor;
    }
    
    @Override
    protected void drawFlaggedDay(Calendar tmpCalendar, JButton day)
    {
        day.setFont(defaultDaysFont);

        if(flaggedDates != null && flaggedDates.contains(SimpleDate.fromCalendar(tmpCalendar)))
        {
            day.setForeground(Color.blue);
            day.setFont(falggedDatesFont);
        }
    }
    
    private class DayMouseListener extends MouseAdapter
    {
        private JButton btn;
        
        public DayMouseListener(JButton btn)
        {
            this.btn = btn;
        }

        @Override
        public void mousePressed(MouseEvent e)
        {
            mouseReleased(e);
        }       
        
        @Override
        public void mouseReleased(MouseEvent e)
        {
            if(e.isPopupTrigger() && dayPopup != null)
            {
                dayPopup.show(btn, e.getX(), e.getY());
            }
        }
    }
}
