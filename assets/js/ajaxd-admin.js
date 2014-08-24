jQuery(document).ready(function($){

	// Chosen select
	$(".chosen-select").chosen({
		allow_single_deselect: true,
		single_backstroke_delete: false
	});

	// Apply chosen class for each post in dropdown
	$(".ajaxd-posts-table input[name=\"ajax_post[]\"]").each(function() {
		$(".ajaxd-posts option[value=\""+$(this).val()+"\"]").prop("disabled", true);
		$(".ajaxd-posts").trigger("chosen:updated");
	});

	// Add post to group
	$(".ajaxd-posts").on("change", function(evt, params) {
		post_id = params.selected;
		$selected = $(this).find("option:selected");
		$table = $(".ajaxd-posts-table");
		$table.find(" > tbody tr.ajaxd-placeholder").hide();
		if ( $selected.val() == 0 ) {
			$selected.siblings("option:enabled").each(function() {
				$table.find(" > tbody:last").append("<tr><td class=\"icon\"><span class=\"dashicons dashicons-menu post-state-format\"></span></td><td><input type=\"hidden\" name=\"ajax_post[]\" value=\""+$(this).val()+"\">"+$(this).text()+"</td><td>"+$(this).data("post-type")+"</td><td><a href=\"#\" class=\"dashicons dashicons-no-alt ajaxd-delete\"></a></td></tr>");
				$(this).prop("disabled", true);
			});
		} else {
			$table.find(" > tbody:last").append("<tr><td class=\"icon\"><span class=\"dashicons dashicons-menu post-state-format\"></span></td><td><input type=\"hidden\" name=\"ajax_post[]\" value=\""+post_id+"\">"+$selected.text()+"</td><td>"+$selected.data("post-type")+"</td><td><a href=\"#\" class=\"dashicons dashicons-no-alt ajaxd-delete\"></a></td></tr>");
			$selected.siblings("[value=0]").each(function() {
				console.log($(this).siblings("option:enabled").length);
				$(this).prop("disabled", $(this).siblings("option:enabled").length <= 1);
			});
		}
		$selected.prop("disabled", true);
		$(this).val("").trigger("chosen:updated");
	});

	// Make posts table sortable and posts removable
	$(".ajaxd-posts-table > tbody").sortable({
		axis: "y"
	}).on("click", ".ajaxd-delete", function() {
		$row = $(this).closest("tr");
		$id = $row.find("input[name=\"ajax_post[]\"]").val();
		$(".ajaxd-posts option[value=\""+$id+"\"]").prop("disabled", false).siblings("[value=0]").prop("disabled", false);
		$(".ajaxd-posts").trigger("chosen:updated");
		$row.remove();
		$table = $(".ajaxd-posts-table");
		if ( $table.find(" > tbody tr:visible").length == 0 ) $table.find(" > tbody tr.ajaxd-placeholder").show();
	});
});
