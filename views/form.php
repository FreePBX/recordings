<div class="container-fluid">
	<div class="row">
		<div class="col-sm-9">
			<h1><?php echo _("Add New System Recording")?></h1>
			<div class="fpbx-container">
				<div class="display full-border">
					<form class="fpbx-submit" name="frm_extensions" action="config.php?display=extensions" method="post" data-fpbx-delete="config.php?display=extensions&amp;extdisplay=1000&amp;action=del" role="form">
						<div class="element-container">
							<div class="row">
								<div class="col-md-12">
									<div class="row">
										<div class="form-group">
											<div class="col-md-3">
												<label class="control-label" for="name"><?php echo _("Name")?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="name"></i>
											</div>
											<div class="col-md-9"><input type="text" class="form-control" id="name" name="name"></div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-12">
											<span id="name-help" class="help-block fpbx-help-block"><?php echo _("The name of the system recording on the file system. If it conflicts with another file then this will overwrite it.")?></span>
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
												<label class="control-label" for="language"><?php echo _("Language")?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="language"></i>
											</div>
											<div class="col-md-9">
												<select class="form-control" id="language" name="language">
													<?php foreach($langs as $code => $lang) {?>
														<option value="<?php echo $code?>"><?php echo $lang?></option>
													<?php } ?>
												</select>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-12">
											<span id="language-help" class="help-block fpbx-help-block"><?php echo _("Language the sound file is recorded in")?></span>
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
												<label class="control-label" for="list"><?php echo _("File List")?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="list"></i>
											</div>
											<div class="col-md-9">
												<div id="list">
													<?php echo _("No Files")?>
												</div>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-12">
											<span id="list-help" class="help-block fpbx-help-block"><?php echo _("Sortable File List")?></span>
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
												<label class="control-label" for="fileupload"><?php echo _("Upload")?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="fileupload"></i>
											</div>
											<div class="col-md-9">
												<input id="fileupload" type="file" name="files[]" data-url="ajax.php?module=recordings&amp;command=upload" class="form-control" multiple>
												<div id="upload-progress" class="progress">
													<div class="progress-bar" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 60%;">
														<span class="sr-only">60% Complete</span>
													</div>
												</div>
												<div id="dropzone">
													<div class="message"><?php echo _("Drop Files Here")?></div>
												</div>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-12">
											<span id="fileupload-help" class="help-block fpbx-help-block"><?php echo sprintf(_("Supported upload formats are: %s"),"<i><strong>".implode(", ",$supported['in'])."</strong></i>")?></span>
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
												<label class="control-label" for="record">Record In Browser</label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="record"></i>
											</div>
											<div class="col-md-9">
												<div id="jquery_jplayer_1" class="jp-jplayer"></div>
												<div id="jp_container_1" class="jp-audio-freepbx" role="application" aria-label="media player">
													<div class="jp-type-single">
														<div class="jp-gui jp-interface">
															<div class="jp-controls">
																<i class="fa fa-play jp-play"></i>
																<i class="fa fa-repeat jp-repeat"></i>
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
															<div class="jp-title" aria-label="title">&nbsp;</div>
														</div>
														<div class="jp-no-solution">
															<span>Update Required</span>
															To play the media you will need to either update your browser to a recent version or update your <a href="http://get.adobe.com/flashplayer/" target="_blank">Flash plugin</a>.
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-12">
											<span id="record-help" class="help-block fpbx-help-block">Help Text</span>
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
												<label class="control-label" for="element1">Record Over Phone</label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="element2"></i>
											</div>
											<div class="col-md-9">
												<div class="input-group">
													<input type="text" class="form-control" placeholder="Enter Extension...">
													<span class="input-group-btn">
														<button class="btn btn-default" type="button">Call!</button>
													</span>
												</div><!-- /input-group -->
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-12">
											<span id="element2-help" class="help-block fpbx-help-block">Help Text</span>
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
												<label class="control-label" for="element1">Convert</label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="element2"></i>
											</div>
											<div class="col-md-9">
												<select class="form-control">
													<?php foreach($supported['out'] as $k => $v) {?>
														<option value="<?php echo $k?>"><?php echo $v?></option>
													<?php } ?>
												</select>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-12">
											<span id="element2-help" class="help-block fpbx-help-block">Help Text</span>
										</div>
									</div>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		<div class="col-sm-3 hidden-xs bootnav">
			<div class="list-group">
				<a href="?display=recordings" class="list-group-item"><?php echo _("List Recordings")?></a>
			</div>
		</div>
	</div>
</div>
<script>var supportedFormats = <?php echo json_encode($supported['in'])?>;</script>
