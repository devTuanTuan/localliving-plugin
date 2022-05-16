/* Italian initialisation for the jQuery time picker plugin. */
/* Written by Serge Margarita (serge.margarita@gmail.com) */
jQuery(function($){
    jQuery.timepicker.regional['it'] = {
                hourText: 'Ore',
                minuteText: 'Minuti',
                amPmText: ['AM', 'PM'],
                closeButtonText: 'Chiudi',
                nowButtonText: 'Adesso',
                deselectButtonText: 'Svuota' }
    jQuery.timepicker.setDefaults(jQuery.timepicker.regional['it']);
});