<div class="modal-header">
    <button aria-hidden="true" data-dismiss="modal" class="close" type="button">&times;</button>
    <a aria-hidden="true" class="btn btn-default" type="button" href="" target="_new"><i
                class="fa fa-arrow-right fa-fw"></i><?php echo $text_more_new; ?></a>
    <a aria-hidden="true" class="btn btn-default" type="button" href=""><i
                class="fa fa-arrow-down fa-fw"></i><?php echo $text_more_current; ?></a>
    <h4 class="modal-title"><?php echo $heading_title; ?></h4>
</div>

<div id="content" class="panel panel-default">
    <?php echo $form['form_open']; ?>
    <div class="panel-body panel-body-nopadding tab-content col-xs-12">
        <label class="h4 heading"><?php echo $form_title; ?></label>

        <div class="container-fluid">
            <div class="col-sm-6 col-xs-12">
                <?php echo $this->getHookVar('order_details_left_pre'); ?>
                <div class="form-group">
                    <label class="control-label col-sm-5"><?php echo $entry_order_id; ?></label>
                    <div class="input-group afield col-sm-7">
                        <p class="form-control-static"><?php echo $order_id; ?></p>
                    </div>
                </div>
                <?php if ($entry_invoice_id) { ?>
                    <div class="form-group">
                        <label class="control-label col-sm-5"><?php echo $entry_invoice_id; ?></label>
                        <div class="input-group afield col-sm-7"><p class="form-control-static">
                                <?php if ($invoice_id) {
                                    echo $invoice_id;
                                } else {
                                    echo '--';
                                } ?>
                            </p></div>
                    </div>
                <?php } ?>
                <div class="form-group">
                    <label class="control-label col-sm-5"><?php echo $entry_customer; ?></label>
                    <div class="input-group afield col-sm-7">
                        <p class="form-control-static"><?php echo $firstname.' '.$lastname; ?></p>
                    </div>
                </div>
                <?php if ($customer_group) { ?>
                    <div class="form-group">
                        <label class="control-label col-sm-5"><?php echo $entry_customer_group; ?></label>
                        <div class="input-group afield col-sm-7">
                            <p class="form-control-static"><?php echo $customer_group; ?></p>
                        </div>
                    </div>
                <?php } ?>
                <div class="form-group">
                    <label class="control-label col-sm-5"><?php echo $entry_email; ?></label>
                    <div class="input-group afield col-sm-7"><?php echo $email; ?></div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-5"><?php echo $entry_telephone; ?></label>
                    <div class="input-group afield col-sm-7"><?php echo $telephone; ?></div>
                </div>
                <?php if ($fax->value) { ?>
                    <div class="form-group">
                        <label class="control-label col-sm-5"><?php echo $entry_fax; ?></label>
                        <div class="input-group afield col-sm-7"><?php echo $fax; ?></div>
                    </div>
                <?php }
                if ($im) { ?>
                    <div class="form-group">
                        <label class="control-label col-sm-5"><?php echo $entry_im; ?></label>
                        <div class="input-group afield col-sm-7">
                            <p class="form-control-static"><?php
                                foreach ($im as $protocol => $uri) {
                                    switch ($protocol) {
                                        case 'sms':
                                            $icon = 'fa-mobile';
                                            break;
                                        default :
                                            $icon = 'fa-'.$protocol;
                                    }
                                    ?>
                                    <i class="fa <?php echo $icon; ?>"></i> <?php echo $uri; ?>
                                <?php }
                                ?></p>
                        </div>
                    </div>
                <?php }
                if ($entry_ip) { ?>
                    <div class="form-group">
                        <label class="control-label col-sm-5"><?php echo $entry_ip; ?></label>
                        <div class="input-group afield col-sm-7">
                            <p class="form-control-static"><?php echo $ip; ?></p>
                        </div>
                    </div>
                    <?php
                }
                echo $this->getHookVar('order_details_left_post'); ?>
            </div>
            <div class="col-sm-6 col-xs-12">
                <?php echo $this->getHookVar('order_details_right_pre'); ?>
                <div class="form-group">
                    <label class="control-label col-sm-5"><?php echo $entry_store_name; ?></label>
                    <div class="input-group afield col-sm-7">
                        <p class="form-control-static"><?php echo $store_name; ?></p>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-5"><?php echo $entry_store_url; ?></label>
                    <div class="input-group afield col-sm-7">
                        <p class="form-control-static"><a href="<?php echo $store_url; ?>"
                                                          target="_blank"><?php echo $store_url; ?></a></p>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-5"><?php echo $entry_date_added; ?></label>
                    <div class="input-group afield col-sm-7">
                        <p class="form-control-static"><?php echo $date_added; ?></p>
                    </div>
                </div>
                <?php if ($entry_shipping_method) { ?>
                    <div class="form-group">
                        <label class="control-label col-sm-5"><?php echo $entry_shipping_method; ?></label>
                        <div class="input-group afield col-sm-7">
                            <p class="form-control-static"><?php echo $form['fields']['shipping_method']; ?></p>
                        </div>
                    </div>
                <?php }
                if ($entry_payment_method) { ?>
                    <div class="form-group">
                        <label class="control-label col-sm-5"><?php echo $entry_payment_method; ?></label>
                        <div class="input-group afield col-sm-7">
                            <p class="form-control-static"><?php echo $form['fields']['payment_method']; ?></p>
                        </div>
                    </div>
                <?php } ?>
                <div class="form-group">
                    <label class="control-label col-sm-5"><?php echo $entry_total; ?></label>
                    <div class="input-group afield col-sm-7">
                        <p class="form-control-static"><?php echo $total; ?></p>
                    </div>
                </div>
                <?php if ($entry_order_status) {?>
                <div class="form-group">
                    <label class="control-label col-sm-5"><?php echo $entry_order_status; ?></label>
                    <div class="input-group afield col-sm-7" id="order_status">
                        <p class="form-control-static"><a target="_blank"
                                                          href="<?php echo $history; ?>"><?php echo $order_status; ?></a>
                        </p>
                    </div>
                </div>
                <?php }
                echo $this->getHookVar('order_details_right_post'); ?>
            </div>
        </div>

        <?php if ($comment) { ?>
            <div class="form-group">
                <label class="control-label col-sm-5"><?php echo $entry_comment; ?></label>
                <div class="input-group afield col-sm-7">
                    <p class="form-control-static"><?php echo $comment; ?></p>

                </div>
            </div>
        <?php } ?>

        <?php echo $this->getHookVar('order_details'); ?>
    </div>

    <div class="panel-body panel-body-nopadding tab-content col-xs-12">
        <label class="h4 heading"><?php echo $form_title; ?></label>

        <table id="products" class="table ">
            <thead>
            <tr>
                <td class="align-left"><?php echo $column_product; ?></td>
                <td class="align-right"><?php echo $column_quantity; ?></td>
                <td class="align-right"><?php echo $column_price; ?></td>
                <td class="align-right"><?php echo $column_total; ?></td>
            </tr>
            </thead>

            <?php foreach ($order_products as $order_product) {
                $oid = $order_product['order_product_id'];
                ?>
                <tbody id="product_<?php echo $oid; ?>">
                <tr <?php if (!$order_product['product_status']
                || $order_product['disable_edit']) { ?>class="alert alert-warning"<?php } ?>>
                    <td class="align-left" data-order-product-id="<?php echo $oid; ?>">
                        <a target="_blank"
                           href="<?php echo $order_product['href']; ?>">
                            <?php
                            echo $order_product['name'].($order_product['model'] ? '('.$order_product['model']
                                    .')' : '');
                            echo ' - '.$order_product['order_status']; ?>
                        </a>
                        <?php
                        if ($order_product['option']) { ?>
                            <dl class="dl-horizontal product-options-list-sm">
                                <?php foreach ($order_product['option'] as $option) { ?>
                                    <dt>
                                        <small title="<?php echo $option['title'] ?>">
                                            - <?php echo $option['name']; ?></small>
                                    </dt>
                                    <dd>
                                        <small title="<?php echo $option['title'] ?>"><?php echo $option['value']; ?></small>
                                    </dd>
                                <?php } ?>
                            </dl>
                        <?php } ?></td>
                    <td class="align-center">
                        <?php echo $order_product['quantity']; ?>
                    </td>
                    <td class="align-center">
                        <?php echo $order_product['price']; ?>
                    </td>
                    <td class="align-center">
                        <?php echo $order_product['total']; ?>
                    </td>
                </tr>
                </tbody>
            <?php } ?>

            <?php echo $this->getHookVar('list_more_product_last'); ?>
        </table>
        <table class="table totals-table">
            <tr>
                <td class="col-sm-6">
                    <table class="original-totals-table table table-striped col-sm-2 col-sm-offset-4 pull-right"></table>
                </td>
                <td class="col-sm-6">
                    <table class="table table-striped col-sm-2 col-sm-offset-4 pull-right">
                        <tbody id="totals">
                        <?php $order_total_row = 0;
                        $count = 0;
                        $total = count($totals); ?>
                        <?php foreach ($totals as $total_row) { ?>
                            <tr>
                                <td class="right">
                                    <?php echo $total_row['title']; ?>
                                </td>
                                <td><?php
                                    if (!in_array($total_row['type'], ['total'])) {
                                        echo html_entity_decode($total_row['text']);
                                    } else { ?>
                                        <b class="<?php echo $total_row['type']; ?>">
                                            <?php echo html_entity_decode($total_row['text']); ?>
                                        </b>
                                    <?php }
                                    $count++;
                                    ?>
                                </td>
                            </tr>
                            <?php $order_total_row++ ?>
                        <?php } ?>
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>

        <div class="panel-footer col-xs-12">
            <div class="text-center">
                <a class="btn btn-primary on_save_close">
                    <i class="fa fa-save"></i> <?php echo $button_save_and_close; ?>
                </a>&nbsp;
                <a class="btn btn-default" data-dismiss="modal" href="<?php echo $cancel; ?>">
                    <i class="fa fa-close"></i> <?php echo $button_close; ?>
                </a>
            </div>
        </div>
        </form>

</div>

<script language="JavaScript" type="application/javascript">

    $('#<?php echo $form['form_open']->name; ?>').submit(function () {
        save_changes();
        return false;
    });
    //save and close modal
    $('.on_save_close').on('click', function () {
        var $btn = $(this);
        save_changes();
        $btn.closest('.modal').modal('hide');
        return false;
    });

    function save_changes() {
        $.ajax({
            url: '<?php echo $update; ?>',
            type: 'POST',
            data: $('#<?php echo $form['form_open']->name; ?>').serializeArray(),
            dataType: 'json',
            success: function (data) {
                success_alert(<?php abc_js_echo($text_saved); ?>, true);
            }
        });
    }
</script>