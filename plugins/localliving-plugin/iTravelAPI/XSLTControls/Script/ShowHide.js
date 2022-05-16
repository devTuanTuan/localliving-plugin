// function used to show/hide a div from a control
function showHide(caller, shDivName, showText, hideText) {
    var d = document.getElementById(shDivName);
    d.style.display = d.style.display == "block" ? "none" : "block";
    caller.innerHTML = d.style.display == "none" ? showText : hideText;
}
// function used to show/hide a div from a control, control first child element if exists changes
// innerHTML, and clickedClass class is assigned to caller control
function showHideChildText(callerInstance, shDivName, showText, hideText, clickedClass) {
    var d = document.getElementById(shDivName);
    d.style.display = d.style.display == "block" ? "none" : "block";
    
    var innerElement;
    if(callerInstance.children&&callerInstance.children[0]){
		innerElement = callerInstance.children[0];
	}else{
		innerElement = callerInstance.innerHTML;
	}
    
    if(d.style.display == "none")
	{	
		innerElement.innerHTML = showText;
		callerInstance.className = callerInstance.className.replace(" " + clickedClass, "");
	
	}else{
		innerElement.innerHTML = hideText;
		
		callerInstance.className += (" " + clickedClass);
	}
}