<?php if ( $type == 'password' && $has_value == 'Y' && $required) { ?>
	<div class="input-group-addon confirm_default" id="<?php echo $id ?>_confirm_default">***********</div>
<?php } ?>
    <input type="<?php echo $type; ?>" name="<?php echo $name; ?>" id="<?php echo $id; ?>" class="form-control atext <?php echo $style; ?>" value="<?php echo $value ?>" data-orgvalue="<?php echo $value ?>" <?php echo $attr; ?> placeholder="<?php echo $placeholder ?>" />
    
<?php if ( $required == 'Y' || $multilingual || !empty ($help_url) ) { ?>
	<div class="input-group-append">
	<?php if ( $required == 'Y') { ?> 
		<span class="input-group-text required">*</span>
	<?php } ?>	

	<?php if ( $multilingual ) { ?>
	<span class="input-group-text multilingual"><i class="fa fa-flag"></i></span>
	<?php } ?>	

	<?php if ( !empty ($help_url) ) { ?>
	<span class="input-group-text help_element"><a href="<?php echo $help_url; ?>" target="new"><i class="fa fa-question-circle fa-lg"></i></a></span>
	<?php } ?>	

	</div>

<?php } ?>