<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:msxsl="urn:schemas-microsoft-com:xslt" exclude-result-prefixes="msxsl"
>
    <xsl:output method="xml" indent="yes" cdata-section-elements="" />

    <xsl:template match="@* | node()">
        <xsl:copy>
            <xsl:apply-templates select="@* | node()"/>
        </xsl:copy>
    </xsl:template>

	<!-- Booking address parameter-->
	<xsl:param name="bookingAddress" />
	<xsl:param name="width" />
	<xsl:param name="height" />

	<xsl:variable name="languageID"
				  select="/ApiSettings/Language/LanguageID" />
				  
	<xsl:template match="/">
		<!-- *************************** -->
		<!--          Navigation         -->
		<!-- *************************** -->
    <!-- Navigation	-->
		<div style="width:{$width}px;">
			<ul class="package-navigation">
				<li class="package-navigation-z1">
					<div class="package-navigation-outer first">
						<div class="package-navigation-inner">
							<span class="package-navigation-title">
								<xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='searchStep']/value"/>
							</span>
						</div>
					</div>
				</li>
				<li class="package-navigation-z2">
					<div class="package-navigation-outer">
						<div class="package-navigation-inner">
							<span class="package-navigation-title">
								<xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='resultsStep']/value"/>
							</span>
						</div>
					</div>
				</li>
				<li class="package-navigation-z3">
					<div class="package-navigation-outer">
						<div class="package-navigation-inner">
							<span class="package-navigation-title">
								<xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='detailsStep']/value"/>
							</span>
						</div>
					</div>
				</li>
				<li class="package-navigation-z4 navigation-selected-step">
					<div class="package-navigation-outer last">
						<div class="package-navigation-inner">
							<span class="package-navigation-title">
								<xsl:value-of select="/*/TranslationList/Translation[LanguageID=$languageID]/root/data[@name='reservationStep']/value"/>
							</span>
						</div>
					</div>
				</li>
			</ul>
		</div>
    <!-- End navigation -->
		<xsl:text disable-output-escaping="yes">
      &lt;script&gt;              
      /* &lt;![CDATA[ */ 
    
      <![CDATA[
			function postMessageHandler(e) {
				if (e.data == "scrollToTopPage") {
					jQuery(document).scrollTop(0);
				}
				else if (e.data == "scrollToTopFrame") {
					jQuery(document).scrollTop(jQuery("iframe[data-iTravelBookingFormFrame='1']").offset().top);
				}
				else {
					// object expected in e.data, parse from URL parameters if e.data is a string
					var params = typeof (e.data) == "string" ? getUrlParams(e.data) : e.data;
					var h = params.if_height;
					if (!isNaN(h) && h > 0) {
						// Height has changed, update the iframe.
						var iframe = jQuery("iframe[data-iTravelBookingFormFrame='1']");
						iframe.height(parseInt(h));
					}
				}
			}

			jQuery(function () {
				var iframe = jQuery("iframe[data-iTravelBookingFormFrame='1']");
				iframe.attr('src', iframe.attr('data-itravel-src') + '#' + encodeURIComponent(document.location.href));
				if (window.addEventListener)
					addEventListener("message", postMessageHandler, false);
				else
					attachEvent("onmessage", postMessageHandler);
			});

			function getUrlParams(string) {
				var qs = {};
				var vars = string.split("&");
				for (var i = 0; i < vars.length; i++) {
					var pair = vars[i].split("=");
					if (typeof qs[pair[0]] === "undefined") {
						qs[pair[0]] = pair[1];
					} else if (typeof qs[pair[0]] === "string") {
						var arr = [qs[pair[0]], pair[1]]; qs[pair[0]] = arr;
					} else {
						qs[pair[0]].push(pair[1]);
				}
	        }
	        return qs;
	    }
		 ]]>
      /* ]]&gt; */
      &lt;/script&gt;              
    </xsl:text>

		<iframe data-itravel-src="{$bookingAddress}"
				width="{$width}"
				height="{$height}"
				scrolling="auto"
				frameborder="0"
			style="border: none; padding: 0px; margin: 0px;"
			data-iTravelBookingFormFrame='1'>
			<xsl:comment></xsl:comment>
		</iframe>
	</xsl:template>
    
</xsl:stylesheet>
