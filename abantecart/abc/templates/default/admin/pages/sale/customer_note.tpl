<?php include($tpl_common_dir . 'action_confirm.tpl'); ?>

<?php echo $summary_form; ?>

<ul class="nav nav-tabs nav-justified nav-profile">
	<?php
	foreach ($tabs as $tab) {
		if ($tab['active']) {
			$classname = 'active';
		} else {
			$classname = '';
		}
		?>
	<li class="nav-item">
		<a class="nav-link <?php echo $classname; ?>" <?php echo($tab['href'] ? 'href="' . $tab['href'] . '" ' : ''); ?>><strong><?php echo $tab['text']; ?></strong></a>
	</li>
	<?php } ?>

	<?php echo $this->getHookVar('extension_tabs'); ?>
</ul>

<div id="content" class="panel panel-default">
	
	<?php echo $form['form_open']; ?>
	<div class="panel-body panel-body-nopadding tab-content col-xs-12">

		<label class="h4 heading"><?php echo $tab_customer_notes; ?></label>

		<?php foreach ($notes as $note) { ?>
		<div class="card" style="margin-top: 10px;">
			<div class="card-body">
				<div class="row">
			<div class="col-md-3">
				<div class="row">
					<b><?php echo (!empty($note->firstname) || !empty($note->lastname)) ? $note->firstname.' '.$note->lastname.' ('.$note->username.')' : $note->username; ?></b>
				</div>
				<div class="row">
					<?php echo $note->note_added; ?>
				</div>

			</div>
			<div class="col-md-9">
				<?php echo $note->note; ?>
			</div>
			</div>
			</div>
		</div>

		<?php } ?>

		<?php foreach ($form['fields'] as $name => $field) {

		//Logic to calculate fields width
		$widthcasses = "col-sm-7";
		if (is_int(stripos($field->style, 'large-field'))) {
			$widthcasses = "col-sm-7";
		} else if (is_int(stripos($field->style, 'medium-field')) || is_int(stripos($field->style, 'date'))) {
			$widthcasses = "col-sm-5";
		} else if (is_int(stripos($field->style, 'small-field')) || is_int(stripos($field->style, 'btn_switch'))) {
			$widthcasses = "col-sm-3";
		} else if (is_int(stripos($field->style, 'tiny-field'))) {
			$widthcasses = "col-sm-2";
		}
		$widthcasses .= " col-xs-12";
		?>
		<div style="margin-top: 20px;" class="form-group row align-items-start <?php if (!empty($error[$name])) {
			echo "has-error";
		} ?>">
			<div class="input-group afield  <?php echo($name == 'description' ? 'ml_ckeditor' : '') ?>">
				<?php echo $field; ?>
			</div>
			<?php if (!empty($error[$name])) { ?>
				<span class="help-block field_err"><?php echo $error[$name]; ?></span>
			<?php } ?>
		</div>
		<?php
		 
		} // end of foreach $form['fields']

		echo $this->getHookVar('hk_order_comment_pre');
		?>
	</div>

	<div class="panel-footer col-xs-12">
		<div class="text-center">
			<button class="btn btn-primary lock-on-click">
			<i class="fa fa-save fa-fw"></i> <?php echo $form['submit']->text; ?>
			</button>
			<button class="btn btn-default" type="reset">
			<i class="fa fa-sync fa-fw"></i> <?php echo $button_reset; ?>
			</button>
		</div>
	</div>

	</form>
</div><!-- <div class="tab-content"> -->

<script>
	$(document).ready(function () {
		$("#noteFrm_note").attr("placeholder", "<?php echo ${'entry_' . $name}; ?>");
	});
</script>