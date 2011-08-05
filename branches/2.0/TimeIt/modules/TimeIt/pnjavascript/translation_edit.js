
function timeitTraslationEditInit()
{
    $$('fieldset.timeit_translate_lang').each(function(n) {
            var legend = n.select('legend');
            legend = legend[0];
            var content = n.select('div');
            content = content[0];
            content.hide();
            Event.observe(legend, 'click',function(event) {
                                            content.toggle()
                                         }, false);
        });

     
}
