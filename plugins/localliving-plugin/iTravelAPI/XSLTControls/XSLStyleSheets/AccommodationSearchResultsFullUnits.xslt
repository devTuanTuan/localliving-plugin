<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:msxsl="urn:schemas-microsoft-com:xslt"
                exclude-result-prefixes="msxsl"
>
  <xsl:include href="CommonFunctions.xslt"/>
  <xsl:include href="Pagination.xslt"/>
  <xsl:output method="xml"
              indent="yes"
              cdata-section-elements=""/>

  <xsl:template match="@* | node()">
    <xsl:copy>
      <xsl:apply-templates select="@* | node()"/>
    </xsl:copy>
  </xsl:template>

  <xsl:param name="countryIDParameter"/>
  <xsl:param name="regionIDParameter"/>
  <xsl:param name="destinationIDParameter"/>
  <xsl:param name="fromParameter"/>
  <xsl:param name="toParameter"/>
  <xsl:param name="numberOfStarsParameter"/>
  <xsl:param name="personsParameter"/>
  <xsl:param name="childrenParameter"/>
  <xsl:param name="childrenAgesParameter"/>
  <xsl:param name="objectTypeIDParameter"/>
  <xsl:param name="objectTypeGroupIDParameter"/>
  <xsl:param name="objectName"/>
  <xsl:param name="categoryIDParameter"/>
  <xsl:param name="ignorePriceAndAvailabilityParam"/>
  <xsl:param name="onlyOnSpecialOfferParameter"/>
  <xsl:param name="urlPrefixParameter"/>
  <xsl:param name="destinationName"/>
  <xsl:param name="serviceName"/>
  <xsl:param name="globalDestinationID"/>
  <xsl:param name="searchSupplier"/>
  <xsl:param name="showWishListButton"/>
  <!-- Additional parameters -->
  <xsl:param name="priceFromParameter"/>
  <xsl:param name="priceToParameter"/>
  <xsl:param name="priceTypeParameter"/>
  <xsl:param name="postaviDirektanLink"/>
  <xsl:param name="BookingLinkInNewWindow"/>
  <xsl:param name="OpenInParent"/>
  <xsl:param name="ClientWebAddress"/>
  <xsl:param name="ignoreStartDay"/>
  <xsl:param name="beginPreviousWeek"/>
  <xsl:param name="endPreviousWeek"/>
  <xsl:param name="beginNextWeek"/>
  <xsl:param name="endNextWeek"/>
  <xsl:param name="beginPreviousWeekDate"/>
  <xsl:param name="endPreviousWeekDate"/>
  <xsl:param name="beginNextWeekDate"/>
  <xsl:param name="endNextWeekDate"/>
  <xsl:variable name="languageID" select="/*/Language/LanguageID"/>
  <xsl:variable name="currencyID" select="/*/Currency/CurrencyID"/>
  <xsl:variable name="dateFormat" select="'dd.mm.yyyy'"/>

  <xsl:param name="setDirectLinkGlobal"/>
  <xsl:param name="bookingAddressDisplayURLReservationGlobal"/>
  <xsl:param name="setDirectLinkInternal"/>
  <xsl:param name="bookingAddressDisplayURLReservation"/>

  <!-- ***************************************************** -->
  <!--                Variables to customize                 -->
  <!-- ***************************************************** -->

  <!-- URL of the Details page (which shows details of the specified object) -->
  <xsl:param name="detailsURL"/>
  <!-- Number of pages to display. Specify a number of pages to display in the pagination field -->
  <!-- If there is more pages, the system will automatically generate the previous and the next button -->
  <xsl:variable name="numberOfPagesToDisplay" select="'10'"/>
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
  <xsl:variable name="currencyShortName" select="/*/Currency/CurrencyShortName"/>
  <xsl:variable name="totalResults" select="/*/TotalNumberOfResults"/>


  <xsl:variable name="new-line" select="'&#10;'"/>
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
    <!-- Result count -->
    <div class="results-count-wrapper">
      <div class="results-count">
        <span>
          <xsl:value-of select="$totalResults"/>
        </span>
        <xsl:text> </xsl:text>
<!--        <xsl:value-of-->
<!--            select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='noOfHomes']/value"/>-->
        ferieboliger passer til dine ønsker
      </div>
      <div id="date-range-show-text">
        <span class="diff-range">0 dage.</span>
        <span class="start-date"><xsl:value-of select="/*/StartDate"/></span>
        <span> - </span>
        <span class="end-date"><xsl:value-of select="/*/EndDate"/></span>
      </div>
    </div>

    <!-- If there are any results, display them! -->
    <xsl:choose>
      <xsl:when test="$totalResults > 0">
        <xsl:for-each select="/*/AccommodationObjectList/AccommodationObject">
          <xsl:variable name="ImageSource" select="PhotoList/Photo[1]/ThumbnailUrl"/>
          <xsl:variable name="AlternateText" select="PhotoList/Photo[1]/AlternateText"/>
          <xsl:variable name="AccommodationName" select="Name"/>
          <xsl:variable name="objectURL" select="ObjectURL"/>
          <xsl:variable name="objectID" select="ObjectID"/>
          <xsl:variable name="objectCode" select="ObjectCode"/>
          <xsl:variable name="objectName"
                        select="AttributeGroupList/AttributeGroup/AttributeList/Attribute[AttributeID=121]/AttributeValue"/>
          <xsl:variable name="objectTypeID" select="ObjectType/ObjectTypeID"/>
          <xsl:variable name="destinationID" select="DestinationID"/>
          <xsl:variable name="destinationName"
                        select="/*/DestinationList/Destination[DestinationID=$destinationID]/DestinationName"/>
          <xsl:variable name="objectPublicCode" select="AccommodationObjectPublicCode"/>
          <xsl:variable name="regionID" select="/*/DestinationList/Destination[DestinationID=$destinationID]/RegionID"/>
          <xsl:variable name="regionName" select="/*/RegionList/Region[RegionID=$regionID]/RegionName"/>
          <xsl:variable name="countryID" select="/*/RegionList/Region[RegionID=$regionID]/CountryID"/>
          <xsl:variable name="countryName" select="/*/CountryList/Country[CountryID=$countryID]/CountryName"/>
          <xsl:variable name="numberOfPersons" select="/*/NumberOfPersons"/>
          <xsl:variable name="numberOfDays" select="/*/NumberOfDays"/>
          <xsl:variable name="personsFromInput" select="/*/PersonsFromInput"/>
          <xsl:variable name="setDirectLinkTemp">
            <xsl:choose>
              <xsl:when test="string-length($setDirectLinkGlobal) &gt; 0">
                <xsl:value-of select="$setDirectLinkGlobal"/>
              </xsl:when>
              <xsl:otherwise>
                <xsl:value-of select="$setDirectLinkInternal"/>
              </xsl:otherwise>
            </xsl:choose>
          </xsl:variable>
          <xsl:variable name="setDirectLink" select="'true' = $setDirectLinkTemp"/>
          <xsl:variable name="AccommodationUnit" select="UnitList/AccommodationUnit"/>
          <xsl:variable name="bookingAddressURLZaRezervaciju">
            <xsl:if test="string-length($bookingAddressDisplayURLReservationGlobal) &gt; 0">
              <xsl:value-of select="$bookingAddressDisplayURLReservationGlobal"/>
            </xsl:if>
            <xsl:if test="string-length($bookingAddressDisplayURLReservation) &gt; 0">
              <xsl:value-of select="$bookingAddressDisplayURLReservation"/>
            </xsl:if>
          </xsl:variable>

          <xsl:variable name="objectFinalURL">
            <xsl:variable name="url">
              <xsl:choose>
                <!-- choose between a custom URL (if exists) and a real URL -->
                <xsl:when test="$objectURL != ''">
                  <xsl:if test="starts-with($objectURL,'/')=false()">
                    <xsl:text>/</xsl:text>
                  </xsl:if>
                  <xsl:value-of select="$objectURL"/>
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
                      <xsl:with-param name="date" select="/*/StartDate"/>
                      <xsl:with-param name="format" select="'yyyy-mm-dd'"/>
                    </xsl:call-template>
                  </xsl:if>
                  <xsl:if test="$toParameter>1">
                    <xsl:text>&amp;dateTo=</xsl:text>
                    <xsl:call-template name="formatDate">
                      <xsl:with-param name="date" select="/*/EndDate"/>
                      <xsl:with-param name="format" select="'yyyy-mm-dd'"/>
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
                      <xsl:with-param name="date" select="/*/StartDate"/>
                      <xsl:with-param name="format" select="'yyyy-mm-dd'"/>
                    </xsl:call-template>
                  </xsl:if>
                  <xsl:if test="$toParameter>1">
                    <xsl:text>&amp;dateTo=</xsl:text>
                    <xsl:call-template name="formatDate">
                      <xsl:with-param name="date" select="/*/EndDate"/>
                      <xsl:with-param name="format" select="'yyyy-mm-dd'"/>
                    </xsl:call-template>
                  </xsl:if>
                </xsl:otherwise>
              </xsl:choose>
            </xsl:variable>
            <!--Remove ?& from URL-->
            <xsl:variable name="urlWithoutExtraAmp">
              <xsl:call-template name="string-replace-all">
                <xsl:with-param name="text" select="$url"/>
                <xsl:with-param name="replace" select="'?&amp;'"/>
                <xsl:with-param name="by" select="'?'"/>
              </xsl:call-template>
            </xsl:variable>
            <!--Remove ? from url if there are no query string parameters-->
            <xsl:choose>
              <xsl:when
                  test="string-length(substring-before($urlWithoutExtraAmp, '?')) + 1 = string-length($urlWithoutExtraAmp)">
                <xsl:call-template name="string-replace-all">
                  <xsl:with-param name="text" select="$urlWithoutExtraAmp"/>
                  <xsl:with-param name="replace" select="'?'"/>
                  <xsl:with-param name="by" select="''"/>
                </xsl:call-template>
              </xsl:when>
              <xsl:otherwise>
                <xsl:value-of select="$urlWithoutExtraAmp"/>
              </xsl:otherwise>
            </xsl:choose>
          </xsl:variable>

          <xsl:if test="position()=1 and $totalResults=1">
            <script type="text/javascript">
              window.location = "&lt;xsl:value-of select="$objectFinalURL" disable-output-escaping="yes"/>"
              &lt;xsl:comment/>
            </script>
          </xsl:if>

          <xsl:variable name="AccommodationRegionID"
                        select="/*/DestinationList/Destination[DestinationID=$destinationID]/RegionID"/>
          <xsl:variable name="AccommodationRegionName"
                        select="/*/RegionList/Region[RegionID=$AccommodationRegionID]/RegionName"/>
          <xsl:variable name="Minimal_CalculatedPriceInfo">
            <xsl:for-each
                select="UnitList/AccommodationUnit/CalculatedPriceInfo[CalculatedPrice &gt; 0]/CalculatedPrice">
              <xsl:sort data-type="number" order="ascending"/>
              <xsl:if test="position()=1">
                <xsl:value-of select="../CalculatedPriceFormated"/>
              </xsl:if>
            </xsl:for-each>
          </xsl:variable>
          <xsl:variable name="region-hor-line-class">
            <xsl:choose>
              <xsl:when test="$AccommodationRegionID=87">
                search-results-row-north-italy
              </xsl:when>
              <xsl:when test="$AccommodationRegionID=49">
                search-results-row-toscana
              </xsl:when>
              <xsl:when test="$AccommodationRegionID=66">
                search-results-row-ligurien
              </xsl:when>
              <xsl:when test="$AccommodationRegionID=50">
                search-results-row-umbrien
              </xsl:when>
              <xsl:when test="$AccommodationRegionID=69">
                search-results-row-sicilien
              </xsl:when>
              <xsl:otherwise>
                search-results-row-default
              </xsl:otherwise>
            </xsl:choose>
          </xsl:variable>
          <div class="article-wrapper {$region-hor-line-class}">
            <article class="row search-results-row">
              <div class="col-md-2 article-image">
                <a href="{$objectFinalURL}" title="{$AccommodationName}">
                  <img class="lazy" data-src="{$ImageSource}">
                    <xsl:attribute name="alt">
                      <xsl:choose>
                        <xsl:when test="$AlternateText = ''">
                          <xsl:value-of select="$AccommodationName"/>
                        </xsl:when>
                        <xsl:otherwise>
                          <xsl:value-of select="$AlternateText"/>
                        </xsl:otherwise>
                      </xsl:choose>
                    </xsl:attribute>
                  </img>
                </a>
              </div>

              <div class="col-md-8 villa-search-result-row">
                <h1 class="heading-caps">
                  <a href="{$objectFinalURL}" title="{$AccommodationName}">
                    <span class="text">
                      <xsl:value-of select="$AccommodationName"/>
                    </span>
                    <span class="stars">
                      <xsl:variable name="NumberOfStars"
                                    select="AttributeGroupList/AttributeGroup/AttributeList/Attribute[AttributeID=970]/AttributeValue"/>
                      <xsl:choose>
                        <xsl:when test="contains(string($NumberOfStars), '.')">
                          <xsl:call-template name="number-of-stars">
                            <xsl:with-param name="count" select="$NumberOfStars - 1"/>
                          </xsl:call-template>
                          <span>+</span>
                        </xsl:when>
                        <xsl:otherwise>
                          <xsl:call-template name="number-of-stars">
                            <xsl:with-param name="count" select="$NumberOfStars"/>
                          </xsl:call-template>
                        </xsl:otherwise>
                      </xsl:choose>
                    </span>
                  </a>
                </h1>
                <div class="description">
                  <xsl:value-of select="ShortDescription" disable-output-escaping="yes"/>
                  <xsl:comment/>
                </div>
              </div>
              <div class="col-md-2 last-column">
                <div class="villa-object-price-row">
                  <div class="text-center heading-caps">

                    <!--Regular price-->
                    <div class="object-price">
                      <xsl:if test="$Minimal_CalculatedPriceInfo != ''">
                        <xsl:if test="$objectTypeID != '70'">
                          <span>Fra </span>
                        </xsl:if>
                        <xsl:value-of select="$Minimal_CalculatedPriceInfo"/>
                        <xsl:text> </xsl:text>
                        <xsl:value-of select="/*/Currency/CurrencyShortName"/>
                      </xsl:if>
                    </div>
                  </div>
                </div>
                <xsl:choose>
                  <xsl:when test="$objectTypeID != '70'">
                    <xsl:variable name="countUnit">
                      <xsl:value-of select="count(UnitList/AccommodationUnit)"/>
                    </xsl:variable>
                    <xsl:variable name="countNotAvailableUnit">
                      <xsl:value-of select="count(UnitList/AccommodationUnit/AvailabilityStatus[text() = 'NotAvailable'])"/>
                    </xsl:variable>
                    <xsl:variable name="countOversizedUnit">
                      <xsl:value-of
                          select="count(UnitList/AccommodationUnit/AttributeGroupList/AttributeGroup/AttributeList/Attribute[AttributeID=120 and AttributeValue &lt; $personsFromInput])"/>
                    </xsl:variable>
                    <xsl:choose>
                      <xsl:when test="$Minimal_CalculatedPriceInfo = '' or $countUnit &lt;= $countNotAvailableUnit
                      or $countUnit &lt;= $countOversizedUnit or $countUnit &lt;= ($countNotAvailableUnit+$countOversizedUnit)">
                        <button class="add-to-cart not-possible" disabled="disabled">
                          <xsl:choose>
                            <xsl:when test="$countUnit &lt;= $countNotAvailableUnit">
                              Ikke ledig
                            </xsl:when>
                            <xsl:otherwise>
                              Ikke mulig
                            </xsl:otherwise>
                          </xsl:choose>
                        </button>
                      </xsl:when>
                      <xsl:otherwise>
                        <button class="add-to-cart" data-object-id="{$objectID}">Vælg</button>
                      </xsl:otherwise>
                    </xsl:choose>
                  </xsl:when>
                  <xsl:otherwise>
                    <xsl:variable name="capacity"
                                  select="UnitList/AccommodationUnit[1]/AttributeGroupList/AttributeGroup/AttributeList/Attribute[AttributeID=120]/AttributeValue"/>
                    <xsl:variable name="errorType">
                      <xsl:if test="$capacity &lt; $personsFromInput">
                        <xsl:text>oversized</xsl:text>
                      </xsl:if>
                      <xsl:if test="UnitList/AccommodationUnit[1]/AvailabilityStatus = 'NotAvailable'">
                        <xsl:text>notAvailable</xsl:text>
                      </xsl:if>
                    </xsl:variable>
                    <xsl:choose>
                      <xsl:when test="$errorType = 'oversized'
                      or UnitList/AccommodationUnit[1]/CalculatedPriceInfo/CalculationStatus/Code = 'Error'
                      or $errorType = 'notAvailable'">
                        <button class="add-to-cart not-possible" disabled="disabled">
                          <xsl:choose>
                            <xsl:when test="$errorType = 'notAvailable'">
                              Ikke ledig
                            </xsl:when>
                            <xsl:otherwise>
                              Ikke mulig
                            </xsl:otherwise>
                          </xsl:choose>
                          <span class="disable-tooltip">
                            <xsl:if test="$errorType = 'oversized'">
                              <p>I er for mange til denne feriebolig</p>
                            </xsl:if>
                            <xsl:choose>
                              <xsl:when test="UnitList/AccommodationUnit[1]/CalculatedPriceInfo/CalculationStatus/Code = 'Error'">
                                <p><xsl:value-of select="UnitList/AccommodationUnit[1]/CalculatedPriceInfo/CalculationStatus/Description"/></p>
                              </xsl:when>
                              <xsl:otherwise>
                                <xsl:if test="$errorType = 'notAvailable'">
                                  Ferieboligen er optaget i den valgte periode. Prøv at ændre datoerne.
                                </xsl:if>
                              </xsl:otherwise>
                            </xsl:choose>
                          </span>
                        </button>
                      </xsl:when>
                      <xsl:otherwise>
                        <button class="add-to-cart" data-object-id="{$objectID}">Vælg</button>
                      </xsl:otherwise>
                    </xsl:choose>
                  </xsl:otherwise>
                </xsl:choose>
              </div>
            </article>

            <!--IS NOT VILLAS THEN PRINT LIST OF APARTMENTS-->
            <xsl:if test="$objectTypeID != '70'">
              <!--LIST OF UNITS-->
              <div class="relative-z2 clearfix villa-details-units">
                <!--Single Unit-->
                <xsl:for-each select="$AccommodationUnit">

                  <!-- ************************************* -->
                  <!--            UNIT Variables             -->
                  <!-- ************************************* -->
                  <xsl:variable name="capacity"
                                select="AttributeGroupList/AttributeGroup/AttributeList/Attribute[AttributeID=120]/AttributeValue"/>
                  <xsl:variable name="minimumStay">
                    <xsl:for-each select="ServiceList/Service/PriceRowList/PriceRow/MinimumStay">
                      <xsl:sort data-type="number" order="ascending"/>
                      <xsl:if test="position()=1">
                        <xsl:value-of select="."/>
                      </xsl:if>
                    </xsl:for-each>
                  </xsl:variable>

                  <xsl:if test="$capacity &lt; $numberOfPersons">
                    <xsl:variable name="cssClass"
                                  select="'accommodation-unit-oversized'"/>
                    <xsl:variable name="errorMessage"
                                  select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='errorOversized']/value"/>
                  </xsl:if>
                  <xsl:if test="AvailabilityStatus = 'NotAvailable'">
                    <xsl:variable name="cssClass"
                                  select="'accommodation-unit-not-available'"/>
                    <xsl:variable name="errorMessage"
                                  select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='errorNotAvailable']/value"/>
                  </xsl:if>
                  <xsl:variable name="cssClass">
                    <xsl:if test="$capacity &lt; $numberOfPersons">
                      accommodation-unit-oversized
                    </xsl:if>
                    <xsl:if test="AvailabilityStatus = 'NotAvailable'">
                      accommodation-unit-not-available
                    </xsl:if>
                    <xsl:if test="CalculatedPriceInfo/CalculatedPrice=0 and $numberOfDays &lt; $minimumStay">
                      accommodation-unit-not-available
                    </xsl:if>
                  </xsl:variable>
                  <xsl:variable name="errorMessage">
                    <xsl:if test="$capacity &lt; $numberOfPersons">
                      <xsl:value-of
                          select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='errorOversized']/value"/>
                    </xsl:if>
                    <xsl:if test="AvailabilityStatus = 'NotAvailable'">
                      <xsl:value-of
                          select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='errorNotAvailable']/value"/>
                    </xsl:if>
                    <xsl:if test="CalculatedPriceInfo/CalculatedPrice=0 and $numberOfDays &lt; $minimumStay">
                      <xsl:value-of
                          select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='serviceCannotBeShorterThan']/value"/>
                      <xsl:text> </xsl:text>
                      <xsl:value-of select="$minimumStay"/>
                      <xsl:text> </xsl:text>
                      <xsl:value-of
                          select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='dayOrDays']/value"/>
                    </xsl:if>
                  </xsl:variable>
                  <xsl:variable name="errorType">
<!--                    <xsl:if test="$capacity &lt; $numberOfPersons">-->
<!--                      <xsl:text>oversized</xsl:text>-->
<!--                    </xsl:if>-->
                    <xsl:if test="$capacity &lt; $personsFromInput">
                      <xsl:text>oversized</xsl:text>
                    </xsl:if>
                    <xsl:if test="AvailabilityStatus = 'NotAvailable'">
                      <xsl:text>notAvailable</xsl:text>
                    </xsl:if>
                  </xsl:variable>

                  <article>
                    <!--Unit images-->
                    <div class="row">
                      <div class="col-md-1 villa-unit-image">
                        <xsl:choose>
                          <xsl:when test="PhotoList/Photo">
                            <xsl:for-each select="PhotoList/Photo">
                              <xsl:if test="position()=1">
                                <!--First image template-->
                                <a href="{PhotoUrl}" class="unit-gallery transition-slow" title="">
                                  <img class="lazy" data-src="{ThumbnailUrl}" alt="{AlternateText}"/>
                                  <span class="icon"></span>
                                </a>
                              </xsl:if>
                            </xsl:for-each>
                          </xsl:when>
                          <xsl:otherwise>
                          <a href="{PhotoUrl}" class="unit-gallery transition-slow" title="">
                            <img class="lazy"
                                data-src="/wp-content/plugins/localliving-plugin/assets/images/empty.png"
                                alt="No Image"/>
                          </a>
                          </xsl:otherwise>
                        </xsl:choose>

                      </div>
                      <!--Unit images END-->

                      <!--Unit Info-->
                      <div class="col-md-5">
                        <div class="villa-unit-desc">
                          <!--Unit name + min - max capacity-->
                          <h2 class="heading-caps">
                            <xsl:value-of
                                select="AttributeGroupList/AttributeGroup/AttributeList/Attribute[AttributeID=133]/AttributeValue"/>
                          </h2>

                          <!--Unit description-->
                          <a href="#" class="show-hide-unit-description" onClick="return false;">Vis beskrivelse</a>
                          <xsl:choose>
                            <xsl:when test="Description">
                              <div class="unit-description">
                                <xsl:value-of select="Description" disable-output-escaping="yes"/>
                              </div>
                            </xsl:when>
                            <xsl:otherwise>
                              <div class="unit-description">
                                <p>No description!</p>
                              </div>
                            </xsl:otherwise>
                          </xsl:choose>

                        </div>
                      </div>
                      <div class="col-md-6 last-column">
                        <div class="row row-reverse">
                          <div class="col-md-4 add-to-cart-wrapper">
                            <xsl:choose>
                              <xsl:when test="$errorType = 'oversized' or
                              CalculatedPriceInfo/CalculationStatus/Code = 'Error' or $errorType = 'notAvailable'">
                                <button class="add-to-cart not-possible" disabled="disabled">
                                  <xsl:choose>
                                    <xsl:when test="$errorType = 'notAvailable'">
                                      Ikke ledig
                                    </xsl:when>
                                    <xsl:otherwise>
                                      Ikke mulig
                                    </xsl:otherwise>
                                  </xsl:choose>
                                  <span class="disable-tooltip">
                                  <xsl:if test="$errorType = 'oversized'">
                                    <p>I er for mange til denne feriebolig</p>
                                  </xsl:if>
                                    <xsl:choose>
                                      <xsl:when test="CalculatedPriceInfo/CalculationStatus/Code = 'Error'">
                                        <p><xsl:value-of select="CalculatedPriceInfo/CalculationStatus/Description"/></p>
                                      </xsl:when>
                                      <xsl:otherwise>
                                        <xsl:if test="$errorType = 'notAvailable'">
                                          Ferieboligen er optaget i den valgte periode. Prøv at ændre datoerne.
                                        </xsl:if>
                                      </xsl:otherwise>
                                    </xsl:choose>
                                  </span>
                                </button>
                              </xsl:when>
                              <xsl:otherwise>
                                <button class="add-to-cart" data-object-id="{$objectID}" data-unit-id="{UnitID}">Vælg</button>
                              </xsl:otherwise>
                            </xsl:choose>
                          </div>
                          <div class="villa-unit-price-row col-md-8">
                            <div class="price-wrapper">
                            </div>
                            <xsl:variable name="BootstrapCSSClass">
                              <xsl:choose>
                                <xsl:when
                                    test="(string-length(substring-before($errorType , 'notAvailable')) != 0) or ($errorType='' and CalculatedPriceInfo/CalculationStatus/Code != 'Error')">
                                  col-md-4
                                </xsl:when>
                                <xsl:otherwise>col-md-7</xsl:otherwise>
                              </xsl:choose>
                            </xsl:variable>
                            <div class="text-right heading-caps">
                              <!--Discount-->
                              <div class="discount">
                                <!-- Output the names of Special Offers! -->
                                <xsl:for-each
                                    select="CalculatedPriceInfo/ServiceList/Service[ServiceType='SpecialOffer']">
                                  <xsl:variable name="serviceID"
                                                select="ServiceID"/>
                                  <xsl:value-of
                                      select="ServiceName"/>
                                  <xsl:if test="position() != last()">
                                    <xsl:text>, </xsl:text>
                                  </xsl:if>
                                </xsl:for-each>

                              </div>
                              <div class="price">
                                <!-- If there's no error show price -->
                                <xsl:if test="$errorMessage=''">
                                  <xsl:if
                                      test="CalculatedPriceInfo/CalculatedPrice &gt; 0 and CalculatedPriceInfo/CalculationStatus/Code != 'Error'">
                                    <!-- If special offers exist, then the old price must be crossed out! -->
                                    <xsl:if
                                        test="CalculatedPriceInfo/CalculatedPrice &lt; CalculatedPriceInfo/BasicCalculatedPrice">
                                      <!--Discount price-->
                                      <div class="unit-old-price">
                                        <xsl:value-of select="CalculatedPriceInfo/BasicCalculatedPriceFormated"/>
                                        <xsl:text> </xsl:text>
                                        <xsl:value-of select="$currencyShortName"/>
                                      </div>
                                    </xsl:if>
                                    <!--Regular price-->
                                    <div class="unit-price">
                                      <xsl:value-of select="CalculatedPriceInfo/CalculatedPriceFormated"/>
                                      <xsl:text> </xsl:text>
                                      <xsl:value-of select="$currencyShortName"/>
                                    </div>
                                  </xsl:if>
  <!--                                <xsl:if test="CalculatedPriceInfo/CalculationStatus/Code = 'Error'">-->
  <!--                                  <p class="calculationErrorDescription">-->
  <!--                                    <xsl:value-of select="CalculatedPriceInfo/CalculationStatus/Description"/>-->
  <!--                                  </p>-->
  <!--                                </xsl:if>-->
                                </xsl:if>
                              </div>

                            </div>
                            <xsl:if test="$BootstrapCSSClass='col-md-4'">
                              <div class="col-md-3 text-right book-button-holder">
                                <xsl:variable name="bookingAddress">
                                  <xsl:if test="true() != $setDirectLink">
                                    <xsl:value-of select="$bookingAddressURLZaRezervaciju"/>
                                    <xsl:text>?languageID=</xsl:text>
                                    <xsl:value-of select="$languageID"/>
                                    <xsl:text>&amp;booking=true</xsl:text>
                                    <xsl:text>&amp;bookingAddress=</xsl:text>
                                    <xsl:call-template name="string-replace-all">
                                      <xsl:with-param name="text">
                                        <xsl:call-template name="string-replace-all">
                                          <xsl:with-param name="text"
                                                          select="BookingAddress"/>
                                          <xsl:with-param name="replace"
                                                          select="'&amp;'"/>
                                          <xsl:with-param name="by"
                                                          select="'%26'"/>
                                        </xsl:call-template>
                                      </xsl:with-param>
                                      <xsl:with-param name="replace"
                                                      select="'?'"/>
                                      <xsl:with-param name="by"
                                                      select="'%3f'"/>
                                    </xsl:call-template>
                                  </xsl:if>
                                  <xsl:if test="true() = $setDirectLink">
                                    <xsl:value-of select="BookingAddress"/>
                                    <xsl:text>&amp;booking=true</xsl:text>
                                  </xsl:if>
                                </xsl:variable>

                                <xsl:if test="string-length(substring-before($errorType , 'notAvailable')) != 0">
                                  <a class="button medium secondary" href="#dateSelection">
                                    <span>
                                      <xsl:value-of
                                          select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='holidayHomeIsNotAvailable']/value"/>
                                    </span>
                                  </a>
                                </xsl:if>
                              </div>
                            </xsl:if>
                          </div>
                        </div>
                      </div>
                      <!--Unit Info END-->
                    </div>
                  </article>
                </xsl:for-each>
                <!--Single Unit END-->
              </div>
              <!--LIST OF UNITS END-->
            </xsl:if>

          </div>
        </xsl:for-each>


        <xsl:variable name="positionsList">
          <xsl:for-each select="/*/ObjectLocationList/ObjectLocation">
            <xsl:value-of select="Latitude"/>
            <xsl:text>/</xsl:text>
            <xsl:value-of select="Longitude"/>
            <xsl:text>/</xsl:text>
            <xsl:value-of select="ObjectID"/>
            <xsl:text>~</xsl:text>
          </xsl:for-each>
        </xsl:variable>

        <xsl:variable name="translationJsObject"
                      select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='learnMore']/value"/>

        <xsl:variable name="dictObjectIDNameStars">
          {
          <xsl:for-each select="/*/AccommodationObjectList/AccommodationObject">
            "<xsl:value-of select="ObjectID"/>":{ "name":"<xsl:value-of select="Name"/>", "stars":"<xsl:value-of
              select="AttributeGroupList/AttributeGroup/AttributeList/Attribute[AttributeID=970]/AttributeValue"/>"}
            <xsl:if test="position()!=last()">
              ,
            </xsl:if>
          </xsl:for-each>
          }
        </xsl:variable>

      </xsl:when>
      <xsl:otherwise>
        <div class="not-found">
          <p>Vi fandt desværre ingen ledige boliger som matcher din søgning.</p>
        </div>
      </xsl:otherwise>
    </xsl:choose>

    <!-- Draw pagination - bottom -->
    <xsl:call-template name="pagination-template"></xsl:call-template>

  </xsl:template>


</xsl:stylesheet>
