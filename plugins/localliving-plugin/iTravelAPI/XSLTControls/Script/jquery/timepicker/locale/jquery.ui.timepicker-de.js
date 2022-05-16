/* German initialisation for the timepicker plugin */
/* Written by Lowie Hulzinga. */
jQuery(function($){
    jQuery.timepicker.regional['de'] = {
                hourText: 'Stunde',
                minuteText: 'Minuten',
                amPmText: ['AM', 'PM'] ,
                closeButtonText: 'Beenden',
                nowButtonText: 'Aktuelle Zeit',
                deselectButtonText: 'Wischen' }
    jQuery.timepicker.setDefaults(jQuery.timepicker.regional['de']);
});
