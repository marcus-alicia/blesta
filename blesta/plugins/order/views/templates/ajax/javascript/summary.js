		$(document).ready(function() {
			resetPaymentMethod($(".payment_type:checked,.gateway:checked"));
			$(".payment_type,.gateway").change(function() {
				resetPaymentMethod($(this));
			});

			function resetPaymentMethod(elem) {
				if (elem.hasClass("gateway")) {
					$(".payment_type").prop("checked", false);
				}
				else {
					$(".gateway").prop("checked", false);
				}
			}

			$("#applycoupon").submit(function(event) {
        if ($(this).blestaDisableFormSubmission($(this))) {
          $(this).blestaRequest('POST', $(this).attr('action'), $(this).serialize(),
            function(data) {
              if (data.error) {
                $("#coupon_box .input-group input").removeClass("is-valid").addClass("is-invalid");
                $("#coupon_box .input-group .btn").removeClass("btn-success").addClass("btn-danger");
              }
              else {
                var success_message = (data.success ? data.success : "");

                $(this).blestaRequest('GET', base_uri + 'order/summary/index/' +  order_label, null,
                  function(data) {
                    $("#summary_section").replaceWith(data);

                    if (success_message.length > 0) {
                      $("#coupon_box .input-group input").removeClass("is-invalid").addClass("is-valid");
                      $("#coupon_box .input-group .btn").removeClass("btn-danger").addClass("btn-success");
                      fetchSummary();
                    }
                  }
                );
              }
            },
            null,
            {dataType: 'json', complete: function() { $("#applycoupon").blestaEnableFormSubmission($("#applycoupon")); }}
          );
        }

				event.preventDefault();
        return false;
			});

		});