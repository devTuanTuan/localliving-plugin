//define the irrelevantTranslation
var irrelevantTranslationCountry = jQuery("#irrelevantTranslationCountry").val();
var irrelevantTranslationCategory = jQuery("#irrelevantTranslationCategory").val();
var irrelevantTranslationServiceType = jQuery("#irrelevantTranslationServiceType").val();

//define the language
var languageIDSetting = jQuery("#languageIDSetting").val();
var proxyPath = jQuery("#proxyPath").val();

//define search suppliers
var searchSuppliersSetting = jQuery("#searchSuppliersHiddenField").val();

//define the currency
var currencyIDSetting = jQuery("#currencyIDSetting").val();

/*Reloads accommodation pricelist witn selected period*/
function changeAccommodationPricelist(select, proxyPath) {
	jQuery("[pricelist-loading-panel='1']").show();
	var startDate = new Date(jQuery(select.options[select.selectedIndex]).attr("data-pricelist-period-start"));
	startDate.setTime(startDate.getTime() - 2 * 24 * 3600 * 1000);
	var endDate = new Date();
	endDate.setTime(startDate.getTime() - 1 * 24 * 3600 * 1000);

	var startDateTicks = startDate.getTime() - (startDate.getTimezoneOffset() * 60 * 1000);
	var dateFormat = jQuery("#reservationsTab_filterDateFrom").datepicker("option", "dateFormat");
	var endDateTicks = endDate.getTime() - (endDate.getTimezoneOffset() * 60 * 1000);
	var numberOfChildren = jQuery("#numberOfChildren").val();
	var childrenAges = getChildrenForReservationTab(numberOfChildren);

	jQuery.post(
	proxyPath + "GetAccommodationPricelistTab",
	{
		'startDateTicks': startDateTicks,
		'endDateTicks': endDateTicks,
		'numberOfPersons': jQuery("#numberOfPersons").val(),
		'objectID': jQuery("#objectIDReservationsTab").val(),
		'currencyID': jQuery("#currencyIDReservationsTab").val(),
		'languageID': jQuery("#languageIDReservationsTab").val(),
		'priceType': jQuery("#priceTypeReservationsTab").val(),
		'bookingAddressDisplayURL': jQuery("#bookingAddressDisplayURLHidden").val(),
		'setDirectLink': jQuery("#postaviDirektanLinkHidden").val(),
		'unitActivityStatus': jQuery("#unitActivityStatusHidden").val(),
		'bookingLinkInNewWindow': jQuery("#bookingLinkInNewWindowHidden").val(),
		'childrenAges': childrenAges,
		'objectCode': jQuery("#objectCodeHidden").val(),
		'dateFormat': dateFormat,
		'showChildrenAges': jQuery("#showChildrenAgesHiddenField").val(),
		'cartID': jQuery("#cartIDHiddenField").val(),
		'customerID': jQuery("#customerIDReservationsTab").val(),
		'marketID': jQuery("#marketIDReservationsTab").val(),
		'affiliateID': jQuery("#affiliateIDReservationsTab").val()
	},
	function (data) {
		jQuery('#pricelistContainer').html(jQuery(data).text());
		jQuery("[pricelist-loading-panel='1']").hide();
	}
	);
}
/*Reloads transportation pricelist witn selected period*/
function changeTransportationPricelist(select, proxyPath) {
	jQuery("[transport-pricelist-loading-panel='1']").show();
	var startDate = new Date(jQuery(select.options[select.selectedIndex]).attr("data-pricelist-period-start"));
	startDate.setTime(startDate.getTime() - 2 * 24 * 3600 * 1000);
	var endDate = new Date();
	endDate.setTime(startDate.getTime() - 1 * 24 * 3600 * 1000);

	var startDateTicks = startDate.getTime() - (startDate.getTimezoneOffset() * 60 * 1000);
	var dateFormat = jQuery("#reservationsTab_filterDateFrom").datepicker("option", "dateFormat");
	var endDateTicks = endDate.getTime() - (endDate.getTimezoneOffset() * 60 * 1000);
	var numberOfChildren = jQuery("#numberOfChildren").val();
	var childrenAges = getChildrenForReservationTab(numberOfChildren);


	jQuery.post(
	proxyPath + "GetAccommodationPricelistTab",
	{
		'startDateTicks': startDateTicks,
		'endDateTicks': endDateTicks,
		'numberOfPersons': jQuery("#numberOfPersons").val(),
		'objectID': jQuery("#objectIDReservationsTab").val(),
		'currencyID': jQuery("#currencyIDReservationsTab").val(),
		'languageID': jQuery("#languageIDReservationsTab").val(),
		'priceType': jQuery("#priceTypeReservationsTab").val(),
		'bookingAddressDisplayURL': jQuery("#bookingAddressDisplayURLHidden").val(),
		'setDirectLink': jQuery("#postaviDirektanLinkHidden").val(),
		'unitActivityStatus': jQuery("#unitActivityStatusHidden").val(),
		'bookingLinkInNewWindow': jQuery("#bookingLinkInNewWindowHidden").val(),
		'childrenAges': childrenAges,
		'objectCode': jQuery("#objectCodeHidden").val(),
		'dateFormat': dateFormat,
		'showChildrenAges': jQuery("#showChildrenAgesHiddenField").val(),
		'cartID': jQuery("#cartIDHiddenField").val(),
		'customerID': jQuery("#customerIDReservationsTab").val(),
		'marketID': jQuery("#marketIDReservationsTab").val(),
		'affiliateID': jQuery("#affiliateIDReservationsTab").val()
	},
	function (data) {
		jQuery('#transportPricelistContainer').html(jQuery(data).text());
		jQuery("[transport-pricelist-loading-panel='1']").hide();
	}
	);
}

//	Returns key values from querystring if exists
//	otherwise returns null
function QueryString(url, key) {
	//	Skidam http adresu
	var t = url.split("?")
	//	Skidam hash
	t = t[t.length - 1].split("#")
	t = t[0];
	var result = {}, queryString = t, re = /([^&=]+)=([^&]*)/g, m;
	url = "";
	while (m = re.exec(queryString)) {
		if (m[1] == key) {
			return m[2];
		}
	}
	return null;
}

function initPhotoGallery() {
	try {
		jQuery("a[rel^='prettyPhoto']").prettyPhoto({ animation_speed: 'fast', theme: 'light_rounded', slideshow: 3000, autoplay_slideshow: true, deeplinking: false });
	} catch (e) { }
}

Date.prototype.getWeek = function () {
	var target = new Date(this.valueOf());
	var dayNr = (this.getDay() + 6) % 7;
	target.setDate(target.getDate() - dayNr + 3);
	var firstThursday = target.valueOf();
	target.setMonth(0, 1);
	if (target.getDay() != 4) {
		target.setMonth(0, 1 + ((4 - target.getDay()) + 7) % 7);
	}
	return 1 + Math.ceil((firstThursday - target) / 604800000);
}

// Initialize datepickers
var initDatePickers = function () {
	jQuery('.ll-datepicker').datepicker({
		daysOfWeekHighlighted: [6],
		startDate: new Date(),
		calendarWeeks: true,
		weekStart: 6,
		firstDay: 1,
		autoclose: true,
		language: languageIDSetting || "da",
		calculateWeek: function (a) {
			return a.getWeek();
		},
	}).on('changeDate', function (evt) {
		var dateGroup = jQuery(evt.target).data('dategroup');
		if (!dateGroup)
			return;

		var $datepickersInGroup = jQuery('.ll-datepicker[data-dategroup=' + dateGroup + ']');
		if ($datepickersInGroup.length !== 2)
			return;

		var fromId = $datepickersInGroup.filter('[data-datetype=from]').attr('id');
		var toId = $datepickersInGroup.filter('[data-datetype=to]').attr('id');

		if (fromId && toId) {
			updateFromTo(fromId, toId);
		}
	}).on('show', function () {
		jQuery('.datepicker > .datepicker-days').find('th.cw').html(languageIDSetting == "da" ? 'Uge' : 'Wk').css('font-weight', 'normal');
	});
};

initDatePickers();


// function is called to "control" two datePicker values, from and to.
// if "to" date is earlier than the "from" date, the "to" date is set a week later than the "from" date
function updateFromTo(fromId, toId) {
	var $from = jQuery('#' + fromId);
	var $to = jQuery('#' + toId);

	var fromDate = $from.datepicker('getDate');
	var toDate = $to.datepicker('getDate');

	if (fromDate != null) {
		fromDate.setHours(0, 0, 0, 0);
	}

	if (toDate != null) {
		toDate.setHours(0, 0, 0, 0);
	}

    if (toDate <= fromDate) {
        if (fromDate) {
            $to.datepicker('setDate', new Date(fromDate.getTime() + 7 * 24 * 3600 * 1000))
        }
	}
}

// function Contains, returns true if the array contains the element "element", otherwise returns false.
Array.prototype.contains = function (element) {
	for (var i = 0; i < this.length; i++) {
		if (this[i] == element) {
			return true;
		}
	}
	return false;
}

// function formates a date to a specified format.
// if format is not specified, MM/dd/yyyy is used.
Date.prototype.formatDate = function (format) {
	var date = this;
	if (!format)
		format = "MM/dd/yyyy";

	var month = date.getMonth() + 1;
	var year = date.getFullYear();
	format = format.replace("MM", month.toString().padL(2, "0"));
	if (format.indexOf("yyyy") > -1)
		format = format.replace("yyyy", year.toString());
	else if (format.indexOf("yy") > -1)
		format = format.replace("yy", year.toString().substr(2, 2));
	format = format.replace("dd", date.getDate().toString().padL(2, "0"));
	var hours = date.getHours();
	if (format.indexOf("t") > -1) {
		if (hours > 11)
			format = format.replace("t", "pm")
		else
			format = format.replace("t", "am")
	}
	if (format.indexOf("HH") > -1)
		format = format.replace("HH", hours.toString().padL(2, "0"));
	if (format.indexOf("hh") > -1) {
		if (hours > 12) hours - 12;
		if (hours == 0) hours = 12;
		format = format.replace("hh", hours.toString().padL(2, "0"));
	}
	if (format.indexOf("mm") > -1)
		format = format.replace("mm", date.getMinutes().toString().padL(2, "0"));
	if (format.indexOf("ss") > -1)
		format = format.replace("ss", date.getSeconds().toString().padL(2, "0"));
	return format;
}

// function that creates a string with a length of "count" constitng of "chr" characters
String.repeat = function (chr, count) {
	var str = "";
	for (var x = 0; x < count; x++) { str += chr };
	return str;
}

// padL - returns a string with a total length of "width" beginning with an appropriate number of "pad" chars. If pad not specified, ' ' is used.
String.prototype.padL = function (width, pad) {
	if (!width || width < 1)
		return this;

	if (!pad) pad = " ";
	var length = width - this.length
	if (length < 1) return this.substr(0, width);

	return (String.repeat(pad, length) + this).substr(0, width);
}

// padR - returns a string with a total length of "width" ending with an appropriate number of "pad" chars. If pad not specified, ' ' is used.
String.prototype.padR = function (width, pad) {
	if (!width || width < 1)
		return this;

	if (!pad) pad = " ";
	var length = width - this.length
	if (length < 1) this.substr(0, width);

	return (this + String.repeat(pad, length)).substr(0, width);
}

// helpful functions for trimming!
// ---------------------------------
String.prototype.trim = function () {
	return this.replace(/^\s+|\s+$/g, "");
}
String.prototype.ltrim = function () {
	return this.replace(/^\s+/, "");
}
String.prototype.rtrim = function () {
	return this.replace(/\s+$/, "");
}
//---------------------------------



/// function is called when select period button is clicked on package tour search results
///  packageTourID - ID of a package tour, used to find correct drop down list
function selectPeriodButtonOnClick(packageTourID, currencyID, detailsURL) {
	redirectToPackageTourDetailWithSelectedPeriod(packageTourID, currencyID, detailsURL);
}

/// function is called when the date is changed in a daily departure based package tour details. Request is made to the server and new accommodation units are shown
function dailyDeparturesSelectOnChange(packageTourID, bookingAddressDisplayURL, setDirectLink, unitActivityStatus, BookingLinkInNewWindowVar, periodStartDateString, proxyPath) {
	getAccommodationUnitsForPackageTour(packageTourID, bookingAddressDisplayURL, setDirectLink, unitActivityStatus, BookingLinkInNewWindowVar, true, periodStartDateString, proxyPath);
}


/// function is called when period is changed in package tour details. Request is made to the server and new accommodation units are shown
function departuresSelectOnChange(packageTourID, bookingAddressDisplayURL, setDirectLink, unitActivityStatusTab, BookingLinkInNewWindowVar, proxyPath) {
	getAccommodationUnitsForPackageTour(packageTourID, bookingAddressDisplayURL, setDirectLink, unitActivityStatusTab, BookingLinkInNewWindowVar, false, "", proxyPath);
}

function getAccommodationUnitsForPackageTour(packageTourID, bookingAddressDisplayURL, setDirectLink, unitActivityStatus, BookingLinkInNewWindowVar, isDailyDeparture, periodStartDateString, proxyPath) {
	jQuery("[data-reservation-loading-panel='1']").show();
	//int numberOfPersons, int packageTourID, int[] objectIDList, long periodStartDateString, int numberOfDays, string currencyID, string languageID)
	var selectedPeriodOption = jQuery("#departuresSelect option:selected");
	var customerID = jQuery("#customerID").val();
	var marketID = jQuery("#marketID").val();
	var numberOfPersons = 1;
	var objectID = jQuery("#accommodationObjectsSelect").val();
	if (objectID == null) {
		objectID = 0;
	}

	var numberOfDays = 1;
	if (isDailyDeparture == false || isDailyDeparture == 'false') {
		if (selectedPeriodOption.attr("numberofdays")) {
			numberOfDays = selectedPeriodOption.attr("numberofdays");
		}

		if (selectedPeriodOption.attr("startdate")) {
			periodStartDateString = selectedPeriodOption.attr("startdate");
		}
	}

	var currencyIDSetting = jQuery("#currencyIDSettingPackageTourHidden").val();
	var galleryPath = jQuery("#galleryPathHidden").val();
	var cartID = jQuery("#cartIDHiddenField").val();
	var affiliateID = jQuery("#affiliateIDUnitList").val();
	jQuery.post(
	 proxyPath + "GetAccommodationUnitsForPackageTour",
	 { 'numberOfPersons': numberOfPersons, 'packageTourID': packageTourID, 'objectIDList': objectID, 'periodStartDateString': periodStartDateString, 'numberOfDays': numberOfDays, 'currencyID': currencyIDSetting, 'languageID': languageIDSetting, 'bookingAddressDisplayURL': bookingAddressDisplayURL, 'galleryPath': galleryPath, 'setDirectLink': setDirectLink, 'unitActivityStatus': unitActivityStatus, 'BookingLinkInNewWindowVar': BookingLinkInNewWindowVar, 'cartID': cartID, 'customerID': customerID, 'marketID': marketID, 'affiliateID': affiliateID },
	 function (data) {
	 	jQuery("div.packaget-tour-accommodation-holder").html(jQuery(data).text());
	 	jQuery("[data-reservation-loading-panel='1']").hide();
	 	initPhotoGallery();
	 }
	);
}

// function is called on accommodationObjecSelect element onchange event
// it filters out the departuresSelect element and calls the web service with the new info!
function accommodationObjectOnChange(packageTourID, bookingAddressDisplayURL, setDirectLink, unitActivityStatusTab, BookingLinkInNewWindowVar, proxyPath) {
	getAccommodationUnitsForPackageTour(packageTourID, bookingAddressDisplayURL, setDirectLink, unitActivityStatusTab, BookingLinkInNewWindowVar, false, "", proxyPath);
}


function packageTourObjectPriceListChanged(packageTourID, unitActivityStatus, proxyPath) {
	jQuery("div[price-list-loading-panel='1']").show();

	var objectID = jQuery("#accommodationObjectsPriceListSelect").val();
	if (objectID == null) {
		objectID = 0;
	}

	var currencyIDSetting = jQuery("#currencyIDSettingPackageTourHidden").val();
	var languageIDSetting = jQuery("#languageIDSettingPackageTourHidden").val();
	var cartID = jQuery("#cartIDHiddenField").val();
	var affiliateID = jQuery("#affiliateIDUnitList").val();

	jQuery.post(
	 proxyPath + "GetPriceListForPackageTour",
	 { 'packageTourID': packageTourID, 'objectIDList': objectID, 'currencyID': currencyIDSetting, 'languageID': languageIDSetting, 'unitActivityStatus': unitActivityStatus, 'cartID': cartID, 'affiliateID': affiliateID },
	 function (data) {

	 	jQuery("div.package-tour-price-list-holder").html(jQuery(data).text());
	 	jQuery("div[price-list-loading-panel='1']").hide();
	 	initPhotoGallery();
	 }
	);
}
