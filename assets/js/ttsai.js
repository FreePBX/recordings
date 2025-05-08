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
    if(text != ""){
        $.ajax({
            url: "ajax.php?module=recordings&command=ttsConvert&engine="+engine+"&file_name="+file_name+"&text="+text+"&voiceId="+voicId+"&langCode="+langCode+"&stability="+stability+"&similarity="+similarity,
            dataType:"json",
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
            },
            error: function(xhr, status, error) {
                fpbxToast( _("An Ajax error has occurred: ") + error,"Error" , "error");
            }
        }); 
    }
    else{
        fpbxToast( _("Text cannot be empty"),"Error" , "error");
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

