<div class="col-sm-12 text-center">
    <?php
    $view_data->attr = 'onclick="openModalRemote(\'#GDPRModal\',\''.$this->html->getUrl('extension/gdpr/viewdata')
        .'\'); return false;"';
    echo $view_data; ?>
    <?php
    $erase_data->attr =
        'onClick="return confirm(\''.$this->language->get('gdpr_erase_confirm_text').'\') ? true : false;"';
    echo $erase_data.$change_data;
    ?>
</div>


<div id="GDPRModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="GDPRModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
				<h3 id="GDPRModalLabel"><?php echo $this->language->get('gdpr_modal_title'); ?></h3>
			</div>
			<div class="modal-body"></div>
			<div class="modal-footer">
				<a href="<?php echo $this->html->getUrl('extension/gdpr/download'); ?>"
				   class="btn fa fa-download"> <?php echo $this->language->get('gdpr_download'); ?></a>
				<button class="btn" data-dismiss="modal"
						aria-hidden="true"><?php echo $this->language->get('text_close'); ?></button>
			</div>
		</div>
	</div>
</div>