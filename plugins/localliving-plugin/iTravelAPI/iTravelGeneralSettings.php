<?php

namespace LocalLiving_Plugin\iTravelAPI;

/**
 * iTravelGeneralSettings short summary.
 *
 * iTravelGeneralSettings description.
 *
 * @version 1.0
 * @author goran.zivkovic
 */
/// static class for general settings
class iTravelGeneralSettings
{    
    //public static $iTravelAPIURL = "http://localhost:51066/itravel/api/webservice/itravelapi_3_0.asmx?WSDL"; 
	public static $iTravelAPIURL = "https://localliving.itravelsoftware.com/itravel/api/webservice/itravelapi_3_0.asmx?WSDL"; 
    public static $iTravelAPIUsername = "APIUserLocalliving";
    public static $iTravelAPIPassword = "e9A57q3Ycc";
    
    //define folder paths
    public static $iTravelResourcesFolderPath = "/wp-content/plugins/localliving-plugin/iTravelAPI/XSLTControls/Resources";
    public static $iTravelXSLTFolderPath;
    //define default parameters
    public static $iTravelDefaultLanguageID = "da";
    public static $iTravelDefaultCurrencyID = 208;
    //define paths to default pages
    //general
	public static $iTravelPageBookingFormPath = "/booking_form";
    //accomodation
    public static $iTravelPageAccommodationSearchFormPath = "/search";
    //public static $iTravelPageAccommodationSearchResultsPath = "/search-results";
    public static $iTravelPageAccommodationSearchResultsPath = "/on-line-booking";
    
    public static $iTravelPageAccommodationDetailedDescriptionPath = "/detaljer";

    //define paths to default xslt stylesheets
    //accomodation
    /****/
    public static $iTravelXSLTPopularAccommodationSearchResultsPath =
	    "/wp-content/plugins/localliving-plugin/iTravelAPI/XSLTControls/XSLStyleSheets/PopularAccommodationSearchResults.xslt";
    public static $iTravelXSLTAccommodationSearchResultsPath =
	    "/wp-content/plugins/localliving-plugin/iTravelAPI/XSLTControls/XSLStyleSheets/AccommodationSearchResults.xslt";
	public static $iTravelXSLTAccommodationSearchResultsABClassPath =
		"/wp-content/plugins/localliving-plugin/iTravelAPI/XSLTControls/XSLStyleSheets/AccommodationSearchResultsABClass.xslt";
    public static $iTravelXSLTAccommodationSearchResultsFullUnitsPath =
	    "/wp-content/plugins/localliving-plugin/iTravelAPI/XSLTControls/XSLStyleSheets/AccommodationSearchResultsFullUnits.xslt";
    public static $iTravelXSLTAccommodationCategorySearchResultsPath =
	    "/wp-content/plugins/localliving-plugin/iTravelAPI/XSLTControls/XSLStyleSheets/AccommodationCategorySearchResults.xslt";
    public static $iTravelXSLTAccommodationDetailedDescriptionPath =
	    "/wp-content/plugins/localliving-plugin/iTravelAPI/XSLTControls/XSLStyleSheets/AccommodationDetailedDescription.xslt";
    public static $iTravelXSLTAccommodationDetailedDescriptionUnitListPath =
	    "/wp-content/plugins/localliving-plugin/iTravelAPI/XSLTControls/XSLStyleSheets/AccommodationDetailedDescriptionUnitList.xslt";
    public static $iTravelXSLTAccommodationSearchFormPath = "";
	public static $iTravelXSLTAccommodationCartPath = "/wp-content/plugins/localliving-plugin/iTravelAPI/XSLTControls/XSLStyleSheets/AccommodationCart.xslt";
    public static $iTravelXSLTAccommodationsMapPath =
	    "/wp-content/plugins/localliving-plugin/iTravelAPI/XSLTControls/XSLStyleSheets/AccommodationsMap.xslt";
	
    //tours - NOT USED
    public static $iTravelXSLTTourSearchFormPath = "";
    public static $iTravelXSLTTourSearchResultsPath = "";
    public static $iTravelXSLTTourDetailedDescriptionPath = "";
    public static $iTravelXSLTTourDetailsAccommodationUnitListPath = "";
    //transportation - NOT USED
    public static $iTravelXSLTTransportationSearchFormPath = "";
    public static $iTravelXSLTTransportationSearchResultsPath = "";
    public static $iTravelXSLTTransportationDetailedDescriptionPath = "";
    public static $iTravelXSLTTransportationDetailsReservationsTabPath = "";
    //tours - NOT USED
    public static $iTravelPageTourSearchFormPath = "";
    public static $iTravelPageTourSearchResultsPath = "";
    public static $iTravelPageTourDetailedDescriptionPath = "";
    //transportation - NOT USED
    public static $iTravelPageTransportationSearchFormPath = "";
    public static $iTravelPageTransportationSearchResultsPath = "";
    public static $iTravelPageTransportationDetailedDescriptionPath = "";


    public static $iTraveContactFormTemplateURL = "/wp-content/plugins/localliving-plugin/iTravelAPI/XSLTControls";

    //static files paths
    public static $iTravelScriptsPath = "/wp-content/plugins/localliving-plugin/iTravelAPI/XSLTControls/Script";
    public static $iTravelImagesPath = "/wp-content/plugins/localliving-plugin/iTravelAPI/XSLTControls/Css/Images";
    public static $iTravelProxyPath = "/wp-content/plugins/localliving-plugin/iTravelAPI/ProxyWebService.php?functionName=";
	
	//	A variable that holds 
	protected static $itravel_urls = array(
					'accommodation_search_results' => array(
						'en'	=> '/en/online-booking',
						'dk'	=> '/on-line-booking'
					),
					'accommodation_details' => array(
						'en'	=> '/en/detailed-description',
						'dk'	=> '/detaljer'
					),
                    'accommodation_search_results_special_offers' => array(
                        'en'    => '/en/local_living_special_offers',
                        'dk'    => '/local_living_tilbud'
                        ),
					'booking_form' => array(
                        'en'    => '/en/booking_form',
                        'dk'    => '/booking_form'
                        )
				);
	
    protected static $itravel_currencies = array(
                    'dk' => 208,
					'en' => 978,
                    'da' => 208
                    );
    
	//	returns accommodation search results path depending on the language requested
	public static function AccommodationSearchResultsUrl( $lang = '' )
	{
		if( !empty( iTravelGeneralSettings::$itravel_urls['accommodation_search_results'][$lang]) ){
			return iTravelGeneralSettings::$itravel_urls['accommodation_search_results'][$lang];	
		}
		return iTravelGeneralSettings::$itravel_urls['accommodation_search_results']['dk'];
	}
    //	returns accommodation search results for special offers path depending on the language requested
	public static function AccommodationSearchResultsSpecialOffersUrl( $lang = '' )
	{
		if( !empty( iTravelGeneralSettings::$itravel_urls['accommodation_search_results_special_offers'][$lang]) ){
			return iTravelGeneralSettings::$itravel_urls['accommodation_search_results_special_offers'][$lang];	
		}
		return iTravelGeneralSettings::$itravel_urls['accommodation_search_results_special_offers']['dk'];
	}
	//	returns accommodation search results path depending on the language requested
	public static function AccommodationDetailsUrl( $lang = '' )
	{
		if( !empty( iTravelGeneralSettings::$itravel_urls['accommodation_details'][$lang]) ){
			return iTravelGeneralSettings::$itravel_urls['accommodation_details'][$lang];	
		}
		return iTravelGeneralSettings::$itravel_urls['accommodation_details']['dk'];
	}	
	//	returns accommodation search results path depending on the language requested
	public static function BookingFormUrl( $lang = '' )
	{
		if( !empty( iTravelGeneralSettings::$itravel_urls['booking_form'][$lang]) ){
			return iTravelGeneralSettings::$itravel_urls['booking_form'][$lang];	
		}
		return iTravelGeneralSettings::$itravel_urls['booking_form']['dk'];
	}	
    
    public static function GetCurrencyID( $lang = '' )
    {
        if( empty( $lang )){
			return iTravelGeneralSettings::$iTravelDefaultCurrencyID;	
		}
		return iTravelGeneralSettings::$itravel_currencies[$lang];
    }
    
}

class APISoapFault{
	public static $INIT_API_FAULT							= 500;
	public static $GETAPISETTINGS							= 1;
	public static $GETSEARCHRESULTS							= 2;
	public static $GETTOURSEARCHRESULTS						= 3;
	public static $GETTRANSPORTATIONSEARCHRESULTS			= 4;
	public static $GETDETAILEDDESCRIPTION					= 5;
	public static $GETTOURDETAILEDDESCRIPTION				= 6;
	public static $GETTRANSPORTATIONDETAILEDDESCRIPTION		= 7;
	public static $GETSEARCHFIELDS							= 8;
	public static $GETALLDESTINATIONS						= 9;
	public static $GETDESTINATIONS							= 10;
	public static $GETALLSEODATA							= 11;
	public static $USERCREDENTIALS							= 12;
	public static $CUSTOMERINSERT							= 13;
	public static $GETALLRESERVATIONS						= 14;
	public static $CHANGERESERVATIONSTATUS					= 15;
	public static $GETRESERVATION							= 16;
	public static $GETALLTRANSACTIONS						= 17;
	public static $GETRESERVATIONCANNOTSTARTDATES			= 18;
	public static $GETRESERVATIONCANNOTENDDATES				= 19;
	public static $GETPASSENGERSONRESERVATION				= 20;
	public static $PASSENGERUPDATE							= 21;
	public static $CREATERESERVATION						= 22;
	public static $GETREGIONS								= 23;
}