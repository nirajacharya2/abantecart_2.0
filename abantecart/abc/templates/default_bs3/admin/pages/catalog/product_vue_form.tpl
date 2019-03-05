<?php if ($modal_mode) { ?>
<div class="panel-heading">
	<div class="panel-btns">
		<a class="panel-close" onclick="$('#viewport_modal').modal('hide');" >×</a>
	</div>
	<h4 class="panel-title">Product Quick View</h4>
</div>
<?php } else { ?>
<?php include($tpl_common_dir . 'action_confirm.tpl'); ?>

<?php echo $summary_form; ?>

<?php echo $product_tabs ?>

<?php } ?>

<?php include($tpl_common_dir . 'vue_form.tpl'); ?>
