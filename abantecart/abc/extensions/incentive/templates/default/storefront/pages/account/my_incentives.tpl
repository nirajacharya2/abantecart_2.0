<h1 class="heading1">
    <span class="maintext"><i class="fa fa-book"></i> <?php echo $heading_title; ?></span>
</h1>

<div class="contentpanel">
    <h4 class="heading4"></h4>

    <div class="content">
        <div class="row">
            <div class="col-md-12 pull-left">
                <?php
                if ($incentivesList['incentives']) {
                    foreach ($incentivesList['incentives'] as $item) { ?>
                        <div class="mt10 row benefit-item">
                            <div class="col-md-3 benefit-item-thumb">
                                <img alt="<?php echo $item['resource']['title']; ?>"
                                     src="<?php echo $item['resource']['url']; ?>"/>
                            </div>
                            <div class="col-md-9 benefit-item-description">
                                <div class="benefit-item-description-title"><b><?php echo $item['name']; ?></b></div>
                                <div
                                    class="benefit-item-description-desc"><?php echo $item['description_short']; ?></div>
                                <?php if ($item['description']) { ?>
                                    <div class="col-12 benefit-item-button text-right mt20">
                                        <p>
                                            <a href="<?php echo $item['details_url']; ?>"
                                               onclick="openModalRemote('#incentiveDetailsModal', '<?php echo $item['details_url']; ?>'); return false;"
                                               class="btn btn-default">
                                                <?php echo $this->language->t('text_view', 'View more'); ?>
                                            </a>
                                        </p>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                        <hr>
                    <?php }
                } else { ?>
                    <div><?php echo $text_empty_list; ?></div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<div id="incentiveDetailsModal" class="modal fade" tabindex="-1" role="dialog"
     aria-labelledby="incentiveDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg ">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h3 id="incentiveDetailsModalLabel"></h3>
            </div>
            <div class="modal-body">
            </div>
        </div>
    </div>
</div>
