<input name="<?php echo $name; ?>" id="<?php echo $id; ?>" type="file" class="file <?php echo $style; ?>" multiple="false" <?php echo $attr; ?>>
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
<script type="text/javascript">
jQuery(document).ready(function() {
	$("#<?php echo $id; ?>").fileinput({
	    overwriteInitial: false,
	    browseClass: 'btn btn-info',
	    showUpload: false,
	    showCaption: true,
	    showPreview: false,
	    browseIcon: '<i class="fa fa-folder-open fa-fw"></i> ',
	    browseLabel: <?php abc_js_echo($text_browse); ?>,
	    initialCaption: <?php abc_js_echo($default_text); ?>
	});
});
</script>