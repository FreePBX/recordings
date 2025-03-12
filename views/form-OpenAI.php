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
                        <textarea name="ttsaiText" id="ttsaiText" class="form-control" required placeholder="<?= _("Enter your text here.") ?>"></textarea>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="ttsaiText-help" class="help-block fpbx-help-block"><?= _("Enter your text here.")?></span>
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
                            <?php foreach ($voices as $voice): ?>
                                <option value="<?= $voice['voice_id'] ?>">
                                    <?= $voice['name'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="ttsaiVoice-help" class="help-block fpbx-help-block"><?= _("Select the human voice avaible into the list.")?></span>
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
<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label"><?= _("Edit API Key")?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="editAPIkey"></i>
					</div>
					<div class="col-md-9">
                        <a class="btn btn-primary" id="editAPIkey" data-toggle="modal" data-target="#modalAPIKey"><?= _("Edit") ?></a>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="editAPIkey-help" class="help-block fpbx-help-block"><?= _("Edit your API key.")?></span>
				</div>
			</div>
		</div>
	</div>
</div>
<!-- The modal APIKey -->
<div class="modal fade" id="modalAPIKey" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="<?= _("Close") ?>">
					<span aria-hidden="true">&times;</span>
				</button>
				<h3 class="modal-title" id="modalLabel"><?= _("Edit API Key") ?></h3>
				</div>
				<div class="modal-body">
					<input type="text" id="apikey" class="form-control" placeholder="<?= _("Enter your API Key here.") ?>">
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" id="saveAPIKey" data-dismiss="modal"><?= _("Save") ?></button> <button type="button" class="btn btn-primary" data-dismiss="modal"><?= _("Close") ?></button>
				</div>
			</div>
		</div>
	</div>
</div>
