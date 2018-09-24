<?php include($tpl_common_dir . 'action_confirm.tpl'); ?>

<div id="content" class="panel panel-default">

	<div class="panel-heading col-xs-12">
		<div class="primary_content_actions pull-left">
		</div>
		<?php include($tpl_common_dir . 'content_buttons.tpl'); ?>	
	</div>

	<div class="panel-body panel-body-nopadding tab-content col-xs-12">
		<?php echo $listing_grid; ?>
	</div>

</div>

<script type="text/javascript">

	var grid_ready = function(){
		$('.grid_action_run').each(function(){
			var job_id = $(this).parents('tr').attr('id');
			var URL = '<?php echo $run_job_url?>' + '&job_id=' + job_id;
			$(this).click(function(){
				$.ajax({
					url: URL,
					type:'POST',
					success: function(data){
					    if(data.result == true) {
                            success_alert(<?php abc_js_echo($text_job_started); ?>, true);
                        }
					},
					complete: function(){
						$('#jobs_grid').trigger("reloadGrid");
					}
				});

				return false;
			})
		});

		$('.grid_action_restart').each(function(){
			var job_id = $(this).parents('tr').attr('id');
			var URL = '<?php echo $restart_job_url?>' + '&job_id=' + job_id;
			$(this).click(function(){
				$.ajax({
					url: URL,
					type:'POST'
				});
				success_alert(<?php abc_js_echo($text_job_started); ?>, true);
				$('#jobs_grid').trigger("reloadGrid");
				return false;
			})
		});
	};

</script>