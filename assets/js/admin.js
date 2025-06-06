/* eslint-disable no-var, prefer-const, object-shorthand */
jQuery( document ).ready( function( $ ) {
	var $form = $( '#gemini-link-importer-form' );
	var $textarea = $( '#gemini-link-importer-textarea' );
	var $submitButton = $( '#gemini-link-importer-submit' );
	var $adminNotices = $( '#gemini-link-importer-admin-notices' );

	console.log( 'Gemini Link Importer: Admin script initialized.' );

	function displayAdminNotice( type, content ) {
		console.log( 'Gemini Link Importer: Displaying admin notice of type "' + type + '".' );
		$adminNotices.html(
			'<div class="gemini-link-importer-notice notice-' +
				type +
				' is-dismissible">' +
				content +
				'<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>' +
				'</div>'
		);
		$( '.gemini-link-importer-notice .notice-dismiss' ).on( 'click', function() {
			console.log( 'Gemini Link Importer: Admin notice dismissed.' );
			$( this )
				.closest( '.gemini-link-importer-notice' )
				.remove();
		} );
	}

	function clearAdminNotices() {
		console.log( 'Gemini Link Importer: Clearing previous admin notices.' );
		$adminNotices.empty();
	}

	$form.on( 'submit', function( e ) {
		e.preventDefault();
		console.log( 'Gemini Link Importer: Form submission initiated.' );
		clearAdminNotices();

		var linksContent = $textarea.val().trim();
		console.log( 'Gemini Link Importer: Textarea content (trimmed):', linksContent );

		if ( ! linksContent ) {
			console.log( 'Gemini Link Importer: Textarea is empty. Preventing AJAX submission.' );
			displayAdminNotice( 'info', '<p>' + geminiLinkImporterAjax.strings.empty_textarea + '</p>' );
			return;
		}

		console.log( 'Gemini Link Importer: Disabling submit button and showing spinner.' );
		$submitButton
			.prop( 'disabled', true )
			.addClass( 'is-loading' )
			.text( geminiLinkImporterAjax.strings.importing_links );

		var ajaxData = {
			action: 'gemini_import_links',
			nonce: geminiLinkImporterAjax.nonce,
			links: linksContent,
		};
		console.log( 'Gemini Link Importer: AJAX request data:', ajaxData );

		$.ajax( {
			url: geminiLinkImporterAjax.ajaxurl,
			type: 'POST',
			data: ajaxData,
			dataType: 'json',
			success: function( response ) {
				console.log( 'Gemini Link Importer: AJAX success callback received.', response );
				$textarea.val( '' );
				console.log( 'Gemini Link Importer: Textarea cleared.' );

				if ( response.success ) {
					var results = response.data;
					var importedCount = results.imported.length;
					var failedCount = results.failed.length;
					var newCategoriesCount = results.new_categories.length;

					console.log( 'Gemini Link Importer: Import Results ->' );
					console.log( '  Imported:', importedCount, 'links', results.imported );
					console.log( '  Failed:', failedCount, 'links', results.failed );
					console.log( '  New Categories:', newCategoriesCount, 'categories', results.new_categories );
					console.log( '  Total Processed Lines:', results.total_processed );

					var message = '';
					var noticeType = 'info';

					if ( importedCount > 0 && failedCount === 0 ) {
						console.log( 'Gemini Link Importer: All valid links imported successfully.' );
						message +=
							'<p><strong>' +
							geminiLinkImporterAjax.strings.overall_success.replace(
								'%d',
								importedCount
							) +
							'</strong></p>';
						noticeType = 'success';
					} else if ( importedCount > 0 && failedCount > 0 ) {
						console.log( 'Gemini Link Importer: Mixed results - some imported, some failed.' );
						message +=
							'<p><strong>' +
							geminiLinkImporterAjax.strings.partial_success
								.replace( '%d', importedCount )
								.replace( '%d', failedCount )
								.replace( '%d', newCategoriesCount ) +
							'</strong></p>';
						noticeType = 'warning';
					} else if ( importedCount === 0 && failedCount > 0 && results.total_processed > 0 ) {
						console.log( 'Gemini Link Importer: All submitted links failed.' );
						message +=
							'<p><strong>' +
							geminiLinkImporterAjax.strings.overall_failure.replace(
								'%d',
								failedCount
							) +
							'</strong></p>';
						noticeType = 'error';
					} else if ( results.total_processed === 0 ) {
						console.log( 'Gemini Link Importer: No valid lines found to process from input.' );
						message +=
							'<p><strong>' +
							geminiLinkImporterAjax.strings.no_valid_links +
							'</strong></p>';
						noticeType = 'info';
					}

					if ( importedCount > 0 ) {
						console.log( 'Gemini Link Importer: Appending successfully imported list.' );
						message += '<h3>' + geminiLinkImporterAjax.strings.successfully_added.replace( '%d', importedCount ) + '</h3>';
						message += '<ul>';
						$.each( results.imported, function( index, url ) {
							message += '<li>' + url + '</li>';
						} );
						message += '</ul>';
					}

					if ( failedCount > 0 ) {
						console.log( 'Gemini Link Importer: Appending failed links list.' );
						message += '<h3>' + geminiLinkImporterAjax.strings.failed_to_add.replace( '%d', failedCount ) + '</h3>';
						message += '<ul>';
						$.each( results.failed, function( index, item ) {
							message += '<li>' + item.url + ' - ' + item.reason + '</li>';
						} );
						message += '</ul>';
					}

					if ( newCategoriesCount > 0 ) {
						console.log( 'Gemini Link Importer: Appending new categories list.' );
						message += '<h3>' + geminiLinkImporterAjax.strings.new_categories.replace( '%d', newCategoriesCount ) + '</h3>';
						message += '<ul>';
						$.each( results.new_categories, function( index, catName ) {
							message += '<li>' + catName + '</li>';
						} );
						message += '</ul>';
					}

					displayAdminNotice( noticeType, message );
				} else {
					console.error( 'Gemini Link Importer: AJAX response indicates failure.', response );
					displayAdminNotice(
						'error',
						'<p><strong>' + ( geminiLinkImporterAjax.strings.message || 'An unknown error occurred on the server.' ) + '</strong></p>'
					);
				}
			},
			error: function( jqXHR, textStatus, errorThrown ) {
				console.error( 'Gemini Link Importer: AJAX Error ->', textStatus, errorThrown, jqXHR );
				displayAdminNotice(
					'error',
					'<p><strong>' +
						geminiLinkImporterAjax.strings.overall_failure.replace( '%d', 0 ) +
						'</strong></p><p>An unexpected error occurred during the request. Please try again or check the browser console for details.</p>'
				);
			},
			complete: function() {
				console.log( 'Gemini Link Importer: AJAX request completed. Re-enabling submit button.' );
				$submitButton
					.prop( 'disabled', false )
					.removeClass( 'is-loading' )
					.text( geminiLinkImporterAjax.strings.import_links );
			},
		} );
	} );
} );
