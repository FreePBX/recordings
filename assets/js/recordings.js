$(function() {
	$(".autocomplete-combobox").chosen({search_contains: true, no_results_text: "No Recordings Found"});
})

function linkFormatter(value, row, index){
	var html = '<a href="?display=recordings&action=edit&id='+row.id+'"><i class="fa fa-pencil"></i></a>';
	html += '&nbsp;<a href="?display=recordings&action=delete&id='+row.id+'" class="delAction"><i class="fa fa-trash"></i></a>';
	return html;
}
