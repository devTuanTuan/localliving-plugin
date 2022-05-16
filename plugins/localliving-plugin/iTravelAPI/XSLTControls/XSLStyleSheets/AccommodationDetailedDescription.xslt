<xsl:stylesheet version="1.0"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:msxsl="urn:schemas-microsoft-com:xslt"
				exclude-result-prefixes="msxsl">
  <xsl:output method="html"
			  indent="yes"
			  cdata-section-elements="" />

  <xsl:include href="CommonFunctions.xslt"/>
  <xsl:include href="AccommodationScrollingPriceList.xslt"/>
  <xsl:include href="AccommodationUnitList.xslt"/>

  <!--PARAMETERS-->
  <xsl:param name="tabToSelectParameter" />
  <xsl:param name="postaviDirektanLink" />
  <xsl:param name="unitActivityStatus" />
  <xsl:param name="BookingLinkInNewWindow" />

  <!-- ******************************************* -->
  <!--              Global variables               -->
  <!-- ******************************************* -->
  <xsl:variable name="languageID" select="/*/Language/LanguageID" />
  <xsl:variable name="accommodationObjectName" select="/*/AccommodationObject/Name"/>
  <xsl:variable name="currencyID" select="/*/Currency/CurrencyID"/>
  <xsl:variable name="currencyShortName" select="/*/Currency/CurrencyShortName"/>
  <xsl:variable name="dateFormat" select="'dd.mm.yyyy'" />


  <!--dodajem globalni parametar zbog toga jer kada saljem parametar iz koda ne mogu ga dodjeliti odredjenom template-u-->
  <!--kasnije u template-u cu provjeriti koji parametar (globalni ili onaj iz template-a) je popunjen i njega koristim-->
  <!-- ***************************************************************************************************-->
  <!-- OVI GLOBALNI PARAMETRI KORISTE SE ZA POZIV TEMPLATEA AKO GA SE ZOVE IZ PROXYA ILI OPĆENITO C# KODA -->
  <!-- ***************************************************************************************************-->
  <xsl:param name="bookingAddressDisplayURLReservationGlobal" />
  <xsl:param name="setDirectLinkGlobal" />
  <xsl:param name="BookingLinkInNewWindowGlobal" />

  <!-- ************************************************************************************************-->
  <!-- OVI LOKALNI PARAMETRI KORISTE SE ZA POZIV TEMPLATEA AKO GA SE ZOVE IZ DRUGOG XSLT-a -->
  <!-- ************************************************************************************************-->
  <xsl:param name="bookingAddressDisplayURLReservation" />
  <xsl:param name="setDirectLinkInternal" />
  <xsl:param name="BookingLinkInNewWindowTab" />

  <xsl:param name="bookingAddressDisplayURL" />

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

  <!--******************************************************************************************************************-->
  <!-- Na kraju će iz uvodnog dijela koda izići nekoliko varijabli kao zbroj / presjek lokalnih i globalnih parametara! -->
  <!-- $bookingAddresZaRezervaciju -> Booking adresa na koju vodi "Book"/"Send Inquiry" botun                           -->
  <!-- $bookingLinkInNewWindowVar -> boolean koji govori treba li otvoriti link u novom prozoru.                        -->
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
  <xsl:variable name="setDirectLink"	select="'true' = $setDirectLinkTemp" />
  <xsl:variable name="AccommodationName" select="/*/AccommodationObject/AttributeGroupList/AttributeGroup/AttributeList/Attribute[AttributeID=121]/AttributeValue" />
  <xsl:variable name="numberOfPersons" select="/*/NumberOfPersons" />
  <xsl:variable name="priceType" select="/*/PriceType" />
  <xsl:variable name="numberOfDays" select="/*/NumberOfDays" />
  <xsl:variable name="availabilityAddress"	select="'/itravel/PregledZauzetosti.aspx'" />
  <xsl:variable name="XCoordinate" select="/*/AccommodationObject/AttributeGroupList/AttributeGroup[GroupID=95]/AttributeList/Attribute[AttributeID=290]/AttributeValue" />
  <xsl:variable name="YCoordinate" select="/*/AccommodationObject/AttributeGroupList/AttributeGroup[GroupID=95]/AttributeList/Attribute[AttributeID=291]/AttributeValue" />

  <xsl:template match="@* | node()">
	 <xsl:copy>
		<xsl:apply-templates select="@* | node()"/>
	 </xsl:copy>
  </xsl:template>

  <xsl:template match="/">


	<xsl:if test="count(/*/AccommodationObject/UnitList/AccommodationUnit) = 0">

		<div class="container" style="padding:80px 0 60px 0;">
			<section class="error-404 not-found">
				<header class="page-header">
					<h1 class="page-title">
						<xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='noPropertyTitle']/value"/>
					</h1>
				</header>
				<!-- .page-header -->
				<div>
					<p><xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='noPropertySubTitle']/value"/></p>
					<a href="{/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='noPropertyURL']/value}">
						<xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='noPropertyURL']/value"/>
					</a>
				</div>

			</section>
		</div>
	  
	</xsl:if>
	  
	<xsl:if test="count(/*/AccommodationObject/UnitList/AccommodationUnit) > 0">
		<section class="no-hor-overlay villa-details-header clearfix">
			<div class="container">
			  <div class="row">
				 <div class="col-md-5 relative-z2">
					<div class="row">
					  <div class="col-md-12 villa-details-header-title">
						 <div class="villa-details-star-fav">
							<!--Object category-->
							<div class="stars" data-hover-show=".stars-description">

							  <xsl:variable name="NumberOfStars" select="/*/AccommodationObject/AttributeGroupList/AttributeGroup/AttributeList/Attribute[AttributeID=970]/AttributeValue" />
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
							<xsl:variable name="objectID" select="/*/AccommodationObject/ObjectID"/>
							<!-- data-wlist-id attribute must be ObjectID css class wl_button_ must have Ibject ID at the end, example: wl_button_53 -->
							<a href="#" class="add-to-wishlist transition wl_button_{$objectID}" data-wlist-id="{$objectID}">
							</a>
						 </div>
						 <div class="stars-description-warp">
							<div class="stars-description">
							  <!-- Category description text, to be placed inside resources-->
							  <xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='categoryDescription']/value"/>
							</div>
						 </div>
						 <!--Object name-->
						 <h1>
							<xsl:value-of select="$accommodationObjectName"/>
						 </h1>
						 <!--Object type - Region name -->
						 <p>
							<xsl:value-of select="/*/AccommodationObject/ObjectType/ObjectTypeName" />
							<xsl:text> - </xsl:text>
							<xsl:value-of select="/*/Region/RegionName" />
						 </p>
					  </div>
					</div>
					<div class="row">
					  <div class="col-md-12 heading">
							<div class="villa-details-header-facts">
								<!--This is Note field in the iTravel named Fact box -->
								<ul class="villa-details-header-list fact-box-note">
									<xsl:value-of select="/*/AccommodationObject/NoteList/Note[NoteTitle='Fact box']/NoteText" disable-output-escaping="yes"/>
								</ul>
								<!--End note-->
							</div>
						 <!--List of distances -->
						 <ul class="villa-details-header-distances">
							<xsl:for-each select="/*/AccommodationObject/AttributeGroupList/AttributeGroup[GroupID=26]/AttributeList/Attribute">
							  <xsl:sort data-type="number"  order="ascending" select="AttributeOriginalValue"/>
							  <li>
								 <span class="list-label">
									<xsl:value-of select="AttributeName"/>
								 </span>
								 <xsl:value-of select="AttributeValue"/>
							  </li>
							</xsl:for-each>
						 </ul>
					  </div>
					</div>
					<div class="villa-details-header-image hidden-xs">
					  <!--First object image-->
					  <a href="{/*/AccommodationObject/PhotoList/Photo[1]/PhotoUrl}">
						 <img class="lazy" data-src="{/*/AccommodationObject/PhotoList/Photo[1]/PhotoUrl}" alt="{/*/AccommodationObject/PhotoList/Photo[1]/AlternateText}" />
					  </a>
					</div>
				 </div>
			  </div>
			</div>
		 </section>

		 <div class="no-hor-overlay">
			<div class="container watermark">
			  <div class="row">
				 <div class="col-md-5 villa-details-call-for-info hidden-xs">
					<!--Static text placed inside resources-->
					<h2 class="heading-caps">
					  <xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='callForMoreInfo']/value"/>
					</h2>
					<p class="heading-caps">
					  <xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='callForMoreInfoPhoneNumber']/value"/>
					</p>
				 </div>
				 <div class="col-md-7 villa-details-gallery-col">
					<div class="row">
					  <div class="col-md-12">
						 <div class="villa-details-gallery">
							<xsl:for-each select="/*/AccommodationObject/PhotoList/Photo">
							  <div>
								 <a href="{PhotoUrl}">
									<img class="lazy" data-src="{ThumbnailUrl}" alt="{AlternateText}" />
								 </a>
							  </div>
							</xsl:for-each>
						 </div>
					  </div>
					</div>
				 </div>
			  </div>

			  <div class="row">
				 <div class="col-md-5">
					<!--Object map-->
					<div class="row">
					  <div class="col-md-12 villa-details-map-warp">
						 <div class="villa-details-map" id="detailsMap">
						 </div>
						 <script defer="defer" src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBTXW7m7qnmxGdIvDHjyu9QBcsV1ljLYzQ">

						 </script>
						 <xsl:variable name="googleMapIframeUrl">
							<xsl:call-template name="string-replace-all">
							  <xsl:with-param name="text" select="/*/AccommodationObject/AttributeGroupList/AttributeGroup/AttributeList/Attribute[AttributeID=839]/AttributeValue" />
							  <xsl:with-param name="replace" select="'&amp;amp;'" />
							  <xsl:with-param name="by" select="'&amp;'" />
							</xsl:call-template>
						 </xsl:variable>
						 <xsl:variable name="googleNewWindowUrl">
							<xsl:call-template name="string-replace-all">
							  <xsl:with-param name="text" select="$googleMapIframeUrl" />
							  <xsl:with-param name="replace" select="'=embed'" />
							  <xsl:with-param name="by" select="'='" />
							</xsl:call-template>

						 </xsl:variable>
							
						 <script defer="defer" src="/wp-content/themes/localliving/js/detailed-description-google-map.js">
							 <xsl:comment></xsl:comment>
						 </script>
							<span  id="detailed-description-google-map" data-url="{$googleNewWindowUrl}" data-x-coordinate="{$XCoordinate}" data-y-coordinate="{$YCoordinate}" data-object-name="{$accommodationObjectName}">
								<xsl:comment></xsl:comment>
							</span>
						  <xsl:if test="$googleNewWindowUrl !=''">
							  <div class="text-right enlarge-map" style="margin:307px 45px 0 0">
									<a href="{$googleNewWindowUrl}" target="_blank" rel="external">
										<xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='enlargeMap']/value"/>
									</a>
								</div>
						  </xsl:if>
					  </div>
					</div>
					<!--Object map End-->

					<!--Object Testimonials, from object notes Note name is 'Testimonial' the name checking is case NOT case sensitive -->
					<div class="row hidden-xs">
					  <section class="col-md-12">
						 <div id="villaTestimonials" class="carousel slide" data-ride="carousel">
							<!-- Wrapper for slides -->
							<div class="carousel-inner" role="listbox">
							  <!--note template (first item must have additional 'active' css class-->
							  <xsl:for-each select="/*/AccommodationObject/NoteList/Note[contains(translate(NoteTitle, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'), 'testimonial')=true()]">
								 <xsl:sort data-type="number" order="descending" select="number(substring-after(NoteTitle, ' '))"/>
								 <xsl:if test="position() &lt; 11">
									<xsl:variable name="Active">
									  <xsl:if test="position()=1">
										 <xsl:value-of select="'active'"/>
									  </xsl:if>
									  <xsl:if test="position()!=1">
										 <xsl:value-of select="''"/>
									  </xsl:if>
									</xsl:variable>
									<article class="item {$Active}">
									  <div>
										 <h1>
											<xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='guestsSay']/value"/>
										 </h1>
										 <p>
											<xsl:value-of select="NoteText" disable-output-escaping="yes"/>
										 </p>
									  </div>
									</article>
								 </xsl:if>

							  </xsl:for-each>
							  <!--Note template end-->
							</div>

							<!-- Indicators list-->
							<ol class="carousel-indicators">
							  <xsl:for-each select="/*/AccommodationObject/NoteList/Note[contains(translate(NoteTitle, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'), 'testimonial')=true()]">
								 <xsl:if test="position() &lt; 11">
									<xsl:variable name="Active">
									  <xsl:if test="position()=1">
										 <xsl:value-of select="'active'"/>
									  </xsl:if>
									  <xsl:if test="position()!=1">
										 <xsl:value-of select="''"/>
									  </xsl:if>
									</xsl:variable>
									<li data-target="#villaTestimonials" data-slide-to="{position()-1}" class="{$Active}"></li>
								 </xsl:if>
							  </xsl:for-each>
							</ol>
						 </div>
					  </section>
					</div>
					<!--Object Testimonials END-->
				 </div>
				 <div class="col-md-7">
					<!--
					  GENNEMFORT IDYL
					  Static text. This text will be written in the iTravel, in the new Note field. Sometimes, Ll will change this text in the system. -->
					<h2 class="green-title overlined">
					  <xsl:value-of select="/*/AccommodationObject/NoteList/Note[NoteTitle='Accommodation title']/NoteText" disable-output-escaping="yes"/>
					</h2>
					<xsl:value-of select="/*/AccommodationObject/Description" disable-output-escaping="yes"/>
					<!--GENNEMFORT IDYL End-->
				 </div>
			  </div>


			  <!--VILLA ATTRIBUTES-->
			  <!--FACILITETER AND AKTIVITETER - This part will be done the same as on the current site. This info is taken from the characteristic/attributes part in iTravel. It is a list of available properties for a specific accommodation.-->
			  <section class="row villa-details-attributes">
				 <!-- attribute group-->
				 <div class="col-md-3">
					<h2 class="green-title">
					  <xsl:value-of select="/*/AccommodationObject/AttributeGroupList/AttributeGroup[GroupID=1132]/GroupName"/>
					</h2>
					<ul>
					  <xsl:for-each select="/*/AccommodationObject/AttributeGroupList/AttributeGroup[GroupID=1132]/AttributeList/Attribute">
						 <li>
							<xsl:value-of select="AttributeName"/>
						 </li>
					  </xsl:for-each>
					</ul>
				 </div>
				 <!-- attribute group END-->

				 <!-- attribute group-->
				 <div class="col-md-3">
					<h2 class="green-title">
					  <xsl:value-of select="/*/AccommodationObject/AttributeGroupList/AttributeGroup[GroupID=1133]/GroupName"/>
					</h2>
					<ul>
					  <xsl:for-each select="/*/AccommodationObject/AttributeGroupList/AttributeGroup[GroupID=1133]/AttributeList/Attribute">
						 <li>
							<xsl:value-of select="AttributeName"/>
						 </li>
					  </xsl:for-each>
					</ul>
				 </div>
				 <!-- attribute group END-->

				 <!-- attribute group-->
				 <div class="col-md-3">
					<h2 class="green-title">
					  <xsl:value-of select="/*/AccommodationObject/AttributeGroupList/AttributeGroup[GroupID=1134]/GroupName"/>
					</h2>
					<ul>
					  <xsl:for-each select="/*/AccommodationObject/AttributeGroupList/AttributeGroup[GroupID=1134]/AttributeList/Attribute">
						 <li>
							<xsl:value-of select="AttributeName"/>
						 </li>
					  </xsl:for-each>
					</ul>
				 </div>
				 <!-- attribute group END-->

				 <!-- attribute group-->
				 <div class="col-md-3">
					<h2 class="green-title">
					  <xsl:value-of select="/*/AccommodationObject/AttributeGroupList/AttributeGroup[GroupID=1135]/GroupName"/>
					</h2>
					<ul>
					  <xsl:for-each select="/*/AccommodationObject/AttributeGroupList/AttributeGroup[GroupID=1135]/AttributeList/Attribute">
						 <li>
							<xsl:value-of select="AttributeName"/>
						 </li>
					  </xsl:for-each>
					</ul>
				 </div>
				 <!-- attribute group END-->
			  </section>
			  <!--VILLA ATTRIBUTES END-->

			  <!--VILLAS UNIT LIST begin Napomena: inpute koji postoje u rezervacijskim tabu a ne korste se u trenutnom dizajnu, treba staviti u neki skriveni div a ne obrisati ih (da skripte ne pucaju)-->
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

			  <!--VILLAS UNIT LIST END -->
			
			 <!--AVAILABILTY CALENDAR-->
			  <section class="villa-details-availability row">
				 <div class="col-md-12">
					<h2 class="green-title overlined">
					  <xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='availableWeeks']/value"/>
					</h2>
					<div class="villa-details-availability-calendar clearfix">
					  <div class="villa-details-availability-calendar-names">
						 <!--Will be populated by javascript-->
						 <h3 id="calendarVisibleYear">

						 </h3>
						 <!--List of unit names-->
						 <ul>
							<xsl:for-each select="/*/AccommodationObject/UnitList/AccommodationUnit">
							  <li>
								 <xsl:value-of select="AttributeGroupList/AttributeGroup/AttributeList/Attribute[AttributeID=133]/AttributeValue"/>
							  </li>
							</xsl:for-each>
						 </ul>
					  </div>
					  <div class="villa-details-availability-calendar-periods">

						 <!--Dynamic content goes here-->
					  </div>
					</div>
				 </div>
				 <!--From object details description we must populate window.uaDates variable
							List must contain all units for the accommodation object
							unitID variable is the UnitID of the unit
							unavailabilityDates is list of unavailability dates, if none, empty string
							Rest of the logic is defined inside theme/js/detailed-desription.js file
							Script requires theme/js/availability-calendar.js script (already included iin functions.php)

							window.callendarResources varibale contains localization resources used in calendar
							 -->
				 <script>
					window.uaDates = [
					<xsl:for-each select="/*/AccommodationObject/UnitList/AccommodationUnit">
					  <xsl:variable name="unitID" select="UnitID"/>
					  <xsl:variable name="unavailabilityDates">
						 <xsl:for-each select="UnavailableDates/dateTime">
							<xsl:value-of select="."/>
							<xsl:if test="position()!=last()">
							  <xsl:text>,</xsl:text>
							</xsl:if>
						 </xsl:for-each>
					  </xsl:variable>
					  <xsl:variable name="bookingAddress">
						 <xsl:if test="true() != $setDirectLink">
							<xsl:value-of select="$bookingAddressDisplayURL"/>
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
					  <xsl:text>{unitID:</xsl:text>
					  <xsl:value-of select="$unitID"/>
					  <xsl:text>,unavailabilityDates:"</xsl:text>
					  <xsl:value-of select="$unavailabilityDates"/>
					  <xsl:text>", bookingAddressLink:"</xsl:text>
					  <xsl:value-of select="$bookingAddress"/>
					  <xsl:text>"}</xsl:text>
					  <xsl:if test="position()!=last()">
						 <xsl:text>,</xsl:text>
					  </xsl:if>
					</xsl:for-each>
					];
					window.callendarResources = {
					week: "<xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='week']/value"/>",
					occupied: "<xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='notAvailable']/value"/>",
					free: "<xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='book']/value"/>"
					};
				 </script>
				 <!--Undefined for now-->
			  </section>
			  <!--AVAILABILTY CALENDAR END-->

			  <!--PRICE-LIST TABLE-->
			  <section class="row brown-stripe pricelist-warp">
				 <div class="col-md-12">
					<!--Napomena: 
								Provjeriti da li ona ima suplements i discounts tablice, ako nema izbaciti ih
								Postojeće H1 naslove zamijeniti sa ovakvim-->
					<h2 class="green-title overlined">
						<xsl:choose>
							<xsl:when test="/*/AccommodationObject/ObjectType/ObjectTypeID != 3">
								<xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='priceListForWholeVilla']/value"/>
							</xsl:when>
							<xsl:otherwise>
								<xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='priceListForWholeApartment']/value"/>
							</xsl:otherwise>
						</xsl:choose>

					</h2>
         
			<!-- Output price list short stay note if exists -->
			 <xsl:for-each select="/*/AccommodationObject/NoteList/Note">
					  <xsl:variable name="lowercaseNoteTitle" select="translate(NoteTitle, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')" />
					  <xsl:if test="$lowercaseNoteTitle = 'short stay'">
				<div class="price-list-note">
				  <xsl:value-of select="NoteText" disable-output-escaping="yes" />
				</div>
					  </xsl:if>
					</xsl:for-each>
         
					<div style="width:100%; overflow:auto">
						<xsl:call-template name="villasScrollingPriceListTemplate">
						</xsl:call-template>
					</div>
				 </div>
			  </section>
			  <!--PRICE-LIST TABLE END-->

			  <!--NOTES-->
			  <section class="row">
				 <div class="col-md-12">
					<!--Ispis napomena, ne ispisuju se sve, vidjeti logiku na njenom postojećem siteu -->
					<xsl:for-each select="/*/AccommodationObject/NoteList/Note">
					  <xsl:variable name="lowercaseNoteTitle" select="translate(NoteTitle, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')" />
					  <xsl:if test="contains($lowercaseNoteTitle, 'testimonial') = false() and contains($lowercaseNoteTitle, 'fact box') = false() and contains($lowercaseNoteTitle, 'short stay') = false()">
						 <xsl:if test="not (contains($lowercaseNoteTitle, 'driving')) and not (contains($lowercaseNoteTitle, 'accommodation title'))">
							<!-- Note value -->
							 <br/>
							<br/>
							<xsl:value-of select="NoteText" disable-output-escaping="yes"/>
						
						 </xsl:if>
					  </xsl:if>
					</xsl:for-each>
				 </div>
			  </section>
			  <!--NOTES END-->
			</div>
		 </div>  
	 
	</xsl:if>

	 

  </xsl:template>
</xsl:stylesheet>
