package org.zikula.timeit;

import java.net.MalformedURLException;
import java.net.URL;
import java.util.ArrayList;
import java.util.Calendar;
import java.util.Date;
import javax.swing.JButton;

/**
 *
 * @author Planetenkiller
 */
public class Main extends javax.swing.JApplet 
{
    private RecurrenceType t = null;
    private ArrayList<SimpleDate> dates;
   
    /** 
     * Initializes the applet Main 
     */
    public void init() 
    {
        try {
            java.awt.EventQueue.invokeAndWait(new Runnable() {
                public void run() {
                    initComponents();
                }
            });
        } catch (Exception ex) {
            ex.printStackTrace();
        }
        
        dates = new ArrayList<SimpleDate>();
        indicator.setVisible(false);
        
        popup_add.setVisible(false);
        
        MyDayChooser mdc = (MyDayChooser)monthView.getDayChooser();
        mdc.setDayPopup(popup);
    }

    @Override
    public void start()
    {
        super.start();
        this.repaint();
    }
    
    
    
    public void setRecurrence(int type, String spec, String frec, String startDate, String endDate)
    {
        indicator.setVisible(true);
        System.out.println("setRecurrence("+type+","+spec+","+frec+","+startDate+","+endDate);
        SimpleDate start = SimpleDate.fromString(startDate);
        SimpleDate end = SimpleDate.fromString(endDate);
        
        System.out.println(start);
        System.out.println(end);
        if(type == 1)
        {
            t = new RecurrenceType1(spec, frec, start, end);
        } else if(type == 2)
        {
            t = new RecurrenceType2(spec, frec, start, end);
        } else if(type == 3)
        {
            t = new RecurrenceType3(spec, frec, start, end);
        }
        
        System.out.println("----------");
        dates = t.getDates(start, end);
        System.out.println("----------");
        
        
        MyDayChooser mdc = (MyDayChooser)monthView.getDayChooser();
        mdc.clearFlaggedDates();
        
        for(SimpleDate d : dates)
        {
            mdc.addFlaggedDate(d);
            System.out.println(d);
        }
        System.out.println("----------");
        indicator.setVisible(false);
    }
    
    public static long diffInDays(SimpleDate a, SimpleDate b)
    {
        Calendar cal_1 = a.toCalendar();
        Calendar cal_2 = b.toCalendar();
        
        long time = cal_2.getTime().getTime() - cal_1.getTime().getTime();  // Diff in ms
        
        return Math.round( (double)time / (24. * 60.*60.*1000.) );  
    }

    /** This method is called from within the init() method to
     * initialize the form.
     * WARNING: Do NOT modify this code. The content of this method is
     * always regenerated by the Form Editor.
     */
    @SuppressWarnings("unchecked")
    // <editor-fold defaultstate="collapsed" desc="Generated Code">//GEN-BEGIN:initComponents
    private void initComponents() {
        java.awt.GridBagConstraints gridBagConstraints;

        popup = new javax.swing.JPopupMenu();
        popup_ignore = new javax.swing.JMenuItem();
        popup_add = new javax.swing.JMenuItem();
        monthView = new com.toedter.calendar.JCalendar();
        indicator = new javax.swing.JLabel();
        jButton1 = new javax.swing.JButton();

        java.util.ResourceBundle bundle = java.util.ResourceBundle.getBundle("org/zikula/timeit/texts"); // NOI18N
        popup_ignore.setText(bundle.getString("popup_ignore")); // NOI18N
        popup_ignore.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                popup_ignoreActionPerformed(evt);
            }
        });
        popup.add(popup_ignore);

        popup_add.setText(bundle.getString("popup_add")); // NOI18N
        popup_add.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                popup_addActionPerformed(evt);
            }
        });
        popup.add(popup_add);

        getContentPane().setLayout(new java.awt.GridBagLayout());
        gridBagConstraints = new java.awt.GridBagConstraints();
        gridBagConstraints.gridx = 0;
        gridBagConstraints.gridy = 1;
        getContentPane().add(monthView, gridBagConstraints);

        indicator.setIcon(new javax.swing.ImageIcon(getClass().getResource("/org/zikula/timeit/indicator.white.gif"))); // NOI18N
        getContentPane().add(indicator, new java.awt.GridBagConstraints());

        jButton1.setText(bundle.getString("btn_today")); // NOI18N
        jButton1.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                jButton1ActionPerformed(evt);
            }
        });
        gridBagConstraints = new java.awt.GridBagConstraints();
        gridBagConstraints.gridx = 0;
        gridBagConstraints.gridy = 2;
        getContentPane().add(jButton1, gridBagConstraints);
    }// </editor-fold>//GEN-END:initComponents

private void jButton1ActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_jButton1ActionPerformed
    monthView.setDate(new Date());
}//GEN-LAST:event_jButton1ActionPerformed

private void popup_ignoreActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_popup_ignoreActionPerformed
    JButton btn = (JButton)popup.getInvoker();
    
    Date date = monthView.getDate();
    Calendar cal = Calendar.getInstance();
    cal.setTime(date);
    cal.set(Calendar.DAY_OF_MONTH, Integer.parseInt(btn.getText()));
    
    SimpleDate d = SimpleDate.fromCalendar(cal);
    System.out.println("ignore:"+d);
    
    try 
    {
        getAppletContext().showDocument(new URL("javascript:addIgnoreDate(\""+d.toString()+"\")"));
    } catch (MalformedURLException me) { }

}//GEN-LAST:event_popup_ignoreActionPerformed

private void popup_addActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_popup_addActionPerformed
     JButton btn = (JButton)popup.getInvoker();
    
    Date date = monthView.getDate();
    Calendar cal = Calendar.getInstance();
    cal.setTime(date);
    cal.set(Calendar.DAY_OF_MONTH, Integer.parseInt(btn.getText()));
    
    SimpleDate d = SimpleDate.fromCalendar(cal);
    System.out.println("add:"+d);
}//GEN-LAST:event_popup_addActionPerformed


    // Variables declaration - do not modify//GEN-BEGIN:variables
    private javax.swing.JLabel indicator;
    private javax.swing.JButton jButton1;
    private com.toedter.calendar.JCalendar monthView;
    private javax.swing.JPopupMenu popup;
    private javax.swing.JMenuItem popup_add;
    private javax.swing.JMenuItem popup_ignore;
    // End of variables declaration//GEN-END:variables

}
