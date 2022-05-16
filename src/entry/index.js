import './index.scss';
import './filter.scss';
import './search-result.scss';
import './cart.scss';
import './tilbud.scss';
import './ferieboliger.scss';
import './multi-select';
var $ = jQuery.noConflict();

//loader
window.addEventListener('DOMContentLoaded', function (event) {
  setTimeout(() => {
    document.querySelector('.loading-first-wrapper').classList.add('hided');
  }, 500);
});

$(document).ready(function () {
  // START Bootstrap datepicker
  // End date
  let today = new Date();
  let endDateInput = $('#end-date-input')
    .datepicker({
      format: 'D. dd M',
      weekStart: 6,
      maxViewMode: 2,
      calendarWeeks: true,
      todayHighlight: true,
      language: 'da',
      startDate: today,
      container: '.date-range',
      daysOfWeekHighlighted: [6],
    })
    .on('changeDate', function (e) {
      if (e.date.getTime() !== moment($('#dateTo').val(), 'DD/MM/YYYY').valueOf()) {
        $('#dateTo').val(e.format(0, 'dd/mm/yyyy'));
      }
      $(this).datepicker('hide');
    })
    .on('show', function () {
      $('.datepicker > .datepicker-days').find('th.cw').html('Uge');
    });
  endDateInput.on('show', function (e) {
    if (!e.currentTarget.classList.contains('active')) {
      e.currentTarget.classList.add('active');
    }
  });
  endDateInput.on('hide', function (e) {
    if (e.currentTarget.classList.contains('active')) {
      e.currentTarget.classList.remove('active');
    }
  });
  endDateInput.keydown(function () {
    return false;
  });
  $('#end-date-input').datepicker('setDate', moment($('#dateTo').val(), 'DD/MM/YYYY').toDate());
  // Start date
  let startDateInput = $('#start-date-input')
    .datepicker({
      format: 'D. dd M',
      weekStart: 6,
      maxViewMode: 2,
      calendarWeeks: true,
      todayHighlight: true,
      language: 'da',
      startDate: today,
      container: '.date-range',
      daysOfWeekHighlighted: [6],
    })
    .on('changeDate', function (e) {
      if (moment($('#dateFrom').val(), 'DD/MM/YYYY').valueOf() != e.date.getTime()) {
        let nextWeek = e.date.setDate(e.date.getDate() + 7);
        endDateInput.datepicker('setDate', new Date(nextWeek));
      }
      $('#dateFrom').val(e.format(0, 'dd/mm/yyyy'));
      $(this).datepicker('hide');
    })
    .on('show', function () {
      $('.datepicker > .datepicker-days').find('th.cw').html('Uge');
    });
  startDateInput.on('show', function (e) {
    if (!e.currentTarget.classList.contains('active')) {
      e.currentTarget.classList.add('active');
    }
  });
  startDateInput.on('hide', function (e) {
    if (e.currentTarget.classList.contains('active')) {
      e.currentTarget.classList.remove('active');
    }
  });
  startDateInput.keydown(function () {
    return false;
  });
  $('#start-date-input').datepicker('setDate', moment($('#dateFrom').val(), 'DD/MM/YYYY').toDate());
  // END Bootstrap datepicker
  $('.dropdown a').click(function () {
    $(this).next('.dropdown-menu').slideToggle();
    $(this).parent().toggleClass('show');
  });

  Element.prototype.hasClass = function (className) {
    return this.className && new RegExp('(^|\\s)' + className + '(\\s|$)').test(this.className);
  };

  //Multiselect
  $('#region-id-select').multiSelect({
    noneText: 'Hvor skal de hen?',
    buttonHTML: '<span class="region-id multi-select-button">',
  });
  $('#category-id-select').multiSelect({
    noneText: 'Type',
    buttonHTML: '<span class="category-id multi-select-button">',
  });
  $('#distance-from-region-id-select').multiSelect({
    noneText: 'Vælg destination',
    buttonHTML: '<span class="distance-from-region-id multi-select-button">',
  });
  $('#region-id-select + div input').on('click', function () {
    $('#distance-from-region-id-select + div input[value="' + $(this).val() + '"]').prop(
      'checked',
      $(this).is(':checked')
    );
    $('#distance-from-region-id-select option[value="' + $(this).val() + '"]').prop(
      'selected',
      $(this).is(':checked')
    );

    updateButtonContentsFromSelectorToSelector(
      '#region-id-select',
      '#distance-from-region-id-select'
    );
  });
  $('#distance-from-region-id-select + div input').on('click', function () {
    $('#region-id-select + div input[value="' + $(this).val() + '"]').prop(
      'checked',
      $(this).is(':checked')
    );
    $('#region-id-select option[value="' + $(this).val() + '"]').prop(
      'selected',
      $(this).is(':checked')
    );

    updateButtonContentsFromSelectorToSelector(
      '#distance-from-region-id-select',
      '#region-id-select'
    );
  });
  function updateButtonContentsFromSelectorToSelector(fromSelector, toSelector) {
    var selected = [];

    $(fromSelector + ' + .multi-select-container')
      .find('input')
      .each(function () {
        var text = /** @type string */ ($(this).parent().text());

        if ($(this).is(':checked')) {
          selected.push($.trim(text));
        }
      });

    var button = $(toSelector + ' + div .multi-select-button');

    if (selected.length === 0) {
      button.text('-- Select --');
    } else {
      button.text(selected.join(', '));
    }
  }

  //Pagination submit
  $('.pagination-list li a').click(function (e) {
    e.preventDefault();

    $('form[name="localliving-form"]').submit();
  });
  $(
    'form[name="localliving-form"], form[name="offer-list-form"],form[name="ferieboliger-search-form"], form[name="offer-list-form"], form[name="generate-pdf-form"]'
  ).submit(function () {
    $('.loading-first-wrapper').removeClass('hided');
    $('.loading-first-wrapper').addClass('loading');
  });
  //Hide & Shop unit description
  $('.unit-description').hide();
  $('.show-hide-unit-description').click(function () {
    $(this).parent().find('.unit-description').toggle('slide');
  });

  //Persons select
  $('#rooms-number-select').select2({
    width: '100%',
    minimumResultsForSearch: -1,
    dropdownParent: $('.rooms-selector'),
  });
  $('#rooms-number-select').on('select2:select', function (e) {
    let data = e.params.data;
    let roomsLabelSelector = $(e.currentTarget).parent().find('.rooms-label');
    if (data.id == '0') {
      roomsLabelSelector.removeClass('show');
    } else {
      if (!roomsLabelSelector.hasClass('show')) {
        roomsLabelSelector.addClass('show');
      }
    }
  });
  $('.persons').click(function (e) {
    $('.persons-selector').show();
    if ($(this).parent().find('.persons-backdrop').length == 0) {
      $(this).parent().append('<div class="persons-backdrop"></div>');
    }
  });
  $(document).on('click', '.persons-backdrop', function (e) {
    $(this).remove();
    $('.persons-selector').hide();
  });
  $('.persons-selector .quantity-remove').click(function () {
    let inputValue = parseInt($(this).next('input').val());
    if (inputValue != 1) {
      $(this)
        .next('input')
        .val(inputValue - 1);
      $('.' + $(this).next('input').attr('name') + ' .value').html(inputValue - 1);
    }
  });
  $('.persons-selector .quantity-add').click(function () {
    let inputValue = parseInt($(this).prev('input').val());
    $(this)
      .prev('input')
      .val(inputValue + 1);
    $('.' + $(this).prev('input').attr('name') + ' .value').html(inputValue + 1);
  });
  $('.persons-selector input').on('change', function () {
    $('.' + $(this).attr('name') + ' .value').html($(this).val());
  });

  //view options select
  $(document).on('change', '.view-options-input', function () {
    var checked = $(this).attr('checked');
    var checkname = $(this).attr('name');

    if (checked === undefined) {
      $("input.view-options-input[type=checkbox]:not([name='" + checkname + "'])").prop(
        'checked',
        false
      );
      this.checked = true;
      $("input.view-options-hidden-input[type=hidden]:not([name='" + checkname + "'])").val('0');
      $("input.view-options-hidden-input[type=hidden][name='" + checkname + "']").val('1');
    } else {
      this.checked = false;

      $("input.view-options-hidden-input[type=hidden][name='" + checkname + "']").val('0');
    }

    $('#search-action-submit').click();
  });

  //price-stars sort
  $(document).on('click', '#sort-by-price', function () {
    var sortByStarsInput = $('input[id="sortByStars"]');
    var sortByPriceInput = $('input[id="sortByPrice"]');

    sortByStarsInput.val('');

    if (sortByPriceInput.val() === 'asc') {
      sortByPriceInput.val('desc');
    } else {
      sortByPriceInput.val('');
    }

    $('button[id="search-action-submit"]').click();
  });

  $(document).on('click', '#sort-by-stars', function () {
    var sortByStarsInput = $('input[id="sortByStars"]');
    var sortByPriceInput = $('input[id="sortByPrice"]');

    sortByPriceInput.val('');

    if (sortByStarsInput.val() === 'desc') {
      sortByStarsInput.val('asc');
    } else {
      sortByStarsInput.val('desc');
    }

    $('button[id="search-action-submit"]').click();
  });

  $('#offer-status-select').select2({
    width: '100%',
    minimumResultsForSearch: -1,
    dropdownParent: $('.offer-status'),
  });
  $('.edit-offer-status-select').each(function () {
    var $this = $(this);
    $this.select2({
      width: '100%',
      minimumResultsForSearch: -1,
      dropdownParent: $(this).parent('.offer-status-column'),
    });
  });
  $('#offer-status-select, #ferieboliger-status-select').change(function () {
    $(this).parent().attr('data-color', $(this).val());
  });
  $('#ferieboliger-status-select').select2({
    width: '100%',
    minimumResultsForSearch: -1,
    dropdownParent: $('.ferieboliger-status'),
  });

  //show start-end date as show text
  var showTextStartDate = $('#date-range-show-text .start-date');
  var showTextEndDate = $('#date-range-show-text .end-date');
  var showTextDiffRange = $('#date-range-show-text .diff-range');

  if (showTextStartDate.length !== 0 && showTextEndDate.length !== 0) {
    var momentStartDate = moment(showTextStartDate.text());
    var momentEndDate = moment(showTextEndDate.text());

    var diffValue = momentEndDate.diff(momentStartDate, 'days');

    showTextDiffRange.text('');
    showTextDiffRange.text(diffValue + ' dage.');
    var startDateText = momentStartDate.format('ddd DD MMM YYYY');
    showTextStartDate.text('');
    showTextStartDate.text(startDateText);
    var endDateText = momentEndDate.format('ddd DD MMM YYYY');
    showTextEndDate.text('');
    showTextEndDate.text(endDateText);
  }

  $('.toggle-input').bootstrapToggle();
});
