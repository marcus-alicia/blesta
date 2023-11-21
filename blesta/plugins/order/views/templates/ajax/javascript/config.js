	$(document).ready(function() {
		// Fetch package options
		fetchPackageOptions();
		$('[name="pricing_id"]').change(function() {
			fetchPackageOptions();
		});
	});