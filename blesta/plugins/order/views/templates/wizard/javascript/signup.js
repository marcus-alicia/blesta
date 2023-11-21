$(document).ready(function() {

	$("#country").change(function() {
		getStates($(this));
	});
	
	if ($("#username_type_email").attr("checked"))
		$(".username").hide();
	
	// Show/hide the username input
	$("#username_type_username").click(function() {
		$(".username").show();
	});
	$("#username_type_email").click(function() {
		$(".username").hide();
	});
	
	// Hide all swapable section by default
	$(".option_section").hide();
	
	// Show any swapable section that's currently active
	var option_section = $("input[name='action']:checked").val();
	$("." + option_section + "_form").show();
	
	// Show the swapable section when selected
	$("input[name='action']").change(function() {
		$(".option_section").hide();
		$("." + $(this).val() + "_form").show();
	});
	
	$("#order_logout").submit(function(event) {
		logout($(this));
		event.preventDefault();
	});
	$("#order_login").submit(function(event) {
		login($(this));
		event.preventDefault();
	});
	$("#order_signup").submit(function(event) {
		signup($(this));
		event.preventDefault();
	});
});