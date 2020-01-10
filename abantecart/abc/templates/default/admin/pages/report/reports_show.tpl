<?php include($tpl_common_dir . 'action_confirm.tpl'); ?>

<div id="content" class="panel panel-default">
	<div class="panel-body panel-body-nopadding tab-content col-xs-12">
		<?php echo $listing_grid; ?>
	</div>
</div>

<?php
if (!$taskData) {
?>
<script>
	$(document).ready(function () {
		var gridTable = '<?php echo $table_id;?>';
		if (gridTable.length > 0) {
			$("#" + gridTable)
				.navGrid('#' + gridTable + '_pager', {edit: false, add: false, del: false, search: false})
				.navButtonAdd('#' + gridTable + '_pager', {
					caption: "Export to CSV",
					buttonicon: "ui-icon-extlink",
					onClickButton: function () {
						var postData = $("#" + gridTable).getGridParam("postData");
						var url = '<?php echo $export_csv_url;?>';
						url = url + '&' + $.param(postData);
						window.open(url, '_blank');
					},
					position: "last"
				});
		}
	});

</script>

<?php }
else {
    ?>
	<script>
		$(document).ready(function () {
			var gridTable = '<?php echo $table_id;?>';
			if (gridTable.length > 0) {
				$("#"+gridTable)
					.navGrid('#'+gridTable+'_pager',{edit:false,add:false,del:false,search:false})
					.navButtonAdd('#'+gridTable+'_pager', {
						caption: "Export to CSV",
						buttonicon: "ui-icon-extlink",
						position: "last",
						id: "exportCSVBtn"
					});
			}

			$("#exportCSVBtn").addClass('task_run');

			$("#"+gridTable).bind('jqGridLoadComplete',  function () {
					var postData = $("#" + gridTable).getGridParam("postData");
					$("#exportCSVBtn").attr('data-run-task-url', '<?php echo $taskData['task_run_url']; ?>'+'&' + $.param(postData));
					$("#exportCSVBtn").attr('data-complete-task-url', '<?php echo $taskData['task_complete_url']; ?>'+'&' + $.param(postData));
					$("#exportCSVBtn").attr('data-abort-task-url', '<?php echo $taskData['task_abort_url']; ?>'+'&' + $.param(postData));
				});


			$('.task_run').on('click', function () {
				task_fail = false;
				run_task_url = $(this).attr('data-run-task-url');
				complete_task_url = $(this).attr('data-complete-task-url');
				abort_task_url = $(this).attr('data-abort-task-url');
				var task_title = $(this).attr('data-task-title');
				if(!task_title) {
					task_title = 'Task Processing';
				}

				var modal =
					'<div id="task_modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">' +
					'<div class="modal-dialog">' +
					'<div class="modal-content">' +
					'<div class="modal-header">' +
					'<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>' +
					'<h4 class="modal-title">'+task_title+'</h4>'+
					'</div>' +
					'<div class="modal-body panel-body panel-body-nopadding"></div>' +
					'</div></div></div>';
				$("body").first().after(modal);
				$('#task_modal').modal({"backdrop": "static", 'show': true});
				$('#task_modal').on('hidden.bs.modal', function(e){
					if($.xhrPool != null){
						$.xhrPool.abortAll();
					}
				});

				var progress_html = '<div class="progress_description">Initialization...</div>' +
					'<div class="progress">'
					+'<div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="2" aria-valuemin="0" aria-valuemax="100" style="width: 1%;">1%</div></div>'
					+'<div class="progress-info"></div>';

				if(abort_task_url && abort_task_url.length > 0){
					progress_html += '<div class="center abort_button">' +
						'<a class="btn btn-default abort" title="Interrupt Task" ><i class="fa fa-times-circle fa-fw"></i> Stop</a>' +
						'</div>';
				}
				progress_html += '</div>';
				$('#task_modal .modal-body').html(progress_html);
				progress_html = null;

				//do the trick before form serialization
				if(tinyMCE) {
					tinyMCE.triggerSave();
				}

				var send_data = $(this).parents('form').serialize();

				$.ajax({
					url: run_task_url,
					type: 'POST',
					dataType: 'json',
					data: send_data,
					cache:false,
					success: runTaskUI,
					global: false,
					error: taskRunError
				});
				return false;
			});
		});
	</script>
    <?php
}
		?>