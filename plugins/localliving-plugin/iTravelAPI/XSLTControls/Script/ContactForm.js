
/// initializes the contact form
jQuery.fn.contactForm = function(options) {
	/// get the URL for the HTML template
    var templateUrl = "/itravel/XSLTControls/ContactFormTemplate.htm";
    if (typeof options.urlGetTemplate != "undefined" && options.urlGetTemplate.length > 0) {
        templateUrl = options.urlGetTemplate;
    }
    /// resources
    var urlGetResources = "/itravel/XSLTControls/Proxy/ProxyWebService.asmx/GetResources";
    if (typeof options.urlGetResources != "undefined" && options.urlGetResources.length > 0) {
        urlGetResources = options.urlGetResources;
    }
    /// language
    var language = "en";
    if (typeof options.language != "undefined" && options.language.length > 0) {
        language = options.language;
    }

    /// captcha
    var urlGetCaptcha = "/itravel/XSLTControls/Proxy/ProxyWebService.asmx/GetCaptchaGuid";
    if (typeof options.urlGetCaptcha != "undefined" && options.urlGetCaptcha.length > 0) {
        urlGetCaptcha = options.urlGetCaptcha;
    }

    var urlPostContactForm = "/itravel/XSLTControls/Proxy/ProxyWebService.asmx/PostContactForm";
    if (typeof options.urlPostContactForm != "undefined" && options.urlPostContactForm.length > 0) {
        urlPostContactForm = options.urlPostContactForm;
    }

    /// remember the external container in a var
    var resultContainer = jQuery(this);

    /// get template
    jQuery.ajax({
        url: templateUrl,
        cache: false,
        dataType: "text"
    }).done(function(data) {
        /// we use this trick because "data" is not exactly an object we can read normally with jQuery
        /// attempting to do jQuery("div[contactFormTemplate='1']", data) results in an empty object because jQuery(data) returns an array
        /// (that is what I mean by "not exactly what we can read")
        var div = jQuery('<div></div>').html(data);
        div = jQuery("div[contactFormTemplate='1']", div);

        /// insert the div into the external container
        resultContainer.html(div);

        /// get translations
        fillResources(language, urlGetResources);

        /// get captcha
        getCaptcha(urlGetCaptcha);

        jQuery("input[refresh='1']").click(function(ev) {
            ev.preventDefault();
            getCaptcha(urlGetCaptcha);
        });

        /// set defaults and click functions on datepickers
        InitializeDatePickers(language);

        /// get input values (or set default)
        initElements(options.elements);

        /// we will need this for posting the contact form
        jQuery("input[languageInput='1']").val(language);

        /// assign the click event for the submit button
        jQuery("input[submit='1']").click(function(ev) {
            ev.preventDefault();
            contactFormSubmit_buttonClick(urlPostContactForm);
        });
    });
}

function getCaptcha(urlGetCaptcha) {
    jQuery.ajax({
        type: "POST",
        contentType: "application/json; charset=utf-8",
        url: urlGetCaptcha,
        data: '{ "captchaLength": "7" }',
        dataType: "json",
        success: function (data) {
        	if (data) {
        		var result = data.d;
        		jQuery("img[captchaimg='1']").attr("src", result.PictureURL);
        		jQuery("input[guidInput='1']").val(result.Guid);
        	}
        }
    });
}

function fillResources(language, urlGetResources) {
    /// get resources
    jQuery.ajax({
        type: "POST",
        contentType: "application/json; charset=utf-8",
        url: urlGetResources,
        data: '{ "language": "' + language + '" }',
        dataType: "json",
        success: function(data) {
        	if (data) {
        		var result = data.d;
        		for (var i = 0; i < result.length; i++) {
        			switch (result[i].Key) {
        				case "refresh":
        				case "submit":
        					var input = jQuery("input[" + result[i].Key + "='1']");
        					input.val(result[i].Value);
        					break;
        				default:
        					var label = jQuery("label[" + result[i].Key + "='1']");
        					label.text(result[i].Value);
        			}
        		}
        	}
        }
    });
}

/// fill the default (or given) data
function initElements(elements) {
    var name = "";
    var surname = "";
    var email = "";
    var telephone = "";
    var product = "";
    var productType = "";
    var numberOfPersons = "";
    var dateFrom = new Date();
    var dateTo = "";
    var comment = "";

    if (typeof elements != "undefined") {
        if (typeof elements.name != "undefined" && elements.name.length > 0) {
            name = elements.name;
        }
        
        if (typeof elements.surname != "undefined" && elements.surname.length > 0) {
            surname = elements.surname;
        }
        
        if (typeof elements.email != "undefined" && elements.email.length > 0) {
            email = elements.email;
        }
        
        if (typeof elements.telephone != "undefined" && elements.telephone.length > 0) {
            telephone = elements.telephone;
        }

        if (typeof elements.product != "undefined" && elements.product.length > 0) {
            product = elements.product;
        }

        if (typeof elements.productType != "undefined" && elements.productType.length > 0) {
            productType = elements.productType;
        }

        if (typeof elements.numberOfPersons != "undefined" && elements.numberOfPersons.length > 0) {
            numberOfPersons = elements.numberOfPersons;
        }

        if (typeof elements.dateFrom != "undefined") {
            dateFrom = elements.dateFrom;
        }

        if (typeof elements.dateTo != "undefined") {
            dateTo = elements.dateTo;
        }

        if (typeof elements.comment != "undefined" && elements.comment.length > 0) {
            comment = elements.comment;
        }
    }

    jQuery("input[nameInput='1']").val(name);
    jQuery("input[surnameInput='1']").val(surname);
    jQuery("input[emailInput='1']").val(email);
    jQuery("input[telephoneInput='1']").val(telephone);
    jQuery("input[productInput='1']").val(product);
    jQuery("input[productTypeInput='1']").val(productType);
    jQuery("input[numberOfPersonsInput='1']").val(numberOfPersons);
    jQuery("input[commenttextareaInput='1']").val(comment);
    if (dateFrom) {
        jQuery("input[dateFromInput='1']").datepicker("setDate", dateFrom);
    }

    if (dateTo) {
        jQuery("input[dateToInput='1']").datepicker("setDate", dateTo);
    }
}

/// validate & submit form data
function contactFormSubmit_buttonClick(urlPostContactForm) {
    var isValid = validateForm();

    if (isValid) {
        /// proceed with submit
        var object = new Object();
        var data = new Object();
        var dataList = new Array();

        jQuery("div[formData='1']").each(function() {
            var div = jQuery(this);

            var element = new Object();
            var label = jQuery("label", div);
            var input = jQuery("input[type='text']", div);
            if (label.attr("comment")) {
                input = jQuery("textarea", div);
            }

            element.Key = label.text();
            element.Value = input.val();
            dataList.push(element);
        });

        data.DataList = dataList;
        data.Captcha = jQuery("input[captchaInput='1']").val();
        data.MailFrom = jQuery("input[emailInput='1']").val();
        data.Guid = jQuery("input[guidInput='1']").val();
        data.Language = jQuery("input[languageInput='1']").val();
        object.data = data;

        jQuery.ajax({
            type: "POST",
            contentType: "application/json; charset=utf-8",
            url: urlPostContactForm,
            data: JSON.stringify(object),
            dataType: "json",
            success: function(data) {
                var result = data.d;
                /// if everything was ok, the errormessage contains a success message
                alert(result.ErrorMessage);
            }
        });


    }
}


/// validation functions
function validateForm() {

    var isValid = true;
    jQuery("input[mandatory='1']").each(function() {
        var thisVal = jQuery(this).val();

        /// validate not empty
        if (typeof thisVal == "undefined" || thisVal.length == 0) {
            jQuery(this).addClass("validationErrorBox");
            isValid = false;
            return;
        }
        else {
            jQuery(this).removeClass("validationErrorBox");
        }

        /// validate email
        if (jQuery(this).attr("emailInput")) {
            isValid = validateEmail(jQuery(this).val());
            if (!isValid) {
                jQuery(this).addClass("validationErrorBox");
                return;
            }
            else {
                jQuery(this).removeClass("validationErrorBox");
            }
        }
    });
    
    return isValid;
}

/// validates an email address string
function validateEmail(email) {
    /// regex for email address validation
    var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(email);
} 
