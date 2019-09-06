<select class="form-control aselect <?php echo $style ?>" data-placeholder="<?php echo $placeholder ?>" name="<?php echo $name ?>" id="<?php echo $id ?>" data-orgvalue="<?php echo $ovalue; ?>" <?php echo $attr ?>>
<?php foreach ( $options as $v => $text ) { ?>
		<option value="<?php echo $v ?>"
            <?php
            //note: need to seek a string! int cause a bug when $v is 0
            echo(in_array((string)$v, (array)$value) ? ' selected="selected" ' : '') ?>
            <?php echo(in_array((string)$v, $disabled_options) ? ' disabled="disabled" ' : '') ?>
		data-orgvalue="<?php echo (in_array($v, $value) ? 'true':'false') ?>"
		><?php echo $text ?></option>
<?php } ?>
</select>

<?php
if ( $required == 'Y' || !empty ($help_url) ) { ?>
	<span class="input-group-addon">
	<?php if ( $required == 'Y') { ?>
		<span class="required">*</span>
	<?php } ?>

	<?php if ( !empty ($help_url) ) { ?>
	<span class="help_element"><a href="<?php echo $help_url; ?>" target="new"><i class="fa fa-question-circle fa-lg"></i></a></span>
	<?php } ?>
	</span>
<?php } ?>
