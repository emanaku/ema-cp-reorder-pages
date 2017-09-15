

/* prepare the modal window and open it */

function emaPrepareAndOpenModal() {
	/* read the information about	 								*/
	/* 	- the unit and				 								*/
	/*  - the pages					 								*/
	/* from the DOM 				 								*/
	/* output the pages as a list of lines, that can be reordered 	*/
	
	/* Get name of active Unit */
	
	unitLI = document.querySelector('li.coursepress-ub-tab.active');
	unitID = unitLI.getAttribute("data-tab");
	formFieldUnit = document.getElementById('emaunit');
	formFieldUnit.setAttribute('value', unitID);
	unitName = document.querySelector('li.coursepress-ub-tab.active span').innerHTML;
	
	locUnitName = document.querySelector('span.ema-unit-name');
	locUnitName.innerHTML = unitName + " (" + unitID + ")";
	
	/* Get names of pages in active Unit */
	existElements = [];
	
	var pageList = document.querySelector('div.section.unit-builder-pager ul');
	var pageItems = pageList.getElementsByTagName("li");
	for (var i = 0; i < (pageItems.length - 1); ++i) {
		existElements.push( pageItems[i].innerHTML);
	}
	
	
	
	/* Test array */
	/*
	existElements = ['First Page','Second Page','Third Page','Fourth Page'
					,'Fifth Page','Sixth Page','Seventh Page','Eightth Page' 
					,'Ninth Page','Tenth Page','Eleventh Page','Twelvth Page' 
					];
	*/
	
	/* create a reorderable list */
	output = "";

	len = existElements.length;
	for (var i = 0; i < len; i++) {
	    output = output + "<li id=\"ema-old-"+ (i+1) +"\" tabindex=\"-1\" style>(" + (i+1) + ") " + existElements[i] + "</li>\n";
	}    
	
	
	/* output into modal window */
	var target = document.getElementById('ema-page-list');
	target.innerHTML = output;
	
	/* open the modal window */
	EmaModal.open();
}


/* function sbr(e) {
	if (e.target.classList.contains('demo-no-reorder')) {
        e.preventDefault();
    }
}

function sbw(e) {
	if (e.target.classList.contains('instant')) e.preventDefault();
}

function sr(e) {
    e.target.parentNode.insertBefore(e.target, e.detail.insertBefore);
    return false;	
}
*/

function setupSlip(list) {
    list.addEventListener('slip:beforereorder', function(e){
        if (e.target.classList.contains('demo-no-reorder')) {
            e.preventDefault();
        }
    }, false);

    list.addEventListener('slip:beforewait', function(e){
        if (e.target.classList.contains('instant')) e.preventDefault();
    }, false);

    list.addEventListener('slip:reorder', function(e){
        e.target.parentNode.insertBefore(e.target, e.detail.insertBefore);
        return false;
    }, false);
    return new Slip(list);
}

function emaValidateReorderData() {
	/* save the Unit! */
	/* var el = document.querySelector('.button.unit-save-button');	*/ /* find the button for saving the unit */
	/* el.click();													*/ /* click it to save the unit (before the reordering) */
	
	/* create here the value for the hidden parameter emaarray */

	var pageList = document.getElementById('ema-page-list');
	var pageItems = pageList.getElementsByTagName("li");
	var arrayText = "";
	for (var i = 0; i < pageItems.length; ++i) {
		var pid = pageItems[i].getAttribute('id');
		arrayText = arrayText + ',' + pid.substr(8);
	}
	arrayText = arrayText.substr(1);
	var inputArrayText = document.getElementById('emaarray');
	inputArrayText.setAttribute('value', arrayText);
		
	return true;
}

