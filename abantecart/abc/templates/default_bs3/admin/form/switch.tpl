<?php if(is_int(strpos($attr,'readonly')) || is_int(strpos($attr,'disable')) ){ ?>
<div id="<?php echo $id ?>_layer" class="btn-group btn-toggle <?php echo $style; ?>" <?php echo $attr ?>>
    <button class="btn btn-<?php echo ($checked ? 'primary' : 'default'); ?> active" <?php echo $attr ?>>
        <?php echo ($checked ? $text_on : $text_off); ?>
    </button>
</div>
<?php }else{ ?>
<div id="<?php echo $id ?>_layer" class="btn-group btn-toggle <?php echo $style; ?>" <?php echo $attr ?>>
	<?php if ($checked) { ?>
    <button class="btn btn-primary active" <?php echo $attr ?>><?php echo $text_on?></button>
    <button class="btn btn-default" <?php echo $attr ?>><?php echo $text_off?></button>
	<?php } else { ?>
    <button class="btn btn-default" <?php echo $attr ?>><?php echo $text_on?></button>
    <button class="btn btn-primary btn-off active" <?php echo $attr ?>><?php echo $text_off?></button>
	<?php } ?>
</div>
<?php } ?>
<input type="hidden"
           name="<?php echo $name ?>"
           id="<?php echo $id ?>"
           value="<?php echo $value ?>"
           data-orgvalue="<?php echo $value ?>"
           class="aswitcher <?php echo $style; ?>"
		   <?php echo $attr; ?>
/>
<?php if ( !empty ($help_url) ) { ?>
<span class="input-group-addon aswitcher">
	<span class="help_element"><a href="<?php echo $help_url; ?>" target="new"><i class="fa fa-question-circle fa-lg"></i></a></span>
</span>
<?php } ?>
