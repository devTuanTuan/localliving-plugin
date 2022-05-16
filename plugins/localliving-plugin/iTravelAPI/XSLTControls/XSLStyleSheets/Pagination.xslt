<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:msxsl="urn:schemas-microsoft-com:xslt" exclude-result-prefixes="msxsl"
>
  <xsl:output method="xml" indent="yes"/>

  <xsl:template match="@* | node()">
    <xsl:copy>
      <xsl:apply-templates select="@* | node()"/>
    </xsl:copy>
  </xsl:template>

  <!-- Pagination variables -->
  <xsl:variable name="currentPage" select="/SearchResults/CurrentPage"/>
  <xsl:variable name="pageSize" select="/SearchResults/PageSize"/>
  <xsl:variable name="numberOfPages" select="ceiling($totalResults div $pageSize)"/>
  <xsl:variable name="firstPageToDisplay"
                select="(ceiling($currentPage div $numberOfPagesToDisplay) - 1) * $numberOfPagesToDisplay + 1"/>

  <xsl:template name="paginationLoop">
    <xsl:param name="count"
               select="1"/>
    <xsl:param name="originalCount"
               select="2"/>
    <xsl:if test="$count > ($firstPageToDisplay - 1)">
      <!-- Logic for loop goes here-->
      <xsl:variable name="pageNumber"
                    select="$originalCount - $count + $firstPageToDisplay"/>
      <xsl:variable name="url">
        <xsl:call-template name="paginationUrl">
          <xsl:with-param name="pageNumberParameter"
                          select="$pageNumber"/>
        </xsl:call-template>
      </xsl:variable>
      <xsl:if test="$pageNumber != $currentPage">
        <li>
          <!--					  <a href="{$url}">-->
          <!--						  <xsl:value-of select="$pageNumber"/>-->
          <!--					  </a>-->
          <button type="submit" name="Pagination" value="{$url}">
            <xsl:value-of select="$pageNumber"/>
          </button>
        </li>
      </xsl:if>
      <xsl:if test="$pageNumber = $currentPage">
        <li class="selected">
          <!--					  <a href="{$url}">-->
          <!--						  <xsl:value-of select="$pageNumber"/>-->
          <!--					  </a>-->
          <button type="submit" name="Pagination" value="{$url}" disabled="disabled">
            <xsl:value-of select="$pageNumber"/>
          </button>
        </li>
      </xsl:if>
      <!-- Recursively call the paginationLoop-->
      <xsl:call-template name="paginationLoop">
        <xsl:with-param name="count"
                        select="$count - 1"/>
        <xsl:with-param name="originalCount"
                        select="$originalCount"/>
      </xsl:call-template>
    </xsl:if>

  </xsl:template>
  <xsl:template name="paginationUrl">
    <xsl:param name="pageNumberParameter"/>
    <xsl:param name="overrideFrom" select="0"/>
    <xsl:param name="overrideTo" select="0"/>

    <!-- Define a URL for a page-->

    <!-- Define parameters -->
    <xsl:if test="$personsParameter != ''">
      <xsl:text>persons=</xsl:text>
      <xsl:value-of select="$personsParameter"/>
    </xsl:if>
    <xsl:if test="$fromParameter != ''">
      <xsl:text>&amp;from=</xsl:text>
      <xsl:choose>
        <xsl:when test="$overrideFrom != 0">
          <xsl:value-of select="$overrideFrom"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="$fromParameter"/>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:if>

    <xsl:if test="$toParameter != ''">
      <xsl:text>&amp;to=</xsl:text>
      <xsl:choose>
        <xsl:when test="$overrideTo != 0">
          <xsl:value-of select="$overrideTo"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="$toParameter"/>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:if>
    <xsl:text>&amp;languageID=</xsl:text>
    <xsl:value-of select="$languageID"/>
    <xsl:text>&amp;currencyID=</xsl:text>
    <xsl:value-of select="$currencyID"/>

    <xsl:text>&amp;currentPage=</xsl:text>
    <xsl:value-of select="$pageNumberParameter"/>


    <xsl:if test="$categoryIDParameter != '' and $categoryIDParameter != '0'">
      <xsl:text>&amp;categoryID=</xsl:text>
      <xsl:value-of select="$categoryIDParameter"/>
    </xsl:if>
    <xsl:if test="$objectTypeIDParameter != '' and $objectTypeIDParameter != '0'">
      <xsl:text>&amp;objectTypeID=</xsl:text>
      <xsl:value-of select="$objectTypeIDParameter"/>
    </xsl:if>
    <xsl:if test="$objectTypeGroupIDParameter != ''">
      <xsl:text>&amp;objectTypeGroupID=</xsl:text>
      <xsl:value-of select="$objectTypeGroupIDParameter"/>
    </xsl:if>
    <xsl:if test="$objectName != ''">
      <xsl:text>&amp;objectName=</xsl:text>
      <xsl:value-of select="$objectName"/>
    </xsl:if>
    <xsl:if test="$countryIDParameter != '' and $countryIDParameter != '0'">
      <xsl:text>&amp;countryID=</xsl:text>
      <xsl:value-of select="$countryIDParameter"/>
    </xsl:if>
    <xsl:if test="$regionIDParameter != '' and $regionIDParameter != '0'">
      <xsl:text>&amp;regionID=</xsl:text>
      <xsl:value-of select="$regionIDParameter"/>
    </xsl:if>
    <xsl:if test="$destinationIDParameter != '' and $destinationIDParameter != '0'">
      <xsl:text>&amp;destinationID=</xsl:text>
      <xsl:value-of select="$destinationIDParameter"/>
    </xsl:if>
    <xsl:if test="$numberOfStarsParameter != '' and $numberOfStarsParameter != '0'">
      <xsl:text>&amp;numberOfStars=</xsl:text>
      <xsl:value-of select="$numberOfStarsParameter"/>
    </xsl:if>
    <xsl:if test="$priceFromParameter != '' and $priceFromParameter != '0'">
      <xsl:text>&amp;priceFrom=</xsl:text>
      <xsl:value-of select="$priceFromParameter"/>
    </xsl:if>
    <xsl:if test="$priceToParameter != '' and $priceToParameter != '0'">
      <xsl:text>&amp;priceTo=</xsl:text>
      <xsl:value-of select="$priceToParameter"/>
    </xsl:if>
    <xsl:if test="$onlyOnSpecialOfferParameter != ''">
      <xsl:text>&amp;onlyOnSpecialOffer=</xsl:text>
      <xsl:value-of select="$onlyOnSpecialOfferParameter"/>
    </xsl:if>
    <xsl:if test="$ignorePriceAndAvailabilityParam != ''">
      <xsl:text>&amp;ignorePriceAndAvailability=</xsl:text>
      <xsl:value-of select="$ignorePriceAndAvailabilityParam"/>
    </xsl:if>
    <xsl:if test="$destinationName != ''">
      <xsl:text>&amp;destinationName=</xsl:text>
      <xsl:value-of select="$destinationName"/>
    </xsl:if>
    <xsl:if test="$childrenParameter != '' and $childrenParameter != '0'">
      <xsl:text>&amp;children=</xsl:text>
      <xsl:value-of select="$childrenParameter"/>
    </xsl:if>
    <xsl:if test="$childrenAgesParameter != ''">
      <xsl:text>&amp;childrenAges=</xsl:text>
      <xsl:value-of select="$childrenAgesParameter"/>
    </xsl:if>
    <xsl:if test="$serviceName != ''">
      <xsl:text>&amp;serviceName=</xsl:text>
      <xsl:value-of select="$serviceName"/>
    </xsl:if>
    <xsl:if test="$globalDestinationID != '' and $globalDestinationID != '0'">
      <xsl:text>&amp;globalDestinationID=</xsl:text>
      <xsl:value-of select="$globalDestinationID"/>
    </xsl:if>
    <xsl:if test="$searchSupplier != ''">
      <xsl:text>&amp;searchSupplier=</xsl:text>
      <xsl:value-of select="$searchSupplier"/>
    </xsl:if>
    <xsl:if test="$ignoreStartDay != ''">
      <xsl:text>&amp;ignoreStartDay=</xsl:text>
      <xsl:value-of select="$ignoreStartDay"/>
    </xsl:if>
    <xsl:if test="/*/QueryString/objectAttributeFilters!=''">
      <xsl:text>&amp;objectAttributeFilters=</xsl:text>
      <xsl:value-of select="/*/QueryString/objectAttributeFilters"/>
    </xsl:if>
    <xsl:if test="/*/QueryString/unitAttributeFilters!=''">
      <xsl:text>&amp;unitAttributeFilters=</xsl:text>
      <xsl:value-of select="/*/QueryString/unitAttributeFilters"/>
    </xsl:if>
    <xsl:if test="/*/QueryString/numberOfStarsCategory!=''">
      <xsl:text>&amp;numberOfStarsCategory=</xsl:text>
      <xsl:value-of select="/*/QueryString/numberOfStarsCategory"/>
    </xsl:if>
    <xsl:if test="/*/QueryString/sortByStars!=''">
      <xsl:text>&amp;sortByStars=</xsl:text>
      <xsl:value-of select="/*/QueryString/sortByStars"/>
    </xsl:if>
    <xsl:if test="/*/QueryString/sortByPrice!=''">
      <xsl:text>&amp;sortByPrice=</xsl:text>
      <xsl:value-of select="/*/QueryString/sortByPrice"/>
    </xsl:if>
    <xsl:if test="/*/QueryString/categoryIntersectionID!=''">
      <xsl:text>&amp;categoryIntersectionID=</xsl:text>
      <xsl:value-of select="/*/QueryString/categoryIntersectionID"/>
    </xsl:if>
    <xsl:if test="/*/QueryString/objectTypeGroupID!=''">
      <xsl:text>&amp;objectTypeGroupID=</xsl:text>
      <xsl:value-of select="/*/QueryString/objectTypeGroupID"/>
    </xsl:if>

  </xsl:template>
  <xsl:template name="pagination-template">
    <div class="pagination-sort-holder">
      <xsl:if test="$numberOfPages > 1">
        <ul class="pagination-list">

          <!-- Draw previous page buttons -->
          <xsl:choose>
            <xsl:when test="$currentPage > 1">
              <xsl:variable name="previousPage"
                            select="$currentPage - 1"/>
              <li>
                <xsl:variable name="url">
                  <xsl:call-template name="paginationUrl">
                    <xsl:with-param name="pageNumberParameter"
                                    select="1"/>
                  </xsl:call-template>
                </xsl:variable>
                <button type="submit" name="Pagination" value="{$url}">
                  «
                </button>
              </li>
              <li>
                <xsl:variable name="url">
                  <xsl:call-template name="paginationUrl">
                    <xsl:with-param name="pageNumberParameter"
                                    select="$previousPage"/>
                  </xsl:call-template>
                </xsl:variable>
                <button type="submit" name="Pagination" value="{$url}">
                  ‹
                </button>
              </li>
            </xsl:when>
            <xsl:otherwise>
              <li>
                <button type="submit" name="Pagination" value="" disabled="disabled">
                  «
                </button>
              </li>
              <li>
                <button type="submit" name="Pagination" value="" disabled="disabled">
                  ‹
                </button>
              </li>
            </xsl:otherwise>
          </xsl:choose>

          <!-- Template $currentPage of $numberOfPages -->
          <div class="page-counter">
            <xsl:value-of select="$currentPage"/> of
            <xsl:value-of select="$numberOfPages"/>
          </div>

          <!-- Draw next page buttons -->
          <xsl:choose>
            <xsl:when test="$currentPage &lt; $numberOfPages">
              <xsl:variable name="nextPage"
                            select="$currentPage + 1"/>
              <li>
                <xsl:variable name="url">
                  <xsl:call-template name="paginationUrl">
                    <xsl:with-param name="pageNumberParameter"
                                    select="$nextPage"/>
                  </xsl:call-template>
                </xsl:variable>
                <button type="submit" name="Pagination" value="{$url}">
                 ›
                </button>
              </li>
              <li>
                <xsl:variable name="url">
                  <xsl:call-template name="paginationUrl">
                    <xsl:with-param name="pageNumberParameter"
                                    select="$numberOfPages"/>
                  </xsl:call-template>
                </xsl:variable>
                <button type="submit" name="Pagination" value="{$url}">
                 »
                </button>
              </li>
            </xsl:when>
            <xsl:otherwise>
              <li>
                <button type="submit" name="Pagination" value="" disabled="disabled">
                  ›
                </button>
              </li>
              <li>
                <button type="submit" name="Pagination" value="" disabled="disabled">
                  »
                </button>
              </li>
            </xsl:otherwise>
          </xsl:choose>
        </ul>
      </xsl:if>
      <xsl:text> </xsl:text>
    </div>
  </xsl:template>
</xsl:stylesheet>
