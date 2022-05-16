	///global variables for curencyID
	var _CURRENCYIDEU = "978";
	var _CURRENCYIDDKK = "208";

	var dateFormatString = "";

	var INPUT_PLACEHOLDERS = ["accommodation-type", "facilities", "activities", "location", "box-extra"];
	/// read tabId from cookie and select appropriate tab
	function selectSearchTabFromValueInCookie() {
		var tabId = tryToReadFromCookie("searchTabId", 0);
		searchTabs.showTab(tabId);
	}

	/// Initialize accommodation search control
	function InitializeAccommodationSearchControl() {
	}

	/// Initialize the transportation search control
	function InitializeTransportationSearchControl() {
	}

	/// Initialize package tour search control
	function InitializePackageTourSearchControl() {
	}

	/// implement Clear method to empty arrays
	Array.prototype.Clear = function () {
		while (this.length > 0) {
			this.pop();
		}
	}

	/*** CATEGORY OBJECT ***/
	///constructor:
	function Category() {
		this.categoryId = 0;
		this.categoryName = "";
	}

	///this method return option element <option>...</option> for country
	Category.prototype.GetOptionElement = function () {
		return "<option value='" + this.categoryId + "'>" + this.categoryName + "</option>";
	}
	/*** END OF CATEGORY OBJECT ***/
	/*** COUNTRY OBJECT ***/
	///constructor:
	function Country() {
		this.countryId = 0;
		this.countryName = "";
	}

	///this method return option element <option>...</option> for country
	Country.prototype.GetOptionElement = function () {
		return "<option value='" + this.countryId + "'>" + this.countryName + "</option>";
	}
	/*** END OF COUNTRY OBJECT ***/
	/*** REGION OBJECT ***/
	///constructor:
	function Region() {
		this.regionId = 0;
		this.regionName = "";
		this.countryId = 0;
	}

	///this method return option element <option>...</option> for region
	Region.prototype.GetOptionElement = function () {
		return "<option value='" + this.regionId + "' countryID='" + this.countryId + "'>" + this.regionName + "</option>";
	}
	/*** END OF REGION OBJECT ***/
	/*** DESTINATION OBJECT ***/
	///constructor:
	function Destination() {
		this.destinationId = 0;
		this.regionId = 0;
		this.destinationName = "";
	}

	///this method return option element <option>...</option> for region
	Destination.prototype.GetOptionElement = function () {
		return "<option value='" + this.destinationId + "' regionID='" + this.regionId + "'>" + this.destinationName + "</option>";
	}
	/*** END OF DESTINATION OBJECT ***/


	/*** GLOBALS ***/
	/// here we hold all categories we get from web service
	var categoriesListPackageTour = new Array();
	var categoriesListAccommodation = new Array();
	var categoriesListTransportation = new Array();

	/// here ve hold all countries we get from web service
	var countriesListPackageTour = new Array();
	var countriesListAccommodation = new Array();
	var countriesListTransportation = new Array();

	/// here ve hold all regions we get from web service
	var regionsListAccommodation = new Array();
	var regionsListPackageTour = new Array();
	var regionsListTransportation = new Array();

	/// here we hold only id's of regions that are currently visible. It's used to properly show all destinations if region is irelevant
	var visibleRegionsIdListAccommodation = new Array();
	var visibleRegionsIdListPackageTour = new Array();
	var visibleRegionsIdListTransportation = new Array();

	/// here ve hold all destinations we get from web service
	var destinationsListAccommodation = new Array();
	var destinationsListPackageTour = new Array();
	var destinationsListTransportation = new Array();


	/// Function will try to find node by name in XML. If successful returns nodes text(), otherwise null
	function tryToFindNodeInXml(xml, nodeName) {
		var node = xml.find(nodeName);
		if (node != null) {
			var nodeText = node.text();

			if (nodeText != null && nodeText != '') {
				return nodeText;
			}
		}
		return null;
	}


	/// find all categories in xml and populate categoriesList array
	///     xml is XML returned from web service GetSearchFields
	///     categoriesList is array in wich categories are saved
	function getCategoriesFromXml(xml, categoriesList) {
		categoriesList.Clear();

		/// for each category in XML find it's name and id and create option element
		xml.find("Category").each(function () {
			var categoryXML = jQuery(this);
			var category = new Category();

			category.categoryId = tryToFindNodeInXml(categoryXML, 'CategoryID');
			category.categoryName = tryToFindNodeInXml(categoryXML, 'CategoryName');

			if (category.categoryId != null && category.categoryName != null) {
				categoriesList.push(category);
			}
		});
	}
	/// populate the contents of Categories select list from array
	///     categoriesSelectListID is ID of a select list in DOM wich will be populated with categories
	///     categoriesList is list of categories from wich select list will be populated
	function populateCategoriesSelectList(categoriesSelectListID, categoriesList) {
		var options = '<option value="0">' + irrelevantTranslationCategory + '</option>';

		jQuery(categoriesList).each(function () {
			var category = this;
			options += category.GetOptionElement();
		});

		jQuery("#" + categoriesSelectListID).html(options);
	}



	/// find all countries in xml and populate countriesList array
	///     xml is XML returned from web service GetSearchFields
	///     countriesList is array in wich countries are saved
	function getCountriesFromXml(xml, countriesList) {
		countriesList.Clear();

		/// for each country in XML find it's name and id and create option element
		xml.find("Country").each(function () {
			var countryXML = jQuery(this);
			var country = new Country();

			country.countryId = tryToFindNodeInXml(countryXML, 'CountryID');
			country.countryName = tryToFindNodeInXml(countryXML, 'CountryName');

			if (country.countryId != null && country.countryName != null) {
				countriesList.push(country);
			}
		});
	}
	/// populate the contents of Countries select list from array
	///     countriesSelectListID is ID of a select list in DOM wich will be populated with countries
	///     countriesList is list of countries from wich select list will be populated
	function populateCountriesSelectList(countriesSelectListID, countriesList) {
		var options = '<option value="0">' + irrelevantTranslationCountry + '</option>';

		jQuery(countriesList).each(function () {
			var country = this;
			options += country.GetOptionElement();
		});

		jQuery("#" + countriesSelectListID).html(options);
	}



	/// find all regions in xml and populate countriesList array
	///     xml is XML returned from web service GetSearchFields
	///     regionsList is array in wich regions are saved
	///     visibleRegionsList is array in wich visible regions id's are saved
	function getRegionsFromXml(xml, regionsList, visibleRegionsList) {
		regionsList.Clear();
		visibleRegionsList.Clear();

		/// for each region in XML find it's name and id and create option element
		xml.find("Region").each(function () {
			var regionXML = jQuery(this);
			var region = new Region();

			region.regionId = tryToFindNodeInXml(regionXML, 'RegionID');
			region.regionName = tryToFindNodeInXml(regionXML, 'RegionName');
			region.countryId = tryToFindNodeInXml(regionXML, 'CountryID');

			if (region.regionId != null && region.regionName != null && region.countryId != null) {
				regionsList.push(region);
				visibleRegionsList.push(region.regionId);
			}
		});
	}
	/// populate the contents of Regions select list from array
	///     regionsSelectListID is ID of a select list in DOM wich will be populated with regions
	///     regionsList is list of regions from wich select list will be populated
	function populateRegionsSelectList(regionsSelectListID, regionsList) {
		let irrelevantTranslationRegion = "Please select";
		var options = '<option value="0">' + irrelevantTranslationRegion + '</option>';

		jQuery(regionsList).each(function () {
			var region = this;
			options += region.GetOptionElement();
		});

		jQuery("#" + regionsSelectListID).html(options);
	}



	/// find all destinations in xml and populate destinationsList array
	///     xml is XML returned from web service GetSearchFields
	///     destinationsList is array in wich destinations are saved
	function getDestinationsFromXml(xml, destinationsList) {
		destinationsList.Clear();

		/// for each destination in XML find it's name and id and create option element
		xml.find("Destination").each(function () {
			var destinationXML = jQuery(this);
			var destination = new Destination();

			destination.destinationId = tryToFindNodeInXml(destinationXML, 'DestinationID');
			destination.destinationName = tryToFindNodeInXml(destinationXML, 'DestinationName');
			destination.regionId = tryToFindNodeInXml(destinationXML, 'RegionID');

			if (destination.destinationId != null && destination.destinationName != null && destination.regionId != null) {
				destinationsList.push(destination);
			}
		});
	}
	/// populate the contents of Destination select list from array
	///     destinationsSelectListID is ID of a select list in DOM wich will be populated with destinations
	///     destinationsList is list of destinations from wich select list will be populated
	function populateDestinationsSelectList(destinationsSelectListID, destinationsList) {
		var options = '<option value="0">' + jQuery('#irrelevantTranslationDestination').val() + '</option>';
		jQuery(destinationsList).each(function () {
			var destination = this;
			options += destination.GetOptionElement();
		});
		jQuery("#" + destinationsSelectListID).html(options);
	}


	/// Function converts strng to XML. It's used for IE6 and IE7 compatibility after ajax requests
	var StringToXML = function (s) {
		var x, ie = /msie/i.test(navigator.userAgent);
		try {
			var p = ie ? new ActiveXObject("Microsoft.XMLDOM") : new DOMParser();
			p.async = false;
		} catch (e) { throw new Error("XML Parser could not be instantiated") };
		try {
			if (ie) x = p.loadXML(s) ? p : false;
			else x = p.parseFromString(s, "text/xml");
		} catch (e) { throw new Error("Error parsing XML string") };
		return x;
	};


	/// get search fields for accommodation search tab and populate select lists
	function bindSearchFieldsToAccomodationSearchTab(objectTypeID, categoryID, countryID, regionID, destinationID, proxyPath) {
		jQuery("#objectTypeSelectAccommodation").val(objectTypeID);

		/// problems with sending array to Server, using xmlGttpRequest instead jQuery
		var xmlhttp = null;
		if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
			xmlhttp = new XMLHttpRequest();
		}
		else {// code for IE6, IE5
			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		}

		xmlhttp.onreadystatechange = function () {
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
				/// parse result from server and populate fields
				//var xml = jQuery(xmlhttp.responseText);
				var xml = jQuery(StringToXML(xmlhttp.responseText));

				getCategoriesFromXml(xml, categoriesListAccommodation);
				populateCategoriesSelectList("categoriesSelectAccommodation", categoriesListAccommodation);
				if (categoryID > 0) {
					jQuery("#categoriesSelectAccommodation").val(categoryID);
				}

				getCountriesFromXml(xml, countriesListAccommodation);
				populateCountriesSelectList("countriesSelectAccommodation", countriesListAccommodation);

				getRegionsFromXml(xml, regionsListAccommodation, visibleRegionsIdListAccommodation);
				populateRegionsSelectList("regionsSelectAccommodation", regionsListAccommodation);

				getDestinationsFromXml(xml, destinationsListAccommodation);
				populateDestinationsSelectList("destinationsSelectAccommodation", destinationsListAccommodation);

				if (countryID > 0) {
					jQuery("#countriesSelectAccommodation").val(countryID);
				}

				updateRegionsList(regionsListAccommodation, visibleRegionsIdListAccommodation, "countriesSelectAccommodation", "regionsSelectAccommodation");

				if (regionID > 0) {
					jQuery("#regionsSelectAccommodation").val(regionID);
				}
				updateDestinationsList(destinationsListAccommodation, visibleRegionsIdListAccommodation, "regionsSelectAccommodation", "destinationsSelectAccommodation");

				if (destinationID > 0) {
					jQuery("#destinationsSelectAccommodation").val(destinationID);
				}
			}
		}

		var objectTypeIDListParam = "";
		if (objectTypeID != null || objectTypeID != undefined) {
			var objectTypeIDList = objectTypeID.toString().split(',');
			for (var pos = 0; pos < objectTypeIDList.length; pos++) {
				objectTypeIDListParam += "&objectTypeIDList=" + objectTypeIDList[pos];
			}
		}
		else {
			objectTypeIDListParam += "&objectTypeIDList=0";
		}

		var objectTypeGroupIDListParam = "";
		var objectTypeGroupIDList = jQuery("#objectTypeGroupIDListHiddenField").val();
		if (objectTypeGroupIDList != null && objectTypeGroupIDList != '') {
			var objectTypeGroupIDListArray = objectTypeGroupIDList.split(',');
			for (var i = 0; i < objectTypeGroupIDListArray.length; i++) {
				objectTypeGroupIDListParam += "&objectTypeGroupIDList=" + objectTypeGroupIDListArray[i];
			}
		}
		else {
			objectTypeGroupIDListParam = "&objectTypeGroupIDList=3&objectTypeGroupIDList=4";
		}

		var parameters = 'languageID=' + languageIDSetting + objectTypeIDListParam + objectTypeGroupIDListParam + '&categoryIDList=' + categoryID + '&countryID=' + countryID + '&regionID=' + regionID + "&searchSupplier=" + searchSuppliersSetting;

		xmlhttp.open("post", proxyPath + "GetSearchFields", true);
		xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		xmlhttp.send(parameters);
	}

	/// get search fields for package tour search tab and populate select lists
	function bindSearchFieldsToPackageTourSearchTab(objectTypeID, categoryID, countryID, regionID, destinationID, proxyPath) {
		jQuery("#objectTypeSelectPackageTour").val(objectTypeID);

		/// problems with sending array to Server, using xmlGttpRequest instead jQuery
		var xmlhttp = null;
		if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
			xmlhttp = new XMLHttpRequest();
		}
		else {// code for IE6, IE5
			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		}

		xmlhttp.onreadystatechange = function () {
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
				/// parse result from server and populate fields
				//var xml = jQuery(xmlhttp.responseText);
				var xml = jQuery(StringToXML(xmlhttp.responseText));

				getCategoriesFromXml(xml, categoriesListPackageTour);
				populateCategoriesSelectList("categoriesSelectPackageTour", categoriesListPackageTour);
				if (categoryID > 0) {
					jQuery("#categoriesSelectPackageTour").val(categoryID);
				}

				getCountriesFromXml(xml, countriesListPackageTour);
				populateCountriesSelectList("countriesSelectPackageTour", countriesListPackageTour);

				getRegionsFromXml(xml, regionsListPackageTour, visibleRegionsIdListPackageTour);
				populateRegionsSelectList("regionsSelectPackageTour", regionsListPackageTour);

				getDestinationsFromXml(xml, destinationsListPackageTour);
				populateDestinationsSelectList("destinationsSelectPackageTour", destinationsListPackageTour);

				if (countryID > 0) {
					jQuery("#countriesSelectPackageTour").val(countryID);
				}

				updateRegionsList(regionsListPackageTour, visibleRegionsIdListPackageTour, "countriesSelectPackageTour", "regionsSelectPackageTour");

				if (regionID > 0) {
					jQuery("#regionsSelectPackageTour").val(regionID);
				}

				updateDestinationsList(destinationsListPackageTour, visibleRegionsIdListPackageTour, "regionsSelectPackageTour", "destinationsSelectPackageTour");

				if (destinationID > 0) {
					jQuery("#destinationsSelectPackageTour").val(destinationID);
				}
			}
		}

		var objectTypeIDListParam;
		if (objectTypeID != null || objectTypeID != undefined) {
			var objectTypeIDList = objectTypeID.toString().split(',');
			for (var pos = 0; pos < objectTypeIDList.length; pos++) {
				objectTypeIDListParam += "&objectTypeIDList=" + objectTypeIDList[pos];
			}
		}
		else {
			objectTypeIDListParam += "&objectTypeIDList=0";
		}
		var parameters = 'languageID=' + languageIDSetting + objectTypeIDListParam + "&objectTypeGroupIDList=" + 6 + '&categoryIDList=' + categoryID + '&countryID=' + countryID + '&regionID=' + regionID + "&searchSupplier=" + searchSuppliersSetting;

		xmlhttp.open("post", proxyPath + "GetSearchFields", true);
		xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		xmlhttp.send(parameters);
	}

	/// functions that populate the transportation search page
	function bindSearchFieldsToTransportationSearchTab(objectTypeID, categoryID, countryID, regionID, destinationID, proxyPath) {
		jQuery("#objectTypeSelectTransportation").val(objectTypeID);

		/// problems with sending array to Server, using xmlGttpRequest instead jQuery
		var xmlhttp = null;
		if (window.XMLHttpRequest) {
			// code for IE7+, Firefox, Chrome, Opera, Safari
			xmlhttp = new XMLHttpRequest();
		}
		else {
			// code for IE6, IE5
			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		}

		xmlhttp.onreadystatechange = function () {
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
				/// parse result from server and populate fields
				var xml = jQuery(StringToXML(xmlhttp.responseText));

				getCategoriesFromXml(xml, categoriesListTransportation);
				populateCategoriesSelectList("categoriesSelectTransportation", categoriesListTransportation);
				if (categoryID > 0) {
					jQuery("#categoriesSelectTransportation").val(categoryID);
				}

				getCountriesFromXml(xml, countriesListTransportation);

				getRegionsFromXml(xml, regionsListTransportation, visibleRegionsIdListTransportation);

				getDestinationsFromXml(xml, destinationsListTransportation);

				/// Instead of having a select list for country, region and destination, create only one select list with region and country added to destination name
				addRegionAndCountryNamesToDestinations(destinationsListTransportation, regionsListTransportation, countriesListTransportation);
				populateDestinationDropDownListInTransportationTab("pickupDestinationsSelectTransportation", destinationsListTransportation, regionsListTransportation, countriesListTransportation);
				populateDestinationDropDownListInTransportationTab("dropoffDestinationsSelectTransportation", destinationsListTransportation, regionsListTransportation, countriesListTransportation);

				if (destinationID > 0) {
					jQuery("#destinationsSelectTransportation").val(destinationID);
				}
			}
		}

		var objectTypeIDListParam;
		if (objectTypeID != null || objectTypeID != undefined) {
			var objectTypeIDList = objectTypeID.toString().split(',');
			for (var pos = 0; pos < objectTypeIDList.length; pos++) {
				objectTypeIDListParam += "&objectTypeIDList=" + objectTypeIDList[pos];
			}
		}
		else {
			objectTypeIDListParam += "&objectTypeIDList=0";
		}
		var parameters = 'languageID=' + languageIDSetting + objectTypeIDListParam + "&objectTypeGroupIDList=" + 7 + '&objectTypeGroupIDList=' + 9 + '&categoryIDList=' + categoryID + '&countryID=' + countryID + '&regionID=' + regionID + "&searchSupplier=" + searchSuppliersSetting;

		xmlhttp.open("post", proxyPath + "GetSearchFields", true);
		xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		xmlhttp.send(parameters);
	}

	/// Adds region and country name to destination names separated by commas
	function addRegionAndCountryNamesToDestinations(destinationList, regionList, countryList) {
		jQuery(destinationList).each(function () {
			var destination = this;
			for (var i in regionList) {
				if (regionList[i].regionId == destination.regionId) {
					destination.destinationName += ", " + regionList[i].regionName;
					for (var j in countryList) {
						if (countryList[j].countryId == regionList[i].countryId) {
							destination.destinationName += ", " + countryList[j].countryName;
							break;
						}
					}
					break;
				}
			}
		});
	}

	/// Populates drop down list in transportation tab
	function populateDestinationDropDownListInTransportationTab(destinationSelectListID, destinationList, regionList, countryList) {
		var options = '<option value="0">' + jQuery('#irrelevantTranslationDestination').val() + '</option>';

		jQuery(destinationList).each(function () {
			var destination = this;

			options += destination.GetOptionElement();
		});

		jQuery("#" + destinationSelectListID).html(options);
	}



	function populateSearchFieldsInTransportationTab(proxyPath) {
		PopulateSearchFieldsFromCookie('personsSelectTransportation', 'dateFromTransportation', 'dateToTransportation', 'onlyOnSpecialOfferCheckBox');

		var searchTabID = tryToReadFromCookie("searchTabID", -1);

		/// if searchTabID is -1 or 0 then search is performed for accommodation so try to read values from cookie
		if (searchTabID == -1 || searchTabID == 0) {
			/// try to read values from cookie. If it fails return default value (second parameter)
			objectTypeID = tryToReadFromCookie("objectTypeID", 0);
			categoryID = tryToReadFromCookie("categoryID", 0);
			countryID = tryToReadFromCookie("countryID", 0);
			regionID = tryToReadFromCookie("regionID", 0);
			destinationID = tryToReadFromCookie("destinationID", 0);
		}
		else {
			/// use default values
			objectTypeID = 0;
			categoryID = 0;
			countryID = 0;
			regionID = 0;
			destinationID = 0;
		}
		/// populate category, country, region and destination in accomodation tab
		bindSearchFieldsToTransportationSearchTab(objectTypeID, categoryID, countryID, regionID, destinationID, proxyPath);
	}



	/// function populates search fields (from cookie or default values) in accommodation tab
	function populateSearchFieldsInAccommodationTab(proxyPath) {

		saveQueryStringInCookie(window.location.toString());

		PopulateSearchFieldsFromCookie('personsSelectAccommodation', 'dateFromAccommodation', 'dateToAccommodation', 'onlyOnSpecialOfferCheckBox');

		var searchTabID = tryToReadFromCookie("searchTabID", -1);

		/// if searchTabID is -1 or 0 then search is performed for accommodation so try to read values from cookie
		if (searchTabID == -1 || searchTabID == 0) {
			/// try to read values from cookie. If it fails return default value (second parameter)
			objectTypeID = tryToReadFromCookie("objectTypeID", 0);
			categoryID = tryToReadFromCookie("categoryID", 0);
			countryID = tryToReadFromCookie("countryID", 0);
			regionID = tryToReadFromCookie("regionID", 0);
			destinationID = tryToReadFromCookie("destinationID", 0);
		}
		else {
			/// use default values
			objectTypeID = 0;
			categoryID = 0;
			countryID = 0;
			regionID = 0;
			destinationID = 0;
		}
		//	check if category ID is given in URL 
		if (window.QueryString) {
			var category = window.QueryString(location.href, "categoryID");
			categoryID = category || categoryID;
		}
		/// populate category, country, region and destination in accomodation tab
		bindSearchFieldsToAccomodationSearchTab(objectTypeID, categoryID, countryID, regionID, destinationID, proxyPath);
	}

	/// function populates search fields (from cookie or default values) in accommodation tab
	function populateSearchFieldsInPackagetourTab(proxyPath) {
		PopulateSearchFieldsFromCookie("personsSelectPackage", 'dateFromPackageTour', 'dateToPackageTour', 'onlyPackageToursOnSpecialOfferCheckBox');

		var searchTabID = tryToReadFromCookie("searchTabID", -1);
		objectTypeID = 0;

		/// if searchTabID is -1 or 1 then search is performed for package tour so try to read values from cookie
		if (searchTabID == -1 || searchTabID == 1) {
			categoryID = tryToReadFromCookie("categoryID", 0);
			countryID = tryToReadFromCookie("countryID", 0);
			regionID = tryToReadFromCookie("regionID", 0);
			destinationID = tryToReadFromCookie("destinationID", 0);
		} else {
			/// use default values
			categoryID = 0;
			countryID = 0;
			regionID = 0;
			destinationID = 0;
		}
		/// populate category, country, region and destination in package tour tab
		bindSearchFieldsToPackageTourSearchTab(objectTypeID, categoryID, countryID, regionID, destinationID, proxyPath);
	}

	/// function populates search fields that that are saved in cookie (date from, date to and number of persons)
	function PopulateSearchFieldsFromCookie(personsSelectListID, dateFromID, dateToID, onlyOnSpecialOfferID) {
		/// defaults
		var numberOfPersons = 1;
		if (personsSelectListID != null) {
			numberOfPersons = tryToReadFromCookie("persons", 1);
			jQuery("#" + personsSelectListID).val(numberOfPersons);
		}

		var dateFrom = new Date();
		if (dateFromID != null) {
			dateFrom.setTime(tryToReadFromCookie("from", dateFrom));
			jQuery('#' + dateFromID).datepicker("setDate", dateFrom);
		}

		var dateTo = new Date();
		dateTo.setDate(dateTo.getDate() + 7);
		if (dateToID != null) {
			dateTo.setTime(tryToReadFromCookie("to", dateTo.getTime()));
			jQuery('#' + dateToID).datepicker("setDate", dateTo);
		}

		var onlyOnSpecialOfferCheckBox = document.getElementById(onlyOnSpecialOfferID);

		setTimeout(function () {
			jQuery('#' + dateFromID).datepicker("setDate", dateFrom);
			jQuery('#' + dateToID).datepicker("setDate", dateTo);
		}, 500);

		if (onlyOnSpecialOfferCheckBox != null) {
			var onlyOnSpecialOffer = tryToReadFromCookie("onlyOnSpecialOffer", "krljb");
			if (onlyOnSpecialOffer == "true" || onlyOnSpecialOffer == "True") {
				onlyOnSpecialOfferCheckBox.checked = true;
			}
		}
	}

	/// function is called if object type or category in accomodation tab is changed. Request is made to the server to get new list od categories, countries, regions and destinations
	function rebindSearchFieldsInAccommodationTab(proxyPath) {
		var objectTypeId = jQuery("#objectTypeSelectAccommodation").val();
		var categoryId = jQuery("#categoriesSelectAccommodation").val();
		var countryId = jQuery("#countriesSelectAccommodation").val();
		var regionId = jQuery("#regionsSelectAccommodation").val();
		var destinationId = jQuery("#destinationsSelectAccommodation").val();

		bindSearchFieldsToAccomodationSearchTab(objectTypeId, categoryId, countryId, regionId, destinationId, proxyPath);
	}

	/// function is called if object type or category in package tour tab is changed. Request is made to the server to get new list od categories, countries, regions and destinations
	function rebindSearchFieldsInPackageTourTab(proxyPath) {
		var objectTypeId = jQuery("#objectTypeSelectPackageTour").val();
		var categoryId = jQuery("#categoriesSelectPackageTour").val();
		var countryId = jQuery("#countriesSelectPackageTour").val();
		var regionId = jQuery("#regionsSelectPackageTour").val();
		var destinationId = jQuery("#destinationsSelectPackageTour").val();

		bindSearchFieldsToPackageTourSearchTab(objectTypeId, categoryId, countryId, regionId, destinationId, proxyPath);
	}

	/// function is called if object type or category in transportation tab is changed. Request is made to the server to get new list od categories, countries, regions and destinations
	function rebindSearchFieldsInTransportationTab(proxyPath) {
		var objectTypeId = jQuery("#objectTypeSelectTransportation").val();
		var categoryId = jQuery("#categoriesSelectTransportation").val();
		var countryId = jQuery("#countriesSelectTransportation").val();
		var regionId = jQuery("#regionsSelectTransportation").val();
		var destinationId = jQuery("#destinationsSelectTransportation").val();

		bindSearchFieldsToTransportationSearchTab(objectTypeId, categoryId, countryId, regionId, destinationId, proxyPath);
	}

	/// function is used to refresh regions. Regions that belong in selected country are shown
	function updateRegionsList(regionsList, visibleRegionsIdList, countriesSelectListID, regionsSelectListID) {
		visibleRegionsIdList.Clear();

		var visibleRegions = new Array();
		var selectedCountryId = jQuery("#" + countriesSelectListID).val();

		jQuery(regionsList).each(function () {
			var region = this;
			if (selectedCountryId == 0 || region.countryId == selectedCountryId) {
				visibleRegions.push(region);
				visibleRegionsIdList.push(region.regionId);
			}
		});

		populateRegionsSelectList(regionsSelectListID, visibleRegions);
	}

	/// function is used to refresh destinations. Destinations that belong in selected region are shown
	function updateDestinationsList(destinationsList, visibleRegionsIdList, regionsSelectListID, destinationsSelectListID) {
		var selectedRegionId = jQuery("#" + regionsSelectListID).val();
		var visibleDestinations = new Array();

		jQuery(destinationsList).each(function () {
			var destination = this;
			if ((selectedRegionId == 0 && jQuery.inArray(destination.regionId, visibleRegionsIdList) >= 0) || destination.regionId == selectedRegionId) {
				visibleDestinations.push(destination);
			}
		});

		populateDestinationsSelectList(destinationsSelectListID, visibleDestinations);
	}


	function redirectSearchControl(address, linkLocationElement, searchResultsAddressElement) {
		var url = address;
		///check settings for frame
		var linkLocation, searchResultsAddress;
		if (linkLocationElement != null)
			linkLocation = linkLocationElement.val();
		if (searchResultsAddressElement != null) {
			searchResultsAddress = searchResultsAddressElement.val();
		}
		if (searchResultsAddress != '') {
			var prefix = '?';
			if (searchResultsAddress.indexOf('?') != -1) {
				prefix = '&';
			}
			url = searchResultsAddress + prefix + 'itravelURL=' + UrlEncode(url);
		}

		if (linkLocation != '') {
			switch (linkLocation) {
				case 'parent':
					parent.location = url;
					break;
				case 'top':
					window.top.location = url;
					break;
				default:
					document.location = url;
					break;
			}
		}
		else {
			document.location = url;
		}
	}

	/// function is called on show entire transportation button click
	function showEntireTransportationOfferClick(searchResultsURL) {

		var fromDate = new Date();
		var fromTicks = GetTicksFromDate(fromDate);
		var toDate = new Date();
		toDate.setDate(toDate.getDate() + 7);
		var toTicks = GetTicksFromDate(toDate);
		/// set accommodation type group id: 3 = private accommodation, 4 = hotels

		var url = searchResultsURL;
		if (searchResultsURL.indexOf('?') != -1) {
			url += "&searchTabID=0";
		}
		else {
			url += "?searchTabID=0";
		}

		url += "&ignorePriceAndAvailability=1";
		url += "&categoryID=0";
		url += "&countryID=0";
		url += "&regionID=0";
		url += "&destinationID=0";
		url += "&objectTypeID=0";
		url += getVariableForQueryString("persons", "personsSelectTransportation");

		var transportationTypeGroupId = "7,9";
		var transportationTypeIdQueryString = getVariableForQueryString("objectTypeID", "objectTypeIDListHiddenField");
		if (transportationTypeIdQueryString != undefined && transportationTypeGroupId.length > 0) {
			url += transportationTypeIdQueryString;
		}
		else {
			url += "&objectTypeGroupID=" + transportationTypeGroupId;
		}
		url += "&languageID=" + languageIDSetting;
		url += "&searchSupplier=" + searchSuppliersSetting;

		// Save all info into a cookie so that the info can be restored on user return
		saveQueryStringInCookie(url);
		redirectSearchControl(url, jQuery("#LinkLocationSettingTransportation"), jQuery("#SearchResultsAddressSettingTransportation"));
	}

	/// function is called on show entire accommodation button click
	function showEntireAccommodationOfferClick(searchResultsURL) {

		var fromDate = new Date();
		var fromTicks = GetTicksFromDate(fromDate);
		var toDate = new Date();
		toDate.setDate(toDate.getDate() + 7);
		var toTicks = GetTicksFromDate(toDate);
		/// set accommodation type group id: 3 = private accommodation, 4 = hotels

		//var url = "/Accommodation/SearchResults.aspx";
		//url += "?searchTabID=0";
		var url = searchResultsURL;
		if (searchResultsURL.indexOf('?') != -1) {
			url += "&searchTabID=0";
		}
		else {
			url += "?searchTabID=0";
		}

		url += "&ignorePriceAndAvailability=1";
		url += "&categoryID=0";
		url += "&countryID=0";
		url += "&regionID=0";
		url += "&destinationID=0";
		url += "&objectTypeID=0";
		url += getVariableForQueryString("persons", "personsSelectAccommodation");

		var accommodationTypeGroupID = "3,4,5";

		var hiddenGroupValue = getVariableForQueryString("objectTypeGroupID", "objectTypeGroupIDListHiddenField");
		if (hiddenGroupValue) {
			url += hiddenGroupValue;
		} else {
			url += "&objectTypeGroupID=" + accommodationTypeGroupID;
		}
		url += "&languageID=" + languageIDSetting;
		url += "&searchSupplier=" + searchSuppliersSetting;

		// Save all info into a cookie so that the info can be restored on user return
		saveQueryStringInCookie(url);
		redirectSearchControl(url, jQuery("#LinkLocationSettingAccommodation"), jQuery("#SearchResultsAddressSettingAccommodation"));
	}


	/// function is called on search button click in transportation tab
	function searchTransportationClick(searchResultsURL) {
		//var url = "/Accommodation/SearchResults.aspx";
		var url = searchResultsURL;
		if (searchResultsURL.indexOf('?') != -1) {
			url += "&searchTabID=0";
		}
		else {
			url += "?searchTabID=0";
		}

		var categoriesSelected = getVariableForQueryString("categoryID", "categoriesSelectTransportation");
		var categoriesQueryString = getVariableForQueryString("categoryID", "categoryIDListHiddenField");

		if (jQuery("#categoriesSelectTransportation").val() != "0") {
			url += categoriesSelected;
		}
		else if (categoriesQueryString != undefined && categoriesQueryString.length > 0) {
			url += categoriesQueryString;
		}

		url += getVariableForQueryString("objectTypeID", "objectTypeSelectTransportation");
		url += getVariableForQueryString("persons", "personsSelectTransportation");
		url += getVariableForQueryString("children", "childrenSelectTransportation");
		url += getVariableForQueryString("numberOfStars", "numberOfStarsSelectTransportation");
		url += getVariableForQueryString("priceFrom", "priceFromInputTransportation");
		url += getVariableForQueryString("priceTo", "priceToInputTransportation");
		url += getVariableForQueryString("balcony", "balconyInputTransportation");
		url += getVariableForQueryString("categoryIntersectionID", "categoryIntersectionHiddenField");
		url += getVariableForQueryString("priceType", "priceTypeSelectTransportation");

		//	Ukoliko su zadani time pickeri njihove vrijednosi će se automatski dodati u tickove
		url += getVariableForQueryStringAsDateTicks("from", "dateFromTransportation", "timePickerFromTransportation");
		url += getVariableForQueryStringAsDateTicks("to", "dateToTransportation", "timePickerToTransportation");

		/// pickup
		url += getVariableForQueryString("pickupDestinationName", "selectedPickupDestinationsHiddenField");
		url += getVariableForQueryString("pickupDestinationID", "selectedPickupDestinationIDsHiddenField");
		url += getVariableForQueryString("pickupDestinationLevel", "selectedPickupDestinationLevelHiddenField");

		url += getVariableForQueryString("pickupDestinationID", "pickupDestinationsSelectTransportation");

		/// dropoff
		url += getVariableForQueryString("dropoffDestinationName", "selectedDropoffDestinationsHiddenField");
		url += getVariableForQueryString("dropoffDestinationID", "selectedDropoffDestinationIDsHiddenField");
		url += getVariableForQueryString("dropoffDestinationLevel", "selectedDropoffDestinationLevelHiddenField");

		url += getVariableForQueryString("dropoffDestinationID", "dropoffDestinationsSelectTransportation");

		/// destinationGlobal
		url += getVariableForQueryString("globalDestinationID", "selectedPickupDestinationIDsHiddenField");
		url += getVariableForQueryString("globalDestinationID", "selectedDropoffDestinationIDsHiddenField");

		url += "&onlyOnSpecialOffer=" + document.getElementById("onlyOnSpecialOfferCheckBox").checked;

		/// set accommodation type group id: 3 = private accommodation, 4 = hotels
		var transportationTypeGroupId = "7,9";
		var transportationTypeIdQueryString = getVariableForQueryString("objectTypeID", "objectTypeIDListHiddenField");
		if (transportationTypeIdQueryString != undefined && transportationTypeIdQueryString.length > 0) {
			url += transportationTypeIdQueryString;
		}
		else {
			url += "&objectTypeGroupID=" + transportationTypeGroupId;
		}
		url += "&languageID=" + languageIDSetting;
		url += "&searchSupplier=" + searchSuppliersSetting;

		var currencyIDQueryString = getVariableForQueryString("currencyID", "currencyIDSettingTransportation");
		if (currencyIDQueryString == '') {
			currencyIDQueryString = "&currencyID=" + currencyIDFromLanguage(languageIDSetting);
		}
		url += currencyIDQueryString;

		//url += getChildrenForSearch();

		// Save all info into a cookie so that the info can be restored on user return
		saveQueryStringInCookie(url);
		redirectSearchControl(url, jQuery("#LinkLocationSettingTransportation"), jQuery("#SearchResultsAddressSettingTransportation"));
	}


	/// function is called on search button click in accommodation tab
	function searchAccommodationClick(url, scope, columnSearchAlternateUrl, defaultregionID) {
		if (!url) {
			url = "/Accommodation/SearchResults.aspx";
		}
		var search_type = jQuery("#hidden-search-button-context").val();
		
		//	On column search page region can be predefined
		//	if region changes or user makes advanced search go to regulear search results page
		if (columnSearchAlternateUrl) {
			if (search_type === "advanced-search" || getAdvancedSearchData("") ) {
				url = columnSearchAlternateUrl;
			} else {
				var regionID = jQuery('#regionsSelectAccommodation_default').val();
				if (regionID != defaultregionID) {
					url = columnSearchAlternateUrl;
				}
			}
		}

		url += "?searchTabID=0";
		url += getVariableForQueryString("categoryID", "categoriesSelectAccommodation");
		url += getVariableForQueryString("countryID", "countriesSelectAccommodation");

		
		if (search_type === "advanced-search") {
			url += getVariableForQueryString("regionID", "regionsSelectAccommodation_advanced");
			url += getVariableForQueryString("destinationID", "destinationsSelectAccommodation_advanced");
		} else if (search_type === "default-search") {
			url += getVariableForQueryString("regionID", "regionsSelectAccommodation_default");
		}

		url += getVariableForQueryString("objectTypeID", "objectTypeSelectAccommodation");
		url += getVariableForQueryString("persons", "personsSelectAccommodation");
		url += getVariableForQueryString("numberOfStars", "numberOfStarsSelectAccommodation");
		url += getVariableForQueryString("priceFrom", "priceFromInputAccommodation");
		url += getVariableForQueryString("priceTo", "priceToInputAccommodation");
		url += getVariableForQueryString("balcony", "balconyInputAccommodation");
		url += getVariableForQueryString("priceType", "priceTypeSelectAccommodation");
		url += getVariableForQueryStringAsDateTicks("from", "dateFromAccommodation");
		url += getVariableForQueryStringAsDateTicks("to", "dateToAccommodation");

		var osf = document.getElementById("onlyOnSpecialOfferCheckBox");
		if (osf && osf.checked) {
			url += "&onlyOnSpecialOffer=" + osf.checked;
		}
		url += getVariableForQueryString("searchByName", "objectName");

		if (!jQuery("#objectName").val()) {
			url += getVariableForQueryString("searchByName", "objectName2");
		}
		/// set accommodation type group id: 3 = private accommodation, 4 = hotels
		var accommodationTypeGroupID = "3,4";
		url += "&objectTypeGroupID=" + accommodationTypeGroupID;
		url += "&languageID=" + languageIDSetting;
		url += "&currencyID=" + currencyIDFromLanguage(languageIDSetting);

		url += (getAdvancedSearchData("") || "").replace("?", "&");

		//url += getVariableForQueryString("regionID", "regionsSelectAccommodation_default");
		url += getVariableForQueryString("searchByName", "searchByName");

		// Save all info into a cookie so that the info can be restored on user return
		saveQueryStringInCookie(url);
        showPreloadingWindow();

        setTimeout(function () {
            document.location = url;
        }, 300);

		
	}

	/// function is called on show all package tours button click
	function showAllPackageToursClick(searchResultsURL) {

		var fromDate = new Date();
		var fromTicks = GetTicksFromDate(fromDate);
		var toDate = new Date();
		toDate.setDate(toDate.getDate() + 7);
		var toTicks = GetTicksFromDate(toDate);
		/// set accommodation type group id: 3 = private accommodation, 4 = hotels

		//    var url = "/Accommodation/PackageTourSearchResults.aspx";
		//    url += "?searchTabID=1";
		var url = searchResultsURL;
		if (searchResultsURL.indexOf('?') != -1) {
			url += "&searchTabID=0";
		}
		else {
			url += "?searchTabID=0";
		}

		var accommodationTypeGroupID = "6";
		url += "&objectTypeGroupID=" + accommodationTypeGroupID;
		url += "&languageID=" + languageIDSetting;
		url += "&searchSupplier=" + searchSuppliersSetting;
		url += "&currencyID=" + currencyIDFromLanguage(languageIDSetting);

		// Save all info into a cookie so that the info can be restored on user return
		saveQueryStringInCookie(url);
		//    document.location = url;
		redirectSearchControl(url, jQuery("#LinkLocationSettingAccommodation"), jQuery("#SearchResultsAddressSettingAccommodation"));
	}

	/// function is called on search button click in package tour tab
	function searchPackageTourClick(searchResultsURL) {
		//var url = "/Accommodation/PackageTourSearchResults.aspx";
		//url += "?searchTabID=1";
		var url = searchResultsURL;
		if (searchResultsURL.indexOf('?') != -1) {
			url += "&searchTabID=1";
		}
		else {
			url += "?searchTabID=1";
		}


		var destintionAirportTextBox = jQuery("#airportNameInput");
		var destinationComboBox = jQuery("#destinationsSelectPackageTour");
		var destinationComboBoxValue = destinationComboBox.val();
		if (destinationComboBoxValue == null || destinationComboBoxValue == "") {
			var destinationsAirportHiddenField = jQuery("#selectedAirportHiddenField");
			if (destinationsAirportHiddenField != null) {
				var destinationAirportTextBoxValue = destinationsAirportHiddenField.val();
				if (destinationAirportTextBoxValue == null || destinationAirportTextBoxValue == "") {
					var unknownDestinationAirportLabel = jQuery("#unknownDestinationAirport");
					unknownDestinationAirportLabel.attr("class", "destinationError");
					destintionAirportTextBox.val("");

					return "";
				}
				else {
					var unknownDestinationAirportLabel = jQuery("#unknownDestinationAirport");
					unknownDestinationAirportLabel.attr("class", "hideClass");
				}
			}
		}



		if (destinationComboBoxValue == null || destinationComboBoxValue == "") {
			if (destintionAirportTextBox.val() == null || destintionAirportTextBox.val() == "") {
				var unknownDestinationAirportLabel = jQuery("#unknownDestinationAirport");
				unknownDestinationAirportLabel.attr("class", "destinationError");
				destintionAirportTextBox.val("");

				return "";
			}
			else {
				var unknownDestinationAirportLabel = jQuery("#unknownDestinationAirport");
				unknownDestinationAirportLabel.attr("class", "hideClass");
			}
		}



		var destintionTextBox = jQuery("#destinationNamePackageInput");
		var destinationComboBox = jQuery("#destinationsSelectPackageTour");
		var destinationComboBoxValue = destinationComboBox.val();
		if (destinationComboBoxValue == null || destinationComboBoxValue == "") {
			var destinationsHiddenField = jQuery("#selectedDestinationsPackageHiddenField");
			if (destinationsHiddenField != null) {
				var destinationTextBoxValue = destinationsHiddenField.val();
				if (destinationTextBoxValue == null || destinationTextBoxValue == "") {
					var unknownDestinationLabel = jQuery("#unknownDestinationPackageTour");
					unknownDestinationLabel.attr("class", "destinationError");
					destintionTextBox.val("");

					return "";
				}
				else {
					var unknownDestinationLabel = jQuery("#unknownDestinationPackageTour");
					unknownDestinationLabel.attr("class", "hideClass");
				}
			}
		}


		if (destinationComboBoxValue == null || destinationComboBoxValue == "") {
			if (destintionTextBox.val() == null || destintionTextBox.val() == "") {
				var unknownDestinationLabel = jQuery("#unknownDestinationPackageTour");
				unknownDestinationLabel.attr("class", "destinationError");
				destintionTextBox.val("");
				return "";
			}
			else {
				var unknownDestinationLabel = jQuery("#unknownDestinationPackageTour");
				unknownDestinationLabel.attr("class", "hideClass");
			}
		}



		url += getVariableForQueryString("categoryID", "categoriesSelectPackageTour");
		url += getVariableForQueryString("countryID", "countriesSelectPackageTour");
		url += getVariableForQueryString("regionID", "regionsSelectPackageTour");
		url += getVariableForQueryString("destinationID", "destinationsSelectPackageTour");
		url += getVariableForQueryString("objectTypeID", "objectTypeSelectPackageTour");
		url += getVariableForQueryString("numberOfStars", "numberOfStarsSelectPackageTour");
		url += getVariableForQueryString("priceFrom", "priceFromInputPackageTour");
		url += getVariableForQueryString("priceTo", "priceToInputPackageTour");
		url += getVariableForQueryString("balcony", "balconyInputPackageTour");
		url += getVariableForQueryString("priceType", "priceTypeSelectPackageTour");
		url += getVariableForQueryStringAsDateTicks("from", "dateFromPackageTour");
		url += getVariableForQueryStringAsDateTicks("to", "dateToPackageTour");
		url += getVariableForQueryString("destinationName", "selectedDestinationsPackageHiddenField");
		url += getVariableForQueryString("airport", "selectedAirportHiddenField");
		url += getVariableForQueryString("packageTourDuration", "packageTourDurationSelect");

		url += "&onlyOnSpecialOffer=" + document.getElementById("onlyPackageToursOnSpecialOfferCheckBox").checked;

		/// set accommodation type group id: 6 = package tours
		var accommodationTypeGroupID = "6";
		url += "&objectTypeGroupID=" + accommodationTypeGroupID;
		url += "&languageID=" + languageIDSetting;
		url += "&searchSupplier=" + searchSuppliersSetting;
		url += "&currencyID=" + currencyIDSetting;

		url += getVariableForQueryString("persons", "personsSelectPackage");
		url += getVariableForQueryString("children", "childrenSelectPackage");
		url += getChildrenForSearchPackageTour();

		// Save all info into a cookie so that the info can be restored on user return
		saveQueryStringInCookie(url);
		//    document.location = url;
		redirectSearchControl(url, jQuery("#LinkLocationSettingAccommodation"), jQuery("#SearchResultsAddressSettingAccommodation"));
	}


	///		if 'elementId' exists in DOM returns string "&variableName=elementValue"
	///     variableName is name of a variable that will appear in query string
	///     elementId id ID of an element to look for in DOM.
	function getVariableForQueryStringAny(variableName, elementId) {
		var element = jQuery("#" + elementId);
		if (element != null) {
			var elementValue;
			if (element.is(":checkbox")) {
				elementValue = element.is(':checked') ? "1" : "0";
			} else {
				elementValue = element.val();
			}
			if (elementValue != null && elementValue != "") {
				return "&" + variableName + "=" + elementValue;
			}
		}
		return "";
	}

	/// if 'elementId' exists in DOM returns string "&variableName=elementValue"
	///     variableName is name of a variable that will appear in query string
	///     elementId id ID of an element to look for in DOM.
	function getVariableForQueryString(variableName, elementId) {
		var element = jQuery("#" + elementId);
		if (element != null) {
			var elementValue = element.val();
			if (elementValue != null && elementValue != "" && elementValue != false) {
				return "&" + variableName + "=" + elementValue;
			}
		}
		return "";
	}
	/// if elementId exists in DOM returns string "&variableName=ticks"
	///     variableName is name of a variable that will appear in query string
	///     elementId id ID of an element to look for in DOM. It must contain a date.
	function getVariableForQueryStringAsDateTicks(variableName, elementId, timeElementId) {
		var element = jQuery("#" + elementId);
		timeElementId = jQuery("#" + timeElementId);
		if (element != null) {
			var elementValue = element.val();
			if (elementValue != null && elementValue != "") {
				var dateValue = element.datepicker("getDate");
				if (timeElementId.get(0) && timeElementId.timepicker) {
					var time = timeElementId.timepicker('getTime').split(":");
					if (time.length = 2) {
						dateValue.setHours(parseInt(time[0]));
						dateValue.setMinutes(parseInt(time[1]));
					}
				}
				// ticks = number of miliseconds since 01.01.1970.
				var ticks = GetTicksFromDate(dateValue);
				return "&" + variableName + "=" + ticks;
			}
		}
		return "";
	}

	/// function converts date to ticks
	function GetTicksFromDate(dateValue) {
		var ticks = dateValue.getTime() - (dateValue.getTimezoneOffset() * 60 * 1000);
		return ticks;
	}

	/// function saves all variables from query string in cookie
	///     url is string from wich variables from query string will be extracted and saved in cookie
function saveQueryStringInCookie(url) {
	if (tryToReadFromCookie('cookieconsent_status', 'deny') == 'deny') {
		return;
	}
	try {
		var querystring = url.substring(url.indexOf('?') + 1);
		var parameters = querystring.split('&');

		var activeUntil = (new Date()).getTime() + 1000 * 60 * 30;
		var activeUntilDate = new Date();
		activeUntilDate.setTime(activeUntil);

		var i;
		for (i = 0; i < parameters.length; i++) {
			document.cookie = parameters[i] + "; expires=" + activeUntilDate.toString() + "; path=/";
		}
	}
	catch (a) {

	}
}

	/// function tries to read parameterName from cookie. If it succeddes returns that value, otherwise returns default value.
	///     parameterName is name of a variable to look for in a cookie
	///     defaultValue if value that will be returned if parameterName is not found in cookie
	function tryToReadFromCookie(parameterName, defaultValue) {
		if (document.cookie != null && document.cookie != "") {
			var cookieValues = document.cookie.split(";");

			for (i = 0; i < cookieValues.length; i++) {
				var pair = cookieValues[i].split('=');
				if (pair.length > 1) {
					pair[0] = pair[0].trim();
					pair[1] = pair[1].trim();

					if (pair[0].toLowerCase() == parameterName.toLowerCase()) {
						return pair[1];
					}
				}
			}
		}
		/// parameterName not found, return default value
		return defaultValue;
	}



	/*** EVENT HANDLERS ***/

	/// function is called on objectTypeChange in accommodation tab to reload the allowed categories, countries, regions and destinations!
	function objectTypeOnChangeAccommodation(proxyPath) {
		rebindSearchFieldsInAccommodationTab(proxyPath);
	}
	/// function is called on categoriesSelectOnChange in accommodation tab to reload the allowed categories, countries, regions and destinations!
	function categoriesSelectOnChangeAccommodation(proxyPath) {
		rebindSearchFieldsInAccommodationTab(proxyPath);
	}
	/// function is called on categoriesSelectOnChange in package tour tab to reload the allowed categories, countries, regions and destinations!
	function categoriesSelectOnChangePackageTour() {
		rebindSearchFieldsInPackageTourTab();
	}
	/// function is called on categoriesSelectOnChange in transportation tab to reload the allowed categories, countries, regions and destinations!
	function categoriesSelectOnChangeTransportation() {
		rebindSearchFieldsInTransportationTab();
	}

	/// function is called on countriesSelectOnchange in accommodation tab and it hides the appropritate regions and destinations
	function countriesSelectOnChangeAccommodation() {
		updateRegionsList(regionsListAccommodation, visibleRegionsIdListAccommodation, "countriesSelectAccommodation", "regionsSelectAccommodation");
		updateDestinationsList(destinationsListAccommodation, visibleRegionsIdListAccommodation, "regionsSelectAccommodation", "destinationsSelectAccommodation");
	}
	/// function is called on countriesSelectOnchange in package tour tab and it hides the appropritate regions and destinations
	function countriesSelectOnChangePackageTour() {
		updateRegionsList(regionsListPackageTour, visibleRegionsIdListPackageTour, "countriesSelectPackageTour", "regionsSelectPackageTour");
		updateDestinationsList(destinationsListPackageTour, visibleRegionsIdListPackageTour, "regionsSelectPackageTour", "destinationsSelectPackageTour");
	}
	/// function is called on regionsSelectOnChange in accommodation tab and hides the required destinations.
	function regionsSelectOnChangeAccommodation() {
		var $ = jQuery;
		$('#regionsSelectAccommodation_advanced').val($('#regionsSelectAccommodation_default').val());
		setTimeout(function () {
			jQuery('#regionsSelectAccommodation_advanced').dropkick('refresh');
		}, 50);
		
		//updateDestinationsList(destinationsListAccommodation, visibleRegionsIdListAccommodation, "regionsSelectAccommodation", "destinationsSelectAccommodation");
	}
	function regionsSelectOnChangeAccommodationAdvanced() {
		var $ = jQuery;
		$('#regionsSelectAccommodation_default').val($('#regionsSelectAccommodation_advanced').val());
		
		jQuery("#regionsSelectAccommodation_default").combobox('setValue', {
			text: jQuery('#regionsSelectAccommodation_advanced option:selected').text(),
			val: jQuery('#regionsSelectAccommodation_advanced').val()
		});
		

		//updateDestinationsList(destinationsListAccommodation, visibleRegionsIdListAccommodation, "regionsSelectAccommodation_advanced", "destinationsSelectAccommodation_advanced");
	}
	/// function is called on regionsSelectOnChange in package tour tab and hides the required destinations.
	function regionsSelectOnChangePackageTour() {
		updateDestinationsList(destinationsListPackageTour, visibleRegionsIdListPackageTour, "regionsSelectPackageTour", "destinationsSelectPackageTour");
	}

	/// function will read period and packageTourId from periods drop down list and will redirect page to package tour details with these parameters in query string
	function redirectToPackageTourDetailWithSelectedPeriod(packageTourID, currencyID, detailsURL) {
		var periodsSelectID = "periodsSelect_" + packageTourID;

		var period = jQuery("#" + periodsSelectID).val();
		var numberOfDays = jQuery("#" + periodsSelectID + " option:selected").attr("numberOfDays");

		var url = detailsURL;
		var prefix = "?";
		if (url.indexOf('?') != '-1') {
			prefix = "&";
		}
		url += prefix + "languageID=" + languageIDSetting + "&packageTourID=" + packageTourID + "&period=" + period + "&tab=reservationsTab" + "&searchSupplier=" + searchSuppliersSetting;

		if (numberOfDays != undefined && numberOfDays != null) {
			var numberOfNights = numberOfDays;
			url += "&numberOfDays=" + numberOfNights;
		}

		if (currencyID != undefined && currencyID != null && currencyID != "") {
			url += "&currencyID=" + currencyID;
		}

		document.location = url;
	}

	///function will return currencyID from languageID
	function currencyIDFromLanguage(languageID) {
		var currencyIDFromQueryString = getValueFromQueryString("currencyID");

		if (currencyIDFromQueryString != "") {
			return currencyIDFromQueryString;
		}

		switch (languageID) {
			case "dk":
			case "da":
				return _CURRENCYIDDKK;
				break;
			default:
				return _CURRENCYIDDKK;
				break;
		}
	}

	/// returns the value of the given parameter from the query string
	/// if that parameter does not exist, the function returns an empty string
	function getValueFromQueryString(parameterName) {
		// window.location is the entire url. search.substring(1) is the part after the question mark.
		var qString = window.location.search.substring(1);

		var qStringSplit = qString.split("&");
		for (i = 0; i < qStringSplit.length; i++) {
			var current = qStringSplit[i].split("=");
			if (current[0] == parameterName) {
				return current[1];
			}
		}

		return "";
	}

	function childrenNumberSelectChange(sender, type) {
		///type=1 -> accommodation
		///type=2 -> tours
		var numberOfChildren = jQuery(sender).val();

		jQuery("div[childAgeDiv='" + type + "']").each(function () {
			var position = jQuery(this).attr("position");

			if (position > numberOfChildren) {
				jQuery(this).hide();
			} else {
				jQuery(this).show();
			}
			//	Skriva ili otkriva "parenta" od diva sa padalicom
			if (numberOfChildren > 0) {
				jQuery(this).parent().show();
			} else {
				jQuery(this).parent().hide();
			}
		});
	}

	function childrenNumberSelectChangeReservationTab(sender) {
		var numberOfChildren = jQuery(sender).val();

		jQuery("span[childAgeDiv='1']").each(function () {
			var position = jQuery(this).attr("position");

			if (position > numberOfChildren) {
				jQuery(this).hide();
			} else {
				jQuery(this).show();
			}
		});
	}

	function getChildrenForSearch() {
		var numberOfChildren = jQuery("#childrenSelectAccommodation").val();
		///accomodation -> 1
		///packages     -> 2
		var url = returnChildrenAges(1, numberOfChildren);

		return url;
	}

	function getChildrenForSearchPackageTour() {
		var numberOfChildren = jQuery("#childrenSelectPackage").val();
		///accomodation -> 1
		///packages     -> 2
		var url = returnChildrenAges(2, numberOfChildren);

		return url;
	}

	function returnChildrenAges(type, numberOfChildren) {
		var url = "";

		jQuery("div[childAgeDiv='" + type + "']").each(function () {
			var position = jQuery(this).attr("position");

			if (position <= numberOfChildren) {
				var age = jQuery("select[childAgeSelect='" + type + "']:first", this).val();

				if (url.length > 0) {
					url += ",";
				}
				url += age;
			}
		});

		if (url.length > 0) {
			url = "&childrenAges=" + url;
		}

		return url;
	}

	function getChildrenForReservationTab(numberOfChildren) {

		var childrenAges = "";

		jQuery("span[childAgeDiv='1']").each(function () {
			var position = jQuery(this).attr("position");

			if (position <= numberOfChildren) {
				var age = jQuery("select[childAgeSelect='1']:first", this).val();

				if (childrenAges.length > 0) {
					childrenAges += ",";
				}
				childrenAges += age;
			}
		});

		return childrenAges;
	}

	function getAdvancedSearchData(url) {
		var $ = jQuery;
		// Fetch all the checked attributes/categories on the advanced search form
		// hldr prefix for holder (a logical region)

		/************* RESET THE INTERNAL OBJECT ATTRIBUTE FILTERS ARRAY ***********/
		filterHelper.objectAttributeFilters = [];
		filterHelper.unitAttributeFilters = [];
		var objectTypes = [];
		$("[data-object-id]:checked").each(function (index) {
			if (objectTypes.indexOf($(this).attr('data-object-id')) == -1) {
				objectTypes.push($(this).attr('data-object-id'));
			}
		});
		if (objectTypes.length) {
			url += '&objectTypes=' + objectTypes.join(',');
		}

		var hldrFacilities = $("#facilities, .optional-search-placeholder");
		$("[data-filter-id]:checked", hldrFacilities).each(function (index) {
			var attributeFilter = filterHelper.createAttributeFilterFromCheckbox($(this));
		});

		var hldrActivities = $("#activities");
		$("[data-filter-id]:checked", hldrActivities).each(function (index) {
			var attributeFilter = filterHelper.createAttributeFilterFromCheckbox($(this));
		});

		var hldrCategory = $("#category");
		var categoryQueryStringParameter = "";
		$("[data-filter-id]:checked", hldrCategory).each(function (index) {
			var prefix = "";
			if (categoryQueryStringParameter) {
				prefix = ",";
			}
			categoryQueryStringParameter += prefix + $(this).attr('data-filter-val');
		});
		if (categoryQueryStringParameter) {
			url += "&numberOfStarsCategory=" + categoryQueryStringParameter;
		}

		var hldrTema = $("#tema");
		var categoryIntersectionID = '';
		$("input:checked", hldrTema).each(function (index) {
			var prefix = "";
			if (categoryIntersectionID) {
				prefix = ",";
			}
			categoryIntersectionID += prefix + $(this).attr("id").replace("t", "");
		});
		if (categoryIntersectionID) {
			url += "&categoryIntersectionID=" + categoryIntersectionID;
		}

		var hldrLocation = $("#location");
		$("[data-filter-id]:checked", hldrLocation).each(function (index) {
			var attributeFilter = filterHelper.createAttributeFilterFromCheckbox($(this));
		});
		filterHelper.createAttributeFilterFromSelect($("#attributeBedroomsInput"));
		var name = $("#objectNameInput").val();
		url = queryString.build("objectName", name, url);
		url = filterHelper.getFilteredUrl(url);

		return url;


	}

	function getOptionalSearchData(url) {
		var $ = jQuery;
		// Fetch all the checked attributes/categories on the advanced search form
		// hldr prefix for holder (a logical region)
		filterHelper.objectAttributeFilters = [];
		filterHelper.unitAttributeFilters = [];
		var optionalSearchPlaceHolder = $(".optional-search-placeholder");
		$("[data-filter-id]:checked", optionalSearchPlaceHolder).each(function (index) {
			var attributeFilter = filterHelper.createAttributeFilterFromCheckbox($(this));
		});

		url = filterHelper.getFilteredUrl(url);

		return url;
	}
	function clearAdvancedSearchForm(selector) {
		var $ = jQuery;
		selector = selector || "#advanced-search-filter";
		$(":input", selector).not(":button, :submit, :reset, :hidden").val("").removeAttr("checked").removeAttr("selected");

		selector = ".optional-search-placeholder";
		$(":input", selector).not(":button, :submit, :reset, :hidden").val("").removeAttr("checked").removeAttr("selected");
	}

	function updateDropkicks() {
		jQuery('.dropkick').dropkick('refresh');
		jQuery("#regionsSelectAccommodation_default").combobox('setValue', {
			text: jQuery('#regionsSelectAccommodation_default option:selected').text(),
			val: jQuery('#regionsSelectAccommodation_default').val()
		});
	}

	function filterDestinationsBySelectedRegion() {
		var $ = jQuery;

		var context = $("#advanced-search-filter");
		var selectedRegion = $("#regionsSelectAccommodation_advanced", context).val();
		
		$("#destinationsSelectAccommodation_advanced option", context).show();
		$("#dk1-destinationsSelectAccommodation_advanced ul li", context).show();
		$("#dk2-destinationsSelectAccommodation_advanced ul li", context).show();

		if (selectedRegion !== "") {
			$("#destinationsSelectAccommodation_advanced option:not([data-region-id=" + selectedRegion + "])", context).each(function () {
				var destinationID = $(this).val();
				if (destinationID !== '') {
					$("#dk1-destinationsSelectAccommodation_advanced ul li[data-value=" + destinationID + "]", context).hide();
					$(this).hide();

					$("#dk2-destinationsSelectAccommodation_advanced ul li[data-value=" + destinationID + "]", context).hide();
					$(this).hide();
				}
			});
		}
		$("#dk1-destinationsSelectAccommodation_advanced ul li", context).removeClass("dk-option-selected");
		$("#dk1-destinationsSelectAccommodation_advanced ul li", context).attr("aria-selected", "false");

		var emptySelect = $("#dk1-destinationsSelectAccommodation_advanced ul li[data-value='']", context);
		emptySelect.addClass("dk-option-selected");
		emptySelect.attr("aria-selected", "true");
		$("#dk1-destinationsSelectAccommodation_advanced div:first", context).text(emptySelect.text());

		$("#dk2-destinationsSelectAccommodation_advanced ul li", context).removeClass("dk-option-selected");
		$("#dk2-destinationsSelectAccommodation_advanced ul li", context).attr("aria-selected", "false");

		emptySelect = $("#dk2-destinationsSelectAccommodation_advanced ul li[data-value='']", context);
		emptySelect.addClass("dk-option-selected");
		emptySelect.attr("aria-selected", "true");
		$("#dk2-destinationsSelectAccommodation_advanced div:first", context).text(emptySelect.text());

		$("#destinationsSelectAccommodation_advanced", context).val("");

	}

function search_button_define_context(context, maybeWithaoutParentform) {
	var $ = jQuery;
	var search_type = $(context).attr("data-button-context");
	$("#hidden-search-button-context").val(search_type);

	//	When advanced search is used on custom results template adavnced search does not have parent form
	if (maybeWithaoutParentform) {
		var columnSearchBox = $('#columSearch');
		if (columnSearchBox.length) {
			columnSearchBox.submit();
		}
	}
}