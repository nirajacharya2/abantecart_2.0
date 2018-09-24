<div class="modal-header">
	<h4 class="modal-title"><?php echo $message['title']; ?></h4>
	<button aria-hidden="true" data-dismiss="modal" class="close" type="button">&times;</button>
</div>
<div class="tab-content">
	<div class="panel-body panel-body-nopadding">
		<div class="row">
			<div class="col-md-6">
				<dt><?php echo $message['subject_title']; ?></dt>
				<dd><?php echo $message['subject'] ?></dd>
			</div>
			<div class="col-md-6">
				<dt><?php echo $message['date_title']; ?></dt>
				<dd><?php echo $message['date_added'] ?></dd>
			</div>
		</div>
		<dl class="dl-horizontal">
			<dt><?php echo $message['body_title']; ?></dt>
			<dd><?php echo $message['body'] ?></dd>
		</dl>

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
