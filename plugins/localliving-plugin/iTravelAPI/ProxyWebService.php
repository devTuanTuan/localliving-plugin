<?php

namespace LocalLiving_Plugin\iTravelAPI;

include_once("iTravelAPI.php");

/// The service will be called with a GET parameter to determine which function to call.
/// Other parameters for the function will be given as POST parameters
if (isset($_GET['functionName'])) {
    switch ($_GET['functionName']) {
        case 'GetSearchFields':
            GetSearchFields();
            break;
        case 'GetReservationsTab':
            GetReservationsTab();
            break;
        case 'GetReservationsTabJSON':
            GetReservationsTabJSON();
            break;
        case 'GetResources':
            GetResources();
            break;
        case 'GetAccommodationUnitsForPackageTour':
            GetAccommodationUnitsForPackageTour();
            break;
        case 'GetCapctchaGuid':
            GetCapctchaGuid();
            break;
        case 'GetDestinationsAutoComplete':
            GetDestinationsAutoComplete();
            break;
        case 'GetDestinationsForRegion':
            GetDestinationsForRegion();
            break;
        case 'GetTransportationReservationsTab':
            GetTransportationReservationsTab();
            break;
        case 'InsertCustomer':
            InsertCustomer();
            break;
        case 'CheckUserCredentials':
            CheckUserCredentials();
            break;
        case 'GetAllReservations':
            GetAllReservations();
            break;
        case 'ChangeReservationStatus':
            ChangeReservationStatus();
            break;
        case 'GetReservation':
            GetReservation();
            break;
        case 'GetAllTransactions':
            GetAllTransactions();
            break;
		case 'GetReservationCanNotStartDates':
            GetReservationCanNotStartDates();
            break;
		case 'GetReservationCanNotEndDates':
            GetReservationCanNotEndDates();
            break;
        case 'GetPassengersOnReservation':
			GetPassengersOnReservation();
			break;
        case 'PassengerUpdate':
			PassengerUpdate();
			break;
		case 'GetPopularAccommodation':
			GetPopularAccommodation();
			break;
        default:
            echo "Error in call to ProxyWebService: functionName parameter not recognized.";
            break;
    }
}

/// function gets the search fields
function GetSearchFields() {
    $getSearchFields = new GetSearchFields();
    $getSearchFields->EchoSearchFieldsXML();
}

/// function gets the Reservation Tab
function GetReservationsTab() {
    $getDetailedDescription = new GetDetailedDescription();
    $getDetailedDescription->xsltPath = iTravelGeneralSettings::$iTravelXSLTAccommodationDetailedDescriptionUnitListPath;
    echo '<?xml version="1.0" encoding="utf-8"?> <string xmlns="http://tempuri.org/"> ' . htmlentities($getDetailedDescription->TransformAPIResponse()) . '</string>';
}

function GetReservationsTabJSON() {
    $getDetailedDescription = new GetDetailedDescription();
    echo $getDetailedDescription->GetJSON();
}



function GetTransportationReservationsTab() {
    $getTransportationDetailedDescription = new GetTransportationDetailedDescription();
    $getTransportationDetailedDescription->xsltPath = iTravelGeneralSettings::$iTravelXSLTTransportationDetailsReservationsTabPath;
    echo '<?xml version="1.0" encoding="utf-8"?> <string xmlns="http://tempuri.org/"> ' . htmlentities($getTransportationDetailedDescription->GetTransportationDetailedDescriptionHTML()) . '</string>';
}

function GetAccommodationUnitsForPackageTour() {
    $packageDetails = new GetTourDetailedDescription();
    $packageDetails->xsltPath = iTravelGeneralSettings::$iTravelXSLTTourDetailsAccommodationUnitListPath;
    $packageDetails->period = GetParameterFromGetOrPost('periodStartDateString', null);
    echo '<?xml version="1.0" encoding="utf-8"?> <string xmlns="http://tempuri.org/"> ' . htmlentities($packageDetails->GetTourDetailedDescriptionHTML(true)) . '</string>';
}

// When the SOAP deserializes the response object an array containing only one element
// is treated as an object (property) not as a list containing one elemement
function FormatSOAPResponseArray($soapResponseArray) {
    $_array = array();
    if(empty($soapResponseArray)){
        return $_array;
    }
    if (is_array($soapResponseArray)) {
        foreach ($soapResponseArray as $item) {
            array_push($_array, $item);
        }
    } else {
        array_push($_array, $soapResponseArray);
    }
    return $_array;
}

function GetResources() {
    return null;
}

function GetCapctchaGuid() {
    return null;
}

class LabelValue {

    public $label;
    public $value;

    public function __construct($label, $value) {
        $this->label = $label;
        $this->value = $value;
    }

}

class LabelValueContainer {

    public $d;

}

function startsWith($haystack, $needle) {
    return $needle === "" || strpos($haystack, $needle) === 0;
}

function endsWith($haystack, $needle) {
    return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
}

function GetDestinationsAutoComplete() {
    $inputJson = file_get_contents('php://input');
    $inputParameters = json_decode($inputJson);
    $getDestinationsResponse = new GetAllDestinations();
    $getDestinationsResponse->languageID = $inputParameters->languageID;
    $getDestinationsResponse->SearchQuery = $inputParameters->searchQuery;
    $fetchedDestinations = $getDestinationsResponse->GetAllDestinations();
    /// Max number of results to be displayed in the dropdown
    $numberOfResults = 15;
    $destinationListResponse = new LabelValueContainer();
    $destinationListResponse->d = array();
    $countryDictionary = array();
    $regionDictionary = array();

    if (is_array($fetchedDestinations->GetAllDestinationsResult->CountryList->Country)) {
        foreach ($fetchedDestinations->GetAllDestinationsResult->CountryList->Country as $country) {
            if (empty($countryDictionary[$country->CountryCode]))
                $countryDictionary[$country->CountryCode] = $country;
        }
    }
    else if ($fetchedDestinations->GetAllDestinationsResult->CountryList->Country != null) {
        if (empty($countryDictionary[$fetchedDestinations->GetAllDestinationsResult->CountryList->Country->CountryCode])) {
            $countryDictionary[$fetchedDestinations->GetAllDestinationsResult->CountryList->Country->CountryCode] = $fetchedDestinations->GetAllDestinationsResult->CountryList->Country;
        }
    }

    if (is_array($fetchedDestinations->GetAllDestinationsResult->RegionList->Region)) {
        foreach ($fetchedDestinations->GetAllDestinationsResult->RegionList->Region as $region) {
            if (empty($regionDictionary[$region->RegionCode]))
                $regionDictionary[$region->RegionCode] = $region;
        }
    }
    else if ($fetchedDestinations->GetAllDestinationsResult->RegionList->Region != null) {
        if (empty($regionDictionary[$fetchedDestinations->GetAllDestinationsResult->RegionList->Region->RegionCode])) {
            $regionDictionary[$fetchedDestinations->GetAllDestinationsResult->RegionList->Region->RegionCode] = $fetchedDestinations->GetAllDestinationsResult->RegionList->Region;
        }
    }

    /// If the max number of results hasn't been reached yet, add destinations
    if ($numberOfResults > 0) {
        if (is_array($fetchedDestinations->GetAllDestinationsResult->DestinationList->Destination)) {
            foreach ($fetchedDestinations->GetAllDestinationsResult->DestinationList->Destination as $destination) {
                array_push($destinationListResponse->d, AddLabelValue($destination, $regionDictionary, $countryDictionary));
                $numberOfResults--;
                if ($numberOfResults == 0)
                    break;
            }
        }
        else if ($fetchedDestinations->GetAllDestinationsResult->DestinationList->Destination != null) {
            array_push($destinationListResponse->d, AddLabelValue($fetchedDestinations->GetAllDestinationsResult->DestinationList->Destination, $regionDictionary, $countryDictionary));
        }
    }
    $json = json_encode($destinationListResponse);
    echo $json;
}

// Helper method for creating the LabelValue object
function AddLabelValue($destination, $regionDictionary, $countryDictionary) {
    $destinationID = $destination->DestinationID;
    if (!empty($regionDictionary[$destination->RegionCode])) {
        $parentRegion = $regionDictionary[$destination->RegionCode];
        if (!empty($countryDictionary[$parentRegion->CountryCode])) {
            return new LabelValue($destination->DestinationName . ", " . $parentRegion->RegionName . ", " . $countryDictionary[$parentRegion->CountryCode]->CountryName, $destinationID);
        } else {
            return new LabelValue($destination->DestinationName . ", " . $parentRegion->RegionName, $destinationID);
        }
    } else {
        return new LabelValue($destination->DestinationName, $destinationID);
    }
}

function GetDestinationsForRegion() {
    $inputJson = file_get_contents('php://input');
    $inputParameters = json_decode($inputJson);

    $getDestinationsResponse = new GetDestinations();
    $getDestinationsResponse->languageID = $inputParameters->languageID;
    if (!empty($inputParameters->regionID))
        $getDestinationsResponse->$regionID = $inputParameters->regionID;

    $fetchedDestinations = $getDestinationsResponse->GetDestinations();

    $destinationListResponse = (object) array('d' => '');
    $destinationListResponse->d = array();

    foreach ($fetchedDestinations->GetDestinationsResult->Destination as $destination) {
        array_push($destinationListResponse->d, (object) array('DestinationID' => $destination->DestinationID, 'DestinationName' => $destination->DestinationName));
    }

    $json = json_encode($destinationListResponse);
    echo $json;
}

function InsertCustomer() {
    $inputJson = file_get_contents('php://input');
    $inputParameters = json_decode($inputJson);

    $customerInsert = new CustomerInsert();
    $customerInsert->Customer["PersonName"] = $inputParameters->PersonName;
    $customerInsert->Customer["PersonSurname"] = $inputParameters->PersonSurname;
    $customerInsert->Customer["Email"] = $inputParameters->Email;
    $customerInsert->Customer["Address"] = $inputParameters->Address;
    $customerInsert->Customer["CitizenshipID"] = $inputParameters->CitizenshipID;
    $customerInsert->Customer["City"] = $inputParameters->City;
    $customerInsert->Customer["CompanyName"] = $inputParameters->CompanyName;
    $customerInsert->Customer["CountryID"] = $inputParameters->CountryID;
    $customerInsert->Customer["ZipCode"] = $inputParameters->ZipCode;
    $customerInsert->Customer["VatCode"] = $inputParameters->VatCode;
    $customerInsert->Customer["IsCustomer"] = true;
    $customerInsert->Customer["IsSupplier"] = false;
    $customerInsert->Customer["IsPartner"] = false;
    $customerInsert->Customer["CustomerType"] = 0;
    $customerInsert->Customer["ContractType"] = 1;
    $customerInsert->Customer["UniqueIdentificationNumber"] = $inputParameters->UniqueIdentificationNumber;
    $customerInsert->Customer["TelephoneNumber1"] = $inputParameters->TelephoneNumber1;
    $customerInsert->Customer["TelephoneNumber2"] = $inputParameters->TelephoneNumber2;
    $customerInsert->Customer["MobilePhoneNumber"] = $inputParameters->MobilePhoneNumber;
    $customerInsert->Customer["Fax"] = $inputParameters->Fax;
    $customerInsert->Customer["PassportNumber"] = $inputParameters->PassportNumber;
    $customerInsert->Customer["TaxPayerType"] = 1;
    $customerInsert->Customer["CustomerID"] = 0;
    $customerInsert->Customer["BirthDate"] = (new DateTime())->format(DATE_ATOM);
    $customerInsert->Customer["BirthPlace"] = $inputParameters->BirthPlace;
    $customerInsert->Customer["Sex"] = $inputParameters->Sex;
    $customerInsert->Customer["CreatedDate"] = (new DateTime())->format(DATE_ATOM);
    $customerInsert->Customer["ModifiedDate"] = $inputParameters->ModifiedDate;
    $customerInsert->Customer["DeletedDate"] = $inputParameters->DeletedDate;
    $customerInsert->Customer["OtherSystemID"] = $inputParameters->OtherSystemID;
    $customerInsert->Customer["listCustomField"] = $inputParameters->listCustomField;


    $tempUserProperties = array
        (
        "Name" => $inputParameters->Name,
        "Surname" => $inputParameters->Surname,
        "Email" => $inputParameters->UserEmail,
        "Password" => $inputParameters->Password,
        "LanguageID" => $inputParameters->LanguageID,
        "CountryID" => $inputParameters->CountryID
    );
    $tempUserObject = array("User" => $tempUserProperties);

    $customerInsert->Customer["UserList"] = $tempUserObject;
    $customerInsertResponse = $customerInsert->InsertCustomer();

    $json = json_encode($customerInsertResponse->CustomerInsertResult);

    echo $json;
}

function CheckUserCredentials() {
    $inputJson = file_get_contents('php://input');
    $inputParameters = json_decode($inputJson);

    $userCredentials = new UserCredentials();
    $userCredentials->Email = $inputParameters->Email;
    $userCredentials->Password = $inputParameters->Password;

    $checkUserCredentialsResponse = $userCredentials->CheckUserCredentials();

    if ($checkUserCredentialsResponse->CheckUserCredentialsResult->Status->Code == "OK") {
        $_SESSION['userEmail'] = $checkUserCredentialsResponse->CheckUserCredentialsResult->User->Email;
        $_SESSION['userCompanyID'] = $checkUserCredentialsResponse->CheckUserCredentialsResult->User->Customer->CustomerID;
    }

    $json = json_encode($checkUserCredentialsResponse);

    echo $json;
}

function GetAllReservations() {
    $inputJson = file_get_contents('php://input');
    $inputParameters = json_decode($inputJson);

    $getAllReservations = new GetAllReservations();
    //$getAllReservations->CustomerID = 75; //$_SESSION['userCompanyID'];
    $getAllReservations->LanguageID = $inputParameters->LanguageID;
    $getAllReservations->ReservationCreationDateFrom = $inputParameters->ReservationCreationDateFrom;
    $getAllReservations->ReservationCreationDateTo = $inputParameters->ReservationCreationDateTo;

	// These parameters are automatically passed to the query string from datatables
	// (datatables handles these on the click events on the column headers).
	// Used for server side pagination. $start is the ID of the starting row, and $lenght is the page size
    $start = $_GET["iDisplayStart"];
    $length = $_GET["iDisplayLength"];

    if($start != null && $length != null){
        $getAllReservations->PageSize = $length;
        $getAllReservations->CurrentPage = ($start + $length) / $length - 1;
    }

	// Fill the filter data (used for sorting)
	// These parameters are automatically passed to the query string from datatables
	// (datatables handles these on the click events on the column headers while sorting the grid).
	$iSortCol = $_GET["iSortCol_0"]; // id of the column that needs sorting
	$sSortDir = $_GET["sSortDir_0"]; // sorting direction "asc" or "desc"

	$sortOrder = null; //TipFilteraVelikeRezervacije enum in iTravel BLL
	$sortBy = null; //Sortiranje enum in iTravel BLL

	// This is done a little bit more complex.
	// Every column in DataTables gets and column ID and that ID is passed to the query string on the Ajax call.
	// because these columns are static (user can not reorder them) we map the column IDs to the corresponding enum fields in itravel BLL.

	switch($iSortCol){
		case 1:
			// Reservation ID
			$sortBy = 1;
			break;
		case 2:
			// Reservation creation date
			$sortBy = 2;
			break;
		case 3:
			// Reservation option date
			$sortBy = 4;
			break;
		case 5:
			// Selling price
			$sortBy = 13;
			break;
		case 6:
			// Paid amount
			$sortBy = 14;
			break;
		case 7:
			// Remaining amount
			$sortBy = 15;
			break;
	}

	switch($sSortDir){
		case "asc":
			$sortOrder = 1;
			break;
		case "desc":
			$sortOrder = 2;
			break;
	}

	$getAllReservations->SortParameters = array(
		"SortBy" => $sortBy,
		"SortOrder" => $sortOrder
	);

    $getAllReservationsResponse = $getAllReservations->GetAllReservations();

    if ($getAllReservationsResponse->GetAllReservationsResult->Status->Code == "OK") {
        $allReservations["iTotalRecords"] = count($getAllReservationsResponse->GetAllReservationsResult->Reservations->Reservation);
		$allReservations["iTotalDisplayRecords"] = $getAllReservationsResponse->GetAllReservationsResult->TotalNumberOfRecords;
        $allReservations["dataArray"] = FormatSOAPResponseArray($getAllReservationsResponse->GetAllReservationsResult->Reservations->Reservation);
        $json = json_encode($allReservations);
        echo $json;
    }
}

function ChangeReservationStatus() {
    $inputJson = file_get_contents('php://input');
    $inputParameters = json_decode($inputJson);

    $changeReservationStatus = new ChangeReservationStatus();
    $changeReservationStatus->ReservationID = $inputParameters->ReservationID;
    $changeReservationStatus->StatusID = $inputParameters->StatusID;

    $changeReservationStatusResponse = $changeReservationStatus->ChangeReservationStatus();
    $json = json_encode($changeReservationStatusResponse->ChangeReservationStatusResult);
    echo $json;
}

function GetReservation() {
    $inputJson = file_get_contents('php://input');
    $inputParameters = json_decode($inputJson);

    $getReservation = new GetReservation();
    $getReservation->ReservationID = $inputParameters->ReservationID;
    $getReservation->LanguageID = $inputParameters->LanguageID;


    $getReservationResponse = $getReservation->GetReservation();
    $tempArrayOfDocuments = FormatSOAPResponseArray($getReservationResponse->GetReservationResult->Reservation->DocumentList->Document);

    // Valid document type IDs
    // Offer, Pro forma invoice, invoice, voucher, contract, specification,
    // advanced payment invoice, itinerary, waiting list respectively
    $allowedDocumentTypeIDs = array(1, 2, 3, 4, 5, 6, 7, 8, 9);
    //echo print_r($allowedDocumentTypeIDs);
    array_filter($getReservationResponse->GetReservationResult->Reservation->DocumentList->Document, function($item){ return in_array($item->DocumentTypeID, $allowedDocumentTypeIDs); });
    $filteredArrayOfDocuments = array();
    foreach($tempArrayOfDocuments as $document)
    {
        if(in_array($document->DocumentTypeID, $allowedDocumentTypeIDs))
        {
            array_push($filteredArrayOfDocuments, $document);
        }
    }
    $getReservationResponse->GetReservationResult->Reservation->DocumentList->Document = $filteredArrayOfDocuments;
    $json = json_encode($getReservationResponse->GetReservationResult);
    echo $json;
}

function GetAllTransactions() {
    $inputJson = file_get_contents('php://input');
    $inputParameters = json_decode($inputJson);

    $getAllTransactions = new GetAllTransactions();
    $getAllTransactions->CustomerID = $_SESSION['userCompanyID'];
	$getAllTransactions->LanguageID = "en";

	// These parameters are automatically passed to the query string from datatables
	// (datatables handles these on the click events on the column headers).
	// Used for server side pagination. $start is the ID of the starting row, and $lenght is the page size
	$start = $_GET["iDisplayStart"];
	$length = $_GET["iDisplayLength"];

	if($start != null && $length != null){
		$getAllTransactions->PageSize = $length;
		$getAllTransactions->CurrentPage = ($start + $length) / $length - 1;
	}
	// Fill the filter data (used for sorting)
	// These parameters are automatically passed to the query string from datatables
	// (datatables handles these on the click events on the column headers while sorting the grid).
	$iSortCol = $_GET["iSortCol_0"]; // id of the column that needs sorting
	$sSortDir = $_GET["sSortDir_0"]; // sorting direction "asc" or "desc"

	$sortOrder = null; //TransactionOrderEnum in iTravel BLL
	$sortBy = null;  //Sortiranje enum in iTravel BLL

	// This is done a little bit more complex.
	// Every column in DataTables gets and column ID and that ID is passed to the query string on the Ajax call.
	// because these columns are static (user can not reorder them) we map the column IDs to the corresponding enum fields in itravel BLL (.
	switch($iSortCol){
		case 0:
			// Transaction date
			$sortBy = 0;
			break;
		case 1:
			// Reservation ID
			$sortBy = 1;
			break;
		case 6:
			// Transaction amount
			$sortBy = 13;
			break;
	}

	switch($sSortDir){
		case "asc":
			$sortOrder = 1;
			break;
		case "desc":
			$sortOrder = 2;
			break;
	}

	$getAllTransactions->SortParameters = array(
		"SortBy" => $sortBy,
		"SortOrder" => $sortOrder
		);

    $getAllTransactionsResponse = $getAllTransactions->GetAllTransactions();

    if ($getAllTransactionsResponse->GetAllTransactionsResult->Status->Code == "OK") {
        $allTransactions["iTotalRecords"] = count($getAllTransactionsResponse->GetAllTransactionsResult->TransactionList->Transaction);
		$allTransactions["iTotalDisplayRecords"] = $getAllTransactionsResponse->GetAllTransactionsResult->TotalNumberOfRecords;
        $allTransactions["dataArray"] = FormatSOAPResponseArray($getAllTransactionsResponse->GetAllTransactionsResult->TransactionList->Transaction);
        $json = json_encode($allTransactions);
        echo $json;
    }
}

function GetReservationCanNotStartDates() {
	$inputJson = file_get_contents('php://input');
	$inputParameters = json_decode($inputJson);

	$getReservationCanNotStartDates = new GetReservationCanNotStartDates();
	$getReservationCanNotStartDates->UnitID = $inputParameters->UnitID;
	$getReservationCanNotStartDates->StartDate = (new DateTime())->format(DATE_ATOM);
	$getReservationCanNotStartDates->EndDate = (new DateTime())->format(DATE_ATOM);

	$getReservationCanNotStartDatesResponse = $getReservationCanNotStartDates->GetReservationCanNotStartDates();
	if ($getReservationCanNotStartDatesResponse->GetReservationCanNotStartDatesResult->Status->Code == "OK") {
        $listDates["ListCanNotStartDates"] = FormatSOAPResponseArray($getReservationCanNotStartDatesResponse->GetReservationCanNotStartDatesResult->ListDates->dateTime);
        $json = json_encode($listDates);
        echo $json;
	}
}

function GetReservationCanNotEndDates() {
	$inputJson = file_get_contents('php://input');
	$inputParameters = json_decode($inputJson);

	$getReservationCanNotEndDates = new GetReservationCanNotEndDates();
	$getReservationCanNotEndDates->UnitID = $inputParameters->UnitID;
	$getReservationCanNotEndDates->StartDate = (new DateTime())->format(DATE_ATOM);
	$getReservationCanNotEndDates->EndDate = (new DateTime())->format(DATE_ATOM);

	$getReservationCanNotEndDatesResponse = $getReservationCanNotEndDates->GetReservationCanNotEndDates();
	if ($getReservationCanNotEndDatesResponse->GetReservationCanNotEndDatesResult->Status->Code == "OK") {
        $listDates["ListCanNotEndDates"] = FormatSOAPResponseArray($getReservationCanNotEndDatesResponse->GetReservationCanNotEndDatesResult->ListDates->dateTime);
        $json = json_encode($listDates);
        echo $json;
	}
}

function GetPassengersOnReservation()
{
    $inputJson = file_get_contents('php://input');
	$inputParameters = json_decode($inputJson);

    $getPassengersOnReservation = new GetPassengersOnReservation();
    $getPassengersOnReservation->ReservationUniqueID = $inputParameters->ReservationUniqueID;
    $getPassengersOnReservation->LanguageID = $inputParameters->LanguageID;

    $getPassengersOnReservationResponse = $getPassengersOnReservation->GetPassengersOnReservation();

    if(!is_array($getPassengersOnReservationResponse->GetPassengersOnReservationResult->ListPassengers->GetPassengerEditFormDTO))
    {
        $getPassengersOnReservationResponse->GetPassengersOnReservationResult->ListPassengers->GetPassengerEditFormDTO = array($getPassengersOnReservationResponse->GetPassengersOnReservationResult->ListPassengers->GetPassengerEditFormDTO);
    }

    $json = json_encode($getPassengersOnReservationResponse);
    echo $json;
}

function PassengerUpdate()
{
    $inputJson = file_get_contents('php://input');
	$inputParameters = json_decode($inputJson);

    $passengerUpdate = new PassengerUpdate();
    $passengerUpdate->Passenger = new GetPassengerEditFormDTO();
    $passengerUpdate->Passenger->PassengerID = $inputParameters->GetPassengerEditFormDTO->PassengerID;
    $passengerUpdate->Passenger->Name = $inputParameters->GetPassengerEditFormDTO->Name;
    $passengerUpdate->Passenger->Surname = $inputParameters->GetPassengerEditFormDTO->Surname;
    $pdate = new DateTime($inputParameters->GetPassengerEditFormDTO->DateOfBirth);
    $passengerUpdate->Passenger->DateOfBirth = $pdate->format(DATE_ATOM);
    $passengerUpdate->Passenger->MobilePhone = $inputParameters->GetPassengerEditFormDTO->MobilePhone;
    $passengerUpdate->Passenger->Birthplace = $inputParameters->GetPassengerEditFormDTO->Birthplace;
    $passengerUpdate->Passenger->PassportNumber = $inputParameters->GetPassengerEditFormDTO->PassportNumber;
    $passengerUpdate->Passenger->Address = $inputParameters->GetPassengerEditFormDTO->Address;
    $passengerUpdate->Passenger->Town = $inputParameters->GetPassengerEditFormDTO->Town;
    $passengerUpdate->Passenger->ZipCode = $inputParameters->GetPassengerEditFormDTO->ZipCode;

    $ListCustomFieldData = $inputParameters->ListCustomFieldData;
    if(!is_array($ListCustomFieldData)){
		$ListCustomFieldData = array($ListCustomFieldData);
    }
    $passengerUpdate->Passenger->ListCustomFieldData = array();
    foreach ($ListCustomFieldData as $cf)
    {

        $customField = new CustomField();
        $customField->CustomFieldID = $cf->CustomField->CustomFieldID;
        $customField->CustomFieldName = $cf->CustomField->CustomFieldName;
        $customField->CustomFieldType = $cf->CustomField->CustomFieldType;
        $customField->CustomFieldValue = new CustomFieldValue();
        $customField->CustomFieldValue->LanguageID = $cf->CustomField->CustomFieldValue->LanguageID;
        $customField->CustomFieldValue->Value = $cf->CustomField->CustomFieldValue->Value;
        $customField->CustomFieldValue->ValueID = 0;

        if($cf->CustomField->CustomFieldType == 0){

			$customField->listCustomFieldValue = array();

            foreach ($cf->CustomField->ListCustomFieldValue as $value)
            {
                $customFieldValueItem = new CustomFieldValue();
                $customFieldValueItem->LanguageID = $value->CustomFieldValue->LanguageID;
                $customFieldValueItem->Value = $value->CustomFieldValue->Value;
                $customFieldValueItem->ValueID = 0;

            	array_push($customField->listCustomFieldValue, $customFieldValueItem);
            }

        }

    	array_push($passengerUpdate->Passenger->ListCustomFieldData, $customField);
    }

    $passengerUpdateResponse = $passengerUpdate->PassengerUpdate();
    $json = json_encode($passengerUpdateResponse);
    echo $json;
}

function CreateReservation()
{
    $inputJson = file_get_contents('php://input');
	$inputParameters = json_decode($inputJson);

    $createReservation = new CreateReservation();
    $createReservation->CurrencyID = $inputParameters->CurrencyID;
    $createReservation->AffiliateID = $inputParameters->AffiliateID;
    $createReservation->MarketID = $inputParameters->MarketID;
    $createReservation->LanguageID = $inputParameters->LanguageID;
    $createReservation->BranchOfficeID = $inputParameters->BranchOfficeID;

    // DTOs
    $createReservation->Customer["Email"] = $inputParameters->Customer->Email;
    $createReservation->Customer["Name"] = $inputParameters->Customer->Name;
    $createReservation->Customer["MiddleName"] = $inputParameters->Customer->MiddleName;
    $createReservation->Customer["Surname"] = $inputParameters->Customer->Surname;
    $createReservation->Customer["CompanyName"] = $inputParameters->Customer->CompanyName;
    $createReservation->Customer["PersonalID"] = $inputParameters->Customer->PersonalID;
    $createReservation->Customer["Gender"] = $inputParameters->Customer->Gender;
    $createReservation->Customer["BirthDate"] = $inputParameters->Customer->BirthDate;
    $createReservation->Customer["Birthplace"] = $inputParameters->Customer->Birthplace;
    $createReservation->Customer["CountryID"] = $inputParameters->Customer->CountryID;
    $createReservation->Customer["CitizenshipCountryID"] = $inputParameters->Customer->CitizenshipCountryID;
    $createReservation->Customer["Address"] = $inputParameters->Customer->Address;
    $createReservation->Customer["City"] = $inputParameters->Customer->City;
    $createReservation->Customer["ZIPcode"] = $inputParameters->Customer->ZIPcode;
    $createReservation->Customer["PassportNumber"] = $inputParameters->Customer->PassportNumber;
    $createReservation->Customer["MobilePhone"] = $inputParameters->Customer->MobilePhone;
    $createReservation->Customer["Telephone"] = $inputParameters->Customer->Telephone;
    $createReservation->Customer["Telefax"] = $inputParameters->Customer->Telefax;
    $createReservation->Customer["CustomerType"] = $inputParameters->Customer->CustomerType;
    $createReservation->Customer["LanguageID"] = $inputParameters->Customer->LanguageID;
    $createReservation->Customer["CustomFields"] = array();

    $createReservation->PaymentMethod["PaymentMethodID"] = $inputParameters->PaymentMethod->PaymentMethodID;
    $createReservation->PaymentMethod["PaymentMethodName"] = $inputParameters->PaymentMethod->PaymentMethodName;

    $createReservation->ReservationCustomFields = array();
    $listReservationCustomFields = $inputParameters->ReservationCustomFields;
    foreach ($listReservationCustomFields as $customField)
    {
    	$CustomFieldReservationProcess = array("CustomFieldReservationProcess" => array("ID" => $customField->ID , "Value" => $customField->Value));
        array_push($createReservation->ReservationCustomFields, $CustomFieldReservationProcess);
    }

    $createReservation->ReservationItemsParametersList = array();
    $createReservation->AdHocReservationItemsParametersList = array();

    $createReservationResponse = $createReservation->CreateReservation();
    $json = json_encode($createReservationResponse);
    echo $json;
}

function GetPopularAccommodation(){

	$region = GetParameterFromGetOrPost( 'region', null );
	$region = GetParameterFromGetOrPost( 'region', null );
	$region = GetParameterFromGetOrPost( 'region', null );
	$region = GetParameterFromGetOrPost( 'region', null );

	$accommodationSearchResults  = new GetSearchResults();
	$accommodationSearchResults->pageSize = 5; // take only 5 search results to avoid speed issues
	$accommodationSearchResults->thumbnailWidth = 400;
	$accommodationSearchResults->thumbnailHeight = 233;
	$accommodationSearchResults->xsltPath = iTravelGeneralSettings::$iTravelXSLTPopularAccommodationSearchResultsPath;



	$apiResponse = $accommodationSearchResults->GetAPIResponse();

	if(isset($apiResponse->GetSearchResultsResult->AccommodationObjectList->AccommodationObject)) {
		$accommodationArray = $apiResponse->GetSearchResultsResult->AccommodationObjectList->AccommodationObject;
		// Filter array, remove current
		$accommodationArray = array_filter($accommodationArray, function($k) use (&$currentObjectID){
			return $k->ObjectID != $currentObjectID;
		});
		if(count($accommodationArray)) {
?>
			
						
						<div class="col-md-9">
							<?php				
								// shuffle it
								shuffle($accommodationArray);
				
								// take first three
								$accommodationArray = array_slice($accommodationArray, 0, 3);
				
								$apiResponse->GetSearchResultsResult->AccommodationObjectList->AccommodationObject = $accommodationArray;
				
								echo $accommodationSearchResults->GetSearchResultsHTML(array(), $apiResponse);
							?>
						</div>
					
			<?php
		}
	}
	
}