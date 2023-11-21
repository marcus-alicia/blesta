		$(document).ready(function() {
			$("#applycoupon").submit(function(event) {
        if ($(this).blestaDisableFormSubmission($(this))) {
          $(this).blestaRequest('POST', $(this).attr('action'), $(this).serialize(),
            function(data) {
              if (data.error) {
                $("#coupon_box .input-group .form-control").removeClass("is-valid").addClass("is-invalid");
                $("#coupon_box .input-group .btn").removeClass("btn-secondary").addClass("btn-danger");
              }
              else {
                fetchSummary();
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