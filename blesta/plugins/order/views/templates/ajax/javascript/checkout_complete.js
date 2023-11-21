	$(document).ready(function() {
		$(".payment_buttons form").first().delay(5000).queue(function() {
            $(this).submit();
        });
	});