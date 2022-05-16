self.CssTab = function (buttonHolderDivID, controlerUl, activeClass, inactiveClass){
	
	this.classA = activeClass;
	this.classIA = inactiveClass;
	
	this.hDivID = buttonHolderDivID;
	this.displayDivID = controlerUl;
	this.buttons = new Array();
	this.tabs = new Array();
	this.curtentBtnIndex = 0;
	this.initialize();
}

CssTab.prototype.initialize = function() {

    this.createDisplayUlItems();

    var docFragment = document.getElementById(this.hDivID);
    if (docFragment == null) return;

    this.buttons.length = 0;
    var indexCounted = 0;
    var toShow = 0;
    for (var i = 0; i < docFragment.childNodes.length; i++) {
        if (docFragment.childNodes[i].nodeName == "LI") {
            this.buttons.push(docFragment.childNodes[i])
            docFragment.childNodes[i].cssTab = this;
            docFragment.childNodes[i].onclick = this.cssTabClick;
            docFragment.childNodes[i].redniBroj = indexCounted;

            /// Ako mu je zadana klasa "selected", selektiraj njega.
            /// Koristi se zbog onih linkova u footeru, kako bi mogli linkati na potrebnu stranicu.
            if (docFragment.childNodes[i].className == "selected") {
                toShow = indexCounted;
            }
            
            indexCounted++;
        }
    }
    this.showTab(toShow);
}

CssTab.prototype.createDisplayUlItems = function() {
    var docFragment = document.getElementById(this.displayDivID);
    if (docFragment == null) return;

    this.tabs.length = 0;
    for (var i = 0; i < docFragment.childNodes.length; i++) {
        if (docFragment.childNodes[i].nodeName == "LI") {
            this.tabs.push(docFragment.childNodes[i]);
            //docFragment.childNodes[i].onclick = this.cssTabClick;
        }
    }
}

CssTab.prototype.showTab = function(index) {
    if (this.tabs.length > index) {
        for (var i = 0; i < this.tabs.length; i++) {
            this.tabs[i].style.display = i == index ? "block" : "none";
        }
    }
    for (var i = 0; i < this.buttons.length; i++) {
        this.buttons[i].className = i != index ? this.classA : this.classIA;
    }
}


///funkcija napravljena kako bi se omogucio clicktale
function showTabFunction(index, displayDivID, hDivID, classA, classIA) {
    var docFragment = document.getElementById(displayDivID);

    if (docFragment == null) return;

    var tabs = new Array();
    for (var i = 0; i < docFragment.childNodes.length; i++) {
        if (docFragment.childNodes[i].nodeName == "LI") {
            tabs.push(docFragment.childNodes[i]);
        }
    }
    if (tabs.length > index) {
        for (var i = 0; i < tabs.length; i++) {
            tabs[i].style.display = i == index ? "block" : "none";
        }
    }

    var docFragmentButtons = document.getElementById(hDivID);

    if (docFragmentButtons == null) return;

    var buttons = new Array();
    for (var i = 0; i < docFragmentButtons.childNodes.length; i++) {
        if (docFragmentButtons.childNodes[i].nodeName == "LI") {
            buttons.push(docFragmentButtons.childNodes[i]);
        }
    }
    if (buttons.length > index) {
        for (var i = 0; i < buttons.length; i++) {
            buttons[i].className = i != index ? classA : classIA;
        }
    }
}

CssTab.prototype.cssTabClick = function() {
    if (typeof ClickTaleExec == 'function') {
        ClickTaleExec("showTabFunction(" + this.redniBroj + ", '" + this.cssTab.displayDivID + "', '" + this.cssTab.hDivID + "', '" + this.cssTab.classA + "', '" + this.cssTab.classIA + "');");
    }
    this.cssTab.showTab(this.redniBroj);
    ///ako je selektiran tab sa kartom onda refresham cijeli iframe zbog toga da bi se karta dobro prikazala
    if (jQuery(this).attr("id") == 'mapTab') {
    	if (jQuery(this).attr("id") == 'mapTab') {
    		try {
    			jQuery('#iframeMapID').attr("src", jQuery('#iframeMapID').attr("src"));
    		} catch (e) { }
    	}
    }
    return false;
}


/// function will search <li> element with id=tabName in <ul> with id 'tab-list' and select it
function selectTabByName(cssTabObject, tabName) {
    tabName = tabName.trim().toLowerCase();

    if (tabName == '') {
        tabName = 'basicinfotab';
    }

    /// use of each function is nessesary because we need to find index of <li> element to select it
    jQuery("#tab-list li").each(function(index, element) {
        var elementId = jQuery(element).attr("id").toLowerCase();
        if (elementId == tabName) {
            cssTabObject.showTab(index);
            return false;
        }
    });
}