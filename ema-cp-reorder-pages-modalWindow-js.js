/* Define Modal window object */

var EmaModal = function(){

	var modalOverlay = document.createElement('div');
		modalOverlay.setAttribute('class', 'ema-modal-overlay');
		document.body.appendChild(modalOverlay);
		
	var config = {
		$modal: document.querySelector('.ema-modal'),
		$modalOverlay: document.querySelector('.ema-modal-overlay'),
		 modalOverlayColor: 'rgba(255,255,255,0.7)',
		$modalClose: document.querySelector('.ema-modal-close'),
		$modalTrigger: document.querySelector('.ema-modal-trigger')
	}
	config.$modalOverlay.style.background = config.modalOverlayColor;


	var EmaModal = {
		open: function(){

			config.$modal.style.visibility = "visible";
			config.$modal.style.opacity = "80%";
			config.$modal.classList.add('opening');
			config.$modal.classList.remove('closing');

			config.$modalOverlay.style.visibility = "visible";
			config.$modalOverlay.classList.add('opening');
			config.$modalOverlay.classList.remove('closing');
		},
		close: function(){

			config.$modal.classList.add('closing');
			config.$modal.classList.remove('opening');
			config.$modalOverlay.classList.add('closing');
			config.$modalOverlay.classList.remove('opening');

			var timer = window.setTimeout(function(){

				config.$modal.style.visibility = "hidden";
				config.$modalOverlay.style.visibility = "hidden";
				config.$modal.style.opacity = "0";

			},500);

		},
	}

	EmaModal.config = config;
	return EmaModal;

}();