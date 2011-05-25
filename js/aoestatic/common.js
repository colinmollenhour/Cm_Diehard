/**
 * Send ajax request to the Magento store in order to insert dynamic content into the
 * static page delivered from Varnish
 *
 * @author Fabrizio Branca
 */
$.noConflict();
jQuery(document).ready(function($) {

	var data = { getBlocks: {} };

	// add placeholders
	$('.placeholder').each(function() {
		data.getBlocks[$(this).attr('id')] = $(this).attr('rel');
	});

	// add current product
	if (typeof CURRENTPRODUCTID !== 'undefined' && CURRENTPRODUCTID) {
		data.currentProductId = CURRENTPRODUCTID;
	}

	// E.T. phone home
	$.get(
		AJAXHOME_URL,
		data,
		function (data) {
			for(var id in data.blocks) {
				$('#' + id).html(data.blocks[id]);
			}
			$.cookie('frontend', data.sid, { path: '/' });
		},
		'json'
	);

});