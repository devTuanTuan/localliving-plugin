<xsl:stylesheet version="1.0"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:msxsl="urn:schemas-microsoft-com:xslt"
				exclude-result-prefixes="msxsl">

	<xsl:output method="html"
				indent="yes"
				cdata-section-elements="" />


	<xsl:template match="@* | node()">
		<xsl:copy>
			<xsl:apply-templates select="@* | node()"/>
		</xsl:copy>
	</xsl:template>

	<!--dodajem globalni parametar zbog toga jer kada saljem parametar iz koda ne mogu ga dodjeliti odredjenom template-u-->
	<!--kasnije u template-u cu provjeriti koji parametar (globalni ili onaj iz template-a) je popunjen i njega koristim-->
	<!-- ***************************************************************************************************-->
	<!-- OVI GLOBALNI PARAMETRI KORISTE SE ZA POZIV TEMPLATEA AKO GA SE ZOVE IZ PROXYA ILI OPĆENITO C# KODA -->
	<!-- ***************************************************************************************************-->
	<xsl:param name="bookingAddressDisplayURLReservationGlobal" />
	<xsl:param name="setDirectLinkGlobal" />
	<xsl:param name="unitActivityStatusGlobal" />
	<xsl:param name="BookingLinkInNewWindowGlobal" />
	<xsl:param name="childrenParamGlobal" />
	<xsl:param name="childrenAgesParamGlobal" />
	<xsl:param name="showChildrenAgesParamGlobal" />
	<xsl:param name="dateFormatParameterGlobal" />
	<xsl:param name="customerIDParameterGlobal" />
	<xsl:param name="marketIDParameterGlobal" />
	<xsl:param name="affiliateIDParameterGlobal" />

	<xsl:template name="villasUnitListTemplate"
					match="/">
		<!-- ************************************************************************************************-->
		<!-- OVI LOKALNI PARAMETRI KORISTE SE ZA POZIV TEMPLATEA AKO GA SE ZOVE IZ DRUGOG XSLT-a -->
		<!-- ************************************************************************************************-->
		<xsl:param name="bookingAddressDisplayURLReservation" />
		<xsl:param name="setDirectLinkInternal" />
		<xsl:param name="unitActivityStatusTab" />
		<xsl:param name="BookingLinkInNewWindowTab" />
		<xsl:param name="childrenParamTab" />
		<xsl:param name="childrenAgesParamTab" />
		<xsl:param name="showChildrenAgesParamTab" />
		<xsl:param name="dateFormatParameterTab" />
		<xsl:param name="customerIDParameterTab" />
		<xsl:param name="marketIDParameterTab" />
		<xsl:param name="affiliateIDParameterTab" />

		<!--******************************************************************************************************************-->
		<!-- Na kraju će iz uvodnog dijela koda izići nekoliko varijabli kao zbroj / presjek lokalnih i globalnih parametara! -->
		<!-- $bookingAddresZaRezervaciju -> Booking adresa na koju vodi "Book"/"Send Inquiry" botun                           -->
		<!-- $bookingLinkInNewWindowVar -> boolean koji govori treba li otvoriti link u novom prozoru.                      -->
		<!--                                 Na temelju ove varijable će se kasnije generirati targetVar                      -->
		<!-- $targetVar -> ovisno o varijabli gore ima vrijednost '' ili '_blank'                                             -->
		<!-- $bookingAddresZaRezervaciju -> Booking adresa na koju vodi "Book"/"Send Inquiry" botun                           -->
		<!-- $setDirectLinkTemp -> varijabla koja govori treba li link ići direktno ili kroz frame                            -->
		<!--                       varijabla tipa string - ne može se uspoređivati sa = true()                                -->
		<!-- $setDirectLink -> varijabla tipa bool. true() = $setDirectLinkTemp                                               -->
		<!-- *****************************************************************************************************************-->


		<!--kreiram novu varijablu u kojoj provjeravam koji je parametar popunjen te njega postavljam-->
		<!--Ako smislimo kako poslati parametar iz koda u template direktno onda ovo zamjeniti boljim rjesenjem-->
		<xsl:variable name="bookingAddressURLZaRezervaciju">
			<xsl:if test="string-length($bookingAddressDisplayURLReservationGlobal) &gt; 0">
				<xsl:value-of select="$bookingAddressDisplayURLReservationGlobal" />
			</xsl:if>
			<xsl:if test="string-length($bookingAddressDisplayURLReservation) &gt; 0">
				<xsl:value-of select="$bookingAddressDisplayURLReservation" />
			</xsl:if>
		</xsl:variable>

		<xsl:variable name="unitActivityStatusVar">
			<xsl:if test="string-length($unitActivityStatusGlobal) &gt; 0">
				<xsl:value-of select="$unitActivityStatusGlobal"/>
			</xsl:if>
			<xsl:if test="string-length($unitActivityStatusTab) &gt; 0">
				<xsl:value-of select="$unitActivityStatusTab"/>
			</xsl:if>
		</xsl:variable>

		<xsl:variable name="BookingLinkInNewWindowVar">
			<xsl:if test="string-length($BookingLinkInNewWindowGlobal) &gt; 0">
				<xsl:value-of select="$BookingLinkInNewWindowGlobal"/>
			</xsl:if>
			<xsl:if test="string-length($BookingLinkInNewWindowTab) &gt; 0">
				<xsl:value-of select="$BookingLinkInNewWindowTab"/>
			</xsl:if>
		</xsl:variable>

		<xsl:variable name="childrenVar">
			<xsl:if test="string-length($childrenParamGlobal) &gt; 0">
				<xsl:value-of select="$childrenParamGlobal"/>
			</xsl:if>
			<xsl:if test="string-length($childrenParamTab) &gt; 0">
				<xsl:value-of select="$childrenParamTab"/>
			</xsl:if>
		</xsl:variable>

		<xsl:variable name="childrenAgesVar">
			<xsl:if test="string-length($childrenAgesParamGlobal) &gt; 0">
				<xsl:value-of select="$childrenAgesParamGlobal"/>
			</xsl:if>
			<xsl:if test="string-length($childrenAgesParamTab) &gt; 0">
				<xsl:value-of select="$childrenAgesParamTab"/>
			</xsl:if>
		</xsl:variable>

		<xsl:variable name="showChildrenAgesVar">
			<xsl:if test="string-length($showChildrenAgesParamGlobal) &gt; 0">
				<xsl:value-of select="$showChildrenAgesParamGlobal"/>
			</xsl:if>
			<xsl:if test="string-length($showChildrenAgesParamTab) &gt; 0">
				<xsl:value-of select="$showChildrenAgesParamTab"/>
			</xsl:if>
		</xsl:variable>

		<xsl:variable name="childrenAgesClass">
			<xsl:if test="$showChildrenAgesVar='false'">
				<xsl:value-of select="'hideClass'"/>
			</xsl:if>
		</xsl:variable>

		<!--varijabla koja odredjuje da li ce se link otvarati u novom tabu-->
		<xsl:variable name="targetVar">
			<xsl:choose>
				<xsl:when  test="$BookingLinkInNewWindowGlobal='true'">
					<xsl:value-of select="'_blank'"/>
				</xsl:when>
				<xsl:when test="$BookingLinkInNewWindowTab='true'">
					<xsl:value-of select="'_blank'"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:text />
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<!--kreiram novu varijablu u kojoj provjeravam koji je parametar popunjen-->
		<!--kreiram prvo temp varijablu $setDirectLinkTemp jer ce ona vratiti vrijednost tipa string-->
		<!--nakon toga kreiram pravu varijablu $setDirectLink gdje u selectu provjerim da li je true ili false da bi tip bio boolean-->
		<!--to radimo zbog toga da bi mogli kasnije u kodu provjeravati uvjet sa boolean vrijednostima, na primjer true()=$setDirectLink-->
		<xsl:variable name="setDirectLinkTemp" >
			<xsl:choose>
				<xsl:when test="string-length($setDirectLinkGlobal) &gt; 0">
					<xsl:value-of select="$setDirectLinkGlobal" />
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="$setDirectLinkInternal" />
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<xsl:variable name="setDirectLink"
						select="'true' = $setDirectLinkTemp" />


		<xsl:variable name="languageID"
						select="/*/Language/LanguageID" />

		<xsl:variable name="AccommodationName"
						select="/*/AccommodationObject/AttributeGroupList/AttributeGroup/AttributeList/Attribute[AttributeID=121]/AttributeValue" />

		<xsl:variable name="currencyShortName"
						select="/*/Currency/CurrencyShortName" />

		<xsl:variable name="unitTypeIDActivity"
						select="116" />

		<xsl:variable name="numberOfPersons"
						select="/*/NumberOfPersons" />

		<xsl:variable name="priceType"
						select="/*/PriceType" />

		<xsl:variable name="adHocUnitType"
						select="/*/AccommodationObject/AdHocObjectType/ObjectTypeID"></xsl:variable>

		<xsl:variable name="dateFormat" >
			<xsl:choose>
				<xsl:when test="$dateFormatParameterTab != ''">
					<xsl:value-of select="$dateFormatParameterTab"/>
				</xsl:when>
				<xsl:when test="$dateFormatParameterGlobal != ''">
					<xsl:value-of select="$dateFormatParameterGlobal"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:text>dd.mm.yyyy</xsl:text>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<xsl:variable name="numberOfDays"
				  
						select="/*/NumberOfDays" />
		<div class="reservation-background-worker-holder"
			 style="display:none;"
			 reservation-loading-panel="1">
			<div class="reservation-background-worker">
				<xsl:comment></xsl:comment>
			</div>
		</div>




		<!--VILLAS UNITS -->
		<!--************************-->
		<section id="unit-list-container">
			<div class="row" style="background:#F5F4F0;">
				<div class="col-md-8 col-md-offset-2 villa-details-calculation-dates">
					<!--Static text-->
					<h2 id="dateSelection">
						<xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='checkAvailabilityAndPrices']/value"/>
					</h2>

					<form class="row" method="get" action="#">
						<!--Date from input iz rezervacijskog taba -->
						<div class="col-sm-3 col-xs-6 col-sm-offset-1">
							<input
								type="text"
								class="theme-input input-from ll-datepicker"
								data-datagroup="unitUpdate"
								data-datatype="from"
								readonly="true"
								id="reservationsTab_filterDateFrom" />
						</div>
						<!--Date to input iz rezervacijskog taba -->
						<div class="col-sm-3 col-xs-6">
							<input
								type="text"
								class="theme-input input-to ll-datepicker"
								data-datagroup="unitUpdate"
								data-datatype="to"
								readonly="true"
								id="reservationsTab_filterDateTo" />
						</div>
						<!--Broj osoba iz rezervacijskog taba-->
						<div class="col-sm-4 col-xs-12">

							<!-- Here we find the maximum capacity of all rooms -->
							<xsl:variable name="maximumCapacity">
								<xsl:for-each select="/*/AccommodationObject/UnitList/AccommodationUnit/AttributeGroupList/AttributeGroup/AttributeList/Attribute[AttributeID=120]/AttributeValue">
									<xsl:sort data-type="number"
											  order="descending"/>
									<xsl:if test="position()=1">
										<xsl:value-of select="."/>
									</xsl:if>
								</xsl:for-each>
							</xsl:variable>

							<select id="numberOfPersons"
									onchange="UpdateReservationTab();"
										class="theme-input input-persons dropkick" >
								<xsl:call-template name="numberOfPersonsDropDownListElements">
									<xsl:with-param name="maximumCapacity"
													select="$maximumCapacity" />
									<xsl:with-param name="selectedValue"
													select="$numberOfPersons" />
								</xsl:call-template>

							</select>
						</div>
					</form>
				</div>
			</div>

			<!--LIST OF UNITS-->
			<div class="relative-z2 clearfix villa-details-units">
				<div data-reservation-loading-panel="1" class="reservation-background-worker">

				</div>
				<!--Single Unit-->
				<xsl:for-each select="/*/AccommodationObject/UnitList/AccommodationUnit">

					<!-- ************************************* -->
					<!--            UNIT Variables             -->
					<!-- ************************************* -->
					<xsl:variable name="unitID"	 select="UnitID"/>
					<xsl:variable name="unitTypeID" select="Type/UnitTypeID"/>
					<xsl:variable name="capacity" select="AttributeGroupList/AttributeGroup/AttributeList/Attribute[AttributeID=120]/AttributeValue" />
					<xsl:variable name="minimumStay" >
						<xsl:for-each select="ServiceList/Service/PriceRowList/PriceRow/MinimumStay">
							<xsl:sort data-type="number"  order="ascending" />
							<xsl:if test="position()=1">
								<xsl:value-of select="."/>
							</xsl:if>
						</xsl:for-each>
					</xsl:variable>

					<xsl:if test="$capacity &lt; $numberOfPersons">
						<xsl:variable name="cssClass"
									  select="'accommodation-unit-oversized'" />
						<xsl:variable name="errorMessage"
									  select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='errorOversized']/value" />
					</xsl:if>
					<xsl:if test="AvailabilityStatus = 'NotAvailable'">
						<xsl:variable name="cssClass"
									  select="'accommodation-unit-not-available'" />
						<xsl:variable name="errorMessage"
									  select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='errorNotAvailable']/value" />
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
							<xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='errorOversized']/value" />
						</xsl:if>
						<xsl:if test="AvailabilityStatus = 'NotAvailable'">
							<xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='errorNotAvailable']/value" />
						</xsl:if>
						<xsl:if test="CalculatedPriceInfo/CalculatedPrice=0 and $numberOfDays &lt; $minimumStay">
							<xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='serviceCannotBeShorterThan']/value" />
							<xsl:text> </xsl:text>
							<xsl:value-of select="$minimumStay" />
							<xsl:text> </xsl:text>
							<xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='dayOrDays']/value" />
						</xsl:if>
					</xsl:variable>
					<xsl:variable name="errorType">
						<xsl:if test="$capacity &lt; $numberOfPersons">
							<xsl:text>oversized</xsl:text>
						</xsl:if>
						<xsl:if test="AvailabilityStatus = 'NotAvailable'">
							<xsl:text>notAvailable</xsl:text>
						</xsl:if>
					</xsl:variable>


					<article class="row">
						<!--Unit images-->
						<div class="col-md-3 villa-unit-image">
							<xsl:for-each select="PhotoList/Photo">
								<xsl:if test="position()=1">
									<!--First image template-->
									<a href="{PhotoUrl}" class="unit-gallery transition-slow" title="">
										<img  class="lazy" data-src="{ThumbnailUrl}" alt="{AlternateText}" />
										<span class="icon"> </span>
									</a>
								</xsl:if>
								<xsl:if test="position()!=1">
									<!--
							Other images list (exclude first) 
							Hidden from the page but accessible through a lightbox initialized on .villa-unit-image, delegate 'a'
						-->
									<div class="hide">
										<a href="{PhotoUrl}" title="{AlternateText}">
											<img  class="lazy" data-src="{ThumbnailUrl}" alt="{AlternateText}" />
										</a>
									</div>
								</xsl:if>
							</xsl:for-each>
						</div>
						<!--Unit images END-->

						<div class="col-md-9">
							<div class="row">
								<div class="col-md-12">
									<!--Unit name + min - max capacity-->
									<h1 class="heading-caps">
										<xsl:value-of select="AttributeGroupList/AttributeGroup/AttributeList/Attribute[AttributeID=133]/AttributeValue"/>
									</h1>
									<!--Unit description-->
									<div>
										<xsl:value-of select="Description" disable-output-escaping="yes"/>
									</div>

									<div class="row villa-unit-price-row">

										<div class="col-md-5">
											<xsl:choose>
												<xsl:when test="/*/PriceType='PerPerson'">
													<xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='perPerson']/value"/>
												</xsl:when>
												<xsl:when test="/*/PriceType='PerDay'">
													<xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='perDay']/value"/>
												</xsl:when>
												<xsl:otherwise>
													<div class="heading-caps">
														<xsl:value-of select="/*/NumberOfDays" />
														<xsl:text> </xsl:text>
														<xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='dayOrDays']/value"/>
													</div>
													<div>
														<xsl:call-template name="formatDate">
															<xsl:with-param name="date" select="/*/StartDate" />
															<xsl:with-param name="format" select="$dateFormat" />
														</xsl:call-template>
														-
														<xsl:call-template name="formatDate">
															<xsl:with-param name="date" select="/*/EndDate" />
															<xsl:with-param name="format" select="$dateFormat" />
														</xsl:call-template>
													</div>
												</xsl:otherwise>
											</xsl:choose>
										</div>

										<xsl:variable name="BootstrapCSSClass">
											<xsl:choose>
												<xsl:when test="(string-length(substring-before($errorType , 'notAvailable')) != 0) or ($errorType='' and CalculatedPriceInfo/CalculationStatus/Code != 'Error')">col-md-4</xsl:when>
												<xsl:otherwise>col-md-7</xsl:otherwise>
											</xsl:choose>
										</xsl:variable>
										<div class="{$BootstrapCSSClass} text-right heading-caps">
											<!--Discount-->
											<div class="row">
												<div class="col-md-12">
													<!-- Output the names of Special Offers! -->
													<xsl:for-each select="CalculatedPriceInfo/ServiceList/Service[ServiceType='SpecialOffer']">
														<xsl:variable name="serviceID"
																		select="ServiceID" />
														<xsl:value-of select="../../../SpecialOfferList/SpecialOffer[ServiceID=$serviceID]/ServiceName"/>
														<xsl:if test="position() != last()">
															<xsl:text>, </xsl:text>
														</xsl:if>
													</xsl:for-each>

												</div>
											</div>
											<div class="row">
												<!-- If there's no error show price -->
												<xsl:if test="$errorMessage=''">
													<xsl:if test="CalculatedPriceInfo/CalculatedPrice &gt; 0 and CalculatedPriceInfo/CalculationStatus/Code != 'Error'">
														<!-- If special offers exist, then the old price must be crossed out! -->
														<xsl:if test="CalculatedPriceInfo/CalculatedPrice &lt; CalculatedPriceInfo/BasicCalculatedPrice">
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
													<xsl:if test="CalculatedPriceInfo/CalculationStatus/Code = 'Error'">
														<p class="calculationErrorDescription">
															<xsl:value-of select="CalculatedPriceInfo/CalculationStatus/Description"/>
														</p>
													</xsl:if>
												</xsl:if>

												<!-- If there's an error message display it -->
												<xsl:if test="$errorMessage!=''">
													<div class="alert-message-container">
														<xsl:if test="$BootstrapCSSClass='col-md-7'">
															<xsl:attribute name="style">
																<xsl:text>padding-right:45px;</xsl:text>
															</xsl:attribute>
														</xsl:if>
														<xsl:choose>
															<xsl:when test="$errorType = 'notAvailable'">
																<a class="button medium secondary" href="#dateSelection">
																	<span>
																		<xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='holidayHomeIsNotAvailable']/value" />
																	</span>
																</a>
															</xsl:when>
															<xsl:otherwise>
																<span>
																	<xsl:value-of select="$errorMessage" />
																</span>
															</xsl:otherwise>
														</xsl:choose>
													</div>
												</xsl:if>
											</div>

										</div>

										<xsl:if test="$BootstrapCSSClass='col-md-4'">
											<div class="col-md-3 text-right book-button-holder">
												<xsl:variable name="bookingAddress">
													<xsl:if test="true() != $setDirectLink">
														<xsl:value-of select="$bookingAddressURLZaRezervaciju"/>
														<xsl:text>?languageID=</xsl:text>
														<xsl:value-of select="$languageID" />
														<xsl:text>&amp;booking=true</xsl:text>
														<xsl:text>&amp;bookingAddress=</xsl:text>
														<xsl:call-template name="string-replace-all">
															<xsl:with-param name="text">
																<xsl:call-template name="string-replace-all">
																	<xsl:with-param name="text"
																					select="BookingAddress" />
																	<xsl:with-param name="replace"
																					select="'&amp;'" />
																	<xsl:with-param name="by"
																					select="'%26'" />
																</xsl:call-template>
															</xsl:with-param>
															<xsl:with-param name="replace"
																			select="'?'" />
															<xsl:with-param name="by"
																			select="'%3f'" />
														</xsl:call-template>
													</xsl:if>
													<xsl:if test="true() = $setDirectLink">
														<xsl:value-of select="BookingAddress" />
														<xsl:text>&amp;booking=true</xsl:text>
													</xsl:if>
												</xsl:variable>
												<!-- Show Booking address link only if there's no error -->

												<xsl:if test="$errorType='' and CalculatedPriceInfo/CalculationStatus/Code != 'Error'">
													<!-- Booking addres link - address defined above! -->
													<xsl:choose>
														<xsl:when test="AvailabilityStatus = 'OnRequest'">
															<a class="button medium"
															  href="{$bookingAddress}"
															  target="_blank" rel="noopener">
																<span>
																	<xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='sendInquiry']/value" />
																</span>
															</a>
														</xsl:when>
														<xsl:when test="AvailabilityStatus = 'notAvailable'">
															<a class="button medium"
																href="{$bookingAddress}"
																target="_blank" rel="noopener">
																<span>
																	<xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='book']/value" />
																</span>
															</a>
														</xsl:when>
													</xsl:choose>
												</xsl:if>
												<xsl:if test="string-length(substring-before($errorType , 'notAvailable')) != 0">
													<a class="button medium secondary" href="#dateSelection">
														<span>
															<xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='holidayHomeIsNotAvailable']/value" />
														</span>
													</a>
												</xsl:if>
											</div>
										</xsl:if>
									</div>
								</div>
							</div>
						</div>
					</article>
				</xsl:for-each>

				<!--Single Unit END-->
			</div>
			<!--LIST OF UNITS END-->
		</section>
		<!--VILLAS UNITS END -->
		<!--************************-->

		<!-- date format is YYYY-mm-ddThh:MM:ss, JavaScript Date constructor needs new Date(YYYY, mm, dd) -->
		<!-- additional complication: days are in rage 1-31, but months are 0-11 -->

		<xsl:variable name="StartDate"
							select="/*/StartDate" />
		<input type="hidden"
				id="startDateConstructorString"
				value="{$StartDate}" />

		<xsl:variable name="EndDate"
						select="/*/EndDate" />
		<input type="hidden"
				id="endDateConstructorString"
				value="{$EndDate}" />

		<input type="hidden"
				id ="childrenAgesHiddenField"
				value="{$childrenAgesVar}"></input>

		<input type="hidden"
				id="showChildrenAgesHiddenField"
				value="{$showChildrenAgesVar}"></input>

		<xsl:variable name="objectIDReservationsTab"
						select="/*/AccommodationObject/ObjectID" />
		<xsl:variable name="currencyIDReservationsTab"
						select="/*/Currency/CurrencyID" />
		<xsl:variable name="languageIDReservationsTab"
						select="/*/Language/LanguageID" />
		<xsl:variable name="priceTypeReservationsTab"
						select="/*/PriceType" />

		<input type="hidden"
				id="objectIDReservationsTab"
				value="{$objectIDReservationsTab}" />
		<input type="hidden"
				id="currencyIDReservationsTab"
				value="{$currencyIDReservationsTab}" />
		<input type="hidden"
				id="languageIDReservationsTab"
				value="{$languageIDReservationsTab}" />
		<input type="hidden"
				id="priceTypeReservationsTab"
				value="{$priceTypeReservationsTab}" />
		<input type="hidden"
				id="bookingAddressDisplayURLHidden"
				value="{$bookingAddressURLZaRezervaciju}" />
		<input type="hidden"
				id="objectCodeHidden"
				value="{/*/AccommodationObject/ObjectCode}" />
		<input type="hidden"
				id="postaviDirektanLinkHidden"
				value="{$setDirectLink}" />
		<input type="hidden"
				id="unitActivityStatusHidden"
				value="{$unitActivityStatusVar}" />
		<input type="hidden"
				id="bookingLinkInNewWindowHidden"
				value="{$BookingLinkInNewWindowVar}" />

		<!-- Do NOT move into wp enqueues as this whole XSLT is reloaded with jQuery (replaced in place) -->
		<script defer="defer" src="/wp-content/themes/localliving/js/update-unit-list.js"> </script>
		<script>
			if(window.LazyLoad){
				var myLazyLoad = new LazyLoad({
					elements_selector: ".lazy"
				});		
			}
	</script>
	</xsl:template>

	<!-- XSL Template to draw a option elements of -number of persons drop down list -->
	<xsl:template name="numberOfPersonsDropDownListElements">
		<xsl:param name="maximumCapacity"
					select="1"/>
		<xsl:param name="selectedValue"
					select="1"/>
		<xsl:param name="minimumValue"
					select="1"></xsl:param>

		<xsl:if test="$maximumCapacity >= $minimumValue">
			<xsl:call-template name="numberOfPersonsDropDownListElements">
				<xsl:with-param name="maximumCapacity"
								select="$maximumCapacity - 1" />
				<xsl:with-param name="selectedValue"
								select="$selectedValue" />
				<xsl:with-param name="minimumValue"
								select="$minimumValue"></xsl:with-param>
			</xsl:call-template>

			<xsl:choose>
				<xsl:when test="$maximumCapacity=$selectedValue">
					<option value="{$maximumCapacity}"
							selected="selected">
						<xsl:value-of select="$maximumCapacity" />
					</option>
				</xsl:when>
				<xsl:when test="$maximumCapacity!=$selectedValue">
					<option value="{$maximumCapacity}">
						<xsl:value-of select="$maximumCapacity" />
					</option>
				</xsl:when>
			</xsl:choose>
		</xsl:if>
	</xsl:template>

	<!--template koji funkcionira kao .Split() funkcija
		pocinje od pozicije 1 (a ne od 0 !!!)-->
	<xsl:template name="split-by">
		<xsl:param name="list" />
		<xsl:param name="delimiter" />
		<xsl:param name="position" />
		<xsl:param name="currentPosition" select="1" />

		<xsl:variable name="first1"
						select="substring-before($list, $delimiter)" />

		<xsl:variable name="first">
			<xsl:if test="contains($list, $delimiter)">
				<xsl:value-of select="substring-before($list, $delimiter)"/>
			</xsl:if>
			<xsl:if test="not(contains($list, $delimiter))">
				<xsl:value-of select="$list"/>
			</xsl:if>
		</xsl:variable>
		<xsl:variable name="remaining"
						select="substring-after($list, $delimiter)" />
		<xsl:choose>
			<xsl:when test="$currentPosition = $position">
				<xsl:value-of select="$first" />
			</xsl:when>
			<xsl:otherwise>
				<xsl:if test="string-length($remaining) &gt; 0">
					<xsl:call-template name="split-by">
						<xsl:with-param name="list"
										select="$remaining" />
						<xsl:with-param name="delimiter"
										select="$delimiter" />
						<xsl:with-param name="position"
										select="$position" />
						<xsl:with-param name="currentPosition"
										select="$currentPosition + 1" />
					</xsl:call-template>
				</xsl:if>
				<xsl:if test="string-length($remaining) = 0">
					<xsl:value-of select="''" />
				</xsl:if>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

</xsl:stylesheet>
