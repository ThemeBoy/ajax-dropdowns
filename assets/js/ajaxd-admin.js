jQuery(document).ready(function($){

	// Chosen select
	$(".chosen-select").chosen({
		allow_single_deselect: true,
		single_backstroke_delete: false
	});

	// Add post to group
	$(".ajaxd-posts").on("change", function(evt, params) {
		post_id = params.selected;
		$selected = $(this).find("option:selected");
		$table = $(".ajaxd-posts-table");
		$table.find(" > tbody tr.ajaxd-placeholder").hide();
		$table.find(" > tbody:last").append("<tr><td class=\"icon\"><span class=\"dashicons dashicons-menu post-state-format\"></span></td><td><input type=\"hidden\" name=\"ajax_post[]\" value=\""+post_id+"\">"+$selected.text()+"</td><td>"+$selected.data("post-type")+"</td><td><a href=\"#\" class=\"dashicons dashicons-no-alt ajaxd-delete\"></a></td></tr>");
		$(this).val("").trigger("chosen:updated");
	});

	// Make posts table sortable and posts removable
	$(".ajaxd-posts-table > tbody").sortable({
		axis: "y"
	}).on("click", ".ajaxd-delete", function() {
		$(this).closest("tr").remove();
		$table = $(".ajaxd-posts-table");
		if ( $table.find(" > tbody tr:visible").length == 0 ) $table.find(" > tbody tr.ajaxd-placeholder").show();
	});
});
