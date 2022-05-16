<xsl:stylesheet version="1.0"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:msxsl="urn:schemas-microsoft-com:xslt"
				exclude-result-prefixes="msxsl"
>
  <xsl:output method="html"
			  indent="yes"
			  cdata-section-elements="" />

  <xsl:param name="countryIDParameter" />
  <xsl:param name="regionIDParameter" />
  <xsl:param name="destinationIDParameter" />
  <xsl:param name="fromParameter" />
  <xsl:param name="toParameter" />
  <xsl:param name="numberOfStarsParameter" />
  <xsl:param name="personsParameter" />
  <xsl:param name="objectTypeIDParameter" />
  <xsl:param name="objectTypeGroupIDParameter" />
  <xsl:param name="categoryIDParameter" />
  <xsl:param name="ignorePriceAndAvailabilityParam" />
  <xsl:param name="onlyOnSpecialOfferParameter" />
  <xsl:param name="urlPrefixParameter" />
  <xsl:param name="actionFlagFilePathParameter" />

  <!-- Additional parameters -->
  <xsl:param name="priceFromParameter" />
  <xsl:param name="priceToParameter" />
  <xsl:param name="priceTypeParameter" />
  <xsl:param name="postaviDirektanLink" />
  <xsl:param name="BookingLinkInNewWindow" />
  <xsl:param name="OpenInParent" />
  <xsl:param name="categoryIntersectionID" />

  <xsl:template match="@* | node()">
	 <xsl:copy>
		<xsl:apply-templates select="@* | node()"/>
	 </xsl:copy>
  </xsl:template>

  <xsl:variable name="languageID" select="/SearchResults/Language/LanguageID" />
  <xsl:variable name="currencyID" select="/SearchResults/Currency/CurrencyID" />
  <xsl:variable name="dateFormat" select="'dd.mm.yyyy'" />

  <xsl:variable name="targetVar">
	 <xsl:if test="$BookingLinkInNewWindow=true()">
		<xsl:value-of select="'_blank'"/>
	 </xsl:if>
  </xsl:variable>

  <xsl:param name="detailsURL" />
  <xsl:variable name="detailsPage">
	 <xsl:value-of select="$detailsURL"/>
	 <xsl:text>/?&amp;languageID=</xsl:text>
	 <xsl:value-of select="$languageID"/>
	 <xsl:text>&amp;currencyID=</xsl:text>
	 <xsl:value-of select="$currencyID"/>
	 <xsl:text>&amp;objectID=</xsl:text>
  </xsl:variable>
	
	<xsl:variable name="new-line" select="'&#10;'" />
	<xsl:variable name="quote">
		<xsl:text>"</xsl:text>
	</xsl:variable>
	<xsl:variable name="singleQuote">
		<xsl:text>'</xsl:text>
	</xsl:variable>
	
	<xsl:template name="jsonescape">
		<xsl:param name="str" select="."/>
		<xsl:choose>
			<xsl:when test="contains($str, '\')">
				<xsl:value-of select="concat(substring-before($str, '\'), '\\' )"/>
				<xsl:call-template name="jsonescape">
					<xsl:with-param name="str" select="substring-after($str, '\')"/>
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="$str"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

  <xsl:template match="/">

	 <div class="clear-both map-and-sort-placeholder">

		<xsl:variable name="positionsList">
		  <xsl:for-each select="/*/AccommodationObjectList/AccommodationObject/AttributeGroupList/AttributeGroup[GroupID=95]">
			 <xsl:if test="AttributeList/Attribute[AttributeID=290]/AttributeValue!='' and AttributeList/Attribute[AttributeID=291]/AttributeValue!=''">
				<xsl:value-of select="AttributeList/Attribute[AttributeID=290]/AttributeValue"/>
				<xsl:text>/</xsl:text>
				<xsl:value-of select="AttributeList/Attribute[AttributeID=291]/AttributeValue"/>
				<xsl:text>/</xsl:text>
				<xsl:value-of select="../../ObjectID"/>
				<xsl:text>~</xsl:text>
			 </xsl:if>
		  </xsl:for-each>
		</xsl:variable>

		<xsl:variable name="translationJsObject" select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='learnMore']/value" />

		<xsl:variable name="dictObjectIDNameStars">
		  {
		  <xsl:for-each select="/*/AccommodationObjectList/AccommodationObject">
			"<xsl:value-of select="ObjectID"/>":{ "name":"<xsl:value-of select="Name"/>", "stars":"<xsl:value-of select="AttributeGroupList/AttributeGroup/AttributeList/Attribute[AttributeID=970]/AttributeValue"/>"}
			<xsl:if test="position()!=last()">
		   ,
			 </xsl:if>
		  </xsl:for-each>
		  }
		</xsl:variable>

		<script>
		 window.infoDescImage = {
		  <xsl:for-each select="/*/AccommodationObjectList/AccommodationObject">
			"<xsl:value-of select="ObjectID"/>":{"description":"<xsl:call-template name="jsonescape">
						<xsl:with-param name="str">
							<xsl:value-of select="translate(translate(translate(ShortDescription, '&quot;', $singleQuote), $quote, $singleQuote), '&#10;', ' ')"/>
						</xsl:with-param>						
						</xsl:call-template>", "image":"<xsl:value-of select="PhotoList/Photo[1]/ThumbnailUrl" />"}
			<xsl:if test="position()!=last()">
		   ,
			 </xsl:if>
		  </xsl:for-each>
		  };
		 </script>
		 
		 
						
		<div class="clear-both google-map" id="map-results">
		  <xsl:comment> </xsl:comment>
		</div>
		<script src="https://maps.google.com/maps/api/js?sensor=false&amp;key=AIzaSyBTXW7m7qnmxGdIvDHjyu9QBcsV1ljLYzQ" defer="defer">
		  <xsl:comment> </xsl:comment>
		</script>
		 <div id="destinations-google-map"
			data-currencyID="{$currencyID}"
			data-languageID="{$languageID}"
			data-detailsPage="{$detailsPage}"
			data-urlPrefixParameter="{$urlPrefixParameter}"
			data-ignorePriceAndAvailabilityParam="{$ignorePriceAndAvailabilityParam}"
			data-personsParameter="{$personsParameter}"
			data-translation="{$translationJsObject}"
			data-positionsList="{$positionsList}"
			data-dictObjectIDNameStars="{$dictObjectIDNameStars}">
			 <xsl:comment></xsl:comment>
		 </div>
		<script defer="defer" src="/wp-content/themes/localliving/js/mapResults.js">
		  <xsl:comment> </xsl:comment>
		</script>
	 </div>
  </xsl:template>
</xsl:stylesheet>
