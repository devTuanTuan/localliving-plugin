/* Brazilan initialisation for the timepicker plugin */
/* Written by Daniel Almeida (quantodaniel@gmail.com). */
jQuery(function($){
    jQuery.timepicker.regional['pt-BR'] = {
                hourText: 'Hora',
                minuteText: 'Minuto',
                amPmText: ['AM', 'PM'],
                closeButtonText: 'Fechar',
                nowButtonText: 'Agora',
                deselectButtonText: 'Limpar' }
    jQuery.timepicker.setDefaults(jQuery.timepicker.regional['pt-BR']);
});