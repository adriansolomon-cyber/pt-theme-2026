/* Project Timber — Delivery: postcode checker.
   Front-end zone simulation (zones mirror the live delivery page). The mobile
   menu/support/search are wired site-wide by header.js, so only the checker
   lives here. */
(function () {
	'use strict';

	var form = document.getElementById('pcForm');
	var input = document.getElementById('pcInput');
	var box = document.getElementById('pcResult');
	var ic = document.getElementById('pcIcon');
	var tt = document.getElementById('pcTitle');
	var msg = document.getElementById('pcMsg');
	if (!form || !input || !box) {
		return;
	}

	var ICON = {
		ok: '<path d="M20 6L9 17l-5-5"/>',
		warn: '<path d="M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/>',
		no: '<circle cx="12" cy="12" r="9"/><path d="M15 9l-6 6M9 9l6 6"/>',
		info: '<circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v4h1"/>'
	};

	var D = ['KW', 'IV', 'BT', 'JE', 'GY', 'IM', 'HS', 'ZE'];
	var C = ['AB', 'PH', 'DD', 'KY', 'PA'];
	var B = ['FK', 'G', 'KA', 'ML', 'EH', 'DG', 'TD', 'TR', 'PL', 'EX', 'TQ'];

	function zoneFor(pc) {
		pc = pc.toUpperCase().replace(/\s+/g, '');
		var m = pc.match(/^([A-Z]{1,2})(\d{1,2})/);
		if (!m) { return null; }
		var a = m[1], n = parseInt(m[2], 10);
		if (D.indexOf(a) >= 0) { return 'D'; }
		if (a === 'PO' && n >= 30 && n <= 41) { return 'D'; }
		if (C.indexOf(a) >= 0) { return 'C'; }
		if (B.indexOf(a) >= 0) { return 'B'; }
		if (a === 'SA' && n >= 31 && n <= 73) { return 'B'; }
		return 'A';
	}

	function paint(cls, icon, title, message) {
		box.className = 'pc-result show ' + cls;
		ic.innerHTML = ICON[icon];
		tt.textContent = title;
		msg.textContent = message;
	}

	form.addEventListener('submit', function (e) {
		e.preventDefault();
		var raw = (input.value || '').trim();
		var z = zoneFor(raw);
		var up = raw.toUpperCase();
		if (!z) {
			paint('r-invalid', 'info', 'Enter a valid UK postcode', 'Please include the area and district — for example NG23 or PO12.');
		} else if (z === 'A') {
			paint('r-free', 'ok', 'Free delivery to ' + up, 'Good news — your area qualifies for free kerbside delivery. Choose your preferred delivery date at checkout.');
		} else if (z === 'B') {
			paint('r-surcharge', 'warn', 'We deliver to ' + up + ' · £99', 'Delivery is available to your area with a £99 surcharge, applied at checkout.');
		} else if (z === 'C') {
			paint('r-surcharge', 'warn', 'We deliver to ' + up + ' · £199', 'Delivery is available with a £199 surcharge and typically takes 15–20 working days. Confirmed at checkout.');
		} else {
			paint('r-none', 'no', "Sorry — we can't deliver to " + up, 'Your area is outside our current delivery network. Please contact our team to discuss options.');
		}
	});
})();
