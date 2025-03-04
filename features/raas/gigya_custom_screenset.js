(function ($) {
	$(function () {
		var processFieldMapping = function() {
			var options = {
				url: gigyaParams.ajaxurl,
				type: 'POST',
				dataType: 'json',
				data: {
					data: {},
					action: 'gigya_process_field_mapping'
				}
			};

			var req = $.ajax(options);
			req.done(function(data) {
				if (data.success) {
					/* Insert field mapping success behavior here */
				}
			});
		};

		$('.gigya-screenset-widget-outer-div').each(function() {
			if ($(this).attr('data-machine-name') !== undefined) {
				var varName = '_gig_' + $(this).attr('data-machine-name');
				var gigyaScreenSetParams = window[varName];

				/**
				 * @var array gigyaScreenSetParams
				 * @property gigyaScreenSetParams.link_id
				 * @property gigyaScreenSetParams.screenset_id
				 * @property gigyaScreenSetParams.mobile_screenset_id
				 * @property gigyaScreenSetParams.container_id
				 * @property gigyaScreenSetParams.is_sync_data
				 */
				if (typeof(gigyaScreenSetParams) !== 'undefined') {
					if (gigyaScreenSetParams.mobile_screenset_id === undefined)
						gigyaScreenSetParams.mobile_screenset_id = gigyaScreenSetParams.screenset_id;

					var screenSetParams = {
						screenSet: gigyaScreenSetParams.screenset_id,
						mobileScreenSet: gigyaScreenSetParams.mobile_screenset_id
					};

					if (gigyaScreenSetParams.is_sync_data) {
						screenSetParams['onAfterSubmit'] = processFieldMapping;
					}

					$('#' + gigyaScreenSetParams.link_id).on('click', function (e) {
						e.preventDefault();

						gigya.accounts.showScreenSet(screenSetParams);
					});

					if (gigyaScreenSetParams.type === 'embed') {
						screenSetParams['containerID'] = gigyaScreenSetParams.container_id;
						gigya.accounts.showScreenSet(screenSetParams);
					}
				}
			}
		});
	});
})(jQuery);