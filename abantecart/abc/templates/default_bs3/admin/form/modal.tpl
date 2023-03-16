<div id="<?php echo $id;?>" class="modal fade <?php echo $style; ?>" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel"
	 aria-hidden="true">
	<?php if (!$modal_type) { $modal_type = 'lg'; } ?> 
	<div class="modal-dialog modal-<?php echo $modal_type; ?>">
		<div class="modal-content">
			<div class="modal-header">
				<button aria-hidden="true" data-dismiss="modal" class="close" type="button">&times;</button>
				<h4 class="modal-title"><?php echo $title; ?></h4>
			</div>
			<div class="modal-body"><?php echo $content;?></div>
            <?php if ($footer) { ?>
                <div class="modal-footer">
                    <?php echo $footer; ?>
                </div>
            <?php } ?>
        </div>
    </div>
</div>
<script type="application/javascript">
    <?php
    //clean up modal for remote data source
    //js for loaded content of modal
    if($data_source == 'ajax'){ ?>

    $(document).on(
        "hidden.bs.modal",
        '#<?php echo $id;?>',
        function (e) {
            $(e.target).removeData("bs.modal");
            var content = $(e.target).find(".modal-content");
            content.find('.modal-body, .modal-header').empty();
            <?php
            echo $js_onclose; ?>
            // hide all tooltips
            $('.tooltip.in').removeClass('in');
        }
    );

    $(document).on(
        'loaded.bs.modal',
        '#<?php echo $id;?>',
        function (e) {
            formOnExit();
            $('.modal-content div.afield').show();
        $('.modal-content .chosen-select').chosen({'width': '100%', 'white-space': 'nowrap'});
        spanHelp2Toggles();
        <?php echo $js_onload; ?>
    }
);

<?php }else{ //js for static modal ?>

$('#<?php echo $id;?>').on('shown.bs.modal', function (e) {
    formOnExit();
    $('.modal-content div.afield').show();
    $('.modal-content .chosen-select').chosen({'width': '100%', 'white-space': 'nowrap'});
    spanHelp2Toggles();
    <?php echo $js_onshow; ?>
});
$('#<?php echo $id;?>').on("hidden.bs.modal", function (e) {
    // hide all tooltips
    $('.tooltip.in').removeClass('in');
    <?php echo $js_onclose; ?>
	});

<?php } ?>


</script>