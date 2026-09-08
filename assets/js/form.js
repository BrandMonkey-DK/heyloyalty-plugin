( function () {
	'use strict';

	function onSubmit( event ) {
		event.preventDefault();

		var form = event.currentTarget;
		var button = form.querySelector( '.hlnl-submit' );
		var message = form.querySelector( '.hlnl-message' );
		var label = button ? button.textContent : '';

		if ( form.dataset.busy === '1' ) {
			return;
		}

		if ( ! form.checkValidity() ) {
			form.reportValidity();
			return;
		}

		form.dataset.busy = '1';
		if ( button ) {
			button.disabled = true;
			button.textContent = HLNL_Config.i18n.sending;
		}
		message.textContent = '';
		message.className = 'hlnl-message';

		var payload = {
			list_id: form.dataset.listId,
			firstname: form.querySelector( '[name="firstname"]' ).value,
			lastname: form.querySelector( '[name="lastname"]' ).value,
			email: form.querySelector( '[name="email"]' ).value,
			opt_in: form.dataset.optIn === '1',
			skip_opt_in: form.dataset.skipOptIn === '1',
			hlnl_hp: form.querySelector( '[name="hlnl_hp"]' ).value
		};

		fetch( HLNL_Config.endpoint, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': HLNL_Config.nonce
			},
			body: JSON.stringify( payload )
		} )
			.then( function ( response ) {
				return response.json().then( function ( data ) {
					return { ok: response.ok, data: data };
				} );
			} )
			.then( function ( result ) {
				if ( result.ok && result.data && result.data.success ) {
					message.className = 'hlnl-message hlnl-message--success';
					message.textContent = form.dataset.success;
					form.reset();
					return;
				}

				message.className = 'hlnl-message hlnl-message--error';
				message.textContent = ( result.data && result.data.message ) || HLNL_Config.i18n.error;
			} )
			.catch( function () {
				message.className = 'hlnl-message hlnl-message--error';
				message.textContent = HLNL_Config.i18n.error;
			} )
			.finally( function () {
				form.dataset.busy = '0';
				if ( button ) {
					button.disabled = false;
					button.textContent = label;
				}
			} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		var forms = document.querySelectorAll( '.hlnl-form' );
		Array.prototype.forEach.call( forms, function ( form ) {
			form.addEventListener( 'submit', onSubmit );
		} );
	} );
} )();
