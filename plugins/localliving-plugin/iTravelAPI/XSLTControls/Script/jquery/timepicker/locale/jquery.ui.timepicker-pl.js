/* Polish initialisation for the timepicker plugin */
/* Written by Mateusz Wadolkowski (mw@pcdoctor.pl). */
jQuery(function($){
    jQuery.timepicker.regional['pl'] = {
                hourText: 'Godziny',
                minuteText: 'Minuty',
                amPmText: ['', ''],
				closeButtonText: 'Zamknij',
                nowButtonText: 'Teraz',
                deselectButtonText: 'Odznacz'}
    jQuery.timepicker.setDefaults(jQuery.timepicker.regional['pl']);
});