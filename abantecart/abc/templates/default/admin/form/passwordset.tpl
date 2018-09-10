    <input class="form-control atext <?php echo $style; ?> passwordset_element" type="password"
		   name="<?php echo $name ?>" id="<?php echo $id ?>"
           value="<?php echo $value ?>" data-orgvalue="<?php echo $value ?>" <?php echo $attr; ?> autocomplete="off" />

    <input class="form-control atext <?php echo $style; ?> passwordset_element" type="password"
		   placeholder="<?php echo $text_confirm_password; ?>"
		   name="<?php echo $name ?>_confirm" id="<?php echo $id ?>_confirm"
           value="" <?php echo $attr; ?> autocomplete="off" />

    <div class="input-group-append">
	<?php if ( $required == 'Y') { ?>
		<span class="input-group-text required">*</span>
	<?php } ?>
	<?php if ( !empty ($help_url) ) { ?>
		<span class="input-group-text help_element"><a href="<?php echo $help_url; ?>" target="new"><i class="fa fa-question-circle fa-lg"></i></a></span>
	<?php } ?>
	<span id="<?php echo $id ?>_strength" class="password_strength"></span>
	</div>

