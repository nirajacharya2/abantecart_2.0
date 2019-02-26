<?php include($tpl_common_dir . 'action_confirm.tpl'); ?>

<div id="content" class="panel panel-default">

	<div class="panel-heading col-xs-12">
		<div class="primary_content_actions pull-left">
				<a href="<?php echo $insert; ?>" class="btn btn-primary" title="<?php echo $button_add; ?>" >
					<i class="fa fa-plus fa-fw"></i>
					</a>
		</div>

		<?php include($tpl_common_dir . 'content_buttons.tpl'); ?>
	</div>

	<div class="panel-body panel-body-nopadding tab-content col-xs-12">
		<?php echo $listing_grid; ?>
	</div>

</div>

