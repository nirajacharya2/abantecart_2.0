<div class="modal-header">
	<h4 class="modal-title"><?php echo $message['title']; ?></h4>
	<button aria-hidden="true" data-dismiss="modal" class="close" type="button">&times;</button>
</div>
<div class="tab-content">
	<div class="panel-body panel-body-nopadding">
		<div class="row" style="padding-left: 10px; padding-right: 10px; overflow: auto;">
			<div class="col-md-6">
				<dt><?php echo $message['subject_title']; ?></dt>
				<dd><?php echo $message['subject'] ?></dd>
			</div>
			<div class="col-md-3">
				<dt><?php echo $message['date_title']; ?></dt>
				<dd><?php echo $message['date_added'] ?></dd>
			</div>
			<div class="col-md-3">
				<dt><?php echo $message['sent_to_title']; ?></dt>
				<dd><?php echo $message['sent_to_address'] ?></dd>
			</div>
		</div>
		<div class="row" style="margin-top: 20px; margin-left: 10px; margin-right: 10px; overflow: auto;">
			<div class="col-md-12"><b><?php echo $message['body_title']; ?></b></div>
			<div class="col-md-12"><?php echo $message['body']; ?></div>
		</div>

		<div><?php echo $message['message']; ?></div>

	</div>
	<div class="panel-footer">
		<div class="row">
			<div class="col-sm-12 center">
				<a class="btn btn-default" data-dismiss="modal" href=""><?php echo $button_close; ?></a>
			</div>
		</div>
	</div>

</div>
<script type="application/javascript">
	var delete_msg = function () {
		$.ajax({
			url: '<?php echo $delete_url; ?>',
			type: 'POST',
			dataType: 'json',
			data: 'oper=del&id=<?php echo $msg_id?>',
			success: function (data) {
				$("#message_grid").trigger("reloadGrid");
				//update_notify();
				$('#message_info_modal').modal('hide');
			}
		});
	}
</script>
