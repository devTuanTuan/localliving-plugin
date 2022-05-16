/* Nederlands initialisation for the timepicker plugin */
/* Written by Lowie Hulzinga. */
jQuery(function($){
    jQuery.timepicker.regional['nl'] = {
                hourText: 'Uren',
                minuteText: 'Minuten',
                amPmText: ['AM', 'PM'],
				closeButtonText: 'Sluiten',
				nowButtonText: 'Actuele tijd',
				deselectButtonText: 'Wissen' }
    jQuery.timepicker.setDefaults(jQuery.timepicker.regional['nl']);
});