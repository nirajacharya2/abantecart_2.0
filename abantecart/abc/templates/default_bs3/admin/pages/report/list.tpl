<?php
//var_Export($report_list); exit;
include($tpl_common_dir.'action_confirm.tpl'); ?>

<div id="content" class="panel panel-default">

	<div class="panel-heading col-xs-12">
        <?php include($tpl_common_dir.'content_buttons.tpl'); ?>
	</div>

	<div class="panel-body panel-body-nopadding tab-content col-xs-12">
        <?php foreach ((array)$report_list['reports'] as $group => $items) { ?>
			<ul style="list-style-type: none;">
			<li class="h4"><?php echo ucfirst($group); ?></li>
			<li>&nbsp;&nbsp;&nbsp;
            <?php
            foreach ($items as $item) {
                if (isset($item['name'])) { ?>
					<dl style="margin-left: 30px"><?php echo '<a class="h5" href="'.$item['url'].'">'
                            .ucfirst($item['name']).'</a>'; ?></dl>
                <?php } ?>

            <?php } ?>
			</li>
			</ul>
        <?php } ?>
	</div>

</div>