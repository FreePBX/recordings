var checker = null,
		soundList = {},
		language = '';
$(function() {
	if (Modernizr.getusermedia) {
		$("#record-container").removeClass("hidden");
	} else {
		$("#record-container").remove();
	}

	language = $("#language").val();
	$("#language").change(function() {
		convertList(language);
		language = $("#language").val();
		generateList(language);
		$(".language").text($(this).find("option:selected").text());
	});

	$("#systemrecording").chosen({search_contains: true, no_results_text: _("No Recordings Found"), placeholder_text_single: _("Select a system recording")});

	$("#systemrecording").on('change', function(evt, params) {
		var rec = $(this).val(),
				info = systemRecordings[rec],
				languages = [],
				paths = {};

		for (var key in info.languages) {
			if (info.languages.hasOwnProperty(key)) {
				var l = info.languages[key];
				languages.push(l);
				paths[l] = info.paths[l];
			}
		}
		addFile(info.name, paths, languages, (languages.indexOf(language) >= 0), true)
		$('#systemrecording').val("");
		$('#systemrecording').trigger('chosen:updated');
	});

	$(".codec").change(function() {
		if(!$(".codec").is(":checked")) {
			alert(_("At least one codec must be checked"));
			$(this).prop("checked", true);
		}
	})

	var el = document.getElementById('files');
	var sortable = Sortable.create(el);

	$("#record-phone").keyup(function (e) {
		if (e.keyCode == 13) {
			$("#dial-phone").click();
		}
	});

	var recording = false,
			recorder = null,
			recordTimer = null,
			startTime = null,
			soundBlob = {};
	$(".jp-play").click(function() {
		if(recording) {
			$(".record").click();
		}
	})
	$(".record").click(function() {
		$("#jquery_jplayer_1").jPlayer( "clearMedia" );
		$(this).parents(".jp-controls").toggleClass("recording");
		var counter = $("#jp_container_1 .jp-duration"),
				title = $("#jp_container_1 .jp-title");
		if (recording) {
			clearInterval(recordTimer);
			title.html('<button id="saverecording" class="btn btn-primary" type="button">'+_("Save Recording")+'</button><button id="deleterecording" class="btn btn-primary" type="button">'+_("Delete Recording")+'</button>');
			$("#saverecording").click(function() {
				$("#jquery_jplayer_1").jPlayer( "clearMedia" );
				$("#browser-recorder-save").removeClass("hidden").addClass("in");
				$("#browser-recorder").removeClass("in").addClass("hidden");
				$("#save-recorder-input").focus();
				$("#save-recorder-input").blur(function(event) {
					if(event.relatedTarget.id != "save-recorder") {
						alert(_("Please enter a valid name and save"));
						$(this).focus();
					}
				});
				$("#save-recorder").on("click", function() {
					if($("#save-recorder-input").val() === "") {
						alert(_("Please enter a valid name and save"));
						$("#save-recorder-input").focus();
						return;
					}
					if(sysRecConflict($("#save-recorder-input").val())) {
						if(!confirm(_("A system recording with this name already exists for this language. Do you want to overwrite it?"))) {
							return;
						}
					}
					$(this).off("click");
					$(this).text(_("Saving..."));
					$(this).prop("disabled", true);
					$("#save-recorder-input").prop("disabled", true);
					title.text(_("Uploading..."));
					var data = new FormData();
					data.append("file", soundBlob);
					$.ajax({
						type: "POST",
						url: "ajax.php?module=recordings&command=savebrowserrecording&filename=" + encodeURIComponent($("#save-recorder-input").val()),
						xhr: function() {
							$("#browser-recorder-progress").removeClass("hidden").addClass("in");
							var xhr = new window.XMLHttpRequest();
							//Upload progress
							xhr.upload.addEventListener("progress", function(evt) {
								if (evt.lengthComputable) {
									var percentComplete = evt.loaded / evt.total,
									progress = Math.round(percentComplete * 100);
									$("#browser-recorder-progress .progress-bar").css("width", progress + "%");
									if(progress == 100) {
										$("#browser-recorder-progress").addClass("hidden").removeClass("in");
										$("#browser-recorder-progress .progress-bar").css("width", "0%");
									}
								}
							}, false);
							return xhr;
						},
						data: data,
						processData: false,
						contentType: false,
						success: function(data) {
							if(data.status) {
								var paths = {};
								paths[language] = data.localfilename;
								addFile(data.filename, paths, [language], true, false);
							}
							$("#browser-recorder-save").addClass("hidden").removeClass("in");
							$("#browser-recorder").addClass("in").removeClass("hidden");
							$("#save-recorder-input").val("");
							$("#save-recorder-input").prop("disabled", false);
							$("#save-recorder").text(_("Save!"));
							$("#save-recorder").prop("disabled", false);
							title.html(_("Hit the red record button to start recording from your browser"));
						},
						error: function() {
						}
					});
				});
			});
			$("#deleterecording").click(function() {
				$("#jquery_jplayer_1").jPlayer( "clearMedia" );
				title.html(_("Hit the red record button to start recording from your browser"));
			});
			recorder.stop();
			recorder.exportWAV(function(blob) {
				soundBlob = blob;
				var url = (window.URL || window.webkitURL).createObjectURL(blob);
				$("#jquery_jplayer_1").jPlayer( "setMedia", {
					wav: url
				});
			});
			recording = false;
		} else {
			window.AudioContext = window.AudioContext || window.webkitAudioContext;

			var context = new AudioContext();

			var gUM = Modernizr.prefixed("getUserMedia", navigator);
			gUM({ audio: true }, function(stream) {
				var mediaStreamSource = context.createMediaStreamSource(stream);
				recorder = new Recorder(mediaStreamSource,{ workerPath: "assets/recordings/js/recorderWorker.js" });
				recorder.record();
				startTime = new Date();
				recordTimer = setInterval(function () {
					var mil = (new Date() - startTime);
					var temp = (mil / 1000);
					var min = ("0" + Math.floor((temp %= 3600) / 60)).slice(-2);
					var sec = ("0" + Math.round(temp % 60)).slice(-2);
					counter.text(min + ":" + sec);
				}, 1000);
				title.text(_("Recording..."));
				recording = true;
			}, function(e) {
				alert(_("Your Browser Blocked The Recording, Please check your settings"));
				recording = false;
			});
		}
	});

	$("#dial-phone").click(function() {
		clearInterval(checker);
		var num = $("#record-phone").val(),
				file = num+Date.now();
		$("#record-phone").prop("disabled",true);
		$(this).text(_("Dialing..."));
		$.post( "ajax.php", {module: "recordings", command: "dialrecording", extension: num, filename: file}, function( data ) {
			if(data.status) {
				$("#dialer").removeClass("in").addClass("hidden");
				$("#record-phone").val("");
				$("#dialer-message").text(_("Recording...")).removeClass("hidden");
				setTimeout(function(){
					check(num, file);
				}, 500);

			} else {
				alert(data.message);
			}
			$("#record-phone").prop("disabled",false);
			$("#dial-phone").text(_("Call!"));
		});
	});

	var check = function(num, file) {
		checker = setInterval(function(){
			$.post( "ajax.php", {module: "recordings", command: "checkrecording", extension: num, filename: file}, function( data ) {
				if(data.finished || (!data.finished && !data.recording)) {
					clearInterval(checker);
					$("#dialer-message").removeClass("in").addClass("hidden");
					$("#dialer-save").addClass("in").removeClass("hidden");
					$("#save-phone-input").focus();
					$("#save-phone-input").blur(function(event) {
						if(typeof event.relatedTarget === "undefined" || typeof event.relatedTarget.id === "undefined" || event.relatedTarget.id != "save-phone") {
							alert(_("Please enter a valid name and save"));
							$(this).focus();
						}
					});
					$("#save-phone").on("click", function() {
						if($("#save-phone-input").val() === "") {
							alert(_("Please enter a valid name and save"));
							$("#save-phone-input").focus();
							return;
						}
						if(sysRecConflict($("#save-phone-input").val())) {
							if(!confirm(_("A system recording with this name already exists for this language. Do you want to overwrite it?"))) {
								return;
							}
						}
						$(this).off("click");
						$.post( "ajax.php", {module: "recordings", command: "saverecording", extension: num, filename: file, name: $("#save-phone-input").val()}, function( data ) {
							if(data.status) {
								var paths = {};
								paths[language] = data.localfilename;
								addFile(data.filename, paths, [language], true, false);
							}
							$("#save-phone-input").val("");
							$("#dialer-save").addClass("hidden").removeClass("in");
							$("#dialer").addClass("in").removeClass("hidden");
						});
					});
				}
			});
		}, 500);
	}

	$("#jquery_jplayer_1").jPlayer({
		ready: function(event) {

		},
		timeupdate: function(event) {
			$("#jp_container_1").find(".jp-ball").css("left",event.jPlayer.status.currentPercentAbsolute + "%");
		},
		ended: function(event) {
			$("#jp_container_1").find(".jp-ball").css("left","0%");
		},
		swfPath: "http://jplayer.org/latest/dist/jplayer",
		supplied: "wav",
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
						alert(_("Unsupported file type"));
						return false;
					}
					var s = v.name.replace(/\.[^/.]+$/, "")
					if(sysRecConflict(s)) {
						if(!confirm(sprintf(_("File %s will overwrite a file that already exists in this language. Is that ok?"),v.name))) {
							submit = false;
							return false;
						}
					}
				});
				if(submit) {
					data.submit();
				}
			},
			drop: function () {
				$("#upload-progress .progress-bar").css("width", "0%");
			},
			dragover: function (e, data) {
			},
			change: function (e, data) {
			},
			done: function (e, data) {
				if(data.result.status) {
					var paths = {};
					paths[language] = data.result.localfilename;
					addFile(data.result.filename, paths, [language], true, false);
				} else {
					alert(data.result.message);
				}
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
	var $this = this,
			file = $($this).data("filename"),
			parent = $($this).parents(".file");
	$($this).addClass("deleting");
	//dont delete already existing files
	if(parent.data("system") == 1) {
		parent.fadeOut("slow", function() {
			$(this).remove();
			if(!$("#files .file").length) {
				$("#file-alert").removeClass("hidden");
			}
		})
	} else {
		$.post( "ajax.php", {module: "recordings", command: "deleterecording", filename: file}, function( data ) {
			if(data.status) {
				parent.fadeOut("slow", function() {
					$(this).remove();
					if(!$("#files .file").length) {
						$("#file-alert").removeClass("hidden");
					}
				})
			} else {
				alert(data.message);
				$($this).removeClass("deleting");
			}
		});
	}
});

$(document).on("click", "#files .play", function() {
	$(this).toggleClass("active");
});

$(document).on("click", "#files li", function(event) {
	if(!$(event.target).hasClass("file")) {
		return;
	}
	if(!$(this).hasClass("replace")) {
		if(!$(this).hasClass("missing") && !confirm(_("You are entering replace mode. The next file you add will replace this one. Are you sure you want to do that?"))) {
			return;
		}
		$(".replace").removeClass("replace")
		$(this).toggleClass("replace");
	} else if($(this).hasClass("replace")) {
		$(this).removeClass("replace");
	}
});


function addFile(name, paths, languages, exists, system) {
	if($(".replace").length) {
		var filenames = $(".replace").data("filenames"),
				languages = $(".replace").data("languages")

		languages.push(language);
		filenames[language] = paths[language];
		$(".replace").data("filenames", filenames);
		$(".replace").data("languages", languages);
		$(".replace").removeClass("replace missing");
	} else {
		if(!exists) {
			$("#missing-file-alert").removeClass("hidden");
		}
		var exists = exists ? "" : "missing ",
				system = system ? 1 : 0;
		$("#file-alert").addClass("hidden");
		$("#files").append('<li class="file '+exists+'" data-filenames=\''+JSON.stringify(paths)+'\' data-name="'+name+'" data-system="'+system+'" data-languages=\''+JSON.stringify(languages)+'\'><i class="fa fa-play play"></i> '+name+'<i class="fa fa-times-circle pull-right text-danger delete-file"></i></li>');
	}
}

function linkFormatter(value, row, index){
	var html = '<a href="?display=recordings&action=edit&id='+row.id+'"><i class="fa fa-pencil"></i></a>';
	html += '&nbsp;<a href="?display=recordings&action=delete&id='+row.id+'" class="delAction"><i class="fa fa-trash"></i></a>';
	return html;
}

function convertList(language) {
	soundList = {};
	$("#files li").each(function() {
		var name = $(this).data("name");
		soundList[name] = {
			"name": $(this).data("name"),
			"filenames": $(this).data("filenames"),
			"system": $(this).data("system") ? true : false,
			"languages": $(this).data("languages")
		};
	})
}

function generateList(language) {
	$("#missing-file-alert").addClass("hidden");
	$("#files").html("");
	if(typeof soundList !== "undefined") {
		if(!isObjEmpty(soundList)) {
			$.each(soundList, function(k,v) {
				var exists = (v.languages.indexOf(language) >= 0);
				addFile(v.name, v.filenames, v.languages, exists, v.system);
			});
			$("#file-alert").addClass("hidden");
		} else {
			$("#file-alert").removeClass("hidden");
		}
	} else {
		$("#file-alert").removeClass("hidden");
	}
}

function isObjEmpty(obj) {
	for(var key in obj) {
		if(obj.hasOwnProperty(key)) {
			return false;
		}
	}
	return true;
}

function sysRecConflict(name) {
	return (typeof systemRecordings[name] !== "undefined" && typeof systemRecordings[name].languages[language] !== "undefined");
}
