$(function() {
	if (Modernizr.getusermedia) {
		$("#record-container").removeClass("hidden");
	} else {
		$("#record-container").remove();
	}

	$("#jquery_jplayer_1").jPlayer({
		ready: function(event) {
			$(this).jPlayer("setMedia", {
				title: "Bubble",
				m4a: "http://jplayer.org/audio/mp3/Miaow-07-Bubble.mp3",
				oga: "http://jplayer.org/audio/ogg/Miaow-07-Bubble.ogg"
			});
		},
		timeupdate: function(event) {
			$("#jp_container_1 .jp-ball").css("left",event.jPlayer.status.currentPercentAbsolute + "%");
		},
		swfPath: "http://jplayer.org/latest/dist/jplayer",
		supplied: "mp3, oga",
		wmode: "window",
		useStateClassSkin: true,
		autoBlur: false,
		smoothPlayBar: true,
		keyEnabled: true,
		remainingDuration: true,
		toggleDuration: true
	});

	var timeDrag = false; /* Drag status */
	$('.jp-play-bar').mousedown(function (e) {
		timeDrag = true;
		updatebar(e.pageX);
	});
	$(document).mouseup(function (e) {
		if (timeDrag) {
			timeDrag = false;
			updatebar(e.pageX);
		}
	});
	$(document).mousemove(function (e) {
		if (timeDrag) {
			updatebar(e.pageX);
		}
	});

	//update Progress Bar control
	var updatebar = function (x) {

		var progress = $('.jp-progress');
		var maxduration = $("#jquery_jplayer_1").data("jPlayer").status.duration;; //audio duration
		var position = x - progress.offset().left; //Click pos
		var percentage = 100 * position / progress.width();

		//Check within range
		if (percentage > 100) {
			percentage = 100;
		}
		if (percentage < 0) {
			percentage = 0;
		}

		$("#jquery_jplayer_1").jPlayer("playHead", percentage);

		//Update progress bar and video currenttime
		$('.jp-ball').css('left', percentage+'%');
		$('.jp-play-bar').css('width', percentage + '%');
		$("#jquery_jplayer_1").jPlayer.currentTime = maxduration * percentage / 100;
	};

	$(document).bind('drop dragover', function (e) {
		e.preventDefault();
	});
	$('#dropzone').on('dragleave drop', function (e) {
		$(this).removeClass("activate");
	});
	$('#dropzone').on('dragover', function (e) {
		$(this).addClass("activate");
	});
	$(".autocomplete-combobox").chosen({search_contains: true, no_results_text: _("No Recordings Found")});
	$('#fileupload').fileupload({
			dataType: 'json',
			dropZone: $("#dropzone"),
			add: function (e, data) {
				console.log(data);
				data.submit();
			},
			drop: function (e, data) {
				console.log(data.name);
				return false;
			},
			dragover: function (e, data) {
			},
			change: function (e, data) {
			},
			done: function (e, data) {
				console.log(data.result.files);
			},
			progressall: function (e, data) {
				var progress = parseInt(data.loaded / data.total * 100, 10);
				$("#upload-progress .progress-bar").css("width", progress+"%");
			},
			fail: function (e, data) {
			},
			always: function (e, data) {
			}
	});
})

function linkFormatter(value, row, index){
	var html = '<a href="?display=recordings&action=edit&id='+row.id+'"><i class="fa fa-pencil"></i></a>';
	html += '&nbsp;<a href="?display=recordings&action=delete&id='+row.id+'" class="delAction"><i class="fa fa-trash"></i></a>';
	return html;
}
