	// Fetch signup/login
	fetchSignup();

	function fetchSignup() {
		$.ajax({
			type: 'GET',
			url: base_uri + 'order/signup/index/' + order_label,
			success: function(data) {
				$("#create-account").html(data);
				setSubmitButtonGroup();
			},
			beforeSend: function() {
				$("#create-account").append($(this).blestaLoadingDialog());
			},
			complete: function() {
				$(".loading_container", $("#create-account")).remove();
			},
			dataType: 'json'
		});
	}

	function logout(elem) {
		$.ajax({
			method: $(elem).attr('method'),
			data: $(elem).serialize(),
			url: $(elem).attr('action'),
			success: function(data) {
				$.ajax({
					url: $("#signup_url").val(),
					success: function(data) {
						// Reload
						window.location.replace(window.location.href);
					},
					beforeSend: function() {
						$("#create-account").append($(elem).blestaLoadingDialog());
					},
					complete: function() {
						$(".loading_container", $("#create-account")).remove();
					},
					dataType: 'json'
				});
			}
		});
	}

	function login(elem) {
		$.ajax({
			method: $(elem).attr('method'),
			data: $(elem).serialize(),
			url: $(elem).attr('action'),
			success: function(data) {
				if (data.error)
					$("#message_section").html(data.error);
				else {
					$.ajax({
						url: $("#order_signup").attr('action'),
						success: function(data) {
							// Reload
							window.location.replace(window.location.href);
						},
						dataType: 'json'
					});
				}
			},
			beforeSend: function() {
				$("#create-account").append($(elem).blestaLoadingDialog());
			},
			complete: function() {
        // Re-enable the submit buttons
        $(document).blestaEnableFormSubmission(elem);

				$(".loading_container", $("#create-account")).remove();
			},
			dataType: 'json'
		});
	}

	function signup(elem) {
		$.ajax({
			method: $(elem).attr('method'),
			data: $(elem).serialize(),
			url: $(elem).attr('action'),
			success: function(data) {
				$("#create-account").html(data);

				// If logout button exists, refresh cart
				if ($("#order_logout").length > 0) {
					// Reload to refresh cart listing
					window.location.replace(window.location.href);
				}
			},
			beforeSend: function() {
				$("#create-account").append($(elem).blestaLoadingDialog());
			},
			complete: function() {
				$(".loading_container", $("#create-account")).remove();
			},
			dataType: 'json'
		});
	}

	function getStates(elem) {
		$(this).blestaRequest("get", base_uri + 'order/signup/getstates/' + order_label + "/" + $(elem).val(), null, function(data) {
				// Remove all existing items
				$("option", "#state").remove();

				// Append all new items
                $.each($(this).blestaSortObject(data), function(index, item) {
                    $("#state").append( new Option(item.value, item.key));
                });
			},
			null,
			{dataType: "json"}
		);
	}

	function setSubmitButtonGroup(elem) {
		elem = elem ? elem : $('#package_config input[name^="addon["]:checked');
		if (elem.val() != "") {
			$("#queue_empty_btns").hide();
			$("#queue_nonempty_btns").show();
		}
		else {
			$("#queue_empty_btns").show();
			$("#queue_nonempty_btns").hide();
		}
	}