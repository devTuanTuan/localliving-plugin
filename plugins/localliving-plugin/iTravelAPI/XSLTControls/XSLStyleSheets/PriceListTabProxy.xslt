<xsl:stylesheet version="1.0"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:msxsl="urn:schemas-microsoft-com:xslt"
				exclude-result-prefixes="msxsl">
	<xsl:output method="html"
				indent="yes"
				cdata-section-elements=""/>

	<xsl:param name="postaviDirektanLink" />
	<xsl:param name="unitActivityStatus" />
	<xsl:param name="BookingLinkInNewWindow" />
	<!--ID of objects without unit - it will be used to show the characteristics of units (becouse this objects doesn't have any (or have a few) characteristics)-->
	<xsl:param name="objectsIDWithoutUnit" />
	<xsl:param name="childrenParam" />
	<xsl:param name="childrenAgesParam" />
	<xsl:param name="showChildrenAgesParam" />
	<xsl:param name="dateFormatParameter" />
	<xsl:param name="cartIDParameter" />
	<xsl:param name="completeQueryString" />
	<xsl:param name="ShowPriceListPrint" />
	<xsl:param name="PriceListPrintUrl" />

	<xsl:param name="marketIDParameter" />
	<xsl:param name="customerIDParameter" />
	<xsl:param name="affiliateIDParameter" />
	<xsl:param name="objectCode" />

	<!--Trenutni datuk kojeg koristimo za usporedbe sezona-->
	<xsl:param name="currentDate"/>

	<xsl:include href="CommonFunctions.xslt"/>
	<xsl:include href="AccommodationPriceList.xslt"/>

	
	<xsl:template match="@* | node()">
		<xsl:copy>
			<xsl:apply-templates select="@* | node()"/>
		</xsl:copy>
	</xsl:template>

	<!-- ***************************************** -->
	<!--     Variables for customization           -->
	<!-- ***************************************** -->
	<xsl:param name="bookingAddressDisplayURL" />
	<xsl:param name="detailsAddressDisplayURL" />

	<!-- ******************************************* -->
	<!--              Global variables               -->
	<!-- ******************************************* -->
	<xsl:variable name="languageID"
				  select="/*/Language/LanguageID" />
	<xsl:variable name="AccommodationName"
				  select="/*/AccommodationObject/AttributeGroupList/AttributeGroup/AttributeList/Attribute[AttributeID=121]/AttributeValue" />
	<xsl:variable name="destinationID"
				  select="/*/AccommodationObject/DestinationID"/>
	<xsl:variable name="DestinationName"
				  select="/*/Destination[DestinationID=$destinationID]/DestinationName" />
	<xsl:variable name="regionID"
				  select="/*/Destination[DestinationID=$destinationID]/RegionID" />
	<xsl:variable name="regionName"
				  select="/*/Region[RegionID=$regionID]/RegionName" />
	<xsl:variable name="countryID"
				  select="/*/Region[RegionID=$regionID]/CountryID" />
	<xsl:variable name="countryName"
				  select="/*/Country[CountryID=$countryID]/CountryName" />
	<xsl:variable name="numberOfPersonsForContactFormVar"
				  select="/*/NumberOfPersons + $childrenParam" />
	<xsl:variable name="Description"
				  select="/*/AccommodationObject/Description"/>
	<xsl:variable name="objectID"
				  select="/*/AccommodationObject/ObjectID" />
	<xsl:variable name="objectTypeID"
				  select="/*/AccommodationObject/ObjectType/ObjectTypeID" />
	<xsl:variable name="objectPublicCode"
				  select="/*/AccommodationObject/AccommodationObjectPublicCode" />
	<xsl:variable name="currencyShortName">
		<xsl:value-of select="/*/Currency/CurrencyShortName"/>
	</xsl:variable>
	<xsl:variable name="searchSuppliers" select="/ApiSettings/SearchSuppliers" />
	<xsl:variable name="currencyID" select="/*/Currency/CurrencyID" />

	<xsl:template match="/">
		<xsl:variable name="dateFormat" >
			<xsl:choose>
				<xsl:when test="$dateFormatParameter != ''">
					<xsl:value-of select="$dateFormatParameter"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:text>dd.mm.yyyy</xsl:text>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<xsl:call-template name="villasPriceListTemplate">
		</xsl:call-template>
	</xsl:template>
</xsl:stylesheet>
