<?php include($tpl_common_dir . 'action_confirm.tpl'); ?>

<div id="content" class="panel panel-default">
	<div class="panel-heading col-xs-12">
		<?php include($tpl_common_dir . 'content_buttons.tpl'); ?>
	</div>
	<?php echo $form['form_open']; ?>
	<div class="panel-body panel-body-nopadding tab-content col-xs-12">

		<?php
		foreach ($form['fields'] as $name => $field) { ?>
		<?php
				//Logic to calculate fields width
				$widthcasses = "col-sm-7";
				if ( is_int(stripos($field->style, 'large-field')) ) {
		$widthcasses = "col-sm-7";
		} else if ( is_int(stripos($field->style, 'medium-field')) || is_int(stripos($field->style, 'date')) ) {
		$widthcasses = "col-sm-5";
		} else if ( is_int(stripos($field->style, 'small-field')) || is_int(stripos($field->style, 'btn_switch')) ) {
		$widthcasses = "col-sm-3";
		} else if ( is_int(stripos($field->style, 'tiny-field')) ) {
		$widthcasses = "col-sm-2";
		}
		$widthcasses .= " col-xs-12";
		?>
		<div class="form-group row align-items-start <?php if (!empty($error[$name])) { echo "has-error"; } ?>">
		<label class="control-label offset-sm-1 col-sm-3 col-xs-12" for="<?php echo $field->element_id; ?>"><?php echo ${'text_'.$name}; ?></label>
		<div class="input-group afield <?php echo $widthcasses; ?> <?php echo ($name == 'description' ? 'ml_ckeditor' : '')?>">
			<?php echo $field; ?>
		</div>
		<?php if (!empty($error[$name])) { ?>
		<span class="help-block field_err"><?php echo $error[$name]; ?></span>
		<?php } ?>
	</div>
	<?php }  ?>

</div>

<div class="panel-footer col-xs-12">
	<div class="text-center">
		<button class="btn btn-primary lock-on-click">
			<i class="fa fa-hourglass-start fa-fw"></i> <?php echo $form['submit']->text; ?>
		</button>
	</div>
</div>
</form>

</div>
