$('#ttsaiengine').on('change', function() {
    $("#ttsAIloading").html('<i class="fa fa-spinner fa-spin"></i>');
    var engine = $(this).find(":selected").val();
    $.ajax({
        url: "ajax.php?module=recordings&command=ttsform&engine="+engine,
        dataType:"json",
        success: function (json) {
            if(json.status === true){
                $("#ttsai-form").html(json.message);
            }
            else{
                $("#ttsai-form").html("");    
            } 
            $("#ttsAIloading").html('');              
        },
        error: function(xhr, status, error) {
            fpbxToast(_("An Ajax error is occured!! ") + error,'Error','error');
            console.error(xhr, status, error);
        }
    });	
});

$(document).on('click', '#generate', function () {
    // Prevent click if button is disabled
    if ($(this).prop('disabled') || $(this).hasClass('disabled')) {
        return false;
    }
    
    var engine = $('#ttsaiengine').find(":selected").val();
    var file_name = $("#name").val().replace(/\.[^/.]+$/, "").replace(/\s|&|<|>|\.|`|'|\*|\?|\"/g, '-').toLowerCase();
    var text = $("#ttsaiText").val();
    var voicId = $("#ttsaiVoice").val();
    var langCode = $('#language').find(":selected").val();
    var stability = $("#stability").val();
    var similarity = $("#similarity").val();
    if(file_name == ""){
        fpbxToast( _("Please enter a name before generating an audio file!!"), "Warning" , "warning");
        return false;
    }
    if (engine == "Scribe" && $('#audio_lang').val() == ""){
        fpbxToast( _("Please select a language before generating an audio file!!"), "Warning" , "warning");
        $("#audio_lang").focus();
        return false;
    }
    if(engine == 'Scribe' && voicId == ""){
        fpbxToast( _("Please select a voice before generating an audio file!!"), "Warning" , "warning");
        $("#ttsaiVoice").focus();
        return false;
    }
    if(text != ""){
        // Check for special characters that might cause issues
        const specialChars = /[&#%+?]/;
        if (specialChars.test(text)) {
            const proceed = confirm(_("Your text contains special characters (e.g., #, &). We recommend using words instead of symbols. Do you still want to proceed?"));
            if (!proceed) {
                return; // User chose not to proceed
            }
        }
        // Store the original text value before disabling the button
        originalTextValue = $('#ttsaiText').val();
        $('#generate').prop('disabled', true).addClass('disabled');
        // Show loading indicator
        $('#generate').html('<i class="fa fa-spinner fa-spin"></i> ' + _("Generating..."));
        file_name = file_name + "-" + Math.random().toString(36).substring(2, 15);
        var audioLang = $('#audio_lang').find(":selected").val();
        $.ajax({
            url: "ajax.php?module=recordings&command=ttsConvert",
            method: "POST",
            data: {
                engine: engine,
                file_name: file_name,
                text: text,
                voiceId: voicId,
                langCode: langCode,
                stability: stability,
                similarity: similarity,
                audioLang:audioLang
            },
            dataType: "json",
            success: function (json) {
                if(json.status === true){
                    let fileUrl = json.file_url;
                    fetch(fileUrl)
                        .then(res => res.blob())
                        .then(blob => {
                            let file = new File([blob], file_name + ".wav", { type: "audio/wav" });
                            let data = { files: [file] };
                            $("#fileupload").fileupload("add", data);                   
                        })
                        .catch(error => console.error(_("Download error : "), error));
                } else {
                    fpbxToast( _("Error while converting TTS: ") + json.message, "Error" , "error");
                    console.error( _("Error while converting TTS: "), json.message);
                }
                $('#generate').html(_("Generate"));
            },
            error: function(xhr, status, error) {
                $('#generate').html(_("Generate"));
                fpbxToast( _("An Ajax error has occurred: ") + error,"Error" , "error");
            }
        }); 
    }
    else{
        fpbxToast( _("Text cannot be empty"),"Error" , "error");
    }
});

var originalTextValue = '';

$(document).on('input keyup paste', '#ttsaiText', function () {
    var currentTextValue = $(this).val();
    
    if (currentTextValue !== originalTextValue && currentTextValue !== '') {
        $('#generate').prop('disabled', false).removeClass('disabled');
    }
});

$(document).on('click', '#editAPIkey', function () {
    var engine = $('#ttsaiengine').find(":selected").val();
    $.ajax({
        url: "ajax.php?module=recordings&command=getapikey&engine="+engine,
        dataType:"json",
        success: function (json) {
            $("#apikey").val(json.message);
        },
        error: function(xhr, status, error) {
            fpbxToast( _("An Ajax error is occured! ") + error, "Error" , "error");
            console.error(xhr, status, error);
        }        
    })
})


$(document).on('click', '#saveAPIKey', function () {
    var engine = $('#ttsaiengine').find(":selected").val();
    $.ajax({
        url: "ajax.php?module=recordings&command=setapikey&engine="+engine+"&key="+$("#apikey").val(),
        dataType:"json",
        success: function (json) {
            if(json.status === true){
                $("#ttsai-form").html(json.message);
            }
            else{
                $("#ttsai-form").html("");    
            }               
        },
        error: function(xhr, status, error) {
            fpbxToast(_("An Ajax error is occured! ") + error, "Error" , "error");
            console.error(xhr, status, error);
        }
    });
    $('#modalAPIKey').modal('hide');
    $(".modal-backdrop").remove();
 });

