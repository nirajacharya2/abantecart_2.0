<?php include($tpl_common_dir . 'action_confirm.tpl'); ?>

<?php echo $summary_form; ?>

<?php echo $tabs; ?>

<div id="content" class="panel panel-default">

	<?php echo $form['form_open']; ?>
	<div class="panel-body panel-body-nopadding tab-content col-xs-12">

		<label class="h4 heading"><?php echo $tab_customer_notes; ?></label>

		<?php foreach ($notes as $note) { ?>
			<table class="table">
				<thead>
				<tr>
					<td class="left"><b><?php echo $column_date_added; ?></b></td>
					<td class="left"><b><?php echo $column_created_by; ?></b></td>
				</tr>
				</thead>
				<tbody>
				<tr>
					<td class="left"><?php echo $note->note_added; ?></td>
					<td class="left"><?php echo (!empty($note->firstname) || !empty($note->lastname)) ? $note->firstname.' '.$note->lastname.' ('.$note->username.')' : $note->username; ?></td>
				</tr>
				</tbody>
				<?php if ($note->note) { ?>
					<thead>
					<tr>
						<td class="left" colspan="3"><b><?php echo $column_note; ?></b></td>
					</tr>
					</thead>
					<tbody>
					<tr>
						<td class="left" colspan="3"><?php echo $note->note; ?></td>
					</tr>
					</tbody>
				<?php } ?>
			</table>
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
		<div class="form-group row align-items-start <?php if (!empty($error[$name])) {
			echo "has-error";
		} ?>">
			<label class="control-label offset-sm-1 col-sm-3 col-xs-12"
				   for="<?php echo $field->element_id; ?>"><?php echo ${'entry_' . $name}; ?></label>

			<div class="input-group afield <?php echo $widthcasses; ?> <?php echo($name == 'description' ? 'ml_ckeditor' : '') ?>">
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
