var checker = null;
$(function() {
	if (Modernizr.getusermedia) {
		$("#record-container").removeClass("hidden");
	} else {
		$("#record-container").remove();
	}

	var el = document.getElementById('files');
	var sortable = Sortable.create(el);

	$("#record-phone").keyup(function (e) {
		if (e.keyCode == 13) {
			$("#dial").click();
		}
	});

	$("#dial").click(function() {
		clearInterval(checker);
		var num = $("#record-phone").val(),
				file = num+Date.now();

		$("#record-phone").prop("disabled",true);
		$("#dial").text(_("Dialing..."));
		$.post( "ajax.php", {module: "recordings", command: "dialrecording", extension: num, filename: file}, function( data ) {
			if(data.status) {
				$("#dialer").fadeOut("slow");
				$("#dialer-message").text(_("Recording...")).removeClass("hidden");
				check(num, file);
			} else {
				alert(data.message);
			}
			$("#record-phone").prop("disabled",false);
			$("#dial").text(_("Call!"));
		});
	})

	var check = function(num, file) {
		checker = setInterval(function(){
			$.post( "ajax.php", {module: "recordings", command: "checkrecording", extension: num, filename: file}, function( data ) {
				if(data.status) {
					$("#dialer-message").addClass("hidden");
					$("#dialer").fadeIn("slow");
					clearInterval(checker);
					addFile(data.filename, data.localfilename);
				}
			});
		}, 500);
	}

	$("#jquery_jplayer_1").jPlayer({
		ready: function(event) {
			//TODO: Do conversions on the fly here???
			$(this).jPlayer("setMedia", {
				title: "Bubble",
				m4a: "http://jplayer.org/audio/mp3/Miaow-07-Bubble.mp3",
				oga: "http://jplayer.org/audio/ogg/Miaow-07-Bubble.ogg"
			});
		},
		timeupdate: function(event) {
			$("#jp_container_1").find(".jp-ball").css("left",event.jPlayer.status.currentPercentAbsolute + "%");
		},
		ended: function(event) {
			$("#jp_container_1").find(".jp-ball").css("left","0%");
		},
		swfPath: "http://jplayer.org/latest/dist/jplayer",
		supplied: "mp3, oga",
		wmode: "window",
		useStateClassSkin: true,
		autoBlur: false,
		keyEnabled: true,
		remainingDuration: true,
		toggleDuration: true
	});

	var acontainer = null;
	$('.jp-play-bar').mousedown(function (e) {
		acontainer = $(this).parents(".jp-audio-freepbx");
		updatebar(e.pageX);
	});
	$(document).mouseup(function (e) {
		if (acontainer) {
			updatebar(e.pageX);
			acontainer = null;
		}
	});
	$(document).mousemove(function (e) {
		if (acontainer) {
			updatebar(e.pageX);
		}
	});

	//update Progress Bar control
	var updatebar = function (x) {
		var player = $("#" + acontainer.data("player")),
				progress = acontainer.find('.jp-progress'),
				maxduration = player.data("jPlayer").status.duration,
				position = x - progress.offset().left,
				percentage = 100 * position / progress.width();

		//Check within range
		if (percentage > 100) {
			percentage = 100;
		}
		if (percentage < 0) {
			percentage = 0;
		}

		player.jPlayer("playHead", percentage);

		//Update progress bar and video currenttime
		acontainer.find('.jp-ball').css('left', percentage+'%');
		acontainer.find('.jp-play-bar').css('width', percentage + '%');
		player.jPlayer.currentTime = maxduration * percentage / 100;
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
				var patt = new RegExp(/\.(mp3|wav)$/);
						submit = true;
				$.each(data.files, function(k, v) {
					if(!patt.test(v.name)) {
						submit = false;
						return false;
					}
				});
				if(submit) {
					data.submit();
				} else {
					alert(_("Unsupported file type"));
				}
			},
			dragover: function (e, data) {
			},
			change: function (e, data) {
			},
			done: function (e, data) {
				if(data.result.status) {
					addFile(data.result.filename, data.result.localfilename);
				} else {
					alert(data.result.message);
				}
				$("#upload-progress .progress-bar").css("width", "0%");
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

$(document).on("click", "#files .delete-file", function() {
	$(this).parents(".file").fadeOut("slow", function() {
		$(this).remove();
		if(!$("#files .file").length) {
			$("#file-alert").removeClass("hidden");
		}
	})
});

$(document).on("click", "#files .play", function() {
	$(this).toggleClass("active");
});

function addFile(file, path) {
	$("#file-alert").addClass("hidden");
	$("#files").append('<li class="file" data-path="'+path+'"><i class="fa fa-play play"></i> '+file+'<i class="fa fa-times-circle pull-right text-danger delete-file"></i></li>');
}

function linkFormatter(value, row, index){
	var html = '<a href="?display=recordings&action=edit&id='+row.id+'"><i class="fa fa-pencil"></i></a>';
	html += '&nbsp;<a href="?display=recordings&action=delete&id='+row.id+'" class="delAction"><i class="fa fa-trash"></i></a>';
	return html;
}
