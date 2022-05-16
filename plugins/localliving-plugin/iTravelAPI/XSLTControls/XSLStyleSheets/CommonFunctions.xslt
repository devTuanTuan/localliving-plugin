<xsl:stylesheet version="1.0"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:msxsl="urn:schemas-microsoft-com:xslt"
				exclude-result-prefixes="msxsl"
>
  <xsl:output method="xml" indent="yes" cdata-section-elements="" />


  <xsl:param name="imagesFolderPath" />
  <xsl:param name="proxyPath" />
  <xsl:param name="scriptsFolderPath" />
  <xsl:param name="contactFormTemplateURL" />
  <xsl:variable name="SQ">'</xsl:variable>
  <xsl:template match="@* | node()">
	 <xsl:copy>
		<xsl:apply-templates select="@* | node()"/>
	 </xsl:copy>
  </xsl:template>

  <!-- Number formatting -->
  <xsl:decimal-format name="fr" decimal-separator="," grouping-separator=" " />
  <xsl:decimal-format name="eu" decimal-separator="," grouping-separator="." />
  <xsl:decimal-format name="us" decimal-separator="." grouping-separator="," />

  <xsl:variable name="decimalSeparator" select="/*/PriceFormat/DecimalSeparator" />
  <xsl:variable name="groupSeparator" select="/*/PriceFormat/GroupSeparator" />

  <xsl:variable name="locale">
	 <xsl:choose>
		<xsl:when test="$decimalSeparator=',' and $groupSeparator=' '">fr</xsl:when>
		<xsl:when test="$decimalSeparator=',' and $groupSeparator='.'">eu</xsl:when>
		<xsl:otherwise>us</xsl:otherwise>
	 </xsl:choose>
  </xsl:variable>

  <xsl:variable name="numberFormat">
	 <xsl:choose>
		<xsl:when test="$locale='fr'">### ### ##0,00</xsl:when>
		<xsl:when test="$locale='eu'">###.###.##0,00</xsl:when>
		<xsl:otherwise>###,###,##0.00</xsl:otherwise>
	 </xsl:choose>
  </xsl:variable>

  <!-- 
  to format number use following syntax: 
  <xsl:value-of select="format-number(13456789.87,$numberFormat,$locale)" />
  -->
  <!-- End of number formatting -->
  <!-- XSL Template to draw a custom amount of stars, each having altText equal to the object name -->
  <xsl:template name="stars">
	 <xsl:param name="count" />
	 <xsl:param name="alternativeText" />
	 <span class="stars">
		<xsl:comment></xsl:comment>
		<xsl:if test="$count > 0">
		  <img alt="{$alternativeText}"
					  width="15"
					  height="14">
			 <xsl:attribute name="src">
				<xsl:value-of select="concat($imagesFolderPath, '/star.gif')" />
			 </xsl:attribute>
		  </img>
		  <xsl:call-template name="stars">
			 <xsl:with-param name="count"
                  select="$count - 1" />
		  </xsl:call-template>
		</xsl:if>
	 </span>
  </xsl:template>

  <!--Template for generating number of stars images-->
  <xsl:template name="number-of-stars">
	 <xsl:param name="count" />
	 <xsl:if test="$count > 0">
		<span class="star">
		  <xsl:comment></xsl:comment>
		</span>
		<xsl:call-template name="number-of-stars">
		  <xsl:with-param name="count" select="$count - 1" />
		</xsl:call-template>
	 </xsl:if>
  </xsl:template>

  <!-- XSL Template to return the translation of the service type -->
  <xsl:template name="translatePriceType">
	 <xsl:param name="priceType" />
	 <xsl:param name="rootNode" />
	 <xsl:param name="languageToTranslateID" />

	 <!-- Reduce the first letter and find in resources -->
	 <xsl:for-each select="$rootNode">
		<xsl:value-of select="TranslationList/Translation[LanguageID=$languageToTranslateID]/root/data[@name=concat(translate(substring($priceType, 1, 1), 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), substring($priceType, 2))]/value"/>
	 </xsl:for-each>
  </xsl:template>

  <!-- XSL Template used to format the date to a desired format -->
  <!-- input format: YYYY-MM-DDTHH:MM:SS -->
  <xsl:template name="formatDate">
	 <xsl:param name="date" />
	 <xsl:param name="format" />
	 <xsl:variable name="dd"
					  select="substring($date, 9, 2)"/>
	 <xsl:variable name="mm"
					  select="substring($date, 6, 2)"/>
	 <xsl:variable name="yy"
					  select="substring($date, 3, 2)"/>
	 <xsl:variable name="yyyy"
					  select="substring($date, 1, 4)"/>

	 <xsl:variable name="upperDateFormat"
					  select="translate($format, 'abcdefghijklmnopqrstuvwxyz','ABCDEFGHIJKLMNOPQRSTUVWXYZ' )" />

	 <!-- Many replace functions -->
	 <!--<xsl:value-of select="translate(translate(translate(translate($upperDateFormat, 'DD', $dd), 'MM', $mm ), 'YYYY', $yyyy), 'YY', $yy)"/>-->
	 <xsl:call-template name="string-replace-all">
		<xsl:with-param name="text">
		  <xsl:call-template name="string-replace-all">
			 <xsl:with-param name="text">
				<xsl:call-template name="string-replace-all">
				  <xsl:with-param name="text">
					 <xsl:call-template name="string-replace-all">
						<xsl:with-param name="text"
													select="$upperDateFormat" />
						<xsl:with-param name="replace"
													select="'DD'"/>
						<xsl:with-param name="by"
													select="$dd" />
					 </xsl:call-template>
				  </xsl:with-param>
				  <xsl:with-param name="replace"
											select="'MM'"/>
				  <xsl:with-param name="by"
											select="$mm" />
				</xsl:call-template>
			 </xsl:with-param>
			 <xsl:with-param name="replace"
									select="'YYYY'"/>
			 <xsl:with-param name="by"
									select="$yyyy" />
		  </xsl:call-template>
		</xsl:with-param>
		<xsl:with-param name="replace"
							select="'YY'"/>
		<xsl:with-param name="by"
							select="$yy" />
	 </xsl:call-template>

  </xsl:template>

  <!-- XSLT Template used like the StringReplace function-->
  <xsl:template name="string-replace-all">
	 <xsl:param name="text"/>
	 <xsl:param name="replace"/>
	 <xsl:param name="by"/>
	 <xsl:choose>
		<xsl:when test="contains($text,$replace)">
		  <xsl:value-of select="substring-before($text,$replace)"/>
		  <xsl:value-of select="$by"/>
		  <xsl:call-template name="string-replace-all">
			 <xsl:with-param name="text"
									select="substring-after($text,$replace)"/>
			 <xsl:with-param name="replace"
									select="$replace"/>
			 <xsl:with-param name="by"
									select="$by"/>
		  </xsl:call-template>
		</xsl:when>
		<xsl:otherwise>
		  <xsl:value-of select="$text"/>
		</xsl:otherwise>
	 </xsl:choose>
  </xsl:template>

  <!-- XSLT Template used for HTML encoding -->
  <!-- declaration of necessary vars -->
  <!--
		Characters we'll support.
       We could add control chars 0-31 and 127-159, but we won't. 
	-->
  <xsl:variable name="ascii">
	 !"#$%&amp;'()*+,-./0123456789:;&lt;=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\]^_`abcdefghijklmnopqrstuvwxyz{|}~
  </xsl:variable>
  <xsl:variable name="latin1">
	 ¡¢£¤¥¦§¨©ª«¬­®¯°±²³´µ¶·¸¹º»¼½¾¿ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõö÷øùúûüýþÿ
  </xsl:variable>
  <!-- Characters that usually don't need to be escaped -->
  <xsl:variable name="safe">
	 !'()*-.0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz~
  </xsl:variable>
  <xsl:variable name="hex">0123456789ABCDEF</xsl:variable>

  <xsl:template name="url-encode">
	 <xsl:param name="str"/>
	 <xsl:if test="$str">
		<xsl:variable name="first-char"
						  select="substring($str,1,1)"/>
		<xsl:choose>
		  <xsl:when test="contains($safe,$first-char)">
			 <xsl:value-of select="$first-char"/>
		  </xsl:when>
		  <xsl:otherwise>
			 <xsl:variable name="codepoint">
				<xsl:choose>
				  <xsl:when test="contains($ascii,$first-char)">
					 <xsl:value-of select="string-length(substring-before($ascii,$first-char)) + 32"/>
				  </xsl:when>
				  <xsl:when test="contains($latin1,$first-char)">
					 <xsl:value-of select="string-length(substring-before($latin1,$first-char)) + 160"/>
				  </xsl:when>
				  <xsl:otherwise>
					 <xsl:message terminate="no">
						Warning: string contains a character that is out of range! Substituting "?".
					 </xsl:message>
					 <xsl:text>63</xsl:text>
				  </xsl:otherwise>
				</xsl:choose>
			 </xsl:variable>
			 <xsl:variable name="hex-digit1"
								  select="substring($hex,floor($codepoint div 16) + 1,1)"/>
			 <xsl:variable name="hex-digit2"
								  select="substring($hex,$codepoint mod 16 + 1,1)"/>
			 <xsl:value-of select="concat('%',$hex-digit1,$hex-digit2)"/>
		  </xsl:otherwise>
		</xsl:choose>
		<xsl:if test="string-length($str) > 1">
		  <xsl:call-template name="url-encode">
			 <xsl:with-param name="str"
									select="substring($str,2)"/>
		  </xsl:call-template>
		</xsl:if>
	 </xsl:if>
  </xsl:template>

  <!--XSLT Template used to draw date from and date to datepickers-->
  <xsl:template name="date-pickers">
	 <xsl:param name="dateFromID" />
	 <xsl:param name="dateToID" />
	 <xsl:param name="dateFromIDHash" />
	 <xsl:param name="dateToIDHash" />
	 <div class="search-datum">
		<div class="search-datum-od-do">
		  <input type="text">
			 <xsl:attribute name="id">
				<xsl:value-of select="$dateFromID"/>
			 </xsl:attribute>
			 <xsl:attribute name="name">
				<xsl:value-of select="$dateFromID"/>
			 </xsl:attribute>
		  </input>
		  <img alt="" name="dateFromAccommodationPicker">
			 <xsl:attribute name="onclick">
				<xsl:value-of select="concat('jQuery(', $SQ)"/>
				<xsl:value-of select="concat('#', $dateFromID)"/>
				<xsl:value-of select="concat($SQ,').datepicker(')"/>
				<xsl:value-of select="concat($SQ,concat('show', $SQ))"/>
				<xsl:value-of select="');'"/>
			 </xsl:attribute>
			 <xsl:attribute name="src">
				<xsl:value-of select="concat($imagesFolderPath, '/date-picker.gif')" />
			 </xsl:attribute>
		  </img>
		</div>

		<div class="search-datum-od-do align-right">
		  <input type="text">
			 <xsl:attribute name="id">
				<xsl:value-of select="$dateToID"/>
			 </xsl:attribute>
			 <xsl:attribute name="name">
				<xsl:value-of select="$dateToID"/>
			 </xsl:attribute>
		  </input>
		  <img alt="" name="dateToAccommodationPicker">
			 <xsl:attribute name="onclick">
				<xsl:value-of select="concat('jQuery(', $SQ)"/>
				<xsl:value-of select="concat('#', $dateToID)"/>
				<xsl:value-of select="concat($SQ,').datepicker(')"/>
				<xsl:value-of select="concat($SQ,concat('show', $SQ))"/>
				<xsl:value-of select="');'"/>
			 </xsl:attribute>
			 <xsl:attribute name="src">
				<xsl:value-of select="concat($imagesFolderPath, '/date-picker.gif')" />
			 </xsl:attribute>
		  </img>
		</div>
	 </div>
  </xsl:template>

  <!--XSLT Template used to draw Category drop down list-->
  <xsl:template name="category-ddl">
	 <xsl:param name="languageID" />
	 <xsl:param name="irrelevantTranslation"/>
	 <xsl:param name="categoriesSelectID" />
	 <xsl:param name="categoriesSelectOnChangeMethod" />
	 <div class="search-usluga">
		<select>
		  <xsl:attribute name="id">
			 <xsl:value-of select="$categoriesSelectID"/>
		  </xsl:attribute>
		  <xsl:attribute name="onchange">
			 <xsl:value-of select="concat(concat(concat($categoriesSelectOnChangeMethod,'('), $proxyPath), ')')"/>
		  </xsl:attribute>
		  <option value="0">
			 <xsl:value-of select="/ApiSettings/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='category']/value"/>
			 <xsl:text>: </xsl:text>
			 <xsl:value-of select="$irrelevantTranslation"/>
		  </option>
		</select>
	 </div>
  </xsl:template>

  <!--XSLT Template used to draw number of persons drop down list-->
  <xsl:template name="number-of-persons-ddl">
	 <xsl:param name="languageID"/>
	 <xsl:param name="personsSelectID"/>
	 <xsl:param name="childrenSelectID" />
	 <div class="search-tip-smjestaja">
		<div class="search-tip-smjestaja-pola">
		  <div class="search-tip-smjestaja-label">
			 <xsl:value-of select="/ApiSettings/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='numberOfAdults']/value"/>
		  </div>
		  <div class="clear-both">
			 <xsl:comment></xsl:comment>
		  </div>
		  <select>
			 <xsl:attribute name="id">
				<xsl:value-of select="$personsSelectID"/>
			 </xsl:attribute>
			 <option value="1">1</option>
			 <option value="2">2</option>
			 <option value="3">3</option>
			 <option value="4">4</option>
			 <option value="5">5</option>
			 <option value="6">6</option>
			 <option value="7">7</option>
			 <option value="8">8</option>
			 <option value="9">9</option>
			 <option value="10">10</option>
		  </select>
		</div>
		<!--Transports do not have children drop down list-->
		<xsl:if test="string-length($childrenSelectID) > 0">
		  <div class="search-tip-smjestaja-pola">
			 <div class="search-tip-smjestaja-label">
				<xsl:value-of select="/ApiSettings/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='numberOfChildren']/value"/>
			 </div>
			 <div class="clear-both">
				<xsl:comment></xsl:comment>
			 </div>
			 <select	onchange="childrenNumberSelectChange(this, 1)">
				<xsl:attribute name="id">
				  <xsl:value-of select="$childrenSelectID"/>
				</xsl:attribute>
				<option value="0"
						selected="selected">0</option>
				<option value="1">1</option>
				<option value="2">2</option>
				<option value="3">3</option>
				<option value="4">4</option>
			 </select>
		  </div>
		</xsl:if>
	 </div>
  </xsl:template>

  <!--XSLT Template used to draw number of children drop down list and children ages drop down lists-->
  <xsl:template name="number-of-children-ddl">
	 <xsl:param name="languageID" />
	 <xsl:param name="childAgeSelect1ID" />
	 <xsl:param name="childAgeSelect2ID" />
	 <xsl:param name="childAgeSelect3ID" />
	 <xsl:param name="childAgeSelect4ID" />

	 <div class="search-tip-smjestaja hideClass">
		<div class="hideClass search-broj-djece"
			 childAgeDiv="1"
			 position="1">
		  <span class="search-tip-smjestaja-label">
			 <xsl:text>1. </xsl:text>
			 <xsl:value-of select="/ApiSettings/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='child']/value"/>
		  </span>
		  <select	childAgeSelect="1">
			 <xsl:attribute name="id">
				<xsl:value-of select="$childAgeSelect1ID"/>
			 </xsl:attribute>
			 <option value="0"
					 selected="selected">0</option>
			 <option value="1">1</option>
			 <option value="2">2</option>
			 <option value="3">3</option>
			 <option value="4">4</option>
			 <option value="5">5</option>
			 <option value="6">6</option>
			 <option value="7">7</option>
			 <option value="8">8</option>
			 <option value="9">9</option>
			 <option value="10">10</option>
			 <option value="11">11</option>
			 <option value="12">12</option>
			 <option value="13">13</option>
			 <option value="14">14</option>
			 <option value="15">15</option>
			 <option value="16">16</option>
			 <option value="17">17</option>
		  </select>
		</div>
		<div class="hideClass search-broj-djece"
			 childAgeDiv="1"
			 position="2">
		  <span class="search-tip-smjestaja-label">
			 <xsl:text>2. </xsl:text>
			 <xsl:value-of select="/ApiSettings/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='child']/value"/>
		  </span>
		  <select	childAgeSelect="1">
			 <xsl:attribute name="id">
				<xsl:value-of select="$childAgeSelect2ID"/>
			 </xsl:attribute>
			 <option value="0"
					 selected="selected">0</option>
			 <option value="1">1</option>
			 <option value="2">2</option>
			 <option value="3">3</option>
			 <option value="4">4</option>
			 <option value="5">5</option>
			 <option value="6">6</option>
			 <option value="7">7</option>
			 <option value="8">8</option>
			 <option value="9">9</option>
			 <option value="10">10</option>
			 <option value="11">11</option>
			 <option value="12">12</option>
			 <option value="13">13</option>
			 <option value="14">14</option>
			 <option value="15">15</option>
			 <option value="16">16</option>
			 <option value="17">17</option>
		  </select>
		</div>
		<div class="hideClass search-broj-djece"
			 childAgeDiv="1"
			 position="3">
		  <span class="search-tip-smjestaja-label">
			 <xsl:text>3. </xsl:text>
			 <xsl:value-of select="/ApiSettings/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='child']/value"/>
		  </span>
		  <select	childAgeSelect="1">
			 <xsl:attribute name="id">
				<xsl:value-of select="$childAgeSelect3ID"/>
			 </xsl:attribute>
			 <option value="0"
					 selected="selected">0</option>
			 <option value="1">1</option>
			 <option value="2">2</option>
			 <option value="3">3</option>
			 <option value="4">4</option>
			 <option value="5">5</option>
			 <option value="6">6</option>
			 <option value="7">7</option>
			 <option value="8">8</option>
			 <option value="9">9</option>
			 <option value="10">10</option>
			 <option value="11">11</option>
			 <option value="12">12</option>
			 <option value="13">13</option>
			 <option value="14">14</option>
			 <option value="15">15</option>
			 <option value="16">16</option>
			 <option value="17">17</option>
		  </select>
		</div>
		<div class="hideClass search-broj-djece"
			 childAgeDiv="1"
			 position="4">
		  <span class="search-tip-smjestaja-label">
			 <xsl:text>4. </xsl:text>
			 <xsl:value-of select="/ApiSettings/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='child']/value"/>
		  </span>
		  <select childAgeSelect="1">
			 <xsl:attribute name="id">
				<xsl:value-of select="$childAgeSelect4ID"/>
			 </xsl:attribute>
			 <option value="0"
					 selected="selected">0</option>
			 <option value="1">1</option>
			 <option value="2">2</option>
			 <option value="3">3</option>
			 <option value="4">4</option>
			 <option value="5">5</option>
			 <option value="6">6</option>
			 <option value="7">7</option>
			 <option value="8">8</option>
			 <option value="9">9</option>
			 <option value="10">10</option>
			 <option value="11">11</option>
			 <option value="12">12</option>
			 <option value="13">13</option>
			 <option value="14">14</option>
			 <option value="15">15</option>
			 <option value="16">16</option>
			 <option value="17">17</option>
		  </select>
		</div>
	 </div>
  </xsl:template>

  <!--XST Template used to draw Country, Region and Destination drop down lists-->
  <xsl:template name="country-region-destination-ddl">
	 <xsl:param name="languageID" />
	 <xsl:param name="irrelevantTranslation" />
	 <xsl:param name="ShowDestinationAsDropDownList" />
	 <xsl:param name="countriesSelectID"/>
	 <xsl:param name="countriesSelectOnChangeMethod" />
	 <xsl:param name="regionsSelectID"/>
	 <xsl:param name="regionsSelectOnChangeMethod" />
	 <xsl:param name="destinationsSelectID"/>

	 <xsl:choose>
		<xsl:when test="$ShowDestinationAsDropDownList = true()">
		  <div class="search-tip-smjestaja">
			 <!-- Country, region & destination -->
			 <div class="search-regija-redak">
				<!-- Country -->
				<select class="dropkick">
				  <xsl:attribute name="id">
					 <xsl:value-of select="$countriesSelectID"/>
				  </xsl:attribute>
				  <xsl:attribute name="onchange">
					 <xsl:value-of select="$countriesSelectOnChangeMethod"/>
				  </xsl:attribute>
				  <option value="0">
					 <xsl:value-of select="/ApiSettings/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='country']/value"/>
					 <xsl:text>: </xsl:text>
					 <xsl:value-of select="$irrelevantTranslation" />
				  </option>
				</select>
			 </div>
			 <div class="search-regija-redak">
				<!-- Regions -->
				<select class="dropkick">
				  <xsl:attribute name="id">
					 <xsl:value-of select="$regionsSelectID"/>
				  </xsl:attribute>
				  <xsl:attribute name="onchange">
					 <xsl:value-of select="$regionsSelectOnChangeMethod"/>
				  </xsl:attribute>
				  <option value="0">
					 <xsl:value-of select="/ApiSettings/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='region']/value"/>
					 <xsl:text>: </xsl:text>
					 <xsl:value-of select="$irrelevantTranslation" />
				  </option>
				</select>
			 </div>
			 <div class="search-regija-redak">
				<!-- Destinations-->
				<select class="dropkick">
				  <xsl:attribute name="id">
					 <xsl:value-of select="$destinationsSelectID"/>
				  </xsl:attribute>
				  <option value="0">
					 <xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='destination']/value"/>
					 <xsl:text>: </xsl:text>
					 <xsl:value-of select="$irrelevantTranslation" />
				  </option>
				</select>
			 </div>
		  </div>
		</xsl:when>
		<xsl:otherwise>
		  <div class="search-tip-smjestaja">
			 <span class="search-tip-smjestaja-label">
				<xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='destination']/value"/>
			 </span>
			 <div class="clear-both">
				<xsl:comment></xsl:comment>
			 </div>
			 <input id="destinationNameInput"
					  class="destinationSearchBox" onclick="hideDestinationError()"></input>
			 <input type="hidden"
					  id="selectedDestinationsHiddenField"/>
			 <input type="hidden"
					  id="selectedDestinationsHiddenField"/>
			 <input type="hidden"
					  id="selectedDestinationsHiddenField"/>
			 <input type="hidden"
					  id="selectedDestinationsIDHiddenField" />
			 <span class="hideClass"
					 id="unknownDestination">
				<xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='youNeedToEnterAValidDestination']/value"/>
			 </span>
		  </div>
		</xsl:otherwise>
	 </xsl:choose>
  </xsl:template>

  <!--XSLT Template used to draw Navigation tabs-->
  <xsl:template name="navigation">
	 <xsl:param name="languageID" />
	 <xsl:param name="first-tab-selected"/>
	 <xsl:param name="second-tab-selected"/>
	 <xsl:param name="third-tab-selected"/>
	 <xsl:param name="fourth-tab-selected"/>

	 <ul class="package-navigation">
		<li class="package-navigation-z1 {$first-tab-selected}">
		  <div class="package-navigation-outer first">
			 <div class="package-navigation-inner">
				<span class="package-navigation-title">
				  <xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='searchStep']/value"/>
				</span>
			 </div>
		  </div>
		</li>
		<li class="package-navigation-z2 {$second-tab-selected}">
		  <div class="package-navigation-outer">
			 <div class="package-navigation-inner">
				<span class="package-navigation-title">
				  <xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='resultsStep']/value"/>
				</span>
			 </div>
		  </div>
		</li>
		<li class="package-navigation-z3 {$third-tab-selected}">
		  <div class="package-navigation-outer">
			 <div class="package-navigation-inner">
				<span class="package-navigation-title">
				  <xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='detailsStep']/value"/>
				</span>
			 </div>
		  </div>
		</li>
		<li class="package-navigation-z4 {$fourth-tab-selected}">
		  <div class="package-navigation-outer last">
			 <div class="package-navigation-inner">
				<span class="package-navigation-title">
				  <xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='reservationStep']/value"/>
				</span>
			 </div>
		  </div>
		</li>
	 </ul>
  </xsl:template>

  <!--XSLT Template used to draw no result form-->
  <xsl:template name="no-results-template">
	 <xsl:param name="totalResults" />
	 <xsl:param name="languageID" />
	 <xsl:param name="ClientWebAddress" />
	 <xsl:if test="$totalResults = 0">
		<div class="no-results">
		  <h1 class="no-results-title">
			 <xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='noResultsFound']/value"/>
		  </h1>
		  <h2 class="no-results-subtitle">
			 <xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='itIsPossibleThat']/value"/>
		  </h2>
		  <ul class="no-results-options-list">
			 <li>
				<xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='noAvailableAccommodationInGivenTime']/value"/>
			 </li>
			 <li>
				<xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='noAvailableAccommodationInGivenLocations']/value"/>
			 </li>
			 <li>
				<xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='noAvailableAccommodationInGivenCategory']/value"/>
			 </li>
		  </ul>
		  <p class="no-results-review">
			 <xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='reviewYourQuery']/value"/>
		  </p>
		  <a href="{$ClientWebAddress}"
			  target="_parent"
			  class="package-filter-button align-left">
			 <span>
				<xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='homepage']/value"/>
			 </span>
		  </a>
		</div>
	 </xsl:if>
  </xsl:template>

  <!--XSLT Template used to draw google maps tab-->
  <xsl:template name="google-maps-tab">
	 <xsl:param name="mapTabVisible"/>
	 <xsl:param name="reservationsTabVisible" />
	 <xsl:param name="reservationsTabID" />
	 <xsl:param name="MapLocationUrl" />
	 <xsl:param name="languageID" />
	 <xsl:param name="ObjectDetails" />

	 <xsl:if test="$mapTabVisible">
		<li id="googleMapContainer">
		  <xsl:if test="$reservationsTabVisible">
			 <a class="book-btn clear-both"
				  href="javascript:void(0);"
				  onclick="jQuery('#{$reservationsTabID}').click();">
				<span>
				  <xsl:value-of select="$ObjectDetails/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='reservations']/value"/>
				  <xsl:comment></xsl:comment>
				</span>
			 </a>
		  </xsl:if>
		  <xsl:variable name="mapWidth"
						  select="652"/>
		  <xsl:variable name="mapHeight"
						  select="452"/>
		  <xsl:variable name="mapLocationUrl"
						  select="$MapLocationUrl" />
		  <iframe id="iframeMapID"
				  height="{$mapHeight}"
				  frameborder="0"
				  width="{$mapWidth}"
				  src="{$mapLocationUrl}&#38;mapWidth={$mapWidth}&#38;mapHeight={$mapHeight}">
			 <xsl:comment></xsl:comment>
		  </iframe>
		</li>
	 </xsl:if>
  </xsl:template>

  <!--XSLT Template used to draw cancellation policy tab-->
  <xsl:template name="cancellation-policity-tab">
	 <xsl:param name="languageID"/>
	 <xsl:param name="cancellationPolicyTabVisible" />
	 <xsl:param name="reservationsTabVisible" />
	 <xsl:param name="CancellationPolicy" />
	 <xsl:param name="ObjectDetails" />
	 <xsl:param name="reservationsTabID" />

	 <xsl:if test="$cancellationPolicyTabVisible=true()">
		<li id="cancellationPolicyContainer">
		  <xsl:if test="$reservationsTabVisible">
			 <a class="book-btn clear-both"
				  href="javascript:void(0);"
				  onclick="jQuery('#{$reservationsTabID}').click();">
				<span>
				  <xsl:value-of select="$ObjectDetails/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='reservations']/value"/>
				</span>
			 </a>
		  </xsl:if>
		  <div class="text-description">
			 <span class="info-title">
				<xsl:value-of select="$ObjectDetails/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='cancellationPolicy']/value"/>
				<xsl:text>: </xsl:text>
			 </span>
			 <div class="hotel-info-text">
				<!-- HTML Description -->
				<xsl:value-of
					select="$CancellationPolicy"
					disable-output-escaping="yes"/>
			 </div>
		  </div>
		  <xsl:if test="$reservationsTabVisible">
			 <a class="book-btn clear-both"
				  href="javascript:void(0);"
				  onclick="jQuery('#{$reservationsTabID}').click();">
				<span>
				  <xsl:value-of select="$ObjectDetails/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='reservations']/value"/>
				</span>
			 </a>
		  </xsl:if>
		</li>
	 </xsl:if>
  </xsl:template>

  <!--XSLT Template used to draw room info details-->
  <xsl:template name="room-info-template">
	 <xsl:param name="languageID" />
	 <xsl:param name="unitID" />
	 <xsl:param name="unitTypeID" />
	 <xsl:param name="errorMessage" />
	 <xsl:param name="specialOfferID" />
	 <xsl:param name="posId" />
	 <xsl:param name="currencyShortName" />
	 <xsl:param name="ObjectDetails" />
	 <xsl:param name="SpecialOfferList" />
	 <xsl:param name="ShortDescription" />

	 <xsl:if test="count($ObjectDetails/AccommodationObject/UnitList/AccommodationUnit[UnitID=$unitID]/AttributeGroupList/AttributeGroup[GroupID!=$unitTypeID]) > 0 or $ShortDescription != '' or ($errorMessage='' and count($SpecialOfferList/SpecialOffer[ServiceID=$specialOfferID]/CalculatedPriceInfo/ServiceList/Service[ServiceType!='Basic' and ServiceType!='SpecialOffer'][Price!=0]) > 0)">
		<div class="room-info-border"
			 id="specialoffers-tab-room-info-{$posId}-{$unitID}"
			 style="display:none;">
		  <!-- Display description if exists -->
		  <xsl:if test="ShortDescription != ''">
			 <span class="info-title">
				<xsl:value-of select="$ObjectDetails/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='description']/value" />
			 </span>
			 <div class="hotel-info-text">
				<!-- The description of the accommodation unit goes here -->
				<xsl:value-of select="$ShortDescription"
								disable-output-escaping="yes"/>
			 </div>
		  </xsl:if>

		  <!-- If there's no error display Supplements and prices -->
		  <xsl:if test="$errorMessage=''">
			 <!-- Display Supplemments if any exist, and only thoose whose price is not 0-->
			 <xsl:variable select="$SpecialOfferList/SpecialOffer[ServiceID=$specialOfferID]/CalculatedPriceInfo/ServiceList/Service[ServiceType!='Basic' and ServiceType!='SpecialOffer'][Price!=0]"
							 name="supplementList"></xsl:variable>
			 <xsl:if test="count($supplementList) > 0">
				<span class="info-title">
				  <xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='supplements']/value" />
				</span>
				<ul class="service-list">
				  <xsl:for-each select="$supplementList">
					 <li>
						<span class="service-list-item">
						  <!-- Output the name-->
						  <xsl:variable select="ServiceID"
										  name="serviceID"></xsl:variable>
						  <!--name is containes in units service list-->
						  <xsl:value-of select="../../../../../ServiceList/Service[ServiceID=$serviceID]/ServiceName" />
						  <xsl:text>:  </xsl:text>
						  <strong class="highlighted">
							 <xsl:value-of select="PriceFormated"/>
							 <xsl:text> </xsl:text>
							 <xsl:value-of select="$currencyShortName"/>
						  </strong>
						</span>
					 </li>
				  </xsl:for-each>
				</ul>
			 </xsl:if>
		  </xsl:if>

		  <!-- If there are attribute groups to output other than the main group -->
		  <xsl:if test="count($ObjectDetails/AccommodationObject/UnitList/AccommodationUnit[UnitID=$unitID]/AttributeGroupList/AttributeGroup[GroupID!=$unitTypeID]) > 0">
			 <span class="info-title">
				<xsl:value-of select="$ObjectDetails/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='characteristics']/value"/>
				<xsl:text>: </xsl:text>
			 </span>
			 <!-- For each group other than the regular one print out its attributes-->
			 <xsl:for-each select="/AccommodationObjectDetails/AccommodationObject/UnitList/AccommodationUnit[UnitID=$unitID]/AttributeGroupList/AttributeGroup[GroupID!=$unitTypeID]">
				<!-- If there is at least one visible attribute -->
				<xsl:if test="count(AttributeList/Attribute[Visible='true']) > 0">
				  <span class="info-subtitle">
					 <xsl:value-of select="GroupName"/>
					 <xsl:text>: </xsl:text>
				  </span>
				  <!-- Print all attributes -->
				  <ul class="features-list">
					 <xsl:for-each select="AttributeList/Attribute[Visible != 'false']">
						<li>
						  <span class="features-list-item">
							 <xsl:value-of select="AttributeName" />
							 <xsl:if test="AttributeType != 'Logical'">
								<xsl:text>: </xsl:text>
								<xsl:value-of select="AttributeValue"
												disable-output-escaping="yes"/>
							 </xsl:if>
						  </span>
						</li>
					 </xsl:for-each>
				  </ul>
				</xsl:if>
			 </xsl:for-each>
		  </xsl:if>
		</div>
	 </xsl:if>
  </xsl:template>

  <xsl:template match="@* | node()">
	 <xsl:copy>
		<xsl:apply-templates select="@* | node()"/>
	 </xsl:copy>
  </xsl:template>

  <xsl:template name="containsElement">
	 <xsl:param name="elementCollections" />
	 <xsl:param name="elementToFind" />
	 <xsl:variable name="newlist"
					select="normalize-space($elementCollections)" />
	 <xsl:variable name="first">
		<xsl:if test="contains($newlist, ';')">
		  <xsl:value-of select="substring-before($newlist, ';')" />
		</xsl:if>
		<xsl:if test="not(contains($newlist, ';'))">
		  <xsl:value-of select="$newlist" />
		</xsl:if>
	 </xsl:variable>
	 <xsl:variable name="remaining"
					select="substring-after($newlist, ';')" />
	 <xsl:choose>
		<xsl:when test="$first = ''">
		  <xsl:value-of select="('false' or '0')"/>
		</xsl:when>
		<xsl:otherwise>
		  <xsl:choose>
			 <xsl:when test="$first = $elementToFind">
				<xsl:value-of select="('true' or '1')"/>
			 </xsl:when>
			 <xsl:otherwise>
				<xsl:choose>
				  <xsl:when test="$remaining">
					 <xsl:call-template name="containsElement">
						<xsl:with-param name="elementCollections"
										select="$remaining" />
						<xsl:with-param name="elementToFind"
										select="$elementToFind"></xsl:with-param>
					 </xsl:call-template>
				  </xsl:when>
				  <xsl:otherwise>
					 <xsl:value-of select="'false'"/>
				  </xsl:otherwise>
				</xsl:choose>
			 </xsl:otherwise>
		  </xsl:choose>
		</xsl:otherwise>
	 </xsl:choose>
  </xsl:template>

  <xsl:template name="PrintOutTheSpecialOfferPrice">
	 <xsl:param name="MainAccommodationObject" />
	 <xsl:param name="CurrencyAbreviation" />

	 <xsl:variable name="Minimal_CalculatedPriceInfo">
		<xsl:for-each select="$MainAccommodationObject/UnitList/AccommodationUnit/CalculatedPriceInfo[CalculatedPrice &gt; 0]/CalculatedPrice">
		  <xsl:sort data-type="number" order="ascending"/>
		  <xsl:if test="position()=1">
			 <xsl:value-of select="."/>
		  </xsl:if>
		</xsl:for-each>
	 </xsl:variable>

	 <xsl:variable name="Minimal_BasicCalculatedPriceInfo">
		<xsl:for-each select="$MainAccommodationObject/UnitList/AccommodationUnit/CalculatedPriceInfo[CalculatedPrice &gt; 0]/CalculatedPrice">
		  <xsl:sort data-type="number" order="ascending"/>
		  <xsl:if test="position()=1">
			 <xsl:value-of select="../BasicCalculatedPrice"/>
		  </xsl:if>
		</xsl:for-each>
	 </xsl:variable>

	 <xsl:choose>
		<xsl:when test="$Minimal_CalculatedPriceInfo &lt; $Minimal_BasicCalculatedPriceInfo">
		  <xsl:for-each select="$MainAccommodationObject/UnitList/AccommodationUnit/CalculatedPriceInfo[CalculatedPrice &gt; 0]/CalculatedPrice">
			 <xsl:sort data-type="number" order="ascending"/>
			 <xsl:if test="position()=1">
				<xsl:variable name="AppliedSpecialOffer" select="../ServiceList/Service[ServiceType='SpecialOffer']/ServiceName" />
				<div class="row">
				  <div>
					 <xsl:value-of select="$AppliedSpecialOffer"/>
				  </div>
				</div>
				<div class="row">
				  <!--Discount price-->
				  <div class="special-offer-unit-price" style="float:right;">
					 <xsl:value-of select="../CalculatedPriceFormated"/>
					 <xsl:text> </xsl:text>
					 <xsl:value-of select="$CurrencyAbreviation"/>
				  </div>
				  <!--Regular price-->
				  <div class="special-offer-unit-old-price" style="float:right;">
					 <xsl:value-of select="../BasicCalculatedPriceFormated"/>
					 <xsl:text> </xsl:text>
					 <xsl:value-of select="$CurrencyAbreviation"/>
				  </div>
				</div>
			 </xsl:if>
		  </xsl:for-each>
		</xsl:when>
		<xsl:when test="$Minimal_CalculatedPriceInfo=0">
		  <xsl:for-each select="$MainAccommodationObject/UnitList/AccommodationUnit/UnitMinimumPriceInfo[Price &gt; 0]/Price">
			 <xsl:sort data-type="number" order="ascending"/>
			 <xsl:if test="position()=1">
				<xsl:value-of select="../PriceFormatted"/>
			 </xsl:if>
		  </xsl:for-each>
		</xsl:when>
		<xsl:otherwise>
		</xsl:otherwise>
	 </xsl:choose>


  </xsl:template>

</xsl:stylesheet>





