<?php

namespace LocalLiving_Plugin\iTravelAPI;

//session_start();
///generate cartID which is unique per session
use DateTime;
use DOMDocument;
use LocalLiving_Plugin\iTravelAPI\Helper\ResourceManager;
use LocalLiving_Plugin\iTravelAPI\Helper\XMLSerializer;
use SoapClient;
use SoapFault;
use SoapHeader;
use XSLTProcessor;

if (empty($_SESSION['iTravelCartID'])) {
    $_SESSION['iTravelCartID'] = uniqid();
}

include_once("Helper/AddResourcesToXML.php");
include_once("Helper/XMLSerializer.php");
include_once("iTravelGeneralSettings.php");
require_once("Helper/LocalCache.php");

/// initialize the API
static $iTravelSoapClient;
$cacheClient = new Helper\LocalCache();

function InitAPI()
{
    global $iTravelSoapClient;
    global $cacheClient;
    if (!isset($iTravelSoapClient)) {
        try {
            //define username and password
            $authHeader = array('Username' => iTravelGeneralSettings::$iTravelAPIUsername, 'Password' => iTravelGeneralSettings::$iTravelAPIPassword);
            $header = new SoapHeader('http://tempuri.org/', 'AuthHeader', $authHeader, false);


            $apiWebService = new SoapClient(
                iTravelGeneralSettings::$iTravelAPIURL,
                array(
                    "connection_timeout" => 120,
                    'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP,
                    'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
                    'cache_wsdl' => WSDL_CACHE_BOTH, // Caching is handled by Lemax_API_Local_Cache,
                )
            );
            $apiWebService->__setSoapHeaders($header);
            $iTravelSoapClient = $apiWebService;
        } catch (SoapFault $fault) {
            return null;
        }
        $soapend = microtime(true) * 1000;
    }
    return $iTravelSoapClient;
}

function GetParameterFromGetOrPost($queryString, $defaultValue, $includeCookie = false)
{
    $inputParameters = file_get_contents('php://input');
    parse_str($inputParameters, $queryStringParameters);
    $returnValue = $defaultValue;

    if (isset($_GET[$queryString]) && (!empty($_GET[$queryString]) || $_GET[$queryString] == '0')) {
        $returnValue = $_GET[$queryString];
    } elseif (isset($_POST[$queryString]) && (!empty($_POST[$queryString]) || $_POST[$queryString] == '0')) {
        $returnValue = $_POST[$queryString];
    }
    ///look into php:input (when webservice calls)
//    elseif (!empty($queryStringParameters) && isset($queryStringParameters[$queryString]) && (!empty($queryStringParameters[$queryString]) || $queryStringParameters[$queryString] == '0')) {
//        $returnValue = $queryStringParameters[$queryString];
//    }
	elseif ($includeCookie && isset($_COOKIE[$queryString]) && (!empty($_COOKIE[$queryString]) || $_COOKIE[$queryString] == '0')) {
        $returnValue = $_COOKIE[$queryString];
    }
    
    if (strtolower($returnValue) == 'false') {
        $returnValue = false;
    }
    
    return $returnValue;
}

function GetParameterFromGetOrPostInArray($queryString, $defaultValue)
{
    $returnArray = array();
    $parameter = GetParameterFromGetOrPost($queryString, $defaultValue);
    if (!empty($parameter)) {
        $returnArray = explode(',', $parameter);
    }

    return $returnArray;
}

/// method used to load and transform xml file from xslt file, and then to echo the result to the page
function TransformResult($iTravelAPIResponse, $xsltParameters, $xsltStyleSheetPath, $iTravelAPIResultClassName, $iTravelAPIClassName)
{
    $iTravelAPIResponseXML = '<?xml version="1.0"?>' . XMLSerializer::generateXmlFromArray($iTravelAPIResponse, "root");
    $iTravelAPIResponseXML = str_replace($iTravelAPIResultClassName, $iTravelAPIClassName, $iTravelAPIResponseXML);

    $translator = new ResourceManager(realpath($_SERVER['DOCUMENT_ROOT'] . iTravelGeneralSettings::$iTravelResourcesFolderPath));
    $resourcesXML = $translator->ResourcesToXml();
    ///read all query string and put it as parameter in xslt
    $queryStringXML = '<QueryString>';
    foreach ($_GET as $key => $value) {
        $queryStringXML .= '<' . $key . '>' . urlencode($value) . '</' . $key . '>';
    }
    $queryStringXML .= '</QueryString>';
    $iTravelAPIResponseXML = str_replace('</' . $iTravelAPIClassName . '>', $resourcesXML . $queryStringXML . '</' . $iTravelAPIClassName . '>', $iTravelAPIResponseXML);

    if (!empty($_GET['debug']) && $_GET['debug'] == '1') {
        echo htmlentities($iTravelAPIResponseXML);
    }

    $xp = new XsltProcessor();
    $xsl = new DomDocument;
    $xsl->load(realpath($_SERVER['DOCUMENT_ROOT'] . $xsltStyleSheetPath));
    $xp->importStylesheet($xsl);
    $xml = new DomDocument;
    $xml->loadXML($iTravelAPIResponseXML);
    $xp->setParameter("", $xsltParameters);

    if ($searchBoxHtml = $xp->transformToXML($xml)) {
        $cleanedHtml = str_replace("<?xml version=\"1.0\"?>", "", $searchBoxHtml);
        return $cleanedHtml;
    } else {
        $xsltTransformError = libxml_get_last_error();
        return 'Error while processing XSLT: ' . $xsltTransformError->message;
    }
}

/// method used to load and transform xml file from xslt file, and then to echo the result to the page
function TransformAndEchoResult($iTravelAPIResponse, $xsltParameters, $xsltStyleSheetPath, $iTravelAPIResultClassName, $iTravelAPIClassName)
{
    echo TransformResult($iTravelAPIResponse, $xsltParameters, $xsltStyleSheetPath, $iTravelAPIResultClassName, $iTravelAPIClassName);
}

function ticks_to_date($ticks)
{
    return new DateTime(floor(($ticks - 621355968000000000) / 10000000));
}

function GeneratAttributeFilterList($additionalFilters, $currentFilterList)
{
    if (!empty($additionalFilters)) {
        foreach ($additionalFilters as $additionalObjectFilter) {
            $filterArray = explode('_', $additionalObjectFilter);
            $comparisonType = 'Equals';
            switch ($filterArray[1]) {
                case '2':
                    $comparisonType = 'GreaterOrEqualThan';
                    break;
                case '3':
                    $comparisonType = 'LessOrEqualThan';
                    break;
                case '4':
                    $comparisonType = 'Between';
                    break;
                case '5':
                    $comparisonType = 'Like';
                    break;
                case '1':
                default:
                    $comparisonType = 'Equals';
                    break;
            }

            $filter = array(
                'AttributeID' => intval($filterArray[0]),
                'AttributeValue' => $filterArray[2],
                'ComparisonType' => $comparisonType
            );

            if (count($filterArray) > 3) {
                $filter['AttributeValue2'] = $filterArray[3];
            }

            array_push($currentFilterList, $filter);
        }
    }

    return $currentFilterList;
}

class GetAPISettings
{
    #region Properties
    public $languageID;
    public $currencyID;
    public $searchSuppliers;
    public $xsltPath;
    public $searchResultsPage;
    public $showDestinationAsDropDownList;
    #endregion

    public function __construct()
    {
        $this->languageID = GetParameterFromGetOrPost('languageID', iTravelGeneralSettings::$iTravelDefaultLanguageID);
        $this->currencyID = GetParameterFromGetOrPost('currencyID', iTravelGeneralSettings::$iTravelDefaultCurrencyID);
        $this->searchSuppliers = GetParameterFromGetOrPost('searchSuppliers', '');
        $this->showDestinationAsDropDownList = true;
        $this->xsltPath = iTravelGeneralSettings::$iTravelXSLTAccommodationSearchFormPath;
    }

    // Return API response object.
    public function GetAPIResponse()
    {
        $getApiSettingsParameters = array(
            'LanguageID' => $this->languageID,
            'CurrencyID' => $this->currencyID,
            'SearchSuppliers' => $this->searchSuppliers
        );

        /// Call the API
        $apiSettings = InitAPI();
        if (isset($apiSettings)) {
            try {
                $apiSettings = $apiSettings->GetApiSettings(array('getApiSettingsParameters' => $getApiSettingsParameters));
                return $apiSettings;
            } catch (SoapFault $fault) {
                return itravel_display_soap_fault(APISoapFault::$GETAPISETTINGS);
            }
        } else {
            return itravel_display_soap_fault(APISoapFault::$INIT_API_FAULT);
        }
    }

    // Get API response object, transform it via XSLT processor and echo the resulting HTML.
    public function TransformAndEchoAPIResponse()
    {
    }
    // Get API response object, transform it via XSLT processor and return the resulting HTML as string.
    public function GetTransformedAPIResponse()
    {
    }

    public function EchoAPISettings()
    {
        $getApiSettingsParameters = array(
            'LanguageID' => $this->languageID,
            'CurrencyID' => $this->currencyID,
            'SearchSuppliers' => $this->searchSuppliers
        );

        ///xslt parameters
        $xsltParameters = array(
            'languageID' => $this->languageID,
            'currencyID' => $this->currencyID,
            'SearchResultsURL' => $this->searchResultsPage,
            'ShowDestinationAsDropDownList' => $this->showDestinationAsDropDownList,
            'imagesFolderPath' => iTravelGeneralSettings::$iTravelImagesPath,
            'proxyPath' => iTravelGeneralSettings::$iTravelProxyPath,
            'scriptsFolderPath' => iTravelGeneralSettings::$iTravelScriptsPath,
            'contactFormTemplateURL' => iTravelGeneralSettings::$iTraveContactFormTemplateURL
        );

        /// Call the API
        $apiSettings = InitAPI();
        if (isset($apiSettings)) {
            try {
                $apiSettings = $apiSettings->GetApiSettings(array('getApiSettingsParameters' => $getApiSettingsParameters));
                TransformAndEchoResult($apiSettings, $xsltParameters, $this->xsltPath, "GetApiSettingsResult", "ApiSettings");
            } catch (SoapFault $fault) {
                return itravel_display_soap_fault(APISoapFault::$GETAPISETTINGS);
            }
        } else {
            return itravel_display_soap_fault(APISoapFault::$INIT_API_FAULT);
        }
    }
}

/// class used for getting Accommodation search results
class GetSearchResults
{
    #region Properties
    public $languageID;
    public $currencyID;
    public $searchSuppliers;
    public $xsltPath;
    public $accommodationSearchResults;
    public $searchTabID;
    public $categoryID;
    public $countryID;
    public $regionID;
    public $destinationID;
    public $destinationName;
    public $objectTypeID;
    public $persons;
    public $children;
    public $onlyOnSpecialOffer;
    public $objectTypeGroup;
    public $currentPage;
    public $ignorePriceAndAvailability;
    public $inPriceType;
    public $searchResultsXSLT;
    public $unitFilters;
    public $objectFilters;
    public $outParameterList;
    public $calculatedInfo;
    public $description;
    public $group;
    public $objectPhoto;
    public $toString;
    public $to;
    public $fromString;
    public $from;
    public $accommodationDetailedDescription;
    public $bookingForm;
    public $objectTypeGroupID;
    public $globalDestinationID;
    public $thumbnailWidth;
    public $thumbnailHeight;
    public $sortParameterList;
    public $pageSize;
    public $doNotCountMandatoryServices;
    public $searchResultsResponse;
    public $categoryIntersectionID;
    public $categoryUnionID;
    public $customerID;
    public $priceFrom = null;
    public $priceTo = null;
    public $unitCategoryIDList;
    public $objectName;
    public $objectIDList;
    public $toDisplaySpecialOffers = null;

    #endregion

    public function __construct()
    {
        $this->accommodationDetailedDescription = iTravelGeneralSettings::$iTravelPageAccommodationDetailedDescriptionPath;
        $this->accommodationSearchResults = iTravelGeneralSettings::$iTravelPageAccommodationSearchResultsPath;
        $this->bookingForm = iTravelGeneralSettings::$iTravelPageBookingFormPath;

        $this->thumbnailWidth = 152;
        $this->thumbnailHeight = 82;
        /// Parse the parameters from query string
        $this->searchTabID = GetParameterFromGetOrPost('searchTabID', 0);
        $this->categoryID = GetParameterFromGetOrPost('categoryID', 0);
        $this->destinationID = GetParameterFromGetOrPost('destinationID', 0);
        if ($this->destinationID == 0) {
            $this->regionID = GetParameterFromGetOrPost('regionID', 0);
            if ($this->regionID == 0) {
                $this->countryID = GetParameterFromGetOrPost('countryID', 0);
            }
        }
        $this->destinationName = GetParameterFromGetOrPost('destinationName', '');
        $this->objectTypeID = GetParameterFromGetOrPost('objectTypeID', 0);
        $this->persons = GetParameterFromGetOrPost('persons', 1);
        $this->children = GetParameterFromGetOrPost('children', 0);
        $this->globalDestinationID = GetParameterFromGetOrPost('globalDestinationID', 0);

        $this->from = new DateTime();
        $this->fromString = GetParameterFromGetOrPost('from', '');
        if (!empty($this->fromString)) {
            $this->from->setTimestamp($this->fromString);
        } else {
            $this->from = null;
        }

        $this->to = new DateTime();
        $this->toString = GetParameterFromGetOrPost('to', '');
        if (!empty($this->toString)) {
            $this->to->setTimestamp($this->toString);
        } else {
            $this->to = null;
        }

        $this->onlyOnSpecialOffer = GetParameterFromGetOrPost('onlyOnSpecialOffer', 'false');
        $this->objectTypeGroup = GetParameterFromGetOrPostInArray('objectTypeGroupID', 0);
        $this->categoryIntersectionID = GetParameterFromGetOrPostInArray('categoryIntersectionID', 0);
        $this->unitCategoryIDList = GetParameterFromGetOrPostInArray("unitCategoryIDList", 0);
        $this->objectTypeGroupID = GetParameterFromGetOrPost('objectTypeGroupID', 0);
        $this->currentPage = GetParameterFromGetOrPost('currentPage', 0);
        $this->ignorePriceAndAvailability = GetParameterFromGetOrPost('ignorePriceAndAvailability', false);
        $this->inPriceType = GetParameterFromGetOrPost('priceType', 'Total');
        $this->languageID = GetParameterFromGetOrPost('languageID', iTravelGeneralSettings::$iTravelDefaultLanguageID);
        $this->currencyID = GetParameterFromGetOrPost('currencyID', iTravelGeneralSettings::$iTravelDefaultCurrencyID);
        $this->objectName = GetParameterFromGetOrPost('objectName', null);

        $this->objectPhoto = array(
            'ResponseDetail' => 'ObjectPhotos',
            'NumberOfResults' => '100'
        );
        $unitPhoto = array(
            'ResponseDetail' => 'UnitPhotos',
            'NumberOfResults' => '100'
        );
        $this->group = array(
            'ResponseDetail' => 'ObjectDetailedAttributes',
            'NumberOfResults' => '10'
        );

        $this->description = array(
            'ResponseDetail' => 'UnitDescription',
            'NumberOfResults' => '1'
        );
        $mapCoordinates = array(
            'ResponseDetail' => 'MapCoordinates',
            'NumberOfResults' => '1'
        );

        if (!$this->ignorePriceAndAvailability) {
            $this->calculatedInfo = array(
                'ResponseDetail' => 'CalculatedPriceInfo',
                'NumberOfResults' => '1'
            );

            $this->outParameterList = array(
                '0' => $this->objectPhoto,
                '1' => $this->group,
                '2' => $this->description,
                '3' => $this->calculatedInfo,
                '4' => $unitPhoto
            );
        } else {
            $this->outParameterList = array(
                '0' => $this->objectPhoto,
                '1' => $this->group,
                '2' => $this->description,
                '3' => $unitPhoto
            );
        }
        $this->outParameterList[] = $mapCoordinates;
	
	    $this->personsFilter = array(
		    'AttributeID' => 120,
		    'AttributeValue' => $this->persons,
		    'ComparisonType' => 'GreaterOrEqualThan'
	    );
	
	    $this->unitFilters = array(
		    '0' => $this->personsFilter
	    );

        $additionalObjectFilters = GetParameterFromGetOrPostInArray('objectAttributeFilters', null);
        $this->objectFilters = array();
        $this->objectFilters = GeneratAttributeFilterList($additionalObjectFilters, $this->objectFilters);

        $additionalUnitFilters = GetParameterFromGetOrPostInArray('unitAttributeFilters', null);
        $this->unitFilters = GeneratAttributeFilterList($additionalUnitFilters, $this->unitFilters);

        $this->xsltPath = iTravelGeneralSettings::$iTravelXSLTAccommodationSearchResultsPath;

        // SORTING
        $this->sortParameterList = array();
        $sortByPrice = GetParameterFromGetOrPost('sortByPrice', '');
        if (!empty($sortByPrice)) {
            switch ($sortByPrice) {
                case "desc":
                    $sortOrder = 'Descending';
                    break;
                default:
                    $sortOrder = 'Ascending';
                    break;
            }
            array_push($this->sortParameterList, array('SortBy' => 'Price', 'SortOrder' => $sortOrder));
        }

        $sortByStars = GetParameterFromGetOrPost('sortByStars', '');
        if (isset($sortByStars) && !empty($sortByStars)) {
            switch ($sortByStars) {
                case "desc":
                    $sortOrder = 'Descending';
                    break;
                default:
                    $sortOrder = 'Ascending';
                    break;
            }
            array_push($this->sortParameterList, array(
                'SortBy' => 'CustomAttribute',
                'SortOrder' => $sortOrder,
                'AttributeID' => 970,
                'AttributeType' => "Numeric"));
        }
        $this->sortParameterList[] = array('SortBy' => 'Priority', 'SortOrder' => 'Descending');

        $this->pageSize = GetParameterFromGetOrPost('pageSize', 10);
        $this->doNotCountMandatoryServices = GetParameterFromGetOrPost('doNotCountMandatoryServices', null, true);

        $this->priceFrom = GetParameterFromGetOrPost('priceFrom', '0');
        $this->priceTo = GetParameterFromGetOrPost('priceTo', '0');

        $numberOfStarsCategory = GetParameterFromGetOrPost('numberOfStarsCategory', '');

        $this->categoryUnionID = array();
        if ($numberOfStarsCategory != '') {
            $numberOfStarsCategoryArray = explode(',', $numberOfStarsCategory);
            foreach ($numberOfStarsCategoryArray as $value) {
                switch ($value) {
                    case "3":
                        array_push($this->categoryUnionID, "5");
                        break;
                    case "3.5":
                        array_push($this->categoryUnionID, "6");
                        break;
                    case "4":
                        array_push($this->categoryUnionID, "7");
                        break;
                    case "4.5":
                        array_push($this->categoryUnionID, "8");
                        break;
                    case "5":
                        array_push($this->categoryUnionID, "9");
                        break;
                }
            }
        }

        $objectTypeCategory = GetParameterFromGetOrPost('objectTypes', '');
        if ($objectTypeCategory != '') {
            $objectTypeCategoryArray = explode(',', $objectTypeCategory);
            foreach ($objectTypeCategoryArray as $value) {
                $this->categoryID .= ',' . $value;
            }
        }
        $this->categoryID = str_replace('0,', '', $this->categoryID);
    }

    public function GetAPIResponse()
    {
        $fromParameter = null;
        if ($this->from != null) {
            $fromParameter = $this->from->format(DATE_ATOM);
        }
        $toParameter = null;
        if ($this->to != null) {
            $toParameter = $this->to->format(DATE_ATOM);
        }
        $destinationIDList = null;
        $regionIDList = null;
        $countryIDList = null;
        if (!empty($this->destinationID)) {
            $destinationIDList = explode(',', $this->destinationID);
        } else {
            if (!empty($this->regionID)) {
                $regionIDList = explode(',', $this->regionID);
            } else {
                if (!empty($this->countryID)) {
                    $countryIDList = explode(',', $this->countryID);
                }
            }
        }

        $globalDestinationID = null;
        if (!empty($this->globalDestinationID)) {
            $globalDestinationID = explode(',', $this->globalDestinationID);
        }

        // Overrides the page size if not searching for Saturday - Saturday
        if (is_string($fromParameter) && is_string($toParameter)) {
            if (date('N', strtotime($fromParameter)) != "6" || date('N', strtotime($toParameter)) != "6") {
                $this->pageSize = 1000;
            }
        }

        $this->bookingForm = iTravelGeneralSettings::BookingFormUrl($this->languageID);

        $categoryIDList = empty($this->categoryID) ? array() : array_unique(explode(',', $this->categoryID));
        if (is_array($this->categoryIntersectionID)) {
            $categoryIntersectionIDList = array_unique($this->categoryIntersectionID);
        } else {
            $categoryIntersectionIDList = empty($this->categoryIntersectionID) ? array() : array_unique(explode(',', $this->categoryIntersectionID));
        }
        $categoryIDListUnion = array_unique(array_merge($categoryIDList, $categoryIntersectionIDList));

        $getSearchResultsParameters = array(
            'StartDate' => $fromParameter,
            'EndDate' => $toParameter,
            'DestinationIDList' => $destinationIDList,
            'RegionIDList' => $regionIDList,
            'CountryIDList' => $countryIDList,
            'UnitCategoryIDList' => $this->unitCategoryIDList,
            'ObjectTypeIDList' => explode(',', $this->objectTypeID),
            'ObjectTypeGroupIDList' => $this->objectTypeGroup,
            'CategoryIDListUnion' => $this->categoryUnionID,
            'PageSize' => $this->pageSize,
            'CurrentPage' => $this->currentPage,
            'CurrencyID' => $this->currencyID,
            'LanguageID' => $this->languageID,
            'IgnorePriceAndAvailability' => $this->ignorePriceAndAvailability,
            'OnlyOnSpecialOffer' => $this->onlyOnSpecialOffer,
            'InPriceType' => $this->inPriceType,
            'OutParameterList' => $this->outParameterList,
            'UnitAttributeFilterList' => $this->unitFilters,
            'ObjectAttributeFilterList' => $this->objectFilters,
            'ThumbnailWidth' => $this->thumbnailWidth,
            'ThumbnailHeight' => $this->thumbnailHeight,
            'DestinationCodes' => $globalDestinationID,
            'SortParameterList' => $this->sortParameterList,
            'DoNotCountMandatoryServices' => $this->doNotCountMandatoryServices,
            'CategoryIDListIntersection' => $categoryIDListUnion,
            'CustomerID' => $this->customerID,
            'PriceFrom' => $this->priceFrom,
            'PriceTo' => $this->priceTo,
            'ObjectIDList' => $this->objectIDList,
            'ToDisplaySpecialOffers' => $this->toDisplaySpecialOffers
        );

        $searchResultsResponse = null;
        /// Call the API
        $apiSettings = InitAPI();
        if (isset($apiSettings)) {
            try {
                $searchResultsResponse = $apiSettings->GetSearchResults(array('getSearchResultsParameters' => $getSearchResultsParameters));

                if (isset($searchResultsResponse->GetSearchResultsResult->TotalNumberOfResults) && $searchResultsResponse->GetSearchResultsResult->TotalNumberOfResults > 0) {
                    foreach ($searchResultsResponse->GetSearchResultsResult->AccommodationObjectList->AccommodationObject as $accommodationObject) {
                        if (isset($accommodationObject->ObjectURL)) {
                            $accommodationObject->ObjectURL = '/' . trim($accommodationObject->ObjectURL, "/") . '/';
                        }
                    }
                }
	
	            if (isset($_SESSION['localliving_cart']) && $_GET['page'] == 'generer_pdf') {
		            $cart = $_SESSION['localliving_cart'];
		
		            if (isset($searchResultsResponse->GetSearchResultsResult->AccommodationObjectList->AccommodationObject)) {
			            $accommodationObjectList =
				            $searchResultsResponse->GetSearchResultsResult->AccommodationObjectList->AccommodationObject;
			
			            //reorder by the cart
//			            foreach ($cart as $selectedAccommodation) {
//				            $selectedAccommodationIds = array_keys($selectedAccommodation);
//
//				            foreach ($selectedAccommodationIds as $index => $selectedAccommodationId) {
//					            foreach ($accommodationObjectList as $value) {
//						            if($value->ObjectID == $selectedAccommodationId) {
//							            $searchResultsResponse->GetSearchResultsResult->AccommodationObjectList->AccommodationObject[$index] = $value;
//						            }
//					            }
//				            }
//			            }
		            }
	            }

                return $searchResultsResponse;
            } catch (SoapFault $fault) {
                file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/iTravelErrors/" . date_timestamp_get(date_create()), $fault->__toString());
                return itravel_display_soap_fault(APISoapFault::$GETSEARCHRESULTS, false);
            }
        } else {
            return itravel_display_soap_fault(APISoapFault::$INIT_API_FAULT, false);
        }
    }

    // Get API response object, transform it via XSLT processor and echo the resulting HTML.
    public function TransformAndEchoAPIResponse()
    {
    }
    // Get API response object, transform it via XSLT processor and return the resulting HTML as string.
    public function GetTransformedAPIResponse()
    {
    }

    public function GetSearchResultsHTML($parametersArray = array(), $searchResults = null, $dateRangeKey = '')
    {
        if (!empty($this->objectName)) {
            $this->objectFilters[]= array(
                  'AttributeID' => 121,
                  'AttributeValue' => $this->objectName,
                  'ComparisonType' => 'Like'
              );
        }
        $fromParameter = null;
        if ($this->from != null) {
            $fromParameter = $this->from->format(DATE_ATOM);
        }
        $toParameter = null;
        if ($this->to != null) {
            $toParameter = $this->to->format(DATE_ATOM);
        }
        $destinationIDList = null;
        if (!empty($this->destinationID)) {
            $destinationIDList = explode(',', $this->destinationID);
        }
        $regionIDList = null;
        if (!empty($this->regionID)) {
            $regionIDList = explode(',', $this->regionID);
        }
        $countryIDList = null;
        if (!empty($this->countryID)) {
            $countryIDList = explode(',', $this->countryID);
        }
        $globalDestinationID = null;
        if (!empty($this->globalDestinationID)) {
            $globalDestinationID = explode(',', $this->globalDestinationID);
        }
		
        $categoryIDList = empty($this->categoryID) ? array() : array_unique(explode(',', $this->categoryID));
        if (is_array($this->categoryIntersectionID)) {
            $categoryIntersectionIDList = array_unique($this->categoryIntersectionID);
        } else {
            $categoryIntersectionIDList = empty($this->categoryIntersectionID) ? array() : array_unique(explode(',', $this->categoryIntersectionID));
        }
        $categoryIDListUnion = array_unique(array_merge($categoryIDList, $categoryIntersectionIDList));

        $getSearchResultsParameters = array(
            'StartDate' => $fromParameter,
            'EndDate' => $toParameter,
            'DestinationIDList' => $destinationIDList,
            'RegionIDList' => $regionIDList,
            'CountryIDList' => $countryIDList,
            'UnitCategoryIDList' => $this->unitCategoryIDList,
            'ObjectTypeIDList' => explode(',', $this->objectTypeID),
            'ObjectTypeGroupIDList' => $this->objectTypeGroup,
            'CategoryIDListUnion' => $this->categoryUnionID,
            'PageSize' => $this->pageSize,
            'CurrentPage' => $this->currentPage,
            'CurrencyID' => $this->currencyID,
            'LanguageID' => $this->languageID,
            'IgnorePriceAndAvailability' => $this->ignorePriceAndAvailability,
            'OnlyOnSpecialOffer' => $this->onlyOnSpecialOffer,
            'InPriceType' => $this->inPriceType,
            'OutParameterList' => $this->outParameterList,
            'UnitAttributeFilterList' => $this->unitFilters,
            'ObjectAttributeFilterList' => $this->objectFilters,
            'ThumbnailWidth' => $this->thumbnailWidth,
            'ThumbnailHeight' => $this->thumbnailHeight,
            'DestinationCodes' => $globalDestinationID,
            'SortParameterList' => $this->sortParameterList,
            'DoNotCountMandatoryServices' => $this->doNotCountMandatoryServices,
            'CategoryIDListIntersection' => $categoryIDListUnion,
            'CustomerID' => $this->customerID,
            'PriceFrom' => $this->priceFrom,
            'PriceTo' => $this->priceTo,
            'ObjectIDList' => $this->objectIDList
        );

        $parameters = array(
            'countryIDParameter' => $this->countryID,
            'regionIDParameter' => $this->regionID,
            'destinationIDParameter' => $this->destinationID,
            'fromParameter' => $this->fromString,
            'toParameter' => $this->toString,
            'numberOfStarsParameter' => 0,
            'personsParameter' => $this->persons,
            'childrenParameter' => $this->children,
            'childrenAgesParameter' => null,
            'objectTypeIDParameter' => $this->objectTypeID,
            'objectTypeGroupID' => $this->objectTypeGroupID,
            'objectName' => $this->objectName,
            'categoryIDParameter' => $this->categoryID,
            'ignorePriceAndAvailabilityParam' => $this->ignorePriceAndAvailability,
            'onlyOnSpecialOfferParameter' => $this->onlyOnSpecialOffer,
            'urlPrefixParameter' => '',
            'destinationName' => $this->destinationName,
            'priceFromParameter' => $this->priceFrom,
            'priceToParameter' => $this->priceTo,
            'priceTypeParameter' => $this->inPriceType,
            'postaviDirektanLink' => false,
            'BookingLinkInNewWindow' => false,
            'OpenInParent' => false,
            'detailsURL' => $this->accommodationDetailedDescription,
            'SearchPage' => $this->accommodationSearchResults,
            'bookingAddressDisplayURL' => $this->bookingForm,
            'imagesFolderPath' => iTravelGeneralSettings::$iTravelImagesPath,
            'proxyPath' => iTravelGeneralSettings::$iTravelProxyPath,
            'scriptsFolderPath' => iTravelGeneralSettings::$iTravelScriptsPath,
            'globalDestinationID' => $this->globalDestinationID,
            'contactFormTemplateURL' => iTravelGeneralSettings::$iTraveContactFormTemplateURL
        );

        if (!empty($parametersArray)) {
            foreach ($parametersArray as $key => $value) {
                $parameters[$key] = $value;
            }
        }

        $searchResultsResponse = $searchResults;
        if ($searchResultsResponse == null) {
            /// Call the API
            $apiSettings = InitAPI();
            if (isset($apiSettings)) {
                try {
                    $searchResultsResponse = $apiSettings->GetSearchResults(array('getSearchResultsParameters' => $getSearchResultsParameters));
					
                    if (isset($_SESSION['localliving_cart']) && $_GET['page'] == 'generer_pdf') {
                        $cart = $_SESSION['localliving_cart'];

                        if (isset($searchResultsResponse->GetSearchResultsResult->AccommodationObjectList->AccommodationObject)) {
                            $accommodationObjectList =
                                $searchResultsResponse->GetSearchResultsResult->AccommodationObjectList->AccommodationObject;
                            
							//fill out unselected unit
                            foreach ($accommodationObjectList as $keyObj => $obj) {
                                $isVilla = $obj->ObjectType->ObjectTypeID == 70;
                                
                                if (!$isVilla) {
                                    $unitList = $obj->UnitList->AccommodationUnit;
                                    
                                    foreach ($unitList as $keyUnit => $unit) {
                                        $unitID = $unit->UnitID;
                                        
                                        foreach ($cart as $dateRange => $selectedAccommodation) {
                                            if ($dateRange === $dateRangeKey) {
                                                $unitInCart = searchAssoc($unitID, $selectedAccommodation);
                                                
                                                if (!$unitInCart) {
                                                    unset($searchResultsResponse->GetSearchResultsResult->AccommodationObjectList->AccommodationObject[$keyObj]->UnitList->AccommodationUnit[$keyUnit]);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
							
							//reorder by the cart
//							foreach ($cart as $selectedAccommodation) {
//								unset($selectedAccommodation['selectedPersons']);
//
//								$selectedAccommodationIds = array_keys($selectedAccommodation);
//
//								foreach ($selectedAccommodationIds as $index => $selectedAccommodationId) {
//									foreach ($accommodationObjectList as $value) {
//										if($value->ObjectID == $selectedAccommodationId) {
//											$searchResultsResponse->GetSearchResultsResult->AccommodationObjectList->AccommodationObject[$index] = $value;
//										}
//									}
//								}
//							}
                        }
                    }
    
                    if (isset($_GET['page']) && isset($_POST['persons'])) {
                        $page    = $_GET['page'];
                        $persons = $_POST['persons'];
        
                        if ($page == 'localliving' && $persons > 0) {
                            $searchResultsResponse->GetSearchResultsResult->PersonsFromInput = $persons;
                        }
                    }
                    
                    if (isset($_GET['page']) && isset($_POST['viewFullUnits'])) {
                        $page          = $_GET['page'];
                        $viewFullUnits = $_POST['viewFullUnits'];
                        
                        if ($page == 'localliving' && $viewFullUnits) {
                            if (isset($searchResultsResponse->GetSearchResultsResult->AccommodationObjectList->AccommodationObject)) {
                                $accommodationObjectList =
                                    $searchResultsResponse->GetSearchResultsResult->AccommodationObjectList->AccommodationObject;
                                
                                foreach ($accommodationObjectList as $accommodationObject) {
                                    $accommodationDetailedDescription = new GetDetailedDescription();
                                    $accommodationDetailedDescription->objectURL = $accommodationObject->ObjectURL;
	                                if (isset($_POST['dateFrom']) && isset($_POST['dateTo'])) {
		                                $dateFrom = $_POST['dateFrom'] == '' ?
			                                date('d/m/Y') : $_POST['dateFrom'];
		                                $dateTo = $_POST['dateTo'] == '' ?
			                                date('d/m/Y', strtotime('+7 days')) : $_POST['dateTo'];
		                                $dateFrom = date_create_from_format(
			                                'd/m/Y',
			                                $dateFrom
		                                );
		                                $dateTo = date_create_from_format(
			                                'd/m/Y',
			                                $dateTo
		                                );
		
		                                $accommodationDetailedDescription->from = $dateFrom->setTime(0, 0);
		                                $accommodationDetailedDescription->to = $dateTo->setTime(0, 0);
	                                }
									
                                    $accommodationDetailedDescription = $accommodationDetailedDescription->getAPIResponse();
                                    
                                    if (isset($accommodationDetailedDescription->GetDetailedDescriptionResult->AccommodationObject->UnitList->AccommodationUnit)) {
                                        $accommodationDetailedUnitList = $accommodationDetailedDescription->GetDetailedDescriptionResult->AccommodationObject->UnitList;
    
                                        $accommodationObject->UnitList = $accommodationDetailedUnitList;
                                    }
                                }
                            }
                        }
                    }
                } catch (SoapFault $fault) {
                    //file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/iTravelErrors/" . date_timestamp_get(date_create()), $fault->__toString());
                    return itravel_display_soap_fault(APISoapFault::$GETSEARCHRESULTS, false);
                }
            } else {
                return itravel_display_soap_fault(APISoapFault::$INIT_API_FAULT, false);
            }
        }
        if (isset($searchResultsResponse->GetSearchResultsResult->TotalNumberOfResults) && $searchResultsResponse->GetSearchResultsResult->TotalNumberOfResults > 0) {
            $numberOfResults = count($searchResultsResponse->GetSearchResultsResult->AccommodationObjectList->AccommodationObject);
            foreach ($searchResultsResponse->GetSearchResultsResult->AccommodationObjectList->AccommodationObject as $accommodationObject) {
                if (isset($accommodationObject->ObjectURL)) {
                    $accommodationObject->ObjectURL = '/' . trim($accommodationObject->ObjectURL, "/") . '/';
                }
                if (isset($accommodationObject->ObjectCode)) {
                    $accommodationObject->ObjectCode = urlencode($accommodationObject->ObjectCode);
                }
            }
        } else {
            //when there are no results, we want to offer the client other days
            //timestamps are multiplied by 1000 (converted to milliseconds) because XSLT expects that format
            if (strtotime("last Saturday", strtotime($getSearchResultsParameters['StartDate'])) > time()) {
                $beginPreviousWeek = strtotime("last Saturday", strtotime($getSearchResultsParameters['StartDate']));
                $endPreviousWeek = strtotime("+1 week", $beginPreviousWeek);
                $beginPreviousWeekDate = date(DATE_ATOM, (int)$beginPreviousWeek);
                $beginPreviousWeek *= 1000;
                $endPreviousWeekDate = date(DATE_ATOM, (int)$endPreviousWeek);
                $endPreviousWeek *= 1000;
            } else {
                $beginPreviousWeek = $endPreviousWeek = $beginPreviousWeekDate = $endPreviousWeekDate = false;
            }
            $beginNextWeek = strtotime("next Saturday", strtotime($getSearchResultsParameters['StartDate']));
            $endNextWeek = strtotime("+1 week", $beginNextWeek);
            $beginNextWeekDate = date(DATE_ATOM, (int)$beginNextWeek);
            $beginNextWeek *= 1000;
            $endNextWeekDate = date(DATE_ATOM, (int)$endNextWeek);
            $endNextWeek *= 1000;
        }
        if (!isset($beginNextWeek)) {
            $beginNextWeek = $beginPreviousWeek = $endPreviousWeek = $endNextWeek = $beginPreviousWeekDate = $endPreviousWeekDate = $beginNextWeekDate = $endNextWeekDate = false;
        }
        $parameters = array_merge($parameters, array(
            'beginPreviousWeek' => $beginPreviousWeek,
            'endPreviousWeek' => $endPreviousWeek,
            'beginNextWeek' => $beginNextWeek,
            'endNextWeek' => $endNextWeek,
            'beginPreviousWeekDate' => $beginPreviousWeekDate,
            'endPreviousWeekDate' => $endPreviousWeekDate,
            'beginNextWeekDate' => $beginNextWeekDate,
            'endNextWeekDate' => $endNextWeekDate));
        
        if (isset($_GET['page'])) {
            $page       = $_GET['page'];
	        $isDefaultView = !isset($_POST['viewAClass']) &&
		        !isset($_POST['viewSpecialOfferOnly']) &&
		        !isset($_POST['viewFullUnits']);
			
			//unset oversize units
			if ($page == 'localliving') {
				$viewAClass           = $_POST['viewAClass'] ?? '0' ;
				$viewFullUnits        = $_POST['viewFullUnits'] ?? '0';
				$viewSpecialOfferOnly = $_POST['viewSpecialOfferOnly'] ?? '0';
				$isSearchResultView   = $viewAClass === '0' && $viewFullUnits === '0' && $viewSpecialOfferOnly === '0';
				
				if($isDefaultView || $viewAClass === '1' || $viewSpecialOfferOnly === '1' || $isSearchResultView ) {
					$personFromInput = 1;
					
					if(isset($searchResultsResponse->GetSearchResultsResult->PersonsFromInput)) {
						$personFromInput = $searchResultsResponse->GetSearchResultsResult->PersonsFromInput;
					}
					
					if (isset($searchResultsResponse->GetSearchResultsResult->AccommodationObjectList->AccommodationObject)) {
						$accommodationObjectList =
							$searchResultsResponse->GetSearchResultsResult->AccommodationObjectList->AccommodationObject;
						
						foreach ($accommodationObjectList as $accommodationIndex => $accommodationObject) {
							$unitList = array();
							
							if(isset($accommodationObject->UnitList->AccommodationUnit)) {
								$unitList = $accommodationObject->UnitList->AccommodationUnit;
							}
							
							$unitCapacity = 1;
							
							foreach ($unitList as $unitIndex => $unit) {
								$attributeGroupList = array();
								
								if(isset($unit->AttributeGroupList->AttributeGroup)) {
									$attributeGroupList = $unit->AttributeGroupList->AttributeGroup;
								}
								
								$attributeList = array();
								
								foreach ($attributeGroupList as $attributeGroup) {
									if(isset($attributeGroup->AttributeList->Attribute)) {
										$attributeList = $attributeGroup->AttributeList->Attribute;
									}
								}
								
								foreach ($attributeList as $attribute) {
									if(isset($attribute->AttributeID) && isset($attribute->AttributeValue)) {
										$attributeId = $attribute->AttributeID;
										$attributeValue = $attribute->AttributeValue;
										
										if($attributeId == 120) {
											$unitCapacity = $attributeValue;
										}
									}
								}
								
								if((int) $personFromInput > (int) $unitCapacity) {
									unset($searchResultsResponse->GetSearchResultsResult->AccommodationObjectList
									->AccommodationObject[$accommodationIndex]->UnitList->AccommodationUnit[$unitIndex]);
								}
							}
							
							$recountUnitList = count($searchResultsResponse->GetSearchResultsResult
								->AccommodationObjectList->AccommodationObject[$accommodationIndex]
								->UnitList->AccommodationUnit);
							
							if($recountUnitList <= 0) {
								unset($searchResultsResponse->GetSearchResultsResult
									->AccommodationObjectList->AccommodationObject[$accommodationIndex]);
							}
						}
					}
				}
			}
            
            if ($page == 'localliving' && isset($_POST['viewAClass'])) {
				if($_POST['viewAClass'] == "1") {
					if (isset($searchResultsResponse->GetSearchResultsResult->AccommodationObjectList->AccommodationObject)) {
						$accommodationObjectList =
							$searchResultsResponse->GetSearchResultsResult->AccommodationObjectList->AccommodationObject;
						
						$aClassAccommodationList = array();
						$bClassAccommodationList = array();
						
						foreach ($accommodationObjectList as $accommodationObject) {
							$categoryList = $accommodationObject->CategoryList->Category ?? array();
							
							$isAClassAccommodation = false;
							
							foreach ($categoryList as $category) {
								$isAClassAccommodation = $category->CategoryID == 42;
							}
							
							if ($isAClassAccommodation) {
								$aClassAccommodationList[] = $accommodationObject;
							} else {
								$bClassAccommodationList[] = $accommodationObject;
							}
						}
						
						
						unset($searchResultsResponse->GetSearchResultsResult->AccommodationObjectList->AccommodationObject);
						
						$searchResultsResponse->GetSearchResultsResult->AccommodationObjectList->AccommodationObject
							= new \stdClass();
						
						$searchResultsResponse->GetSearchResultsResult->AccommodationObjectList->AccommodationObject->AClass
							= $aClassAccommodationList;
						
						$searchResultsResponse->GetSearchResultsResult->AccommodationObjectList->AccommodationObject->BClass
							= $bClassAccommodationList;
					}
				}
            }
        }
        
        return TransformResult($searchResultsResponse, $parameters, $this->xsltPath, 'GetSearchResultsResult', 'SearchResults');
    }

    public function EchoSearchResults($parametersArray = array(), $dateRangeKey = '')
    {
        echo $this->GetSearchResultsHTML($parametersArray, null, $dateRangeKey);
    }
}

/// class used for getting Tour search results
class GetTourSearchResults
{

    #region Properties
    public $currentPage;
    public $objectIDList = array();
    public $currencyID;
    public $categoryValue = "";
    public $personsValue = "";
    public $childrenValue = "";
    public $childrenAges;
    public $categoryID;
    public $priceType;
    public $priceFrom = null;
    public $priceTo = null;
    public $onlyOnSpecialOffer;
    public $objectTypeIDList = array();
    public $objectTypeGroupIDList = array();
    public $categoryIDList = array();
    public $fromString;
    public $toString;
    public $from;
    public $to;
    public $countryID;
    public $regionID;
    public $destinationID;
    public $globalDestinationID;
    public $destinationIDList;
    public $objectTypeID;
    public $objectTypeGroup;
    public $persons;
    public $children;
    public $numberOfStars;
    public $languageID;
    public $sortParameterList;
    public $sortParameter = "";
    public $outParameterList;
    public $outParameter = "";
    public $pageSize = 10;
    public $destinationName = '';
    public $airport;
    public $minNumberOfNights = 0;
    public $maxNumberOfNights = 0;
    public $urlPrefix = "";
    public $marketIDList = array();
    public $marketIDs = 0;
    public $isRelativeUrl = false;
    public $customerID;
    public $splitResultsByHotel = false;
    public $ignoreCapacity = false;
    public $xsltPath;
    public $tourDetailedDescription;
    public $tourSearchResults;
    public $marketID;
    public $thumbnailWidth;
    public $thumbnailHeight;
    public $unitFilters;
    public $objectFilters;
    public $bookingForm;
    public $unitCategoryIDList;
    #endregion

    public function __construct()
    {
        $this->tourDetailedDescription = iTravelGeneralSettings::$iTravelPageTourDetailedDescriptionPath;
        $this->tourSearchResults = iTravelGeneralSettings::$iTravelPageTourSearchResultsPath;
        $this->bookingForm = iTravelGeneralSettings::$iTravelPageBookingFormPath;

        $this->thumbnailWidth = 152;
        $this->thumbnailHeight = 82;
        $this->currentPage = GetParameterFromGetOrPost('currentPage', 1);
        $this->currencyID = GetParameterFromGetOrPost('currencyID', iTravelGeneralSettings::$iTravelDefaultCurrencyID);
        $this->countryID = GetParameterFromGetOrPost('countryID', 0);
        $this->regionID = GetParameterFromGetOrPost('regionID', 0);
        $this->destinationID = GetParameterFromGetOrPost('destinationID', 0);
        $this->objectTypeID = GetParameterFromGetOrPost('objectTypeID', 0);
        $this->objectTypeGroup = explode(',', GetParameterFromGetOrPost('objectTypeGroupID', ''));
        $this->persons = GetParameterFromGetOrPost('persons', 0);
        $this->categoryID = GetParameterFromGetOrPost('categoryID', 0);
        $this->childrenAges = GetParameterFromGetOrPost('childrenAges', '');
        $this->numberOfStars = GetParameterFromGetOrPost('numberOfStars', 0);
        $this->languageID = GetParameterFromGetOrPost('languageID', iTravelGeneralSettings::$iTravelDefaultLanguageID);
        $this->children = GetParameterFromGetOrPost('children', 0);
        $this->priceType = GetParameterFromGetOrPost('priceType', 'Total');
        $this->onlyOnSpecialOffer = GetParameterFromGetOrPost('onlyOnSpecialOffer', false);
        $this->splitResultsByHotel = GetParameterFromGetOrPost('splitResultsByHotel', false);
        $this->destinationName = GetParameterFromGetOrPost('destinationName', '');
        $this->airport = GetParameterFromGetOrPost('airport', '');
        $this->objectTypeGroupID = GetParameterFromGetOrPost('objectTypeGroupID', 0);
        $this->globalDestinationID = GetParameterFromGetOrPost('globalDestinationID2', 0);

        $this->from = new DateTime();
        $this->fromString = GetParameterFromGetOrPost('from', '');
        if (!empty($this->fromString)) {
            $this->from->setTimestamp($this->fromString / 1000);
        } else {
            $this->from = null;
        }

        $this->to = new DateTime();
        $this->toString = GetParameterFromGetOrPost('to', '');
        if (!empty($this->toString)) {
            $this->to->setTimestamp($this->toString / 1000);
        } else {
            $this->to = null;
        }


        $this->customerID = GetParameterFromGetOrPost('customerID', 0);

        $this->objectPhoto = array(
            'ResponseDetail' => 'ObjectPhotos',
            'NumberOfResults' => '100'
        );
        $unitPhoto = array(
            'ResponseDetail' => 'UnitPhotos',
            'NumberOfResults' => '100'
        );

        $this->group = array(
            'ResponseDetail' => 'ObjectDetailedAttributes',
            'NumberOfResults' => '10'
        );

        $this->description = array(
            'ResponseDetail' => 'UnitDescription',
            'NumberOfResults' => '1'
        );

        $this->outParameterList = array(
            '0' => $this->objectPhoto,
            '1' => $this->group,
            '2' => $this->description,
            '3' => $unitPhoto
        );
        if ($this->persons != 0) {
            $this->personsFilter = array(
                'AttributeID' => 120,
                'AttributeValue' => $this->persons,
                'ComparisonType' => 'GreaterOrEqualThan'
            );

            $this->unitFilters = array(
                '0' => $this->personsFilter
            );
        }
        $additionalObjectFilters = GetParameterFromGetOrPostInArray('objectAttributeFilters', null);
        $this->objectFilters = array();
        $this->objectFilters = GeneratAttributeFilterList($additionalObjectFilters, $this->objectFilters);

        $additionalUnitFilters = GetParameterFromGetOrPostInArray('unitAttributeFilters', null);
        $this->unitFilters = GeneratAttributeFilterList($additionalUnitFilters, $this->unitFilters);
        $this->unitCategoryIDList = GetParameterFromGetOrPostInArray("unitCategoryIDList", 0);


        $this->sortParameterList = array();
        $sortByPrice = GetParameterFromGetOrPost('sortByPrice', '');
        if ($sortByPrice != '') {
            $sortOrder = 'Ascending';
            if ($sortByPrice == '0') {
                $sortOrder = 'Descending';
            }
            array_push($this->sortParameterList, array('SortBy' => 'Price', 'SortOrder' => $sortOrder));
        }

        $this->xsltPath = iTravelGeneralSettings::$iTravelXSLTTourSearchResultsPath;

        $this->pageSize = GetParameterFromGetOrPost('pageSize', $this->pageSize);
    }

    // Get API response object, transform it via XSLT processor and echo the resulting HTML.
    public function TransformAndEchoAPIResponse()
    {
    }
    // Get API response object, transform it via XSLT processor and return the resulting HTML as string.
    public function GetTransformedAPIResponse()
    {
    }

    public function EchoSearchResults()
    {
        $fromParameter = null;
        if ($this->from != null) {
            $fromParameter = $this->from->format(DATE_ATOM);
        }
        $toParameter = null;
        if ($this->to != null) {
            $toParameter = $this->to->format(DATE_ATOM);
        }

        $destinationIDList = null;
        if (!empty($this->destinationID)) {
            $destinationIDList = explode(',', $this->destinationID);
        }
        $regionIDList = null;
        if (!empty($this->regionID)) {
            $regionIDList = explode(',', $this->regionID);
        }
        $countryIDList = null;
        if (!empty($this->countryID)) {
            $countryIDList = explode(',', $this->countryID);
        }
        $childrenAgesList = null;
        if (!empty($this->childrenAges)) {
            $childrenAgesList = explode(',', $this->childrenAges);
        }
        $getPackageSearchResultsParameters = array(
            'StartDate' => $fromParameter,
            'EndDate' => $toParameter,
            'DestinationIDList' => $destinationIDList,
            'RegionIDList' => $regionIDList,
            'CountryIDList' => $countryIDList,
            'UnitCategoryIDList' => $this->unitCategoryIDList,
            'CategoryIDListUnion' => empty($this->categoryID) ? array('0' => $this->categoryID) : explode(',', $this->categoryID),
            'CategoryIDListIntersection' => array(),
            'PriceFrom' => $this->priceFrom,
            'PriceTo' => $this->priceTo,
            'InPriceType' => 'PerPerson',
            'SortParameterList' => $this->sortParameterList,
            'PageSize' => $this->pageSize,
            'CurrentPage' => $this->currentPage,
            'ObjectAttributeFilterList' => $this->objectFilters,
            'UnitAttributeFilterList' => $this->unitFilters,
            'ThumbnailWidth' => $this->thumbnailWidth,
            'ThumbnailHeight' => $this->thumbnailHeight,
            'CurrencyID' => $this->currencyID,
            'OutParameterList' => $this->outParameterList,
            'LanguageID' => $this->languageID,
            'OnlyOnSpecialOffer' => $this->onlyOnSpecialOffer,
            'ChildrenAgeList' => $childrenAgesList,
            'IsRelativeUrl' => $this->isRelativeUrl,
            'CustomerID' => $this->customerID,
            'SplitResultsByHotel' => $this->splitResultsByHotel,
            'IgnoreCapacity' => $this->ignoreCapacity,
            'MarketIDList' => array(),
            'DestinationName' => $this->destinationName,
            'Airport' => $this->airport,
            'MinNumberOfNights' => $this->minNumberOfNights,
            'MaxNumberOfNights' => $this->maxNumberOfNights,
            'DestinationCodes' => explode(',', $this->globalDestinationID)
        );

        $this->bookingForm = iTravelGeneralSettings::BookingFormUrl($this->languageID);

        $parameters = array(
            'urlPrefixParameter' => '',
            'fromParameter' => $this->fromString,
            'toParameter' => $this->toString,
            'countryIDParameter' => $this->countryID,
            'regionIDParameter' => $this->regionID,
            'destinationIDParameter' => $this->destinationID,
            'objectTypeIDParameter' => $this->objectTypeID,
            'objectTypeGroupIDParameter' => implode(',', $this->objectTypeGroup),
            'personsParameter' => $this->persons,
            'childrenParameter' => $this->children,
            'childrenAgesParameter' => '',
            'numberOfStarsParameter' => $this->numberOfStars,
            'categoryIDParameter' => $this->categoryID,
            'priceTypeParameter' => $this->priceType,
            'onlyOnSpecialOfferParameter' => $this->onlyOnSpecialOffer,
            'splitResultsByHotel' => $this->splitResultsByHotel,
            'objectTypeGroupID' => implode(',', $this->objectTypeGroup),
            'affiliateID' => '',
            'detailsURL' => $this->tourDetailedDescription,
            'SearchPage' => $this->tourSearchResults,
            'destinationName' => $this->destinationName,
            'airport' => $this->airport,
            'packageTourDuration' => $this->minNumberOfNights,
            'ClientWebAddress' => '',
            'bookingAddressDisplayURL' => $this->bookingForm,
            'imagesFolderPath' => iTravelGeneralSettings::$iTravelImagesPath,
            'proxyPath' => iTravelGeneralSettings::$iTravelProxyPath,
            'scriptsFolderPath' => iTravelGeneralSettings::$iTravelScriptsPath,
            'contactFormTemplateURL' => iTravelGeneralSettings::$iTraveContactFormTemplateURL
        );

        /// Call the API
        $apiSettings = InitAPI();
        if (isset($apiSettings)) {
            try {
                $searchResults = $apiSettings->GetPackageSearchResults(array('getPackageSearchResultsParameters' => $getPackageSearchResultsParameters));
                TransformAndEchoResult($searchResults, $parameters, $this->xsltPath, 'GetPackageSearchResultsResult', 'PackageSearchResults');
            } catch (SoapFault $fault) {
                file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/iTravelErrors/" . date_timestamp_get(date_create()), $fault->__toString());
                return itravel_display_soap_fault(APISoapFault::$GETTOURSEARCHRESULTS);
            }
        } else {
            return itravel_display_soap_fault(APISoapFault::$INIT_API_FAULT);
        }
    }
}

function get_unit_name($unit)
{
    if ($unit
        && isset($unit->AttributeGroupList)
        && isset($unit->AttributeGroupList->AttributeGroup)
    ) {
        foreach ($unit->AttributeGroupList->AttributeGroup as $group) {
            if ($group
                && isset($group->AttributeList)
                && isset($group->AttributeList->Attribute)) {
                foreach ($group->AttributeList->Attribute as $attr) {
                    if ($attr && $attr->AttributeID == 133) {
                        return $attr->AttributeValue;
                    }
                }
            }
        }
    }
    return null;
}

function sort_accom_units_by_name($a, $b)
{
    $a_name = get_unit_name($a);
    $b_name = get_unit_name($b);
    return strcmp($a_name, $b_name);
}

/// class used for getting Accommodation detailed description
class GetDetailedDescription
{
    #region Properties
    public $ignorePriceAndAvailability;
    public $ticksFrom1970;
    public $from;
    public $fromString;
    public $to;
    public $toString;
    public $persons;
    public $objectID;
    public $languageID;
    public $currencyID;
    public $objectURL;
    public $xsltPath;
    public $bookingForm;
    public $objectCode;
    public $cartID;
    public $childrenAges;
    public $doNotCountMandatoryServices;
    public $customerID;
    public $outParameterList;
    #endregion

    public function __construct()
    {
        $this->bookingForm = iTravelGeneralSettings::$iTravelPageBookingFormPath;
        $this->xsltPath = iTravelGeneralSettings::$iTravelXSLTAccommodationDetailedDescriptionPath;

        $this->ignorePriceAndAvailability = GetParameterFromGetOrPost('ignorePriceAndAvailability', false);

        $this->from = new DateTime();
        $this->fromString = GetParameterFromGetOrPost('from', 0, true);
        if (!empty($this->fromString)) {
            $this->from->setTimestamp($this->fromString / 1000);
        } else {
            $this->from = null;
        }

        $this->to = new DateTime();
        $this->toString = GetParameterFromGetOrPost('to', 0, true);
        if (!empty($this->toString)) {
            $this->to->setTimestamp($this->toString / 1000);
        } else {
            $this->to = null;
        }

        if (isset($_GET['dateFrom'])) {
            $this->from = new DateTime($_GET['dateFrom']);
        }
        if (isset($_GET['dateTo'])) {
            $this->to = new DateTime($_GET['dateTo']);
        }

        $this->persons = GetParameterFromGetOrPost('persons', 1, true);
        $this->objectID = GetParameterFromGetOrPost('objectID', 0);
        $this->languageID = GetParameterFromGetOrPost('languageID', iTravelGeneralSettings::$iTravelDefaultLanguageID);
        $this->currencyID = GetParameterFromGetOrPost('currencyID', iTravelGeneralSettings::$iTravelDefaultCurrencyID);
        $this->objectURL = GetParameterFromGetOrPost('objectURL', null);
        if (empty($this->objectURL) && empty($this->objectID)) {
            $requestUri = trim(explode('?', $_SERVER['REQUEST_URI'], 2)[0], "/");
            $this->objectURL = $requestUri;
        }

        $this->objectCode = GetParameterFromGetOrPost('objectCode', null);
        $this->cartID = $_SESSION['iTravelCartID'];
        $this->childrenAges = GetParameterFromGetOrPost('childrenAges', '', true);
        $this->doNotCountMandatoryServices = GetParameterFromGetOrPost('doNotCountMandatoryServices', null, true);

        // Fetch unavailable dates
        $this->outParameterList[] = array(
            'ResponseDetail' => 2
        );
    }


    public function GetAPIResponse()
    {
        $fromParameter = null;
        if ($this->from != null) {
            $fromParameter = $this->from->format(DATE_ATOM);
        }
        $toParameter = null;
        if ($this->to != null) {
            $toParameter = $this->to->format(DATE_ATOM);
        }
        $childrenAgesParam = array();
        if (!empty($this->childrenAges) || $this->childrenAges == "0") {
            $childrenAgesParam = explode(',', $this->childrenAges);
        }

        if ($this->objectURL != null and isset($this->objectURL)) {
            $getDetailedDescriptionParameters = array(
                'StartDate' => $fromParameter,
                'EndDate' => $toParameter,
                'NumberOfPersons' => $this->persons,
                'CurrencyID' => $this->currencyID,
                'ObjectURL' => $this->objectURL,
                'ObjectCode' => $this->objectCode,
                'ChildrenAgeList' => $childrenAgesParam,
                'DoNotCountMandatoryServices' => $this->doNotCountMandatoryServices,
                'CustomerID' => $this->customerID,
                'OutParameterList' => $this->outParameterList
            );
        } else {
            $getDetailedDescriptionParameters = array(
                'ObjectID' => $this->objectID,
                'StartDate' => $fromParameter,
                'EndDate' => $toParameter,
                'NumberOfPersons' => $this->persons,
                'LanguageID' => $this->languageID,
                'CurrencyID' => $this->currencyID,
                'ObjectCode' => $this->objectCode,
                'ObjectURL' => null,
                'ChildrenAgeList' => $childrenAgesParam,
                'DoNotCountMandatoryServices' => $this->doNotCountMandatoryServices,
                'CustomerID' => $this->customerID,
                'OutParameterList' => $this->outParameterList
            );
        }

        $getDetailedDescriptionParameters['ThumbnailWidth'] = 275;
        $getDetailedDescriptionParameters['ThumbnailHeight'] = 184;
        $getDetailedDescriptionParameters['PhotoWidth'] = 2090;
        $getDetailedDescriptionParameters['PhotoHeight'] = 1162;

        /// Call the API
        $apiSettings = InitAPI();
        if (isset($apiSettings)) {
            try {
                $accommodationObjectDetails = $apiSettings->GetDetailedDescription(array('getDetailedDescriptionParameters' => $getDetailedDescriptionParameters));

                if ($accommodationObjectDetails
                    && isset($accommodationObjectDetails->GetDetailedDescriptionResult)
                    && isset($accommodationObjectDetails->GetDetailedDescriptionResult->AccommodationObject)
                    && isset($accommodationObjectDetails->GetDetailedDescriptionResult->AccommodationObject->UnitList)
                    && is_array($accommodationObjectDetails->GetDetailedDescriptionResult->AccommodationObject->UnitList->AccommodationUnit)) {
                    usort($accommodationObjectDetails->GetDetailedDescriptionResult->AccommodationObject->UnitList->AccommodationUnit, "sort_accom_units_by_name");


                    foreach ($accommodationObjectDetails->GetDetailedDescriptionResult->AccommodationObject->UnitList->AccommodationUnit as $unit) {
                        if ($unit) {
                            $has_short_stay_note = false;

                            if (isset($unit->NoteList) && isset($unit->NoteList->Note)) {
                                foreach ($unit->NoteList->Note as $note) {
                                    if (isset($note->NoteTitle)) {
                                        if (trim(mb_strtolower($note->NoteTitle)) == 'short stay') {
                                            $has_short_stay_note = true;
                                        }
                                    }
                                }
                            }

                            if (!$has_short_stay_note && isset($unit->ServiceList)
                                && is_array($unit->ServiceList->Service)) {
                                foreach ($unit->ServiceList->Service as $service) {
                                    if ($service
                                        && isset($service->PriceRowList)
                                        && is_array($service->PriceRowList->PriceRow)) {
                                        $delete_indexes = array();
                                        for ($i = count($service->PriceRowList->PriceRow) - 1; $i > -1; $i--) {
                                            $row = $service->PriceRowList->PriceRow[$i];
                                            if ($row->MinimumStay && $row->MinimumStay < 7) {
                                                $delete_indexes[] = $i;
                                            }
                                        }
                                        foreach ($delete_indexes as $index) {
                                            if ($index != 0 || ($index == 0 && count($delete_indexes) < count($service->PriceRowList->PriceRow))) {
                                                unset($service->PriceRowList->PriceRow[ $index ]);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                return $accommodationObjectDetails;
            } catch (SoapFault $fault) {
                file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/iTravelErrors/" . date_timestamp_get(date_create()), $fault->__toString());
                return null;
            }
        } else {
            return null;
        }
    }

    // Get API response object, transform it via XSLT processor and echo the resulting HTML.
    public function TransformAndEchoAPIResponse($apiResponse = null)
    {
        echo $this->TransformAPIResponse($apiResponse);
    }

    public function GetJSON()
    {
        $accommodationObjectDetails = $this->GetAPIResponse();
        if (!empty($this->childrenAges) || $this->childrenAges == '0') {
            $accommodationObjectDetails->GetDetailedDescriptionResult->ChildrenAges = array_map('intval', explode(',', $this->childrenAges));
        }
        $accommodationObjectDetails->GetDetailedDescriptionResult->CustomerID = $this->customerID;
        $accommodationObjectDetails->GetDetailedDescriptionResult->BookingFormPath = $this->bookingForm;
        return json_encode((array)$accommodationObjectDetails);
    }

    public function TransformAPIResponse($apiResponse = null)
    {
        if ($apiResponse == null) {
            $apiResponse = $this->GetAPIResponse();
        }

        $childrenAgesParam = array();
        if (!empty($this->childrenAges) || $this->childrenAges == "0") {
            $childrenAgesParam = explode(',', $this->childrenAges);
        }

        $this->bookingForm = iTravelGeneralSettings::BookingFormUrl($this->languageID);

        $parameters = array(
            'languageID' => $this->languageID,
            'currencyID' => $this->currencyID,
            'unitActivityStatus' => '0,1,2',
            'bookingAddressDisplayURL' => $this->bookingForm,
            'cartIDParameter' => $this->cartID,
            'imagesFolderPath' => iTravelGeneralSettings::$iTravelImagesPath,
            'proxyPath' => iTravelGeneralSettings::$iTravelProxyPath,
            'scriptsFolderPath' => iTravelGeneralSettings::$iTravelScriptsPath,
            'contactFormTemplateURL' => iTravelGeneralSettings::$iTraveContactFormTemplateURL,
            'childrenAgesParam' => $this->childrenAges,
            'childrenParam' => count($childrenAgesParam)
        );

        try {
            //$accommodationObjectDetails = $this->GetAPIResponse();
            if (isset($apiResponse)) {
                return TransformResult($apiResponse, $parameters, $this->xsltPath, 'GetDetailedDescriptionResult', 'AccommodationObjectDetails');
            }
        } catch (SoapFault $fault) {
            file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/iTravelErrors/" . date_timestamp_get(date_create()), $fault->__toString());
            return itravel_display_soap_fault(APISoapFault::$GETDETAILEDDESCRIPTION, false);
        }
    }
}

class Date
{
    public $StartDate;
    public $EndDate;
    public $NumberOfDays;
}

/// class used for getting Tour detailed description
class GetTourDetailedDescription
{

    #region Properties
    public $objectID;
    public $objectIDList;
    public $period;
    public $packageTourID;
    public $currencyID;
    public $languageID;
    public $packageTourCode;
    public $numberOfDays = 0;
    public $persons = 0;
    public $numberOfPersons = 0;
    public $customerID = 0;
    public $marketID = 0;
    public $EncryptedAffiliateID = 0;
    public $isRelativeURL = false;
    public $packageDetailsXSLTStyleSheet;
    public $bookingForm;
    public $xsltPath;
    public $from;
    public $to;
    public $fromString;
    public $toString;
    public $periods;
    public $objectURL;
    public $thumbnailWidth;
    public $thumbnailHeight;
    #endregion

    public function __construct()
    {
        $this->xsltPath = iTravelGeneralSettings::$iTravelXSLTTourDetailedDescriptionPath;
        $this->bookingForm = iTravelGeneralSettings::$iTravelPageBookingFormPath;

        $this->thumbnailWidth = 191;
        $this->thumbnailHeight = 142;

        $this->objectID = GetParameterFromGetOrPost('objectID', 0);
        $this->objectIDList = GetParameterFromGetOrPost('objectIDList', 0);
        $this->period = GetParameterFromGetOrPost('period', null, true);
        $this->packageTourID = GetParameterFromGetOrPost('packageTourID', 0);
        $this->packageTourCode = GetParameterFromGetOrPost('packageTourCode', "");
        $this->languageID = GetParameterFromGetOrPost('languageID', iTravelGeneralSettings::$iTravelDefaultLanguageID);
        $this->currencyID = GetParameterFromGetOrPost('currencyID', iTravelGeneralSettings::$iTravelDefaultCurrencyID);
        $this->numberOfDays = GetParameterFromGetOrPost('numberOfDays', 1);
        $this->persons = GetParameterFromGetOrPost('persons', 0, true);
        $this->objectURL = GetParameterFromGetOrPost('objectURL', '');
    }

    // Get API response object, transform it via XSLT processor and echo the resulting HTML.
    public function TransformAndEchoAPIResponse()
    {
    }
    // Get API response object, transform it via XSLT processor and return the resulting HTML as string.
    public function GetTransformedAPIResponse()
    {
    }

    public function GetTourDetailedDescriptionHTML($flag)
    {
        if (!empty($this->period)) {
            $periodStartDate = new DateTime($this->period);
            $periodEndDate = new DateTime($this->period);
            $periodEndDate->add(new DateInterval('P' . ($this->numberOfDays - 1) . 'D'));

            $this->periods = array();

            $datePeriod = (object) array('Date' => (object) array('StartDate' => $periodStartDate->format('Y-m-d'), 'EndDate' => $periodEndDate->format('Y-m-d'), 'NumberOfDays' => $this->numberOfDays));
            array_push($this->periods, $datePeriod->Date);
        } else {
            $this->periods = array();
        }

        $objectIDListParameter = array();
        if (!empty($this->objectIDList)) {
            $objectIDListParameter = explode(',', $this->objectIDList);
        } elseif (!empty($this->objectID)) {
            $objectIDListParameter = array('0' => $this->objectID);
        }
        $getPackageDetailedDescriptionParameters = array(
            'NumberOfPersons' => $this->persons,
            'PackageTourID' => $this->packageTourID,
            'ObjectIDList' => $objectIDListParameter,
            'Periods' => $this->periods,
            'ThumbnailWidth' => $this->thumbnailWidth,
            'ThumbnailHeight' => $this->thumbnailHeight,
            'CurrencyID' => $this->currencyID,
            'LanguageID' => $this->languageID,
            'ListUnitActivityStatus' => '0,1,2',
            'ObjectURL' => $this->objectURL,
            'PackageTourCode' => $this->packageTourCode,
            'IsRelativeURL' => $this->isRelativeURL,
            'CustomerID' => $this->customerID,
            'MarketID' => $this->marketID
        );

        $requestParameter = array('getPackageDetailedDescriptionParameters' => $getPackageDetailedDescriptionParameters);
        $packageTourDetails = null;
        /// Call the API
        $apiSettings = InitAPI();
        if (isset($apiSettings)) {
            try {
                $packageTourDetails = $apiSettings->GetPackageDetailedDescription($requestParameter);
            } catch (SoapFault $fault) {
                file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/iTravelErrors/" . date_timestamp_get(date_create()), $fault->__toString());
                return itravel_display_soap_fault(APISoapFault::$GETTOURDETAILEDDESCRIPTION);
            }
        } else {
            return itravel_display_soap_fault(APISoapFault::$INIT_API_FAULT);
        }

        $objectIDParameter = 0;
        if (is_array($packageTourDetails->GetPackageDetailedDescriptionResult->PackageTour->PackageUnitList->PackageUnit)) {
            foreach ($packageTourDetails->GetPackageDetailedDescriptionResult->PackageTour->PackageUnitList->PackageUnit as $packageUnit) {
                $accommodationObjectFound = false;
                if (is_array($packageUnit->PackagePeriodList->PackagePeriod)) {
                    foreach ($packageUnit->PackagePeriodList->PackagePeriod as $packagePeriod) {
                        if ($packagePeriod->Visible == true && $packageUnit->AccommodationUnitID != 0) {
                            if (is_array($packageTourDetails->GetPackageDetailedDescriptionResult->AccommodationObjectList->AccommodationObject)) {
                                foreach ($packageTourDetails->GetPackageDetailedDescriptionResult->AccommodationObjectList->AccommodationObject as $accommodationObject) {
                                    if (is_array($accommodationObject->UnitList->AccommodationUnit)) {
                                        foreach ($accommodationObject->UnitList->AccommodationUnit as $accommodationUnit) {
                                            if ($accommodationUnit->UnitID == $packageUnit->AccommodationUnitID) {
                                                $objectIDParameter = $accommodationObject->ObjectID;
                                                $accommodationObjectFound = true;
                                                break;
                                            }
                                        }
                                    } else {
                                        if ($accommodationObject->UnitList->AccommodationUnit->UnitID == $packageUnit->AccommodationUnitID) {
                                            $objectIDParameter = $accommodationObject->ObjectID;
                                            $accommodationObjectFound = true;
                                            break;
                                        }
                                    }
                                }
                            } else {
                                $accommodationObject = $packageTourDetails->GetPackageDetailedDescriptionResult->AccommodationObjectList->AccommodationObject;
                                if (is_array($accommodationObject->UnitList->AccommodationUnit)) {
                                    foreach ($accommodationObject->UnitList->AccommodationUnit as $accommodationUnit) {
                                        if ($accommodationUnit->UnitID == $packageUnit->AccommodationUnitID) {
                                            $objectIDParameter = $accommodationObject->ObjectID;
                                            $accommodationObjectFound = true;
                                            break;
                                        }
                                    }
                                } else {
                                    if ($accommodationObject->UnitList->AccommodationUnit->UnitID == $packageUnit->AccommodationUnitID) {
                                        $objectIDParameter = $accommodationObject->ObjectID;
                                        $accommodationObjectFound = true;
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
                if ($accommodationObjectFound) {
                    break;
                }
            }
        }

        ///re-format period string
        if (!empty($this->period)) {
            try {
                $tempDate = new DateTime($this->period);
                $this->period = $tempDate->format('d.m.Y');
            } catch (Exception $e) {
            }
        }

        $this->bookingForm = iTravelGeneralSettings::BookingFormUrl($this->languageID);

        $parameters = array(
            'periodParameter' => $this->period,
            'numberOfDaysParameter' => $this->numberOfDays,
            'affiliateIDParameter' => $this->EncryptedAffiliateID,
            'languageID' => $this->languageID,
            'currencyID' => $this->currencyID,
            'bookingAddressDisplayURL' => $this->bookingForm,
            'imagesFolderPath' => iTravelGeneralSettings::$iTravelImagesPath,
            'proxyPath' => iTravelGeneralSettings::$iTravelProxyPath,
            'scriptsFolderPath' => iTravelGeneralSettings::$iTravelScriptsPath,
            'contactFormTemplateURL' => iTravelGeneralSettings::$iTraveContactFormTemplateURL,
            'objectIDParameter' => $objectIDParameter
        );

        return TransformResult($packageTourDetails, $parameters, $this->xsltPath, 'GetPackageDetailedDescriptionResult', 'PackageTourDetails');
    }

    public function EchoTourDetailedDescription($flag)
    {
        echo $this->GetTourDetailedDescriptionHTML($flag);
    }
}

/// class used for getting Transportation search results
class GetTransportationSearchResults
{

    #region Properties
    public $currentPage;
    public $dateFrom;
    public $dateTo;
    public $languageID;
    public $currencyID;
    public $categoryID;
    public $personsValue;
    public $childrenValue;
    public $childrenAges;
    public $onlyOnSpecialOffer;
    public $ignorePriceAndAvailability = false;
    public $from;
    public $fromString;
    public $to;
    public $toString;
    public $pickupDestinationID;
    public $pickupRegionID;
    public $pickupCountryID;
    public $pickupDestinationLevel;
    public $dropoffDestinationID;
    public $dropoffDestinationLevel;
    public $objectTypeID;
    public $objectTypeGroupID;
    public $persons;
    public $children;
    public $numberOfStars;
    public $priceType;
    public $toDisplaySpecialOffers;
    public $categoryIntersectionID;
    public $globalDestinationID;
    public $searchSupplier;
    public $destinationID;
    public $countryID;
    public $isRelativeURL = false;
    public $transportationDetailedDescription;
    public $transportationSearchResults;
    public $openInParent = false;
    public $urlPrefix = "/";
    public $objectFilters;
    public $personsFilter;
    public $unitFilters;
    public $outParameterList;
    public $sortParameterList;
    public $person = '';
    public $priceFrom = null;
    public $priceTo = null;
    public $bookingForm;
    public $xsltPath;
    public $EncryptedAffiliateID;
    public $regionID;
    public $thumbnailWidth;
    public $thumbnailHeight;
    public $dropoffRegionID;
    public $dropoffCountryID;
    public $pageSize = 10;
    public $unitCategoryIDList;
    #endregion

    public function __construct()
    {
        $this->bookingForm = iTravelGeneralSettings::$iTravelPageBookingFormPath;
        $this->transportationSearchResults = iTravelGeneralSettings::$iTravelPageTransportationSearchFormPath;
        $this->transportationDetailedDescription = iTravelGeneralSettings::$iTravelPageTransportationDetailedDescriptionPath;

        $this->thumbnailWidth = 152;
        $this->thumbnailHeight = 82;

        $this->from = new DateTime();
        $this->fromString = GetParameterFromGetOrPost('from', '');
        if (!empty($this->fromString)) {
            $this->from->setTimestamp($this->fromString / 1000);
        } else {
            $this->from = null;
        }

        $this->to = new DateTime();
        $this->toString = GetParameterFromGetOrPost('to', '');
        if (!empty($this->toString)) {
            $this->to->setTimestamp($this->toString / 1000);
        } else {
            $this->to = null;
        }

        $this->ignorePriceAndAvailability = GetParameterFromGetOrPost('ignorePriceAndAvailability', false);
        $this->currentPage = GetParameterFromGetOrPost('currentPage', 1);
        $this->languageID = GetParameterFromGetOrPost('languageID', iTravelGeneralSettings::$iTravelDefaultLanguageID);
        $this->currencyID = GetParameterFromGetOrPost('currencyID', iTravelGeneralSettings::$iTravelDefaultCurrencyID);
        $this->pickupDestinationID = GetParameterFromGetOrPost('pickupDestinationID', 0);
        $this->pickupRegionID = GetParameterFromGetOrPost('pickupRegionID', 0);
        $this->pickupCountryID = GetParameterFromGetOrPost('pickupCountryID', 0);
        $this->dropoffDestinationID = GetParameterFromGetOrPost('dropoffDestinationID', 0);
        $this->dropoffRegionID = GetParameterFromGetOrPost('dropoffRegionID', 0);
        $this->dropoffCountryID = GetParameterFromGetOrPost('dropoffCountryID', 0);
        $this->pickupDestinationLevel = GetParameterFromGetOrPost('pickupDestinationLevel', 0);
        $this->dropoffDestinationLevel = GetParameterFromGetOrPost('dropoffDestinationLevel', 0);
        $this->objectTypeID = GetParameterFromGetOrPost('objectTypeID', 0);
        $this->objectTypeGroupID = GetParameterFromGetOrPost('objectTypeGroupID', 0);
        $this->persons = GetParameterFromGetOrPost('persons', 1);
        $this->children = GetParameterFromGetOrPost('children', 0);
        $this->childrenAges = GetParameterFromGetOrPostInArray('childrenAges', null);

        $this->numberOfStars = GetParameterFromGetOrPost('numberOfStars', 0);
        $this->categoryID = GetParameterFromGetOrPost('categoryID', 0);

        $this->onlyOnSpecialOffer = GetParameterFromGetOrPost('onlyOnSpecialOffer', false);

        $this->priceType = GetParameterFromGetOrPost('priceType', 'Total');
        $this->toDisplaySpecialOffers = GetParameterFromGetOrPost('toDisplaySpecialOffers', false);
        $this->categoryIntersectionID = GetParameterFromGetOrPost('categoryIntersectionID', 0);
        $this->unitCategoryIDList = GetParameterFromGetOrPostInArray("unitCategoryIDList", 0);
        $this->globalDestinationID = GetParameterFromGetOrPost('globalDestinationID', 0);
        $this->searchSupplier = GetParameterFromGetOrPost('searchSupplier', 0);
        $this->destinationID = GetParameterFromGetOrPost('destinationID', 0);
        $this->regionID = GetParameterFromGetOrPost('regionID', 0);
        $this->countryID = GetParameterFromGetOrPost('countryID', 0);

        $this->objectPhoto = array(
            'ResponseDetail' => 'ObjectPhotos',
            'NumberOfResults' => '100'
        );

        $unitPhoto = array(
            'ResponseDetail' => 'UnitPhotos',
            'NumberOfResults' => '100'
        );
        $this->group = array(
            'ResponseDetail' => 'ObjectDetailedAttributes',
            'NumberOfResults' => '10'
        );

        $this->description = array(
            'ResponseDetail' => 'UnitDescription',
            'NumberOfResults' => '1'
        );

        if (!$this->ignorePriceAndAvailability) {
            $this->calculatedInfo = array(
                'ResponseDetail' => 'CalculatedPriceInfo',
                'NumberOfResults' => '1'
            );


            $this->outParameterList = array(
                '0' => $this->objectPhoto,
                '1' => $this->group,
                '2' => $this->description,
                '3' => $this->calculatedInfo,
                '4' => $unitPhoto
            );
        } else {
            $this->outParameterList = array(
                '0' => $this->objectPhoto,
                '1' => $this->group,
                '2' => $this->description,
                '3' => $unitPhoto
            );
        }

        $this->personsFilter = array(
            'AttributeID' => 120,
            'AttributeValue' => $this->persons,
            'ComparisonType' => 'GreaterOrEqualThan'
        );

        $this->unitFilters = array(
            '0' => $this->personsFilter
        );

        $additionalObjectFilters = GetParameterFromGetOrPostInArray('objectAttributeFilters', null);
        $this->objectFilters = array();
        $this->objectFilters = GeneratAttributeFilterList($additionalObjectFilters, $this->objectFilters);

        $additionalUnitFilters = GetParameterFromGetOrPostInArray('unitAttributeFilters', null);
        $this->unitFilters = GeneratAttributeFilterList($additionalUnitFilters, $this->unitFilters);


        $this->sortParameterList = array();
        $sortByPrice = GetParameterFromGetOrPost('sortByPrice', '');
        if ($sortByPrice != '') {
            $sortOrder = 'Ascending';
            if ($sortByPrice == '0') {
                $sortOrder = 'Descending';
            }
            array_push($this->sortParameterList, array('SortBy' => 'Price', 'SortOrder' => $sortOrder));
        }
        ///add sort by priority
        array_push($this->sortParameterList, array('SortBy' => 'Priority', 'SortOrder' => 'Descending'));
        $this->xsltPath = iTravelGeneralSettings::$iTravelXSLTTransportationSearchResultsPath;

        $this->pageSize = GetParameterFromGetOrPost('pageSize', $this->pageSize);
    }

    // Get API response object, transform it via XSLT processor and echo the resulting HTML.
    public function TransformAndEchoAPIResponse()
    {
    }
    // Get API response object, transform it via XSLT processor and return the resulting HTML as string.
    public function GetTransformedAPIResponse()
    {
    }

    public function EchoTransportationSearchResults()
    {
        $fromParameter = null;
        if ($this->from != null) {
            $fromParameter = $this->from->format(DATE_ATOM);
        }
        $toParameter = null;
        if ($this->to != null) {
            $toParameter = $this->to->format(DATE_ATOM);
        }

        $pickupDestinationIDList = null;
        if (!empty($this->pickupDestinationID)) {
            $pickupDestinationIDList = explode(',', $this->pickupDestinationID);
        }
        $pickupRegionIDList = null;
        if (!empty($this->pickupRegionID)) {
            $pickupRegionIDList = explode(',', $this->pickupRegionID);
        }
        $pickupCountryIDList = null;
        if (!empty($this->pickupCountryID)) {
            $pickupCountryIDList = explode(',', $this->pickupCountryID);
        }
        $dropoffDestinationIDList = null;
        if (!empty($this->dropoffDestinationID)) {
            $dropoffDestinationIDList = explode(',', $this->dropoffDestinationID);
        }
        $dropoffRegionIDList = null;
        if (!empty($this->dropoffRegionID)) {
            $dropoffRegionIDList = explode(',', $this->dropoffRegionID);
        }
        $dropoffCountryIDList = null;
        if (!empty($this->dropoffCountryID)) {
            $dropoffCountryIDList = explode(',', $this->dropoffCountryID);
        }

        $getTransportationSearchResultsParameters = array(
            'StartDate' => $fromParameter,
            'EndDate' => $toParameter,
            'PickupDestinationIDList' => $pickupDestinationIDList,
            'PickupRegionIDList' => $pickupRegionIDList,
            'PickupCountryIDList' => $pickupCountryIDList,
            'DropoffDestinationIDList' => $dropoffDestinationIDList,
            'DropoffRegionIDList' => $dropoffRegionIDList,
            'DropoffCountryIDList' => $dropoffCountryIDList,
            'ObjectTypeIDList' => array("0" => $this->objectTypeID),
            'ObjectTypeGroupIDList' => explode(',', $this->objectTypeGroupID),
            'CategoryIDListUnion' => array("0" => $this->categoryID),
            'PriceFrom' => $this->priceFrom,
            'PriceTo' => $this->priceTo,
            'InPriceType' => $this->priceType,
            'SortParameterList' => $this->sortParameterList,
            'PageSize' => $this->pageSize,
            'CurrentPage' => $this->currentPage,
            'ObjectAttributeFilterList' => $this->objectFilters,
            'UnitAttributeFilterList' => $this->unitFilters,
            'ThumbnailWidth' => $this->thumbnailWidth,
            'ThumbnailHeight' => $this->thumbnailHeight,
            'CurrencyID' => $this->currencyID,
            'OutParameterList' => $this->outParameterList,
            'LanguageID' => $this->languageID,
            'IgnorePriceAndAvailability' => false,
            'UnitTypeIDList' => array(),
            'UnitCategoryIDList' => $this->unitCategoryIDList,
            'CategoryIDListIntersection' => array(),
            'OnlyOnSpecialOffer' => false,
            'ChildrenAgeList' => $this->childrenAges,
            'IsRelativeUrl' => $this->isRelativeURL,
            'CustomerID' => 0,
            'AffiliateID' => 0,
            'DestinationCodes' => explode(',', $this->globalDestinationID),
            'SearchSupplierList' => array()
        );

        $this->bookingForm = iTravelGeneralSettings::BookingFormUrl($this->languageID);

        $parameters = array(
            'urlPrefixParameter' => $this->urlPrefix,
            'OpenInParent' => $this->openInParent,
            'ignorePriceAndAvailabilityParam' => $this->ignorePriceAndAvailability,
            'fromParameter' => $this->fromString,
            'toParameter' => $this->toString,
            'pickupDestinationLevelParameter' => 0,
            'pickupDestinationIDParameter' => $this->pickupDestinationID,
            'pickupRegionIDParameter' => $this->pickupRegionID,
            'pickupCountryIDParameter' => $this->pickupCountryID,
            'dropoffDestinationLevelParameter' => 0,
            'dropoffDestinationIDParameter' => 0,
            'objectTypeIDParameter' => 1,
            'objectTypeGroupIDParameter' => 1,
            'numberOfStarsParameter' => 0,
            'childrenAgesParameter' => 0,
            'childrenParameter' => 0,
            'personsParameter' => 0,
            'categoryIDParameter' => 0,
            'onlyOnSpecialOfferParameter' => false,
            'affiliateID' => $this->EncryptedAffiliateID,
            'priceTypeParameter' => $this->priceType,
            'bookingAddressDisplayURL' => $this->bookingForm,
            'detailsURL' => $this->transportationDetailedDescription,
            'SearchPage' => $this->transportationSearchResults,
            'postaviDirektanLink' => false,
            'BookingLinkInNewWindow' => false,
            'pickupDestinationID' => 0,
            'dropoffDestinationID' => 0,
            'globalDestinationID' => $this->globalDestinationID,
            'searchSupplier' => 0,
            'ClientWebAddress' => '',
            'imagesFolderPath' => iTravelGeneralSettings::$iTravelImagesPath,
            'proxyPath' => iTravelGeneralSettings::$iTravelProxyPath,
            'scriptsFolderPath' => iTravelGeneralSettings::$iTravelScriptsPath,
            'contactFormTemplateURL' => iTravelGeneralSettings::$iTraveContactFormTemplateURL
        );

        /// Call the API
        $apiSettings = InitAPI();
        if (isset($apiSettings)) {
            try {
                $transportationSearchResults = $apiSettings->GetTransportationSearchResults(array('getTransportationSearchResultsParameters' => $getTransportationSearchResultsParameters));
                TransformAndEchoResult($transportationSearchResults, $parameters, $this->xsltPath, 'GetTransportationSearchResultsResult', 'TransportationSearchResults');
            } catch (SoapFault $fault) {
                return itravel_display_soap_fault(APISoapFault::$GETTRANSPORTATIONSEARCHRESULTS);
            }
        } else {
            return itravel_display_soap_fault(APISoapFault::$INIT_API_FAULT);
        }
    }
}

/// class used for getting Transportation detailed description
class GetTransportationDetailedDescription
{

    #region Properties
    public $ignorePriceAndAvailability = false;
    public $from;
    public $fromString;
    public $to;
    public $toString;
    public $persons;
    public $children;
    public $childrenAges;
    public $objectID;
    public $objectCode;
    public $provider;
    public $searchName = '';
    public $searchDestination;
    public $startDate;
    public $endDate;
    public $languageID;
    public $currencyID;
    public $tab;
    public $unitActivityStatus;
    public $objectURL;
    public $priceType;
    public $pickUpDestinationID;
    public $dropOffDestinationID;
    public $marketID = 0;
    public $customerID = 0;
    public $cartID = 0;
    public $xsltPath;
    public $bookingForm;
    public $EncryptedAffiliateID = 0;
    public $bookingAdresa = '';
    #endregion

    public function __construct()
    {
        $this->bookingForm = iTravelGeneralSettings::$iTravelPageBookingFormPath;

        $this->ignorePriceAndAvailability = GetParameterFromGetOrPost('ignorePriceAndAvailability', false);

        $this->from = new DateTime();
        $this->fromString = GetParameterFromGetOrPost('from', 0, true);
        if (!empty($this->fromString)) {
            $this->from->setTimestamp($this->fromString / 1000);
        } else {
            $this->from = null;
        }

        $this->to = new DateTime();
        $this->toString = GetParameterFromGetOrPost('to', 0, true);
        if (!empty($this->toString)) {
            $this->to->setTimestamp($this->toString / 1000);
        } else {
            $this->to = null;
        }

        $this->languageID = GetParameterFromGetOrPost('languageID', iTravelGeneralSettings::$iTravelDefaultLanguageID);
        $this->currencyID = GetParameterFromGetOrPost('currencyID', iTravelGeneralSettings::$iTravelDefaultCurrencyID);
        $this->persons = GetParameterFromGetOrPost('persons', 1, true);
        $this->childrenAges = GetParameterFromGetOrPost('childrenAges', '', true);
        $this->objectID = GetParameterFromGetOrPost('objectID', 0);
        $this->objectCode = GetParameterFromGetOrPost('objectCode', null);
        $this->provider = GetParameterFromGetOrPost('provider', 0);
        $this->searchName = GetParameterFromGetOrPost('searchName', '');
        $this->searchDestination = GetParameterFromGetOrPost('searchDestination', 0);
        $this->tab = GetParameterFromGetOrPost('tab', 0);
        $this->objectURL = GetParameterFromGetOrPost('objectURL', '');
        $this->priceType = GetParameterFromGetOrPost('priceType', 'Total');
        $this->pickUpDestinationID = GetParameterFromGetOrPost('pickUpDestinationID', 0);
        $this->dropOffDestinationID = GetParameterFromGetOrPost('dropOffDestinationID', 0);
        $this->unitActivityStatus = GetParameterFromGetOrPost('unitActivityStatus', 0);

        $this->xsltPath = iTravelGeneralSettings::$iTravelXSLTTransportationDetailedDescriptionPath;
    }

    // Get API response object, transform it via XSLT processor and echo the resulting HTML.
    public function TransformAndEchoAPIResponse()
    {
    }
    // Get API response object, transform it via XSLT processor and return the resulting HTML as string.
    public function GetTransformedAPIResponse()
    {
    }

    public function GetTransportationDetailedDescriptionHTML()
    {
        $fromParameter = null;
        if ($this->from != null) {
            $fromParameter = $this->from->format(DATE_ATOM);
        }
        $toParameter = null;
        if ($this->to != null) {
            $toParameter = $this->to->format(DATE_ATOM);
        }
        $childrenAgesParam = array();
        if (!empty($this->childrenAges) || $this->childrenAges == "0") {
            $childrenAgesParam = explode(',', $this->childrenAges);
        }

        $getTransportationDetailedDescriptionParameters = array(
            'StartDate' => $fromParameter,
            'EndDate' => $toParameter,
            'NumberOfPersons' => $this->persons,
            'ObjectID' => $this->objectID,
            'ThumbnailWidth' => 191,
            'ThumbnailHeight' => 142,
            'CurrencyID' => $this->currencyID,
            'LanguageID' => $this->languageID,
            'IgnorePriceAndAvailability' => $this->ignorePriceAndAvailability,
            'InPriceType' => $this->priceType,
            'ListUnitActivityStatus' => "Active",
            'ObjectURL' => $this->objectURL,
            'ObjectCode' => $this->objectCode,
            'ChildrenAgeList' => $childrenAgesParam,
            'CustomerID' => $this->customerID,
            'MarketID' => $this->marketID,
            'AffiliateID' => $this->EncryptedAffiliateID
        );

        $this->bookingForm = iTravelGeneralSettings::BookingFormUrl($this->languageID);

        $parameters = array(
            'bookingAddressDisplayURL' => $this->bookingForm,
            'unitActivityStatus' => 0,
            'affiliateIDParameter' => $this->EncryptedAffiliateID,
            'postaviDirektanLink' => false,
            'BookingLinkInNewWindow' => false,
            'childrenParam' => count($childrenAgesParam),
            'childrenAgesParam' => $this->childrenAges,
            'showChildrenAgesParam' => false,
            'detailsAddressDisplayURL' => iTravelGeneralSettings::$iTravelPageTransportationDetailedDescriptionPath,
            'objectsIDWithoutUnit' => 0,
            'cartIDParameter' => $this->cartID,
            'marketIDParameter' => $this->marketID,
            'customerIDParameter' => $this->customerID,
            'objectCode' => $this->objectCode,
            'SelectedPickUpDestinationIDGlobal' => 0,
            'SelectedDropOffDestinationIDGlobal' => 0,
            'imagesFolderPath' => iTravelGeneralSettings::$iTravelImagesPath,
            'proxyPath' => iTravelGeneralSettings::$iTravelProxyPath,
            'scriptsFolderPath' => iTravelGeneralSettings::$iTravelScriptsPath,
            'contactFormTemplateURL' => iTravelGeneralSettings::$iTraveContactFormTemplateURL
        );

        /// Call the API
        $apiSettings = InitAPI();
        if (isset($apiSettings)) {
            try {
                $transportationDetailedDescription = $apiSettings->GetTransportationDetailedDescription(array('getTransportationDetailedDescriptionParameters' => $getTransportationDetailedDescriptionParameters));
                return TransformResult($transportationDetailedDescription, $parameters, $this->xsltPath, 'GetTransportationDetailedDescriptionResult', 'TransportationDetails');
            } catch (SoapFault $fault) {
                return itravel_display_soap_fault(APISoapFault::$GETTRANSPORTATIONDETAILEDDESCRIPTION);
            }
        } else {
            return itravel_display_soap_fault(APISoapFault::$INIT_API_FAULT);
        }
    }

    public function EchoTransportationDetailedDescription()
    {
        echo $this->GetTransportationDetailedDescriptionHTML();
    }
}

class GetSearchFields
{
    public $languageID;
    public $objectTypeIDList;
    public $objectTypeGroupIDList;
    public $categoryIDList;
    public $countryID;
    public $regionID;

    public function __construct()
    {
        $this->languageID = GetParameterFromGetOrPost('languageID', iTravelGeneralSettings::$iTravelDefaultLanguageID);
        $this->currencyID = GetParameterFromGetOrPost('currencyID', iTravelGeneralSettings::$iTravelDefaultCurrencyID);

        if (isset($_POST['objectTypeIDList'])) {
            $this->objectTypeIDList = explode(',', $_POST['objectTypeIDList']);
        }
        if (isset($_POST['objectTypeGroupIDList'])) {
            $this->objectTypeGroupIDList = explode(',', $_POST['objectTypeGroupIDList']);
        }
        if (isset($_POST['categoryIDList'])) {
            $this->categoryIDList = explode(',', $_POST['categoryIDList']);
        }
        $this->countryID = GetParameterFromGetOrPost('countryID', 0);
        $this->regionID = GetParameterFromGetOrPost('regionID', 0);
    }

    // Return API response object.
    public function GetAPIResponse()
    {
        /// Create the object for calling the API
        $getSearchFieldsParameter = array(
            'LanguageID' => $this->languageID,
            'ObjectTypeIDList' => $this->objectTypeIDList,
            'ObjectTypeGroupIDList' => $this->objectTypeGroupIDList,
            'CategoryIDList' => $this->categoryIDList,
            'CountryID' => $this->countryID,
            'RegionID' => $this->regionID
        );

        /// Call the API
        $apiSettings = InitAPI();
        if (isset($apiSettings)) {
            try {
                $getSearchFieldsResult = $apiSettings->GetSearchFields(array('getSearchFieldsParameters' => $getSearchFieldsParameter));
                return $getSearchFieldsResult;
            } catch (SoapFault $fault) {
                file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/iTravelErrors/" . date_timestamp_get(date_create()), $fault->__toString());
                return itravel_display_soap_fault(APISoapFault::$GETSEARCHFIELDS);
            }
        } else {
            return itravel_display_soap_fault(APISoapFault::$INIT_API_FAULT);
        }
    }

    // Get API response object, transform it via XSLT processor and echo the resulting HTML.
    public function TransformAndEchoAPIResponse()
    {
    }
    // Get API response object, transform it via XSLT processor and return the resulting HTML as string.
    public function GetTransformedAPIResponse()
    {
    }

    public function EchoSearchFieldsXML()
    {

        /// Create the object for calling the API
        $getSearchFieldsParameter = array(
            'LanguageID' => $this->languageID,
            'ObjectTypeIDList' => $this->objectTypeIDList,
            'ObjectTypeGroupIDList' => $this->objectTypeGroupIDList,
            'CategoryIDList' => $this->categoryIDList,
            'CountryID' => $this->countryID,
            'RegionID' => $this->regionID
        );

        /// Call the API
        $apiSettings = InitAPI();
        if (isset($apiSettings)) {
            try {
                $getSearchFieldsResult = $apiSettings->GetSearchFields(array('getSearchFieldsParameters' => $getSearchFieldsParameter));

                $iTravelAPIResponseXML = '<?xml version="1.0"?>' . XMLSerializer::generateXmlFromArray($getSearchFieldsResult, "root");

                echo $iTravelAPIResponseXML;
            } catch (SoapFault $fault) {
                file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/iTravelErrors/" . date_timestamp_get(date_create()), $fault->__toString());
                return itravel_display_soap_fault(APISoapFault::$GETSEARCHFIELDS);
            }
        } else {
            return itravel_display_soap_fault(APISoapFault::$INIT_API_FAULT);
        }
    }
}

class GetAllDestinations
{
    public $languageID;
    public $transferDestinations;
    public $SearchQuery;

    public function __construct()
    {
        $this->languageID = GetParameterFromGetOrPost('languageID', iTravelGeneralSettings::$iTravelDefaultLanguageID);
        $this->transferDestinations = GetParameterFromGetOrPost('transfers', null);
    }

    // Get API response object, transform it via XSLT processor and echo the resulting HTML.
    public function TransformAndEchoAPIResponse()
    {
    }
    // Get API response object, transform it via XSLT processor and return the resulting HTML as string.
    public function GetTransformedAPIResponse()
    {
    }

    public function GetAllDestinations()
    {
        /// Create the object for calling the API
        $getAllDestinationsParameter = array(
            'LanguageID' => $this->languageID,
            'TransferDestinations' => $this->transferDestinations,
            'SearchQuery' => $this->SearchQuery
        );

        global $cacheClient;
        $api = InitAPI();
        return $cacheClient->GetAPIMethodDataOrWrite($api, 'GetAllDestinations', ['getAllDestinationsParameter' => $getAllDestinationsParameter]);
    }
}

class GetDestinations
{
    public $languageID;
    public $categoryID;
    public $countryID;
    public $objectTypeGroupID;
    public $objectTypeID;
    public $regionID;
    public $seasonID;

    public function __construct()
    {
        $this->languageID = GetParameterFromGetOrPost('languageID', iTravelGeneralSettings::$iTravelDefaultLanguageID, true);
    }

    // Get API response object, transform it via XSLT processor and echo the resulting HTML.
    public function TransformAndEchoAPIResponse()
    {
    }
    // Get API response object, transform it via XSLT processor and return the resulting HTML as string.
    public function GetTransformedAPIResponse()
    {
    }

    public function GetDestinations()
    {
        /// Create the object for calling the API
        $getDestinationsParameters = array(
            'LanguageID' => $this->languageID,
            'CategoryID' => $this->categoryID,
            'CountryID' => $this->countryID,
            'ObjectTypeGroupID' => $this->objectTypeGroupID,
            'ObjectTypeID' => $this->objectTypeID,
            'RegionID' => $this->regionID,
            'SeasonID' => $this->seasonID
        );

        /// Call the API
        $apiSettings = InitAPI();
        if (isset($apiSettings)) {
            try {
                $getAllDestinationsResponse = $apiSettings->GetDestinations(array('getDestinationsParameters' => $getDestinationsParameters));

                return $getAllDestinationsResponse;
            } catch (SoapFault $fault) {
                file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/iTravelErrors/" . date_timestamp_get(date_create()), $fault->__toString());
                return itravel_display_soap_fault(APISoapFault::$GETDESTINATIONS);
            }
        } else {
            return itravel_display_soap_fault(APISoapFault::$INIT_API_FAULT);
        }
    }
}

class GetRegions
{

    // <editor-fold defaultstate="collapsed" desc="Properties">
    public $languageID;
    public $categoryID;
    public $countryID;
    public $objectTypeGroupID;
    public $objectTypeID;
    public $seasonID;
    // </editor-fold>

    public function __construct()
    {
        $this->languageID = GetParameterFromGetOrPost('languageID', iTravelGeneralSettings::$iTravelDefaultLanguageID, true);
    }

    public function GetRegions()
    {
        /// Create the object for calling the API
        $getRegionsParameters = array(
            'CountryID' => $this->countryID,
            'ObjectTypeID' => $this->objectTypeID,
            'ObjectTypeGroupID' => $this->objectTypeGroupID,
            'CategoryID' => $this->categoryID,
            'LanguageID' => $this->languageID,
            'SeasonID' => $this->seasonID
        );
        global $cacheClient;
        $api = InitAPI();
        return $cacheClient->GetAPIMethodDataOrWrite($api, 'GetRegions', ['getRegionsParameters' => $getRegionsParameters]);
    }
}

/// object holder for the Booking Form
class BookingForm
{
    public $bookingAddress;

    public function __construct()
    {
        $this->bookingAddress = GetParameterFromGetOrPost('bookingAddress', '');
    }

    public function EchoBookingForm()
    {
        $finalBookingAddress = $this->bookingAddress;
        if (!empty($_GET['cart'])) {
            $finalBookingAddress = $finalBookingAddress . "&cartID=" . $_SESSION['iTravelCartID'];
        }
        echo "<iframe class=\"booking-form-frame\" width=\"300\" height=\"2000\" src=\"" . $finalBookingAddress . "\" frameborder=\"0\" allowtransparency=\"1\" style=\"margin:0; padding:0; border:none; width:100%;\" ></iframe>";
    }
}

class GetAllSeoData
{
    public function __construct()
    {
    }

    // Get API response object, transform it via XSLT processor and echo the resulting HTML.
    public function TransformAndEchoAPIResponse()
    {
    }
    // Get API response object, transform it via XSLT processor and return the resulting HTML as string.
    public function GetTransformedAPIResponse()
    {
    }

    public function GetAllSeoData()
    {
        $seoDataParameters = array();
        /// Call the API
        $apiSettings = InitAPI();
        if (isset($apiSettings)) {
            try {
                $getAllSeoDataResults = $apiSettings->GetAllSeoData(array('seoDataParameters' => $seoDataParameters));

                return $getAllSeoDataResults;
            } catch (SoapFault $fault) {
                return itravel_display_soap_fault(APISoapFault::$GETALLSEODATA);
            }
        } else {
            return itravel_display_soap_fault(APISoapFault::$INIT_API_FAULT);
        }
    }
}

class Customer
{

    #region Properties
    public $CustomerID;
    public $IsCustomer;
    public $IsSupplier;
    public $IsPartner;
    public $LanguageID;
    public $UniqueIndentificationNumber;
    public $CustomerType;
    public $Adress;
    public $City;
    public $ZipCode;
    public $TelephoneNumber1;
    public $TelephoneNumber2;
    public $MobilePhoneNumber;
    public $Fax;
    public $Email;
    public $PersonName;
    public $PersonSurname;
    public $CompanyName;
    public $PassportNumber;
    public $taxPayerType = 1;
    public $BirthDate;
    public $BirthPlace;
    public $CountryID;
    public $CitizenshipID;
    public $Sex;
    public $ContractType;
    public $UserList;
    public $OtherSystemID;
    public $listCustomField;
    #endregion

    public function __construct()
    {
    }
}

class UserCredentials
{
    public $Email;
    public $Password;

    public function __construct()
    {
    }

    // Get API response object, transform it via XSLT processor and echo the resulting HTML.
    public function TransformAndEchoAPIResponse()
    {
    }
    // Get API response object, transform it via XSLT processor and return the resulting HTML as string.
    public function GetTransformedAPIResponse()
    {
    }

    public function CheckUserCredentials()
    {
        $checkUserCredentialsRequest = array('Email' => $this->Email,
            'Password' => $this->Password);
        /// Call the API
        $apiSettings = InitAPI();
        if (isset($apiSettings)) {
            try {
                $checkUserCredentialsResponse = $apiSettings->CheckUserCredentials(array('checkUserCredentialsRequest' => $checkUserCredentialsRequest));

                return $checkUserCredentialsResponse;
            } catch (SoapFault $fault) {
                return itravel_display_soap_fault(APISoapFault::$USERCREDENTIALS);
            }
        } else {
            return itravel_display_soap_fault(APISoapFault::$INIT_API_FAULT);
        }
    }
}

class CustomerInsert
{
    public $Customer;

    public function __construct()
    {
        $this->Customer = array(
            'TaxPayerType' => 1,
            'CustomerID' => 0,
            'IsCustomer' => true,
            'IsSupplier' => false,
            'IsPartner' => false,
            'CustomerType' => 0,
            'ContractType' => 1,
            'CreatedDate' => (new DateTime())->format(DATE_ATOM),
            'BirthDate' => (new DateTime())->format(DATE_ATOM)
        );
    }

    // Get API response object, transform it via XSLT processor and echo the resulting HTML.
    public function TransformAndEchoAPIResponse()
    {
    }
    // Get API response object, transform it via XSLT processor and return the resulting HTML as string.
    public function GetTransformedAPIResponse()
    {
    }

    public function InsertCustomer()
    {
        $customerInsertParameters = array('Customer' => $this->Customer);
        /// Call the API
        $apiSettings = InitAPI();
        if (isset($apiSettings)) {
            try {
                $customerInsertResponse = $apiSettings->CustomerInsert(array('customerInsertParameters' => $customerInsertParameters));

                return $customerInsertResponse;
            } catch (SoapFault $fault) {
                return itravel_display_soap_fault(APISoapFault::$CUSTOMERINSERT);
            }
        } else {
            return itravel_display_soap_fault(APISoapFault::$INIT_API_FAULT);
        }
    }
}

class GetAllReservations
{
    public $LanguageID;
    public $CustomerID;
    public $ReservationCreationDateFrom;
    public $ReservationCreationDateTo;
    public $PageSize;
    public $CurrentPage;
    public $FetchDocuments;
    public $SortParameters;

    public function __construct()
    {
    }

    // Get API response object, transform it via XSLT processor and echo the resulting HTML.
    public function TransformAndEchoAPIResponse()
    {
    }
    // Get API response object, transform it via XSLT processor and return the resulting HTML as string.
    public function GetTransformedAPIResponse()
    {
    }

    public function GetAllReservations()
    {
        $getAllReservationsParameters = array('LanguageID' => $this->LanguageID,
            'CustomerID' => $this->CustomerID,
            'ReservationCreationDateFrom' => $this->ReservationCreationDateFrom,
            'ReservationCreationDateTo' => $this->ReservationCreationDateTo,
            'PageSize' => $this->PageSize,
            'CurrentPage' => $this->CurrentPage,
            'FetchDocuments' => $this->FetchDocuments,
            'SortParameters' => $this->SortParameters);

        /// Call the API
        $apiSettings = InitAPI();
        if (isset($apiSettings)) {
            try {
                $getAllReservationsReponse = $apiSettings->GetAllReservations(array('request' => $getAllReservationsParameters));

                return $getAllReservationsReponse;
            } catch (SoapFault $fault) {
                return itravel_display_soap_fault(APISoapFault::$GETALLRESERVATIONS);
            }
        } else {
            return itravel_display_soap_fault(APISoapFault::$INIT_API_FAULT);
        }
    }
}

class ChangeReservationStatus
{
    public $ReservationID;
    public $StatusID;

    public function __construct()
    {
    }

    // Get API response object, transform it via XSLT processor and echo the resulting HTML.
    public function TransformAndEchoAPIResponse()
    {
    }
    // Get API response object, transform it via XSLT processor and return the resulting HTML as string.
    public function GetTransformedAPIResponse()
    {
    }

    public function ChangeReservationStatus()
    {
        $changeReservationsStatusParameters = array('ReservationID' => $this->ReservationID,
            'StatusID' => $this->StatusID);

        /// Call the API
        $apiSettings = InitAPI();
        if (isset($apiSettings)) {
            try {
                $changeReservationStatusResponse = $apiSettings->ChangeReservationStatus(array('request' => $changeReservationsStatusParameters));

                return $changeReservationStatusResponse;
            } catch (SoapFault $fault) {
                return itravel_display_soap_fault(APISoapFault::$CHANGERESERVATIONSTATUS);
            }
        } else {
            return itravel_display_soap_fault(APISoapFault::$INIT_API_FAULT);
        }
    }
}

class GetReservation
{
    public $LanguageID;
    public $ReservationID;

    public function __construct()
    {
    }

    // Get API response object, transform it via XSLT processor and echo the resulting HTML.
    public function TransformAndEchoAPIResponse()
    {
    }
    // Get API response object, transform it via XSLT processor and return the resulting HTML as string.
    public function GetTransformedAPIResponse()
    {
    }

    public function GetReservation()
    {
        $getReservationParameters = array('LanguageID' => $this->LanguageID,
            'ReservationID' => $this->ReservationID);

        /// Call the API
        $apiSettings = InitAPI();
        if (isset($apiSettings)) {
            try {
                $getReservationResponse = $apiSettings->GetReservation(array('getReservationParameters' => $getReservationParameters));

                return $getReservationResponse;
            } catch (SoapFault $fault) {
                return itravel_display_soap_fault(APISoapFault::$GETRESERVATION);
            }
        } else {
            return itravel_display_soap_fault(APISoapFault::$INIT_API_FAULT);
        }
    }
}

class GetAllTransactions
{
    public $CustomerID;
    public $PageSize;
    public $CurrentPage;
    public $SortParameters;
    public $LanguageID;

    public function __construct()
    {
    }

    // Get API response object, transform it via XSLT processor and echo the resulting HTML.
    public function TransformAndEchoAPIResponse()
    {
    }
    // Get API response object, transform it via XSLT processor and return the resulting HTML as string.
    public function GetTransformedAPIResponse()
    {
    }

    public function GetAllTransactions()
    {
        $getAllTransactionsParameters = array('CustomerID' => $this->CustomerID,
            'LanguageID' => $this->LanguageID,
            'PageSize' => $this->PageSize,
            'CurrentPage' => $this->CurrentPage,
            'SortParameters' => $this->SortParameters
            );

        /// Call the API
        $apiSettings = InitAPI();
        if (isset($apiSettings)) {
            try {
                $getAllTransactionsReponse = $apiSettings->GetAllTransactions(array('request' => $getAllTransactionsParameters));

                return $getAllTransactionsReponse;
            } catch (SoapFault $fault) {
                return itravel_display_soap_fault(APISoapFault::$GETALLTRANSACTIONS);
            }
        } else {
            return itravel_display_soap_fault(APISoapFault::$INIT_API_FAULT);
        }
    }
}

class GetReservationCanNotStartDates
{
    public $UnitID;
    public $StartDate;
    public $EndDate;

    public function __construct()
    {
    }

    // Get API response object, transform it via XSLT processor and echo the resulting HTML.
    public function TransformAndEchoAPIResponse()
    {
    }
    // Get API response object, transform it via XSLT processor and return the resulting HTML as string.
    public function GetTransformedAPIResponse()
    {
    }

    public function GetReservationCanNotStartDates()
    {
        $GetReservationCanNotStartDatesRequest = array(
            'UnitID' => $this->UnitID,
            'StartDate' => $this->StartDate,
            'EndDate' => $this->EndDate
        );
        /// Call the API
        $apiSettings = InitAPI();
        if (isset($apiSettings)) {
            try {
                $GetReservationCanNotStartDatesResponse = $apiSettings->GetReservationCanNotStartDates(array('request' => $GetReservationCanNotStartDatesRequest));
                return $GetReservationCanNotStartDatesResponse;
            } catch (SoapFault $fault) {
                return itravel_display_soap_fault(APISoapFault::$GETRESERVATIONCANNOTSTARTDATES);
            }
        } else {
            return itravel_display_soap_fault(APISoapFault::$INIT_API_FAULT);
        }
    }
}

class GetReservationCanNotEndDates
{
    public $UnitID;
    public $StartDate;
    public $EndDate;

    public function __construct()
    {
    }

    // Get API response object, transform it via XSLT processor and echo the resulting HTML.
    public function TransformAndEchoAPIResponse()
    {
    }
    // Get API response object, transform it via XSLT processor and return the resulting HTML as string.
    public function GetTransformedAPIResponse()
    {
    }

    public function GetReservationCanNotEndDates()
    {
        $GetReservationCanNotEndDatesRequest = array(
            'UnitID' => $this->UnitID,
            'StartDate' => $this->StartDate,
            'EndDate' => $this->EndDate
            );

        /// Call the API
        $apiSettings = InitAPI();
        if (isset($apiSettings)) {
            try {
                $GetReservationCanNotEndDatesResponse = $apiSettings->GetReservationCanNotEndDates(array('request' => $GetReservationCanNotEndDatesRequest));


                return $GetReservationCanNotEndDatesResponse;
            } catch (SoapFault $fault) {
                return itravel_display_soap_fault(APISoapFault::$GETRESERVATIONCANNOTENDDATES);
            }
        } else {
            return itravel_display_soap_fault(APISoapFault::$INIT_API_FAULT);
        }
    }
}

class GetPassengersOnReservation
{
    public $ReservationUniqueID;
    public $LanguageID;
    public function __construct()
    {
    }

    public function GetPassengersOnReservation()
    {
        $GetPassengersOnReservationRequest = array(
            'ReservationUniqueID' => $this->ReservationUniqueID,
            'LanguageID' => $this->LanguageID
            );
        /// Call the API
        $apiSettings = InitAPI();
        if (isset($apiSettings)) {
            try {
                $GetPassengersOnReservationResponse = $apiSettings->GetPassengersOnReservation(array('request' => $GetPassengersOnReservationRequest));

                return $GetPassengersOnReservationResponse;
            } catch (SoapFault $fault) {
                return itravel_display_soap_fault(APISoapFault::$GETPASSENGERSONRESERVATION);
            }
        } else {
            return itravel_display_soap_fault(APISoapFault::$INIT_API_FAULT);
        }
    }
}

class PassengerUpdate
{
    public $Passenger;

    public function __construct()
    {
    }

    public function PassengerUpdate()
    {
        $PassengerUpdateRequest = array(
            'Passenger' => $this->Passenger
            );
        /// Call the API
        $apiSettings = InitAPI();
        if (isset($apiSettings)) {
            try {
                $PassengerUpdateResponse = $apiSettings->PassengerUpdate(array('request' => $PassengerUpdateRequest));

                return $PassengerUpdateResponse;
            } catch (SoapFault $fault) {
                return itravel_display_soap_fault(APISoapFault::$PASSENGERUPDATE);
            }
        } else {
            return itravel_display_soap_fault(APISoapFault::$INIT_API_FAULT);
        }
    }
}

class GetPassengerEditFormDTO
{
    public $PassengerID;
    public $Name;
    public $Surname;
    public $DateOfBirth;
    public $MobilePhone;
    public $Birthplace;
    public $PassportNumber;
    public $Address;
    public $Town;
    public $ZipCode;
    //DTO
    public $ListCustomFieldData; // Collection of type CustomField

    public function __construct()
    {
    }
}

class CustomField
{
    public $CustomFieldID;
    public $CustomFieldName;
    public $CustomFieldType;
    //DTOs
    public $CustomFieldValue; // Property of type CustomFiledValue
    public $listCustomFieldValue; // Collection of type CustomFieldValue
    public $AvailableValuesList; // Collection of collections of type CustomFieldValue (Double list)

    public function __construct()
    {
    }
}

class CustomFieldValue
{
    public $LanguageID;
    public $Value;
    public $ValueID;


    public function __construct()
    {
    }
}

class CreateReservationRequest
{
    public $CurrencyID;
    public $Customer;
    public $PaymentMethod ;
    public $AffiliateID;
    public $MarketID;
    public $LanguageID;
    //DTOs
    public $ReservationCustomFields; // Collection of type CustomFieldReservationProcess
    public $ReservationItemsParametersList; // Collection of type ReservationItemParameters
    public $AdHocReservationItemsParametersList; // Collection of type AdHocReservationItemParameters
    public function __construct()
    {
    }
}

class CustomerReservationProcess
{
    public $Email;
    public $Name;
    public $MiddleName;
    public $Surname;
    public $CompanyName;
    public $PersonalID;
    public $Gender;
    public $BirthDate;
    public $Birthplace;
    public $CountryID;
    public $CitizenshipCountryID;
    public $Address;
    public $City;
    public $ZIPCode;
    public $PassportNumber;
    public $MobilePhone;
    public $Telephone;
    public $Telefax;
    public $CustomerType;
    public $LanguageID;
    //DTO
    public $CustomFields; // Collection of type CustomFieldReservationProcess

    public function __construct()
    {
    }
}

class CustomFieldReservationProcess
{
    public $ID;
    public $Value;

    public function __construct()
    {
    }
}

class PaymentMethod
{
    public $PaymentMethod;
    public $PaymentMethodName;

    public function __construct()
    {
    }
}

class ReservationItemParameters
{
    public $ReservationItemOrder;
    public $UnitID;
    public $UnitGDSCode;
    public $StartDate;
    public $EndDate;
    public $CuponCode;
    //DTOs
    public $PassengerList; // Collection of type ReservationProcessPassenger
    public $SelectedServices; // Collection of type ReservationProcessService

    public function __construct()
    {
    }
}

class ReservationProcessPassenger
{
    public $ID;
    public $Name;
    public $Surname;
    public $DateOfBirth;
    //DTO
    public $SelectedServices; // Collection of type ReservationProcessService

    public function __construct()
    {
    }
}

class ReservationProcessService
{
    public $ServiceID;
    public $Amount;
    public $AdHocPrice;

    public function __construct()
    {
    }
}

class AdHocReservationItemParameters
{
    public $ReservationItemOrder;
    public $StartDate;
    public $EndDate;
    //DTOs
    public $PassengerList; // Collection of type ReservationProcessPassenger
    public $SelectedServices; // Collection of type ReservationProcessService

    public function __construct()
    {
    }
}

class CreateReservation
{
    public $CreateReservationRequest;

    public function __construct()
    {
    }

    public function CreateReservation()
    {
        /// Call the API
        $apiSettings = InitAPI();
        if (isset($apiSettings)) {
            try {
                $CreateReservationResponse = $apiSettings->CreateReservation(array('createReservationRequest' => $this->CreateReservationRequest));
                return $CreateReservationResponse;
            } catch (SoapFault $fault) {
                return itravel_display_soap_fault(APISoapFault::$CREATERESERVATION);
            }
        } else {
            return itravel_display_soap_fault(APISoapFault::$INIT_API_FAULT);
        }
    }
}

class GetCustomers
{
	
	public function __construct()
	{
	}
	
	public function getAllSuppliers()
	{
		/// Call the API
		$apiSettings = InitAPI();
		if (isset($apiSettings)) {
			try{
				$getAllCustomersResponse = $apiSettings->GetCustomers(array('customerExportCustomers' => array()));
				
				$supplierList = array();
				
				if(isset($getAllCustomersResponse->GetCustomersResult->listCustomer->Customer)) {
					$customerList = $getAllCustomersResponse->GetCustomersResult->listCustomer->Customer;
					
					foreach ($customerList as $customer) {
						if(isset($customer->IsSupplier)) {
							if($customer->IsSupplier == '1') {
								$supplierList[] = $customer;
							}
						}
					}
				}
				
				return $supplierList;
			} catch (SoapFault $fault) {
				return itravel_display_soap_fault(APISoapFault::$CREATERESERVATION);
			}
		} else {
			return itravel_display_soap_fault(APISoapFault::$INIT_API_FAULT);
		}
	}
}


if (!function_exists('itravel_display_soap_fault')) {
    function itravel_display_soap_fault($fault_code = 0, $is_echo = true)
    {
        switch ($fault_code) {
            case(APISoapFault::$GETAPISETTINGS):
            case(APISoapFault::$GETSEARCHRESULTS):
            case(APISoapFault::$GETTOURSEARCHRESULTS):
            case(APISoapFault::$GETTRANSPORTATIONSEARCHRESULTS):
            case(APISoapFault::$GETDETAILEDDESCRIPTION):
            case(APISoapFault::$GETTOURDETAILEDDESCRIPTION):
            case(APISoapFault::$GETTRANSPORTATIONDETAILEDDESCRIPTION):
            case(APISoapFault::$GETALLDESTINATIONS):
            case(APISoapFault::$GETDESTINATIONS):
            case(APISoapFault::$GETALLSEODATA):
            case(APISoapFault::$USERCREDENTIALS):
            case(APISoapFault::$CUSTOMERINSERT):
            case(APISoapFault::$GETALLRESERVATIONS):
            case(APISoapFault::$CHANGERESERVATIONSTATUS):
            case(APISoapFault::$GETRESERVATION):
            case(APISoapFault::$GETALLTRANSACTIONS):
            case(APISoapFault::$GETRESERVATIONCANNOTSTARTDATES):
            case(APISoapFault::$GETRESERVATIONCANNOTENDDATES):
            case(APISoapFault::$GETPASSENGERSONRESERVATION):
            case(APISoapFault::$PASSENGERUPDATE):
            case(APISoapFault::$CREATERESERVATION):
            case(APISoapFault::$GETREGIONS):
            default:
                return itravel_echo_soap_fault_message('<div class="alert alert-error">Oops... An error has occurred. Please try again.</div>', $is_echo);
        }
    }
}
if (!function_exists('itravel_echo_soap_fault_message')) {
    function itravel_echo_soap_fault_message($message ='', $is_echo = true)
    {
        if ($is_echo) {
            echo($message);
        } else {
            return $message;
        }
    }
}

function searchAssoc($value, $array)
{
    $result = false;
    foreach ($array as $el) {
        if (!is_array($el)) {
            $result = $result||($el==$value);
        } elseif (in_array($value, $el)) {
            $result= $result||true;
        } else {
            $result= $result||false;
        }
    }
    return $result;
}
