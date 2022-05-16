/* Croatian/Bosnian initialisation for the timepicker plugin */
/* Written by Rene Brakus (rene.brakus@infobip.com). */
jQuery(function($){
    jQuery.timepicker.regional['hr'] = {
                hourText: 'Sat',
                minuteText: 'Minuta',
                amPmText: ['Prijepodne', 'Poslijepodne'],
                closeButtonText: 'Zatvoriti',
                nowButtonText: 'Sada',
                deselectButtonText: 'Poništite'}

    jQuery.timepicker.setDefaults(jQuery.timepicker.regional['hr']);
});