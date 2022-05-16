<xsl:stylesheet version="1.0"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:msxsl="urn:schemas-microsoft-com:xslt"
				exclude-result-prefixes="msxsl">
	<xsl:output method="html"
				indent="yes"
				cdata-section-elements=""/>
	<xsl:include href="CommonFunctions.xslt"/>

	<xsl:param name="postaviDirektanLink" />
	<xsl:param name="unitActivityStatus" />
	<xsl:param name="BookingLinkInNewWindow" />
	<xsl:param name="childrenParam" />
	<xsl:param name="childrenAgesParam" />
	<xsl:param name="showChildrenAgesParam" />
	<xsl:param name="dateFormatParameter" />
	<xsl:param name="marketIDParameter" />
	<xsl:param name="customerIDParameter" />
	<xsl:param name="affiliateIDParameter" />
	
	<xsl:include href="AccommodationUnitList.xslt"/>

	<xsl:template match="@* | node()">
		<xsl:copy>
			<xsl:apply-templates select="@* | node()"/>
		</xsl:copy>
	</xsl:template>


	<!-- ***************************************** -->
	<!--     Variables for customization           -->
	<!-- ***************************************** -->
	<xsl:param name="bookingAddressDisplayURL" />

	<!-- ******************************************* -->
	<!--              Global variables               -->
	<!-- ******************************************* -->
	<xsl:variable name="languageID"
				  select="/*/Language/LanguageID" />
				   
	<xsl:variable name="currencyID"
				  select="/*/Currency/CurrencyID" />

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

	<xsl:template match="/">

		<xsl:call-template name="villasUnitListTemplate">
			<xsl:with-param name="bookingAddressDisplayURLReservation"
							select="$bookingAddressDisplayURL" />
			<xsl:with-param name="setDirectLinkInternal"
							select="$postaviDirektanLink" />
			<xsl:with-param name="unitActivityStatusTab"
							select="$unitActivityStatus" />
			<xsl:with-param name="BookingLinkInNewWindowTab"
							select="$BookingLinkInNewWindow" />
			<xsl:with-param name="childrenParamTab"
							select="$childrenParam"/>
			<xsl:with-param name="childrenAgesParamTab"
							select="$childrenAgesParam"/>
			<xsl:with-param name="dateFormatParameterTab"
							select="$dateFormat"/>
			<xsl:with-param name="showChildrenAgesParamTab"
							select="$showChildrenAgesParam"/>
			<xsl:with-param name="customerIDParameterTab"
							select="$customerIDParameter"/>
			<xsl:with-param name="marketIDParameterTab"
							select="$marketIDParameter"/>
			<xsl:with-param name="affiliateIDParameterTab"
							select="$affiliateIDParameter"/>
		</xsl:call-template>

	</xsl:template>
</xsl:stylesheet>
