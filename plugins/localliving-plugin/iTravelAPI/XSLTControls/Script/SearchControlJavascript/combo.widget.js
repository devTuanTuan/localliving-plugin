(function ($) {
	$.widget("custom.combobox", {
		setValue: function (value) {
			this.element.val(value.val);
			this.input.val(value.text);
		},
		_create: function (options) {
			this.wrapper = $("<span>")
				.addClass("custom-combobox")
				.insertAfter(this.element);

			this.element.hide();
			this._createAutocomplete();
			this._createShowAllButton();
		},

		_createAutocomplete: function () {
			var self = this;
			var selectElement = this.element;
			var selected = this.element.children(":selected"),
				value = selected.val() ? selected.text() : $("option:first", selectElement).text();
			this.input = $("<input>")
				.appendTo(this.wrapper)
				.val(value)
				.attr("title", "")
				.addClass("custom-combobox-input ui-widget ui-widget-content ui-state-default ui-corner-left")
				.autocomplete({
					delay: 0,
					minLength: 0,
					source: $.proxy(this, "_source"),
					open: function (event, ui) {
						$('.ui-autocomplete').off('menufocus hover mouseover mouseenter');
					},
					change: function () {
						setTimeout(function () {
							selectElement.change();
						}, 100);
					}
				})
				/*.tooltip({
					tooltipClass: "ui-state-highlight"
				});*/
			this.input.focus(function () {
				if ($(this).val() == $("option:first", selectElement).text()) {
					$(this).val("");
				}
			});
			this._on(this.input, {
				autocompleteselect: function (event, ui) {
					ui.item.option.selected = true;
					this._trigger("select", event, {
						item: ui.item.option
					});
					if (this.element[0] && typeof this.element[0].onchange == typeof Function) {
						this.element[0].onchange();
					}
					this.element.change();
					this.element.attr('data-text-val', this.element.val());
				},

				autocompletechange: function (event, ui) {
					var input = this.input;
					var selectAttrValue = selectElement.attr('data-text-val');
					$('option', selectElement).each(function (i, v) {
						if (v.value == selectElement.attr('data-text-val')) {
							// the following line actually completes the input box on auto select if a match is found (eg. "br" gets replaced with "Brac Island")
							// &amp; is replaced with "&" since "&amp;" is not an expected value in the input box.
							input.val(v.innerHTML.replace("&amp;", "&"));
						}
					});

					this._removeIfInvalid(event, ui);
				}
			});
			this.input.bind("change paste keyup", function () {
				var input = $(this);

				var $dropdown = $('.ui-autocomplete:visible');
				var firstMatchingValue;
				var matchingId;

				// if the dropdown is showing, set the first value as the matching value.
				if ($dropdown.length) {
					firstMatchingValue = $dropdown.children('li').first().html();
				} else {
					firstMatchingValue = null;
				}

				// if there is a matching text value, set the neccessary ids in order for the form to POST correcly.
				if (firstMatchingValue != null) {
					$('option', selectElement).each(function (index, value) {
						if (firstMatchingValue == value.innerHTML) {
							matchingId = value.value;
							selectElement.attr('data-text-val', matchingId);
							selectElement.val(matchingId);
						}
					});
				} else {
					//selectElement.attr('data-text-val', input.val());
					//selectElement.val(null);
				}
			});
		},

		_createShowAllButton: function () {
			var input = this.input,
				wasOpen = false;

			$("<a>")
				.attr("tabIndex", -1)
				.attr("title", "Show All Items")
				/*.tooltip()*/
				.appendTo(this.wrapper)
				.button({
					icons: {
						primary: "ui-icon-triangle-1-s"
					},
					text: false
				})
				.removeClass("ui-corner-all")
				.addClass("custom-combobox-toggle ui-corner-right")
				.mousedown(function () {
					wasOpen = input.autocomplete("widget").is(":visible");
				})
				.click(function () {
					input.focus();

					// Close if already visible
					if (wasOpen) {
						return;
					}

					// Pass empty string as value to search for, displaying all results
					input.autocomplete("search", "");
				});
		},

		_source: function (request, response) {
			this.element.attr('data-text-val', this.input.val());
			var matcher = new RegExp($.ui.autocomplete.escapeRegex(request.term), "i");
			response(this.element.children("option").map(function () {
				var text = $(this).text();
				if (this.value && (!request.term || matcher.test(text)))
					return {
						label: text,
						value: text,
						option: this
					};
			}));
		},

		_removeIfInvalid: function (event, ui) {

			// Selected an item, nothing to do
			if (ui.item) {
				return;
			}

			// Search for a match (case-insensitive)
			var value = this.input.val(),
				valueLowerCase = value.toLowerCase(),
				valid = false;
			this.element.children("option").each(function () {
				if ($(this).text().toLowerCase() === valueLowerCase) {
					this.selected = valid = true;
					return false;
				}
			});

			// Found a match, nothing to do
			if (valid) {
				return;
			}

			// Remove invalid value
			this.input
				.val("")
				.attr("title", value + " didn't match any item");
				/*.tooltip("open");*/
			this.element.val("");
			this._delay(function () {
				/*this.input.tooltip("close").attr("title", "");*/
			}, 2500);
			this.input.autocomplete("instance").term = "";
		},

		_destroy: function () {
			this.wrapper.remove();
			this.element.show();
		}
	});
})(jQuery);