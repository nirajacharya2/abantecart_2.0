<select class="form-control aselect <?php echo $style ?>" data-placeholder="<?php echo $placeholder ?>" name="<?php echo $name ?>" id="<?php echo $id ?>" data-orgvalue="<?php echo $ovalue; ?>"  <?php echo $attr ?>>
<?php foreach ( $options as $v => $text ) { ?>
		<option value="<?php echo $v ?>"
		<?php echo (in_array((string)$v, (array)$value, true) ? ' selected="selected" ':'') ?>
		<?php echo (in_array((string)$v, (array)$disabled_options, true) ? ' disabled="disabled" ':'') ?>
		data-orgvalue="<?php echo (in_array($v, $value) ? 'true':'false') ?>"
		><?php echo $text ?></option>
<?php } ?>
</select>

<?php if ( $required == 'Y' || !empty ($help_url) ) { ?>
	<div class="input-group-append">
	<?php if ( $required == 'Y') { ?>
		<span class="input-group-text required">*</span>
	<?php } ?>

	<?php if ( !empty ($help_url) ) { ?>
	<span class="input-group-text help_element"><a href="<?php echo $help_url; ?>" target="new"><i class="fa fa-question-circle fa-lg"></i></a></span>
	<?php } ?>
	</div>
<?php } ?>
