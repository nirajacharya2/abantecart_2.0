<?php include($tpl_common_dir . 'action_confirm.tpl'); ?>

<div id="content" class="panel panel-default">
	<div class="panel-body panel-body-nopadding tab-content col-xs-12">
		<?php echo $listing_grid; ?>
	</div>
</div>

<script>
	$(document).ready(function () {
		var gridTable = '<?php echo $table_id;?>';
		if (gridTable.length > 0) {
			$("#"+gridTable)
				.navGrid('#'+gridTable+'_pager',{edit:false,add:false,del:false,search:false})
				.navButtonAdd('#'+gridTable+'_pager', {
					caption: "Export to CSV",
					buttonicon: "ui-icon-extlink",
					onClickButton: function () {
						var postData = $("#"+gridTable).getGridParam("postData");
						var url = '<?php echo $export_csv_url;?>';
						url = url + '&' + $.param(postData);
						window.open(url, '_blank');
					},
					position: "last"
				});
		}
	});
</script>
