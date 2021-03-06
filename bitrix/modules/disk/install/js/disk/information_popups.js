BX.namespace("BX.Disk.InformationPopups");
BX.Disk.InformationPopups = (function ()
{
	return {
		getContentWarningLockedDocument: function (data)
		{
			return '<div class="disk-locked-document-popup">' +
					'<div class="disk-locked-document-popup-container">' +
						'<div class="disk-locked-document-popup-img-container">' +
							'<div class="disk-locked-document-popup-img"></div>' +
						'</div>' +
						'<div class="disk-locked-document-popup-content">' +
							'<h3 class="disk-locked-document-popup-content-title">' + BX.message('DISK_JS_INF_POPUPS_LOCKED_DOC_TITLE')+ '</h3>' +
							'<div class="disk-locked-document-popup-content-info">' +
								'<span class="disk-locked-document-popup-content-text">' + BX.message('DISK_JS_INF_POPUPS_LOCKED_DOC_WAS_LOCKED_FORKED_COPY').replace('#LINK#', data.link) + '</span>' +
							'</div>' +
							'<a href="#" class="webform-button webform-button-create disk-locked-document-popup-content-button">' + BX.message('DISK_JS_INF_POPUPS_LOCKED_DOC_GO_TO_FILE') + '</a>' +
						'</div>' +
					'</div>' +
				'</div>'
			;
		},
		getContentWarningLockedDocumentDesktop: function (data)
		{
			return '<div class="disk-locked-document-popup">' +
					'<div class="disk-locked-document-popup-desktop-container">' +
						'<div class="disk-locked-document-popup-desktop-img-container">' +
							'<div class="disk-locked-document-popup-desktop-img"></div>' +
						'</div>' +
						'<div class="disk-locked-document-popup-desktop-content">' +
							'<h3 class="disk-locked-document-popup-desktop-content-title">' + BX.message('DISK_JS_INF_POPUPS_LOCKED_DOC_TITLE')+ '</h3>' +
							'<div class="disk-locked-document-popup-desktop-content-info">' +
								'<span class="disk-locked-document-popup-desktop-content-text">' + BX.message('DISK_JS_INF_POPUPS_LOCKED_DOC_WAS_LOCKED_FORKED_COPY').replace('#LINK#', data.link) + '</span>' +
							'</div>' +
							'<span onclick="document.location=\'' + data.link + '\'" class="popup-window-button popup-window-button-accept disk-locked-document-popup-desktop-content-button">' + BX.message('DISK_JS_INF_POPUPS_LOCKED_DOC_GO_TO_FILE') + '</span>' +
						'</div>' +
					'</div>' +
				'</div>';
		},
		showWarningLockedDocument: function (data)
		{
			(new BX.PopupWindow('testy', null, {
				content: BX.create('div', {html: BX.Disk.InformationPopups.getContentWarningLockedDocument({
					link: data.link
				})}),
				autoHide: true,
				lightShadow: true,
				closeIcon: {right: "20px", top: "10px"},
				events: {
					onPopupClose: function ()
					{
						this.destroy();
					}
				},
				buttons: []
			})).show();
		}
	};
})();