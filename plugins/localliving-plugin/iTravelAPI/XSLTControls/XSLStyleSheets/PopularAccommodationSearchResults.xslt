<xsl:stylesheet version="1.0"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:msxsl="urn:schemas-microsoft-com:xslt"
				exclude-result-prefixes="msxsl"
>
  <xsl:include href="CommonFunctions.xslt" />
  <xsl:output method="xml"
			  indent="yes"
			  cdata-section-elements="" />

  <xsl:template match="@* | node()">
    <xsl:copy>
      <xsl:apply-templates select="@* | node()"/>
    </xsl:copy>
  </xsl:template>

  <xsl:param name="countryIDParameter" />
  <xsl:param name="regionIDParameter" />
  <xsl:param name="destinationIDParameter" />
  <xsl:param name="fromParameter" />
  <xsl:param name="toParameter" />
  <xsl:param name="numberOfStarsParameter" />
  <xsl:param name="personsParameter" />
  <xsl:param name="childrenParameter" />
  <xsl:param name="childrenAgesParameter" />
  <xsl:param name="objectTypeIDParameter" />
  <xsl:param name="objectTypeGroupIDParameter" />
  <xsl:param name="categoryIDParameter" />
  <xsl:param name="ignorePriceAndAvailabilityParam" />
  <xsl:param name="onlyOnSpecialOfferParameter" />
  <xsl:param name="urlPrefixParameter" />
  <xsl:param name="destinationName" />
  <xsl:param name="serviceName" />
  <xsl:param name="globalDestinationID" />
  <xsl:param name="searchSupplier" />

  <!-- Additional parameters -->
  <xsl:param name="priceFromParameter" />
  <xsl:param name="priceToParameter" />
  <xsl:param name="priceTypeParameter" />
  <xsl:param name="postaviDirektanLink" />
  <xsl:param name="BookingLinkInNewWindow" />
  <xsl:param name="OpenInParent" />
  <xsl:param name="ClientWebAddress" />
  <xsl:param name="ignoreStartDay" />
  <xsl:variable name="languageID"
				 select="/*/Language/LanguageID" />
  <xsl:variable name="currencyID"
				 select="/*/Currency/CurrencyID" />

  <xsl:variable name="dateFormat"
				 select="'dd.mm.yyyy'" />

  <!-- ***************************************************** -->
  <!--                Variables to customize                 -->
  <!-- ***************************************************** -->

  <!-- URL of the Details page (which shows details of the specified object) -->
  <xsl:param name="detailsURL" />

  <!-- Number of pages to display. Specify a number of pages to display in the pagination field -->
  <!-- If there is more pages, the system will automatically generate the previous and the next button -->
  <xsl:variable name="numberOfPagesToDisplay" select="'10'" />

  <!-- *****************-->
  <!-- Global variables -->
  <!-- *****************-->
  <xsl:variable name="detailsPage">
    <xsl:value-of select="$detailsURL"/>
    <xsl:text>?languageID=</xsl:text>
    <xsl:value-of select="$languageID"/>
    <xsl:text>&amp;currencyID=</xsl:text>
    <xsl:value-of select="$currencyID"/>
    <xsl:text>&amp;objectID=</xsl:text>
  </xsl:variable>
  <xsl:variable name="currencyShortName"
				 select="/*/Currency/CurrencyShortName" />

  <xsl:variable name="totalResults"
				 select="/*/TotalNumberOfResults" />

  <xsl:template match="/">
    <!-- If there are any results, display them! -->
    <xsl:if test="$totalResults > 0">
      <ul class="popular-villas-results row">

        <xsl:for-each select="/*/AccommodationObjectList/AccommodationObject">
          <xsl:variable name="ImageSource" select="PhotoList/Photo[1]/ThumbnailUrl" />
          <xsl:variable name="AlternateText" select="PhotoList/Photo[1]/AlternateText" />
          <xsl:variable name="AccommodationName" select="Name" />

          <xsl:variable name="objectURL" select="ObjectURL" />
          <xsl:variable name="objectID"
                 select="ObjectID" />
          <xsl:variable name="objectCode"
                 select="ObjectCode" />
          <xsl:variable name="objectName"
                 select="AttributeGroupList/AttributeGroup/AttributeList/Attribute[AttributeID=121]/AttributeValue" />
          <xsl:variable name="objectTypeID"
                 select="ObjectType/ObjectTypeID" />
          <xsl:variable name="destinationID"
                 select="DestinationID" />
          <xsl:variable name="destinationName"
                 select="/*/DestinationList/Destination[DestinationID=$destinationID]/DestinationName" />
          <xsl:variable name="objectPublicCode"
                 select="AccommodationObjectPublicCode" />
          <xsl:variable name="regionID"
                 select="/*/DestinationList/Destination[DestinationID=$destinationID]/RegionID" />
          <xsl:variable name="regionName"
                 select="/*/RegionList/Region[RegionID=$regionID]/RegionName" />
          <xsl:variable name="countryID"
                 select="/*/RegionList/Region[RegionID=$regionID]/CountryID" />
          <xsl:variable name="countryName"
                 select="/*/CountryList/Country[CountryID=$countryID]/CountryName" />
          <xsl:variable name="objectFinalURL">
            <xsl:variable name="url">
              <xsl:choose>
                <!-- choose between a custom URL (if exists) and a real URL -->
                <xsl:when test="$objectURL != ''">
                  <xsl:if test="starts-with($objectURL,'/')=false()">
                    <xsl:text>/</xsl:text>
                  </xsl:if>
                  <xsl:value-of select="$objectURL" />
                  <xsl:choose>
                    <xsl:when test="contains($objectURL, '?')">
                      <xsl:text>&amp;</xsl:text>
                    </xsl:when>
                    <xsl:otherwise>
                      <xsl:text>?</xsl:text>
                    </xsl:otherwise>
                  </xsl:choose>
                  <xsl:if test="$personsParameter>1">
                    <xsl:text>&amp;persons=</xsl:text>
                    <xsl:value-of select="$personsParameter"/>
                  </xsl:if>
                  <xsl:if test="$childrenParameter > 0">
                    <xsl:text>&amp;children=</xsl:text>
                    <xsl:value-of select="$childrenParameter"/>
                    <xsl:text>&amp;childrenAges=</xsl:text>
                    <xsl:value-of select="$childrenAgesParameter"/>
                  </xsl:if>
                  <xsl:if test="$fromParameter>1">
                    <xsl:text>&amp;dateFrom=</xsl:text>
                    <xsl:call-template name="formatDate">
                      <xsl:with-param name="date" select="/*/StartDate" />
                      <xsl:with-param name="format" select="'yyyy-mm-dd'" />
                    </xsl:call-template>
                  </xsl:if>
                  <xsl:if test="$toParameter>1">
                    <xsl:text>&amp;dateTo=</xsl:text>
                    <xsl:call-template name="formatDate">
                      <xsl:with-param name="date" select="/*/EndDate" />
                      <xsl:with-param name="format" select="'yyyy-mm-dd'" />
                    </xsl:call-template>
                  </xsl:if>
                </xsl:when>
                <xsl:otherwise>
                  <xsl:value-of select="$detailsPage"/>
                  <xsl:value-of select="$objectID"/>
                  <xsl:text>&amp;objectCode=</xsl:text>
                  <xsl:value-of select="$objectCode"/>
                  <xsl:if test="$personsParameter>1">
                    <xsl:text>&amp;persons=</xsl:text>
                    <xsl:value-of select="$personsParameter"/>
                  </xsl:if>
                  <xsl:if test="$childrenParameter > 0">
                    <xsl:text>&amp;children=</xsl:text>
                    <xsl:value-of select="$childrenParameter"/>
                    <xsl:text>&amp;childrenAges=</xsl:text>
                    <xsl:value-of select="$childrenAgesParameter"/>
                  </xsl:if>
                  <xsl:if test="$priceTypeParameter = 'PerDay'">
                    <xsl:text>&amp;priceType=PerDay</xsl:text>
                  </xsl:if>
                  <xsl:if test="$fromParameter>1">
                    <xsl:text>&amp;dateFrom=</xsl:text>
                    <xsl:call-template name="formatDate">
                      <xsl:with-param name="date" select="/*/StartDate" />
                      <xsl:with-param name="format" select="'yyyy-mm-dd'" />
                    </xsl:call-template>
                  </xsl:if>
                  <xsl:if test="$toParameter>1">
                    <xsl:text>&amp;dateTo=</xsl:text>
                    <xsl:call-template name="formatDate">
                      <xsl:with-param name="date" select="/*/EndDate" />
                      <xsl:with-param name="format" select="'yyyy-mm-dd'" />
                    </xsl:call-template>
                  </xsl:if>
                </xsl:otherwise>
              </xsl:choose>
            </xsl:variable>
            <!--Remove ?& from URL-->
            <xsl:variable name="urlWithoutExtraAmp">
              <xsl:call-template name="string-replace-all">
                <xsl:with-param name="text" select="$url" />
                <xsl:with-param name="replace" select="'?&amp;'" />
                <xsl:with-param name="by" select="'?'" />
              </xsl:call-template>
            </xsl:variable>
            <!--Remove ? from url if there are no query string parameters-->
            <xsl:choose>
              <xsl:when test="string-length(substring-before($urlWithoutExtraAmp, '?')) + 1 = string-length($urlWithoutExtraAmp)">
                <xsl:call-template name="string-replace-all">
                  <xsl:with-param name="text" select="$urlWithoutExtraAmp" />
                  <xsl:with-param name="replace" select="'?'" />
                  <xsl:with-param name="by" select="''" />
                </xsl:call-template>
              </xsl:when>
              <xsl:otherwise>
                <xsl:value-of select="$urlWithoutExtraAmp"/>
              </xsl:otherwise>
            </xsl:choose>

          </xsl:variable>

          <xsl:variable name="AccommodationRegionID" select="/*/DestinationList/Destination[DestinationID=$destinationID]/RegionID" />
          <xsl:variable name="region-hor-line-class-small">
            <xsl:if test="$AccommodationRegionID=49">
              popular-results-row-toscana
            </xsl:if>
            <xsl:if test="$AccommodationRegionID=66">
              popular-results-row-ligurien
            </xsl:if>
            <xsl:if test="$AccommodationRegionID=50">
              popular-results-row-umbrien
            </xsl:if>
            <xsl:if test="$AccommodationRegionID=69">
              popular-results-row-sicilien
            </xsl:if>
          </xsl:variable>
          <li class="col-md-4 col-xs-12" data-r="{$fromParameter}">
            <div>
              <a href="{$objectFinalURL}" title="{$AccommodationName}">
                <img src="{$ImageSource}">
                  <xsl:attribute name="alt">
                    <xsl:choose>
                      <xsl:when test="$AlternateText = ''">
                        <xsl:value-of select ="$AccommodationName"/>
                      </xsl:when>
                      <xsl:otherwise>
                        <xsl:value-of select="$AlternateText"/>
                      </xsl:otherwise>
                    </xsl:choose>
                  </xsl:attribute>
                </img>
              </a>
            </div>
            <div class="description {$region-hor-line-class-small}">
              <h2>
                <a href="{$objectFinalURL}" title="{$AccommodationName}" style="text-decoration:none;color: rgb(89, 93, 86);">
                  <xsl:value-of select="$AccommodationName"/>
                </a>
              </h2>
              <p>
                <xsl:value-of select="AttributeGroupList/AttributeGroup/AttributeList/Attribute[AttributeID=1125]/AttributeValue" />
              </p>
              <!--Object category-->
              <div class="stars">
                <xsl:variable name="NumberOfStars" select="AttributeGroupList/AttributeGroup/AttributeList/Attribute[AttributeID=970]/AttributeValue" />
                <xsl:choose>
                  <xsl:when test="contains(string($NumberOfStars), '.')">
                    <xsl:call-template name="number-of-stars">
                      <xsl:with-param name="count" select="$NumberOfStars - 1" />
                    </xsl:call-template>
                    <span class="plus">+</span>
                  </xsl:when>
                  <xsl:otherwise>
                    <xsl:call-template name="number-of-stars">
                      <xsl:with-param name="count" select="$NumberOfStars" />
                    </xsl:call-template>
                  </xsl:otherwise>
                </xsl:choose>
              </div>
              <div class="row">
                <div class="col-sm-6">
                  <xsl:variable name="VillageAttributeValue" select="AttributeGroupList/AttributeGroup[GroupID=26]/AttributeList/Attribute[AttributeID=102]/AttributeValue" />
                  <xsl:variable name="FoodAndShoppingAttributeValue" select="AttributeGroupList/AttributeGroup[GroupID=26]/AttributeList/Attribute[AttributeID=894]/AttributeValue" />

                  <ul class="features">
                    <xsl:if test="string-length($VillageAttributeValue) > 0">
                      <li>
                        <strong>
                          <xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='Village']/value"/>:
                        </strong>
                        <xsl:value-of select="$VillageAttributeValue"/>
                      </li>
                    </xsl:if>
                    <xsl:if test="string-length($FoodAndShoppingAttributeValue) > 0">
                      <li>
                        <strong>
                          <xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='FoodShopping']/value"/>:
                        </strong>
                        <xsl:value-of select="$FoodAndShoppingAttributeValue"/>
                      </li>
                    </xsl:if>
                  </ul>
                </div>
                <div class="col-sm-6">
                  <a class="button" href="{$objectFinalURL}" target="_blank" rel="noopener" title="{$AccommodationName}">
                    <xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='ViewAccommodation']/value"/>
                  </a>
                </div>
              </div>
            </div>
          </li>
        </xsl:for-each>
      </ul>
    </xsl:if>
    <xsl:if test="$totalResults = 0">
      <div class="popular-villas-results row">
        <div class="col-md-12">
          <xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='noResultsFound']/value" disable-output-escaping="yes"/>
        </div>
      </div>
    </xsl:if>

  </xsl:template>
</xsl:stylesheet>
