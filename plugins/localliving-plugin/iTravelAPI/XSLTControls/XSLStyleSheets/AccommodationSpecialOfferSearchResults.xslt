<xsl:stylesheet version="1.0"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:msxsl="urn:schemas-microsoft-com:xslt"
				exclude-result-prefixes="msxsl"
>
	<xsl:include href="CommonFunctions.xslt" />
  <xsl:include href="Pagination.xslt" />
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
	<xsl:param name="showWishListButton"/>
	<xsl:variable name="languageID" select="/*/Language/LanguageID" />
	<xsl:variable name="currencyID" select="/*/Currency/CurrencyID" />
	<xsl:variable name="dateFormat" select="'dd.mm.yyyy'" />

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
	<xsl:variable name="currencyShortName" select="/*/Currency/CurrencyShortName" />
	<xsl:variable name="totalResults" select="/*/TotalNumberOfResults" />

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
    <!-- Draw pagination - top -->
    <xsl:call-template name="pagination-template"></xsl:call-template>

    <!-- If there are any results, display them! -->
		<xsl:choose>
			<xsl:when test="$totalResults > 0">
				<xsl:for-each select="/*/AccommodationObjectList/AccommodationObject">
					<xsl:variable name="ImageSource" select="PhotoList/Photo[1]/ThumbnailUrl" />
					<xsl:variable name="AlternateText" select="PhotoList/Photo[1]/AlternateText" />
					<xsl:variable name="AccommodationName" select="Name" />
					<xsl:variable name="objectURL" select="ObjectURL" />
					<xsl:variable name="objectID" select="ObjectID" />
					<xsl:variable name="objectCode" select="ObjectCode" />
					<xsl:variable name="objectName" select="AttributeGroupList/AttributeGroup/AttributeList/Attribute[AttributeID=121]/AttributeValue" />
					<xsl:variable name="objectTypeID" select="ObjectType/ObjectTypeID" />
					<xsl:variable name="destinationID" select="DestinationID" />
					<xsl:variable name="destinationName" select="/*/DestinationList/Destination[DestinationID=$destinationID]/DestinationName" />
					<xsl:variable name="objectPublicCode" select="AccommodationObjectPublicCode" />
					<xsl:variable name="regionID" select="/*/DestinationList/Destination[DestinationID=$destinationID]/RegionID" />
					<xsl:variable name="regionName" select="/*/RegionList/Region[RegionID=$regionID]/RegionName" />
					<xsl:variable name="countryID" select="/*/RegionList/Region[RegionID=$regionID]/CountryID" />
					<xsl:variable name="countryName" select="/*/CountryList/Country[CountryID=$countryID]/CountryName" />

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
									<xsl:if test="searchDateFrom != ''">
										<xsl:text>&amp;dateFrom=</xsl:text>
										<xsl:call-template name="formatDate">
											<xsl:with-param name="date" select="searchDateFrom" />
											<xsl:with-param name="format" select="'yyyy-mm-dd'" />
										</xsl:call-template>
									</xsl:if>
									<xsl:if test="searchDateTo != ''">
										<xsl:text>&amp;dateTo=</xsl:text>
										<xsl:call-template name="formatDate">
											<xsl:with-param name="date" select="searchDateTo" />
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

									<xsl:if test="searchDateFrom != ''">
										<xsl:text>&amp;dateFrom=</xsl:text>
										<xsl:call-template name="formatDate">
											<xsl:with-param name="date" select="searchDateFrom" />
											<xsl:with-param name="format" select="'yyyy-mm-dd'" />
										</xsl:call-template>
									</xsl:if>
									<xsl:if test="searchDateTo != ''">
										<xsl:text>&amp;dateTo=</xsl:text>
										<xsl:call-template name="formatDate">
											<xsl:with-param name="date" select="searchDateTo" />
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
					<xsl:variable name="AccommodationRegionName" select="/*/RegionList/Region[RegionID=$AccommodationRegionID]/RegionName" />

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
					<article class="row search-results-row">
						<div class="col-md-3">
							<a href="{$ImageSource}" title="{$AccommodationName}" data-toggle="lightbox">
								<img class="lazy" data-src="{$ImageSource}">
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

						<div class="col-md-9 villa-search-result-row">
							<h1>
								<a href="{$objectFinalURL}" title="{$AccommodationName}">
									<span class="text">
										<xsl:value-of select="$AccommodationName"/>
									</span>
									<span class="stars">
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
									</span>
								</a>
							</h1>
							<div class="description">
								<xsl:value-of select="ShortDescription" disable-output-escaping="yes"/>
								<xsl:comment></xsl:comment>
							</div>

							<div class="row villa-object-price-row-special-offer">
								<div class="col-md-3">
									<div class="heading-caps">
										<xsl:value-of select="/*/NumberOfDays" />
										<xsl:text> </xsl:text>
										<xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='dayOrDays']/value"/>
									</div>
									<div style="line-heigh:22px;">
										<xsl:choose>
											<xsl:when test="searchDateFrom!='' and searchDateFrom!=''">
												<xsl:call-template name="formatDate">
													<xsl:with-param name="date" select="searchDateFrom" />
													<xsl:with-param name="format" select="$dateFormat" />
												</xsl:call-template>
												-
												<xsl:call-template name="formatDate">
													<xsl:with-param name="date" select="searchDateTo" />
													<xsl:with-param name="format" select="$dateFormat" />
												</xsl:call-template>
											</xsl:when>
											<xsl:otherwise>
												<xsl:call-template name="formatDate">
													<xsl:with-param name="date" select="/*/StartDate" />
													<xsl:with-param name="format" select="$dateFormat" />
												</xsl:call-template>
												-
												<xsl:call-template name="formatDate">
													<xsl:with-param name="date" select="/*/EndDate" />
													<xsl:with-param name="format" select="$dateFormat" />
												</xsl:call-template>
											</xsl:otherwise>
										</xsl:choose>

									</div>
								</div>
								<div class="col-md-6 text-right heading-caps">
									<xsl:call-template name="PrintOutTheSpecialOfferPrice">
										<xsl:with-param name="MainAccommodationObject" select="."/>
										<xsl:with-param name="CurrencyAbreviation" select="/*/Currency/CurrencyShortName" />
									</xsl:call-template>
								</div>
								<a href="{$objectFinalURL}" title="{$AccommodationName}" class="button medium" style="float:right;" target="_blank" rel="noopener">
									<xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='seFerieboligen']/value"/>
								</a>
							</div>
						</div>
						<div class="col-xs-12">
							<div class="row region-name-row {$region-hor-line-class}">
								<div class="col-md-3 col-xs-12 text-center">
									<xsl:value-of select="$AccommodationRegionName"/>
								</div>
							</div>
						</div>
					</article>

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



			</xsl:when>
			<xsl:otherwise>
				<!-- If there is no results, display this template -->
				<xsl:choose>
					<xsl:when test="$showWishListButton=1">
						<div class="no-results">
							<h2 class="no-results-naslov">
								<xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='noResultsFound']/value"
											   disable-output-escaping="yes"/>
							</h2>
							<p class="no-results-review">
								<br/>
								<br/>
								<xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='noFavoritesFound']/value" disable-output-escaping="yes"/>
							</p>
						</div>
					</xsl:when>
					<xsl:otherwise>
						<div class="no-results">
							<h2 class="no-results-naslov">
								<xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='noResultsFound']/value"
											   disable-output-escaping="yes"/>
							</h2>
							<h3 class="green-title">
								<xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='itIsPossibleThat']/value"
											   disable-output-escaping="yes"/>
							</h3>
							<ul class="no-results-options-list">
								<li>
									<xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='noBookingBecauseOfSaturday']/value"
												  disable-output-escaping="yes"/>
								</li>
								<li>
									<xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='noAvailableAccommodationInGivenTime']/value"
												  disable-output-escaping="yes"/>
								</li>
								<li>
									<xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='noAvailableAccommodationInGivenLocations']/value"
												  disable-output-escaping="yes"/>
								</li>
								<li>
									<xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='noAvailableAccommodationInGivenCategory']/value"
												  disable-output-escaping="yes"/>
								</li>
							</ul>
							<p class="no-results-review">
								<xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='reviewYourQuery']/value"/>
							</p>
						</div>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:otherwise>
		</xsl:choose>

    <!-- Draw pagination - bottom -->
    <xsl:call-template name="pagination-template"></xsl:call-template>

	</xsl:template>
</xsl:stylesheet>
