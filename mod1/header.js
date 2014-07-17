script_ended = 0;

function jumpToUrl(URL) {
	document.location = URL;
}

function view_order(order) {

	$("#detailed").val(order);
	$("#docForm").submit();

}

$(document).ready(function() {

	var detailed = parseInt($("#detailed").val());
	if (detailed > 0)
		$(".typo3-usersettings .td-label").each(function() {
			if (detailed == parseInt($(this).html())) {
				$(this).parent().addClass("active");
			}
		});

	$('.typo3-usersettings TR').hover(function() {
		$(this).addClass('highlight');
	}, function() {
		$(this).removeClass('highlight');
	});

	$('.td-image').hover(function() {
		$(this).addClass('showImage');
	}, function() {
		$(this).removeClass('showImage');
	});

});



function statusChanged() {

	var currentStatus = $('#tx_shopmanager_f2_statusCurrent').val();
	var newStatus = $('#tx_shopmanager_f2_statusSelect').val();
	if (currentStatus != newStatus) {
		$('#tx_shopmanager_f2_status').slideDown();
	} else {
		$('#tx_shopmanager_f2_status').slideUp();
	}

}



function statusChange() {

	var newStatus = $('#tx_shopmanager_f2_statusSelect').val();
	var comment = $('#tx_shopmanager_f2_statusComment').val();
	$('#opt1').val('statusChange');
	$('#opt2').val(newStatus);
	$('#opt3').val(comment);
	$("#docForm").submit();

}
