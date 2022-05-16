<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:msxsl="urn:schemas-microsoft-com:xslt"
				exclude-result-prefixes="msxsl"
>
  <xsl:output method="html"
			  indent="yes"/>

  <xsl:template match="@* | node()">
	 <xsl:copy>
		<xsl:apply-templates select="@* | node()"/>
	 </xsl:copy>
  </xsl:template>

  <!-- KeyTables is defined as Key = f(ServiceType_TypeOfPayment_Periods) -->
  <!-- KeyTables is used to determine how many tables we need to draw -->
  <xsl:key name="KeyTables"
			match="/*/AccommodationObject/UnitList/AccommodationUnit/ServiceList/Service"
			use="concat(ServiceType, '_', BillingType/BillingTypeID, '_', GroupID)"/>

  <!-- KeyRows is defined as Key = f(ServiceType_TypeOfPayment_Periods_ServiceID -->
  <!-- For each distinct record matching KeyRows a new row needs to be added to the table -->
  <xsl:key name="KeyRows"
			match="/*/AccommodationObject/UnitList/AccommodationUnit/ServiceList/Service"
			use="concat(ServiceType, '_', BillingType/BillingTypeID, '_', GroupID, '_', ServiceID)"/>

  <xsl:key name="KeyItems"
			match="/*/AccommodationObject/UnitList/AccommodationUnit/ServiceList/Service/PriceRowList/PriceRow/PriceItemList/PriceItem"
			use="generate-id()"/>

  <xsl:key name="KeyItemsPrices"
			match="/*/AccommodationObject/UnitList/AccommodationUnit/ServiceList/Service/PriceRowList/PriceRow/PriceItemList/PriceItem/ListPriceOnDayOfWeek/PriceOnDayOfWeek"
			use="concat(generate-id(../..),'_',PriceOnDay)"/>

  <!-- ************************************************** -->
  <!--                Global variables                    -->
  <!-- ************************************************** -->
  <xsl:variable name="languageIDInternalVar" select="/*/Language/LanguageID" />

  <xsl:template name="villasPriceListTemplate">
	 <xsl:variable name="numberOfAccommodationUnits" select="count(/*/AccommodationObject/UnitList/AccommodationUnit)" />
	 <xsl:for-each select="/*/AccommodationObject/UnitList/AccommodationUnit/ServiceList/Service[generate-id() = generate-id(key('KeyTables',concat('Basic', '_', BillingType/BillingTypeID, '_', GroupID))[1])]">
		<!-- Define whether the MaxDays column should be displayed -->
		<xsl:variable name="countOfServicesWithMaxDays"
					  select="count(key('KeyTables',concat('Basic', '_', BillingType/BillingTypeID, '_', GroupID))[PriceRowList/PriceRow/MaximumStay > 0])" />
		<xsl:variable name="drawMaxColumn">
		  <xsl:choose>
			 <xsl:when test="$countOfServicesWithMaxDays = 0">false</xsl:when>
			 <xsl:otherwise>true</xsl:otherwise>
		  </xsl:choose>
		</xsl:variable>

		<table class="reponsive-table pricing-table">
		  <xsl:call-template name="DrawTableHeader">
			 <xsl:with-param name="Type"
							 select="ServiceType"/>
			 <xsl:with-param name="DrawMaxColumn"
							 select="$drawMaxColumn" />
		  </xsl:call-template>
		  <tbody>
			 <xsl:for-each select="key('KeyTables',concat('Basic', '_', BillingType/BillingTypeID, '_', GroupID))">
				<xsl:call-template name="DrawTableRowBasic">
				  <xsl:with-param name="DrawMaxColumn" select="$drawMaxColumn" />
				</xsl:call-template>
			 </xsl:for-each>
		  </tbody>
		</table>
	 </xsl:for-each>
  </xsl:template>

  <xsl:template name="DrawTableHeader">
	 <xsl:param name="Type" />
	 <xsl:param name="DrawMaxColumn" />
	 <xsl:param name="ShowLeftSide" select="true()" />
	 <xsl:param name="ShowRightSide" select="true()" />
	 <thead>
		<tr>
		  <!--If left side of table is visible -->
		  <xsl:if test="$ShowLeftSide = true()">
			 <!-- Header of first column is the BillingTypeName -->
			 <th data-toggle="true" class="billing-type">
				<xsl:value-of select="BillingType/BillingTypeName"/>
			 </th>
			 <xsl:if test="$Type = 'Basic'">
				<th data-hide="phone,tablet" class="min-persons">
				  <xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageIDInternalVar]/root/data[@name='maxPersons']/value" disable-output-escaping="yes" />
				</th>
			 </xsl:if>
			 <xsl:if test="$Type = 'Supplement' or $Type = 'Basic' ">
				<th data-hide="phone,tablet" class="min-days">
				  <xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='cleaningIncluded']/value"/>
				  <!--if all cleaning services has billing type per person then display per person text in the table heading-->
				  <xsl:variable name="extraCleaning" select="normalize-space(/*/AccommodationObject/AttributeGroupList/AttributeGroup/AttributeList/Attribute[AttributeID=1010]/AttributeValue)" />
				  <xsl:choose>
					 <xsl:when test="$extraCleaning = '1'">
						<xsl:variable name="numberOfCleaningServices"
									  select="count(/*/AccommodationObject/UnitList/AccommodationUnit/ServiceList/Service[(ServiceID=176 or ServiceID=303 or ServiceID=307 or ServiceID=313 or ServiceID=314 or ServiceID=315 or ServiceID=321)])"/>
						<xsl:variable name="numberOfCleaningServicePricePerPerson"
									  select="count(/*/AccommodationObject/UnitList/AccommodationUnit/ServiceList/Service[(ServiceID=176 or ServiceID=303 or ServiceID=307 or ServiceID=313 or ServiceID=314 or ServiceID=315 or ServiceID=321) and (BillingType/BillingTypeID = 2 or BillingType/BillingTypeID = 6 or BillingType/BillingTypeID = 9)])"/>
						<xsl:if test="$numberOfCleaningServices = $numberOfCleaningServicePricePerPerson and $numberOfCleaningServices != 0 and $numberOfCleaningServicePricePerPerson  != 0">
						  <xsl:text> (per person)</xsl:text>
						</xsl:if>
					 </xsl:when>
				  </xsl:choose>
				</th>
				<xsl:if test="$DrawMaxColumn = 'true'">
				  <th data-hide="phone,tablet" class="max-days">
					 <xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageIDInternalVar]/root/data[@name='maxDays']/value" />
				  </th>
				</xsl:if>
			 </xsl:if>
		  </xsl:if>
		  <!--If right side of table is visible -->
		  <xsl:if test="$ShowRightSide = true()">
			 <!-- A header for each period dates -->
			 <xsl:for-each select="PriceRowList/PriceRow[1]/PriceItemList/PriceItem">
				<xsl:variable name="periodID" select="PeriodID" />
				<th data-hide="phone,tablet" class="period">
				  <xsl:for-each select="/*/PeriodList/Period[PeriodID=$periodID]/DateList/Date">
					 <xsl:call-template name="formatDate">
						<xsl:with-param name="date" select="StartDate" />
						<xsl:with-param name="format" select="'dd.mm.'" />
					 </xsl:call-template>
					 <xsl:text> - </xsl:text>
					 <xsl:call-template name="formatDate">
						<xsl:with-param name="date" select="EndDate" />
						<xsl:with-param name="format" select="'dd.mm.'" />
					 </xsl:call-template>
					 <br/>
				  </xsl:for-each>
				</th>
			 </xsl:for-each>
		  </xsl:if>
		</tr>
	 </thead>
  </xsl:template>

  <xsl:template name="DrawTableRowBasic">
	 <xsl:param name="DrawMaxColumn" />
	 <xsl:param name="ShowLeftSide" select="true()" />
	 <xsl:param name="ShowRightSide" select="true()" />
	 <xsl:for-each select="PriceRowList/PriceRow">
		<tr>
		  <!--If left side of table is visible -->
		  <xsl:if test="$ShowLeftSide = true()">
			 <!-- Output "unit name - service name"-->
			 <xsl:if test="position() = 1">
				<xsl:variable name="rowSpan" select="count(../PriceRow)"/>
				<td rowspan="{$rowSpan}" class="unit-name">
				  <!-- Room name & Description-->
				  <strong>
					 <xsl:value-of select="../../../../AttributeGroupList/AttributeGroup/AttributeList/Attribute[AttributeID=133]/AttributeValue"/>
				  </strong>
				</td>
			 </xsl:if>

			 <td class="min-persons">
				<xsl:choose>
				  <xsl:when test="position()=last()">
					 <xsl:value-of select="../../../../AttributeGroupList/AttributeGroup/AttributeList/Attribute[AttributeID=120]/AttributeValue" />
				  </xsl:when>
				  <xsl:otherwise>
					 <xsl:value-of select="number(following-sibling::PriceRow/MinimumPersons) - 1" />
				  </xsl:otherwise>
				</xsl:choose>
			 </td>

			 <td class="min-days">
				<xsl:variable name="extraCleaning"
							  select="normalize-space(../../../../../../AttributeGroupList/AttributeGroup/AttributeList/Attribute[AttributeID=1010]/AttributeValue)" />
				<xsl:variable name="cleaningIncluded"
							  select="normalize-space(../../../../AttributeGroupList/AttributeGroup/AttributeList/Attribute[AttributeID=1031]/AttributeValue)" />
				<xsl:choose>
				  <xsl:when test="$extraCleaning = '' or $extraCleaning = '0' or $cleaningIncluded = '1'">
					 <xsl:choose>
						<xsl:when test="$languageIDInternalVar='da'">
						  <xsl:text>Inkl.</xsl:text>
						</xsl:when>
						<xsl:otherwise>
						  <xsl:text>Incl.</xsl:text>
						</xsl:otherwise>
					 </xsl:choose>
				  </xsl:when>
				  <xsl:when test="$extraCleaning = '1'">
					 <xsl:variable name="currentUnitID" select="../../../../UnitID" />

					 <xsl:for-each select="/*/AccommodationObject/UnitList/AccommodationUnit[UnitID=$currentUnitID]">
						<xsl:for-each select="ServiceList/Service[(ServiceID=176 or ServiceID=303 or ServiceID=307 or ServiceID=313 or ServiceID=314 or ServiceID=315 or ServiceID=321)]">
						  <xsl:for-each select="PriceRowList/PriceRow/PriceItemList/PriceItem/ListPriceOnDayOfWeek/PriceOnDayOfWeek[PriceOnDay &gt; 0]">
							 <xsl:if test="position() = 1">
								<xsl:value-of select="format-number(PriceOnDay, $numberFormat, $locale)"/>
								<br />
							 </xsl:if>
						  </xsl:for-each>
						</xsl:for-each>
					 </xsl:for-each>
				  </xsl:when>
				</xsl:choose>
			 </td>
		  </xsl:if>
		  <!--If right side of table is visible -->
		  <xsl:if test="$ShowRightSide = true()">
			 <!-- For each period fill in its price -->
			 <xsl:for-each select="PriceItemList/PriceItem">
				<td class="price-item">
				  <xsl:call-template name="OutputPrices" />
				</td>
			 </xsl:for-each>
		  </xsl:if>
		</tr>
	 </xsl:for-each>
  </xsl:template>

  <xsl:template name="DrawTableRowSupplement">
	 <xsl:param name="Type" />
	 <xsl:param name="NumberOfAccommodationUnits" />
	 <xsl:param name="DrawMaxColumn" />
	 <xsl:for-each select="PriceRowList/PriceRow">
		<tr>
		  <!-- Output "unit name - service name"-->
		  <xsl:if test="position() = 1">
			 <xsl:variable name="rowSpan"
							select="count(../PriceRow)"/>
			 <td rowspan="{$rowSpan}"
				 class="unit-name">
				<xsl:value-of select="../../ServiceName"/>

				<xsl:if test="count(key('KeyRows',concat(../../ServiceType, '_', ../../BillingType/BillingTypeID, '_', ../../GroupID, '_', ../../ServiceID))) != $NumberOfAccommodationUnits">
				  <br/>
				  <xsl:text> (</xsl:text>
				  <xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageIDInternalVar]/root/data[@name='validFor']/value"/>
				  <xsl:text>: </xsl:text>
				  <!-- Units for which this service is vaild -->
				  <xsl:for-each select="key('KeyRows',concat(../../ServiceType, '_', ../../BillingType/BillingTypeID, '_', ../../GroupID, '_', ../../ServiceID))">
					 <xsl:value-of select="../../OrdinalNumber"/>
					 <xsl:if test="position() != last()">
						<xsl:text>, </xsl:text>
					 </xsl:if>
				  </xsl:for-each>
				  <xsl:text>)</xsl:text>
				</xsl:if>

			 </td>
		  </xsl:if>
		  <xsl:if test="$Type = 'Supplement'">
			 <td class="min-days">
				<xsl:value-of select="MinimumStay"/>
			 </td>

			 <xsl:if test="$DrawMaxColumn = 'true'">
				<td class="max-stay">
				  <xsl:choose>
					 <xsl:when test="MaximumStay = 0">
						<xsl:text>-</xsl:text>
					 </xsl:when>
					 <xsl:otherwise>
						<xsl:value-of select="MaximumStay"/>
					 </xsl:otherwise>
				  </xsl:choose>
				</td>
			 </xsl:if>
		  </xsl:if>

		  <!-- For each period fill in its price -->
		  <xsl:for-each select="PriceItemList/PriceItem">
			 <td class="price-item">
				<xsl:call-template name="OutputPrices" />
			 </td>
		  </xsl:for-each>
		</tr>
	 </xsl:for-each>
  </xsl:template>

  <xsl:template name="OutputPrices">
	 <xsl:for-each select="ListPriceOnDayOfWeek/PriceOnDayOfWeek[generate-id() = generate-id(key('KeyItemsPrices',concat(generate-id(../..),'_',PriceOnDay))[1])]">

		<!-- Output the price -->
		<xsl:choose>
		  <xsl:when test="key('KeyItemsPrices',concat(generate-id(../..), '_', PriceOnDay))/PriceOnDay = 0">
			 <xsl:text> - </xsl:text>
		  </xsl:when>
		  <xsl:otherwise>
			 <xsl:if test="../../../../../../ServicePriceType = 'Fixed'">
				<xsl:value-of select="format-number(round(key('KeyItemsPrices',concat(generate-id(../..), '_', PriceOnDay))/PriceOnDay), $numberFormat, $locale)"/>
			 </xsl:if>
			 <xsl:if test="../../../../../../ServicePriceType = 'Percentage'">
				<xsl:value-of select="format-number(round(key('KeyItemsPrices',concat(generate-id(../..), '_', PriceOnDay))/PriceOnDay * 100), $numberFormat, $locale)"/>
				<xsl:text>%</xsl:text>
			 </xsl:if>
		  </xsl:otherwise>
		</xsl:choose>

		<!-- If price is valid on certain days only, output the days -->
		<xsl:if test="count(key('KeyItemsPrices',concat(generate-id(../..), '_', PriceOnDay))) &lt; 7">
		  <xsl:text> (</xsl:text>
		  <xsl:for-each select="key('KeyItemsPrices',concat(generate-id(../..), '_', PriceOnDay))">
			 <xsl:call-template name="ShortDayName">
				<xsl:with-param name="DayName" select="DayOfWeek" />
			 </xsl:call-template>
			 <xsl:if test="position() != last()">
				<xsl:text>, </xsl:text>
			 </xsl:if>
		  </xsl:for-each>
		  <xsl:text>)</xsl:text>
		</xsl:if>
		<xsl:if test="position() != last()">
		  <br/>
		</xsl:if>
	 </xsl:for-each>
  </xsl:template>

  <xsl:template name="ShortDayName">
	 <xsl:param name="DayName" />

	 <xsl:choose>
		<xsl:when test="$DayName = 'Monday'">
		  <xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageIDInternalVar]/root/data[@name='mondayShort']/value" />
		</xsl:when>
	 </xsl:choose>
	 <xsl:choose>
		<xsl:when test="$DayName = 'Tuesday'">
		  <xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageIDInternalVar]/root/data[@name='tuesdayShort']/value" />
		</xsl:when>
	 </xsl:choose>
	 <xsl:choose>
		<xsl:when test="$DayName = 'Wednesday'">
		  <xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageIDInternalVar]/root/data[@name='wednesdayShort']/value" />
		</xsl:when>
	 </xsl:choose>
	 <xsl:choose>
		<xsl:when test="$DayName = 'Thursday'">
		  <xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageIDInternalVar]/root/data[@name='thursdayShort']/value" />
		</xsl:when>
	 </xsl:choose>
	 <xsl:choose>
		<xsl:when test="$DayName = 'Friday'">
		  <xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageIDInternalVar]/root/data[@name='fridayShort']/value" />
		</xsl:when>
	 </xsl:choose>
	 <xsl:choose>
		<xsl:when test="$DayName = 'Saturday'">
		  <xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageIDInternalVar]/root/data[@name='saturdayShort']/value" />
		</xsl:when>
	 </xsl:choose>
	 <xsl:choose>
		<xsl:when test="$DayName = 'Sunday'">
		  <xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageIDInternalVar]/root/data[@name='sundayShort']/value" />
		</xsl:when>
	 </xsl:choose>
  </xsl:template>

</xsl:stylesheet>
