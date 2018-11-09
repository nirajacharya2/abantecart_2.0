<?php 
	if(!$style) {
		$style = ' btn-primary';
	} 
?>
<button id="<?php echo $id; ?>"  type="submit" class="btn <?php echo $href_class . $style; ?>" title="<?php echo $name ?>"  <?php echo $attr; ?>>
<?php if($icon) { ?>
<i class="<?php echo $icon; ?>"></i>
<?php } ?>
<?php echo $name ?>
</button>