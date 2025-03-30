<div class="container-fluid">
	<div class="row">
		<div class="col-sm-12">
			<h1><?= (isset($data['id'])?_("Edit Recording"):_("Add New System Recording"))?></h1>
			<?php if(!empty($missingLangs)) {?><div class="alert alert-danger" role="alert"><?= sprintf(_("Some languages are not installed on this system [%s], saving this recording without them installed could have unknown results. Please install them through the %s module"),implode(",",$missingLangs),"<a href='?display=soundlang'>"._("Sound Languages")."</a>")?></div><?php } ?>
			<?php if(!empty($message)) {?><div class="alert alert-warning" role="alert"><?= $message?></div><?php } ?>
			<div class="fpbx-container">
				<div class="display full-border">

					<form id="recordings-frm" class="fpbx-submit" name="recordings-frm" action="config.php?display=recordings" method="post" <?php if(isset($data['id'])) {?>data-fpbx-delete="config.php?display=recordings&amp;action=delete&amp;id=<?php echo $data['id']?>"<?php } ?> role="form">
						<input type="hidden" name="id" id="id" value="<?php echo $data['id'] ?? ''?>">
						<input type="hidden" name="id" id="id" value="<?= isset($data['id']) ? $data['id'] : ''?>">
						<div class="element-container">
							<div class="row">
								<div class="col-md-12">
									<div class="row">
										<div class="form-group">
											<div class="col-md-3">
												<label class="control-label" for="name"><?= _("Name")?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="name"></i>
											</div>
											<div class="col-md-9"><input type="text" class="form-control" id="name" name="name" value="<?= isset($data['displayname']) ? $data['displayname'] : ''?>"></div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-12">
											<span id="name-help" class="help-block fpbx-help-block"><?= _("The name of the system recording on the file system. If it conflicts with another file then this will overwrite it.")?></span>
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
												<label class="control-label" for="description"><?= _("Description")?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="description"></i>
											</div>
											<div class="col-md-9"><input type="text" class="form-control" id="description" name="description" value="<?= isset($data['description']) ? $data['description'] : ''?>"></div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-12">
											<span id="description-help" class="help-block fpbx-help-block"><?= _("Describe this recording")?></span>
										</div>
									</div>
								</div>
							</div>
						</div>

						<!-- TTS A.I -->
						<div class="element-container">
							<div class="row">
								<div class="col-md-12">
									<div class="row">
										<div class="form-group">
											<div class="col-md-3">
												<label class="control-label" for="ttsaiengine"><?= _("TTS A.I Engine")?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="ttsaiengine"></i>&nbsp;<span id="ttsAIloading"></span>
											</div>
											<div class="col-md-9">
											<select name="ttsaiengine" id="ttsaiengine" class="form-control">
												<option value="none"><?= _("None") ?></option>
												<?php
													foreach($drivers as $driver){
														echo "<option value='".$driver["name"]."'>".$driver["name"]."</option>";
													}
												?>
											</select><br>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-12">
											<span id="ttsaiengine-help" class="help-block fpbx-help-block"><?= _("Select your engine generating audio file from A.I.")?></span>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div id="ttsai-form"></div>
						<!-- END TTS A.I -->

						<div class="element-container">
							<div class="row">
								<div class="col-md-12">
									<div class="row">
										<div class="form-group">
											<div class="col-md-3">
												<label class="control-label" for="list"><?= sprintf(_("File List for %s"),"<span class='language'>".$langs[$default]."</span>")?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="list"></i>
											</div>
											<div class="col-md-9">
												<select class="form-control" id="language" name="language">
													<?php foreach($langs as $code => $lang) {?>
														<option value="<?= $code?>" <?= ($code == $default) ? 'SELECTED': ''?>><?= $lang?></option>
													<?php } ?>
												</select>
												<div id="file-alert" class="alert alert-info <?= !empty($data['soundlist']) ? "hidden" : ""?>" role="alert"><?= sprintf(_("No files for %s"),"<span class='language'>".$langs[$default]."</span>")?></div>
												<ul id="files">
													<?php if(isset($data['soundlist'])) { foreach($data['soundlist'] as $item) {?>
														<li id="file-<?= $item['name']?>" class="file"><?= $item['name']?><i class="fa fa-times-circle pull-right text-danger delete-file"></i></li>
													<?php } } ?>
												</ul>
												<div id="missing-file-alert" class="alert alert-warning text-center hidden" role="alert"><?= _("You have a missing file for this language. Click any red recording above to replace it with a recording/upload below. It will then turn green. Once you have finished uploading/recording the recording will turn grey")?></div>
												<div id="replace-file-alert" class="alert alert-success text-center hidden" role="alert"><?= _("You can click any file above to replace it with a recording option below. Clicking a file will turn it green putting it into replace mode")?></div>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-12">
											<span id="list-help" class="help-block fpbx-help-block"><?= _("Sortable File List/Play order. The playback will be done starting from the top to the bottom. You can click the play icon to preview the files. If a file is red it is missing for said selected language. Files can be replaced by clicking them once (which will turn them green) placing them into replace mode. Anything you upload will then replace this file on save")?></span>
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
												<label class="control-label" for="fileupload"><?= _("Upload Recording")?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="fileupload"></i>
											</div>
											<div class="col-md-9">
												<span class="btn btn-default btn-file">
													<?= _("Browse")?>
													<input id="fileupload" type="file" class="form-control" name="files[]" data-url="ajax.php?module=recordings&amp;command=upload" class="form-control" multiple>
												</span>
												<div id="upload-progress" class="progress">
													<div class="progress-bar" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 0%;"></div>
												</div>
												<div id="dropzone">
													<div class="message"><?= _("Drop Multiple Files or Archives Here")?></div>
												</div>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-12">
											<span id="fileupload-help" class="help-block fpbx-help-block"><?= sprintf(_("Upload files from your local system. Supported upload formats are: %s. This includes archives (that include multiple files) and multiple files"),"<i><strong>".implode(", ",$supported['in'])."</strong></i>")?></span>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div id="record-container" class="element-container hidden">
							<div class="row">
								<div class="col-md-12">
									<div class="row">
										<div class="form-group">
											<div class="col-md-3">
												<label class="control-label" for="record"><?= _("Record In Browser")?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="record"></i>
											</div>
											<div class="col-md-9">
												<div id="browser-recorder">
													<div id="jquery_jplayer_1" class="jp-jplayer"></div>
													<div id="jp_container_1" data-player="jquery_jplayer_1" class="jp-audio-freepbx" role="application" aria-label="media player">
														<div class="jp-type-single">
															<div class="jp-gui jp-interface">
																<div class="jp-controls">
																	<i class="fa fa-play jp-play"></i>
																	<i id="record" class="fa fa-circle"></i>
																</div>
																<div class="jp-progress">
																	<div class="jp-seek-bar progress">
																		<div class="jp-current-time" role="timer" aria-label="time">&nbsp;</div>
																		<div class="progress-bar progress-bar-striped active" style="width: 100%;"></div>
																		<div class="jp-play-bar progress-bar"></div>
																		<div class="jp-play-bar">
																			<div class="jp-ball"></div>
																		</div>
																		<div class="jp-duration" role="timer" aria-label="duration">&nbsp;</div>
																	</div>
																</div>
																<div class="jp-volume-controls">
																	<i class="fa fa-volume-up jp-mute"></i>
																	<i class="fa fa-volume-off jp-unmute"></i>
																</div>
															</div>
															<div class="jp-details">
																<div class="jp-title" aria-label="title"><?= _("Hit the red record button to start recording from your browser")?></div>
															</div>
															<div class="jp-no-solution">
																<span><?= _("Update Required")?></span>
																<?= sprintf(_("To play the media you will need to either update your browser to a recent version or update your %s"),'<a href="http://get.adobe.com/flashplayer/" target="_blank">Flash plugin</a>')?>
															</div>
														</div>
													</div>
												</div>
												<div id="browser-recorder-progress" class="progress fade hidden">
													<div class="progress-bar" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 0%;">
													</div>
												</div>
												<div id="browser-recorder-save" class="fade hidden">
													<div class="input-group">
														<input type="text" class="form-control name-check" id="save-recorder-input" placeholder=<?= _("Name this file") ?>">
														<span class="input-group-btn">
															<button class="btn btn-default cancel" type="button" id="cancel-recorder"><?= _('Cancel')?></button>
															<button class="btn btn-default" type="button" id="save-recorder"><?= _('Save')?></button>
														</span>
													</div>
												</div>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-12">
											<span id="record-help" class="help-block fpbx-help-block"><?= _("This will initate a WebRTC request so that you will be able to record from you computer in your browser")?></span>
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
												<label class="control-label" for="record-phone"><?= _("Record Over Extension")?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="record-phone"></i>
											</div>
											<div class="col-md-9">
												<div id="dialer-message" class="alert alert-warning hidden" role="alert"></div>
												<div id="dialer" class="">
													<div class="input-group">
														<input type="text" class="form-control" id="record-phone" placeholder="<?= _("Enter Extension")?>...">
														<span class="input-group-btn">
															<button class="btn btn-default" type="button" id="dial-phone"><?= _("Call")?></button>
														</span>
													</div>
												</div>
												<div id="dialer-save" class="hidden">
													<div class="input-group">
														<input type="text" class="form-control name-check" id="save-phone-input" placeholder="<?= _("Name this file")?>">
														<span class="input-group-btn">
															<button class="btn btn-default cancel" type="button" id="cancel-phone"><?= _('Cancel')?></button>
															<button class="btn btn-default" type="button" id="save-phone"><?= _("Save")?></button>
														</span>
													</div>
												</div>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-12">
											<span id="record-phone-help" class="help-block fpbx-help-block"><?= _("The system will call the extension you specify to the left. Upon hangup you will be able to name the file and it will be placed in the list above")?></span>
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
												<label class="control-label" for="systemrecording"><?= _("Add System Recording")?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="systemrecording"></i>
											</div>
											<div class="col-md-9">
												<select name="systemrecording" id="systemrecording" class="autocomplete-combobox form-control">
													<option></option>
													<?php foreach($sysrecs as $key => $sr) {?>
														<option value="<?= $key?>"><?= $sr['name']?></option>
													<?php } ?>
												</select>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-12">
											<span id="systemrecording-help" class="help-block fpbx-help-block"><?= _("Add any previously created system recording or a recording that was added previously")?></span>
										</div>
									</div>
								</div>
							</div>
						</div>
						<!--
						<div class="element-container">
							<div class="row">
								<div class="col-md-12">
									<div class="row">
										<div class="form-group">
											<div class="col-md-3">
												<label class="control-label" for="combine"><?= _("Combine Files")?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="combine"></i>
											</div>
											<div class="col-md-9">
												<span class="radioset">
													<input type="radio" id="combine-yes1" name="combine" value="yes">
													<label for="combine-yes1"><?= _("Yes")?></label>
													<input type="radio" id="combine-no1" name="combine" value="no" checked>
													<label for="combine-no1"><?= _("No")?></label>
												</span>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-12">
											<span id="combine-help" class="help-block fpbx-help-block"><?= _("Instead of chaining files together (of which some applications do not support playback) combine the files above into a single file. After this is done you will not be able to resort or remove the files from the list above but you will be able to add files to the end of or the beginning of this file. This will not destroy any previously existing files listed above.")?></span>
										</div>
									</div>
								</div>
							</div>
						</div>
						-->
						<div class="element-container">
							<div class="row">
								<div class="col-md-12">
									<div class="row">
										<div class="form-group">
											<div class="col-md-3">
												<label class="control-label" for="fcode-link"><?= _("Link to Feature Code")?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="fcode-link"></i>
											</div>
											<div class="col-md-9">
												<span class="radioset">
													<input type="radio" id="fcode-link-yes1" name="fcode-link" value="yes" class="fcode-item" <?php echo (isset($data['fcode']) && ($data['fcode'])) ? "checked" : ""?> <?php echo (isset($data['soundlist']) && ((is_countable($data['soundlist']) ? count($data['soundlist']) : 0) == 1)) ? "" : "disabled"?>>
													<label for="fcode-link-yes1"><?php echo _("Yes")?></label>
													<input type="radio" id="fcode-link-no1" name="fcode-link" value="no" class="fcode-item" <?php echo (!isset($data['fcode']) || !($data['fcode'])) ? "checked" : ""?> <?php echo (isset($data['soundlist']) && ((is_countable($data['soundlist']) ? count($data['soundlist']) : 0) == 1)) ? "" : "disabled"?>>
													<label for="fcode-link-no1"><?php echo _("No")?></label>
												</span>
												<strong><span id="fcode-message" data-message="<?php echo isset($data['rec_code']) ? sprintf(_("Optional Feature Code %s"),$data['rec_code']) : ""?>"><?php echo (isset($data['soundlist']) && ((is_countable($data['soundlist']) ? count($data['soundlist']) : 0) == 1)) ? sprintf(_("Optional Feature Code %s"),$data['rec_code'] ?? "") : _("Not supported on compounded or Non-Existent recordings")?></span></strong>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-12">
											<span id="fcode-link-help" class="help-block fpbx-help-block"><?= _("Check this box to create an options feature code that will allow this recording to be changed directly.")?></span>
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
												<label class="control-label" for="fcode-password"><?= _("Feature Code Password")?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="fcode-password"></i>
											</div>
											<div class="col-md-9"><input name="fcode_pass" id="fcode_pass" class="form-control fcode-item" value="<?php echo $data['fcode_pass'] ?? ""?>" <?php echo (isset($data['soundlist']) && ((is_countable($data['soundlist']) ? count($data['soundlist']) : 0) == 1)) ? "" : "disabled"?>></div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-12">
											<span id="fcode-password-help" class="help-block fpbx-help-block"><?= _("Optional password to protect access to this feature code which allows a user to re-record it.")?></span>
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
												<label class="control-label" for="convert"><?= _("Convert To")?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="convert"></i>
											</div>
											<div class="col-md-9 text-center">
												<span class="radioset">
													<?php $c=0;foreach($convertto as $k => $v) { ?>
														<?php if(($c % 5) == 0 && $c != 0) { ?></span></br><span class="radioset"><?php } ?>
														<input type="checkbox" id="<?= $k?>" name="codec[]" class="codec" value="<?= $k?>" <?= ((!empty($data['codecs']) && in_array($k,$data['codecs'])) || $k == $recformat) ? 'CHECKED' : ''?>>
														<label for="<?= $k?>"><?= $v?></label>
													<?php $c++; } ?>
												</span>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-12">
											<span id="convert-help" class="help-block fpbx-help-block"><?= _("Check all file formats you would like this system recording to be encoded into")?></span>
										</div>
									</div>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
<script>var langs = <?php echo json_encode($langs, JSON_THROW_ON_ERROR)?>;var supportedHTML5 = "<?php echo $supportedHTML5?>";var supportedFormats = <?php echo json_encode($supported['in'], JSON_THROW_ON_ERROR)?>;var supportedRegExp = "<?php echo implode("|",array_keys($supported['in']))?>";var systemRecordings = <?php echo $jsonsysrecs?>;var soundList = <?php echo isset($data['soundlist']) ? json_encode($data['soundlist'], JSON_THROW_ON_ERROR) : "{}"?>;var playbackList = <?php echo isset($data['playbacklist']) ? json_encode($data['playbacklist'], JSON_THROW_ON_ERROR) : "[]"?>;</script>
<div id="playbacks">
</div>
<div id="recscreen" class="hidden">
	<div class="holder">
		<label></label>
		<div class="progress">
			<div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
			</div>
		</div>
	</div>
</div>
<script>
$(document).ready(function() {
    $(document).on('mouseenter', '.fpbx-help-icon', function() {
        var target = $(this).data('for');
        $('#' + target + '-help').fadeIn(200); // Afficher l'aide en douceur
    });

    $(document).on('mouseleave', '.fpbx-help-icon', function() {
        var target = $(this).data('for');
        $('#' + target + '-help').fadeOut(200); // Cacher l'aide
    });
});

var record_names = new Array();
<?php
if(!empty($record_names)){
	echo "record_names = " . json_encode($record_names, JSON_THROW_ON_ERROR) . ";";
}
?>
</script>
