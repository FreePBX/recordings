<?php 
	if(!empty($error)){
		?>
		<script>fpbxToast( "<?= $error ?>", "Error" , "error");</script>
		<?php 
	}
?>
<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label"><?= _("Enter your API Key")?></label>
					</div>
					<div class="col-md-9">
                        <input type="text" id="apikey" class="form-control" placeholder="<?= _("Enter your API Key here.") ?>" value="<?= empty($apikey)? "" : $apikey; ?>">
					</div>
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
						<label class="control-label"><?= _("Save API Key")?></label>
					</div>
					<div class="col-md-9">
                        <span class="btn btn-primary" id="saveAPIKey"><?= _("Save") ?></span>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
