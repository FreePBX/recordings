<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="audio_lang"><?= _("Scribe Audio Language")?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="audio_lang"></i>
					</div>
					<div class="col-md-9">
                        <select name="audio_lang" id="audio_lang" class="form-control" required>
							<option value=""><?php echo _("Select Scribe Audio Language"); ?></option>
                            <?php
							foreach ($languages as $lang):
								$langname = array_key_exists($lang, $lang_codes) ? '[' . $lang_codes[$lang] . ']' : '';
								$langcodeName = $lang . " " . $langname;
							?>
                                <option value="<?php echo $lang; ?>">
									<?php echo $langcodeName; ?>
								</option>
                            <?php endforeach; ?>
                        </select>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="audio_lang-help" class="help-block fpbx-help-block"><?= _("Select a human voice language from the available options.")?></span>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="ttsaiVoice"><?= _("Human Voices")?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="ttsaiVoice"></i>
					</div>
					<div class="col-md-9">
                        <select name="ttsaiVoice" id="ttsaiVoice" class="form-control" required>
							<option value=""><?php echo _("Select a voice"); ?></option>
                        </select>
						<div id="playsample" style="display:none;">
							<p style="display: flex; align-items: center; gap: 10px;">
								<span><?php echo _("Play Sample Voice"); ?></span>
								<audio id="audiosample" controls style="margin: 0;"></audio>
							</p>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="ttsaiVoice-help" class="help-block fpbx-help-block"><?= _("Choose a human voice from the available options.")?></span>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label"><?= _("Text to Convert")?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="ttsaiText"></i>
					</div>
					<div class="col-md-9">
						<textarea name="ttsaiText" id="ttsaiText" maxlength="2000" class="form-control" required="" placeholder="<?= _("Enter your text here, We recommend using words instead of symbols.")?>"></textarea>
						<!-- Word count will appear here -->
						<small id="wordCount" class="help-block text-muted">Words: 0 | Characters: 0/2000</small>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="ttsaiText-help" class="help-block fpbx-help-block"><?= _("Enter your text here, We recommend using words instead of symbols.")?></span>
				</div>
			</div>
		</div>
	</div>
</div> 
<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label"><?= _("Convert Text to Audio")?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="generate"></i>
					</div>
					<div class="col-md-9">
                        <span class="btn btn-primary" id="generate"><?= _("Generate") ?></span>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="generate-help" class="help-block fpbx-help-block"><?= _("Generate your audio file.")?></span>
				</div>
			</div>
		</div>
	</div>
</div>
<script>

	const textarea = document.getElementById("ttsaiText");
    const wordCount = document.getElementById("wordCount");

    textarea.addEventListener("input", function () {
        const text = textarea.value;

        // Word count (ignore multiple spaces/newlines)
        const words = text.trim().length > 0 ? text.trim().split(/\s+/).length : 0;

        // Character count (includes spaces, tabs, newlines)
        const chars = text.length;

        wordCount.textContent = `Words: ${words} | Characters: ${chars}/2000`;
    });

	var voices = <?php echo json_encode($voices) ?>;
	$('#audio_lang').on('change', function () {
		var lang = $(this).val();
		var voiceSelect = $('#ttsaiVoice');

		voiceSelect.empty(); // clear previous options
		$('#playsample').hide();
		voiceSelect.append('<option value="">Select a voice</option>');

		if (voices[lang]) {
			voices[lang].forEach(function (voice) {
				voiceSelect.append('<option value="' + voice.canonical_name + '">' + voice.name + '</option>');
			});
		}
	});

	$('#ttsaiVoice').on('change', function () {
		var lang = $('#audio_lang').val();
		var voice = $(this).val();

		if (lang && voice) {
			var voiceslist = voices[lang];

			var selectedVoice = voiceslist.find(function(v) {
				return v.canonical_name === voice;
			});

			if (selectedVoice) {

				$('#audiosample').attr('src', selectedVoice.metadata.sample);
				$('#playsample').show();
			} else {
				$('#playsample').hide();
			}
		} else {
			$('#playsample').hide();
		}
	});
</script>
