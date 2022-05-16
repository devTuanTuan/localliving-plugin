/* Czech initialisation for the timepicker plugin */
/* Written by David Spohr (spohr.david at gmail). */
jQuery(function($){
    jQuery.timepicker.regional['cs'] = {
                hourText: 'Hodiny',
                minuteText: 'Minuty',
                amPmText: ['AM', 'PM'] ,
                closeButtonText: 'Zavřít',
                nowButtonText: 'Nyní',
                deselectButtonText: 'Odoznačit' }
    jQuery.timepicker.setDefaults(jQuery.timepicker.regional['cs']);
});