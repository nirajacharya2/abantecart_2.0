<div class="modal-content">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">
            <span aria-hidden="true">&times;</span>
            <span class="sr-only"><?php echo $this->language->t('text_close', 't'); ?>></span>
        </button>
        <h4 class="modal-title"><?php
            echo $details['incentive']['description']['name']
                . ' - '
                . $details['customer']['firstname'] . ' ' . $details['customer']['lastname']
                . ' - ' . $details['date_added']
                . ' - '
                . \abc\core\engine\Registry::currency()->format($details['bonus_amount']); ?></h4>
    </div>
    <div class="tab-content">
        <div class="panel-body panel-body-nopadding tab-content col-xs-12">

            <div class="form-group padding10">
                <label><?php echo \abc\core\engine\Registry::language()->t('incentive_text_match_conditions', 'Matched Conditions') ?> </label>
                <dl>
                    <?php
                    foreach ($details['result']['matched_conditions'] as $key => $value) { ?>
                        <dt><?php
                            list($condKey,) = explode(":", $key);
                            echo '<a href="' . $this->html->getSecureUrl('sale/incentive/edit', '&tab=conditions&incentive_id=' . $details['incentive_id']) . '" target="_blank">' . ($conditionList[$condKey] ?: $key) . '</a>'; ?>
                        </dt>
                        <dd><?php
                            if (is_array($value)) {

                                foreach ($value as $k => $v) {
                                    if (is_numeric($k)) {
                                        if (is_array($v)) { ?>
                                            <div style="overflow: scroll;" class="row">
                                                <table class="table-bordered table-hover">
                                                    <tr>
                                                        <?php
                                                        foreach ($v as $val) {
                                                            echo '<td class="padding5">' . $val . '</td>';
                                                        }
                                                        ?>
                                                    </tr>
                                                </table>
                                            </div>
                                        <?php } else {
                                            echo $v;
                                        }
                                    }
                                }
                            } else {
                                echo '<p class="ml10">' . $value . '</p>';
                            }
                            ?></dd>
                    <?php } ?>
                </dl>
                <?php
                if ($details['result']['matched_items']) {
                    ?>
                    <label><?php echo \abc\core\engine\Registry::language()->t('incentive_text_match_items', 'Matched Items') ?></label>
                    <dl>
                        <?php
                        foreach ($details['result']['matched_items'] as $key => $row) { ?>
                            <div style="overflow: scroll;" class="row">
                                <table class="table-bordered table-hover">
                                    <tr>
                                        <?php
                                        foreach ($row as $val) {
                                            echo '<td class="padding5">' . $val . '</td>';
                                        }
                                        ?>
                                    </tr>
                                </table>
                            </div>
                        <?php } ?>
                    </dl>
                <?php } ?>

            </div>
            <?php

            ?>
        </div>
        <div class="panel-footer col-xs-12">
            <div class="center">
                <button class="btn btn-default" data-dismiss="modal">
                    <i class="fa fa-times fa-fw"></i> <?php echo $text_close; ?>
                </button>
            </div>
        </div>

    </div>
</div>
