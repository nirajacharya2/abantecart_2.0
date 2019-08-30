<?php include($tpl_common_dir.'action_confirm.tpl'); ?>

<?php echo $summary_form; ?>

<?php echo $order_tabs ?>

<div id="content" class="panel panel-default">

    <div class="panel-heading col-xs-12">
        <div class="primary_content_actions pull-left">

            <?php if (!empty ($list_url)) { ?>
                <div class="btn-group">
                    <a class="btn btn-white tooltips" href="<?php echo $list_url; ?>" data-toggle="tooltip"
                       data-original-title="<?php echo $text_back_to_list; ?>">
                        <i class="fa fa-arrow-left fa-lg"></i>
                    </a>
                </div>
            <?php } ?>
            <div class="btn-group mr10 toolbar">
                <?php if ($register_date) { ?>
                    <a class="btn btn-white disabled"><?php echo $register_date; ?></a>
                <?php } ?>
                <?php if ($last_login) { ?>
                    <a class="btn btn-white disabled"><?php echo $last_login; ?></a>
                <?php } ?>
                <a class="btn btn-white" href="<?php echo $transactions_url; ?>"
                   target="_new"><?php echo $balance; ?></a>
                <a target="_blank"
                   class="btn btn-white tooltips"
                   href="<?php echo $button_orders_count->href; ?>"
                   data-toggle="tooltip"
                   title="<?php echo $button_orders_count->title; ?>"
                   data-original-title="<?php echo $button_orders_count->title; ?>"><?php echo $button_orders_count->text; ?>
                </a>
                <a target="_blank"
                   class="btn btn-white tooltips"
                   href="<?php echo $message->href; ?>"
                   data-toggle="tooltip"
                   title="<?php echo $message->text; ?>"
                   data-original-title="<?php echo $message->text; ?>"><i class="fa fa-paper-plane"></i>
                </a>
                <a target="_blank"
                   class="btn btn-white tooltips"
                   href="<?php echo $actas->href; ?>"
                   data-toggle="tooltip"
                   title="<?php echo $actas->text; ?>"
                    <?php
                    //for additional store show warning about login in that store's admin (because of crossdomain restriction)
                    if ($warning_actonbehalf) { ?>
                        data-confirmation="delete"
                        data-confirmation-text="<?php echo $warning_actonbehalf; ?>"
                    <?php } ?>
                   data-original-title="<?php echo $actas->text; ?>"><i class="fa fa-male"></i>
                </a>
            </div>
        </div>
        <?php include($tpl_common_dir.'content_buttons.tpl'); ?>
    </div>

    <?php echo $form['form_open']; ?>
    <div class="panel-body panel-body-nopadding tab-content col-xs-12">
        <label class="h4 heading"><?php echo $form_title; ?></label>
    </div>

    <div class="panel-body panel-body-nopadding tab-content col-xs-12">
        <label class="h5 heading"><?php echo $text_common_order_details; ?></label>
        <div class="form-group col-sm-12 col-xs-12">
            <label class="control-label col-sm-3 col-xs-12"><?php echo $entry_order_language; ?></label>
            <div class="input-group afield col-sm-7  col-xs-12">
                <?php echo $order_language_id; ?>
            </div>
        </div>
        <div class="form-group col-sm-12 col-xs-12">
            <label class="control-label col-sm-3 col-xs-12"><?php echo $entry_order_currency; ?></label>
            <div class="input-group afield col-sm-7  col-xs-12">
                <?php echo $order_currency; ?>
            </div>
        </div>

        <label class="h5 heading "></label>
        <div class="form-group col-sm-12 col-xs-12">
            <label class="control-label col-sm-3 col-xs-12"><?php echo $text_order_products; ?></label>
            <div class="input-group afield col-sm-7  col-xs-12">
                <table id="products" class="table">
                    <thead>
                    <tr>
                        <td></td>
                        <td class="align-left"><?php echo $column_product; ?></td>
                        <td class="align-right"><?php echo $column_quantity; ?></td>
                        <td class="align-right"><?php echo $column_price; ?></td>
                        <td class="align-right"><?php echo $column_total; ?></td>
                    </tr>
                    </thead>

                    <?php $order_product_row = 0; ?>
                    <?php foreach ($order_products as $order_product) { ?>
                        <tbody id="product_<?php echo $order_product_row; ?>">
                        <tr <?php if (!$order_product['product_status']) { ?>class="alert alert-warning"<?php } ?>>
                            <td>
                                <a class="remove btn btn-xs btn-danger-alt tooltips"
                                   data-original-title="<?php echo $button_delete; ?>"
                                   data-order-product-row="<?php echo $order_product_row; ?>"
                                   href="<?php echo $order_product['remove_url'] ?>"
                                >
                                    <i class="fa fa-minus-circle"></i>
                                </a>
                                <?php if ($order_product['product_status']) { ?>
                                    <a class="edit_product btn btn-xs btn-info-alt tooltips"
                                       data-original-title="<?php echo $text_edit; ?>"
                                       data-order-product-id="<?php echo $order_product['order_product_id']; ?>">
                                        <i class="fa fa-pencil-alt"></i>
                                    </a>
                                <?php } ?>
                            </td>
                            <td class="align-left">
                                <a target="_blank"
                                   href="<?php echo $order_product['href']; ?>"><?php echo $order_product['name']; ?>
                                    <?php echo $order_product['model'] ? '('.$order_product['model'].')' : ''; ?>
                                </a>
                                <input type="hidden"
                                       name="product[<?php echo $order_product_row; ?>][order_product_id]"
                                       value="<?php echo $order_product['order_product_id']; ?>"/>
                                <input type="hidden"
                                       name="product[<?php echo $order_product_row; ?>][product_id]"
                                       value="<?php echo $order_product['product_id']; ?>"/>
                                <?php
                                if ($order_product['option']) { ?>
                                    <dl class="dl-horizontal product-options-list-sm">
                                        <?php
                                        foreach ($order_product['option'] as $option) { ?>
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
                            <td class="align-right">
                                <input class="afield no-save" type="text"
                                    <?php if (!$order_product['product_status']) { ?>
                                        readonly
                                    <?php } ?>
                                       name="product[<?php echo $order_product_row; ?>][quantity]"
                                       value="<?php echo $order_product['quantity']; ?>"
                                       size="4"/></td>
                            <td><input class="no-save pull-right" type="text"
                                       readonly
                                       name="product[<?php echo $order_product_row; ?>][price]"
                                       value="<?php echo $order_product['price']; ?>"/></td>
                            <td><input readonly class="no-save pull-right" type="text"
                                       name="product[<?php echo $order_product_row; ?>][total]"
                                       value="<?php echo $order_product['total']; ?>"/></td>
                        </tr>
                        </tbody>
                        <?php $order_product_row++ ?>
                    <?php } ?>
                </table>
                <div class="container-fluid form-inline">
                    <div class="list-inline col-sm-12"><?php echo $entry_add_product; ?></div>
                    <div class="list-inline input-group afield col-sm-7 col-xs-9">
                        <?php echo $add_product; ?>
                    </div>
                    <div class="list-inline input-group afield col-sm-offset-0 col-sm-3 col-xs-1">
                        <a class="add btn btn-success tooltips"
                           data-original-title="<?php echo $text_add; ?>">
                            <i class="fa fa-plus-circle fa-lg"></i></a>
                    </div>
                </div>
            </div>
            <?php
            if (!$error_warning) {
                ?>
                <label class="h5 heading"><?php echo $text_shipping_details; ?></label>
                <div class="form-group col-sm-12 col-xs-12">
                    <label class="control-label col-sm-3 col-xs-12"><?php echo $entry_shipping_address; ?></label>
                    <div class="input-group afield col-sm-7 col-xs-12">
                        <?php echo $shipping_address; ?>
                    </div>
                </div>
                <div id="shipping_method_container" class="form-group col-sm-12 col-xs-12">
                    <label class="control-label col-sm-3 col-xs-12"><?php echo $entry_shipping_method; ?></label>
                    <div class="input-group afield col-sm-7  col-xs-12">
                        <?php echo $shipping_method; ?>
                    </div>
                </div>

                <label class="h5 heading"><?php echo $text_payment_details; ?></label>
                <div class="form-group col-sm-12 col-xs-12">
                    <label class="control-label col-sm-3 col-xs-12"><?php echo $entry_payment_address; ?></label>
                    <div class="input-group afield col-sm-7  col-xs-12">
                        <?php echo $payment_address; ?>
                    </div>
                </div>
                <div id="payment_method_container" class="form-group col-sm-12 col-xs-12">
                    <label class="control-label col-sm-3 col-xs-12"><?php echo $entry_payment_method; ?></label>
                    <div class="input-group afield col-sm-7  col-xs-12">
                        <?php echo $payment_method; ?>
                    </div>
                </div>
                <div class="form-group col-sm-12 col-xs-12">
                    <label class="control-label col-sm-3 col-xs-12"><?php echo $entry_coupon_code; ?></label>
                    <div class="input-group afield col-sm-7  col-xs-12">
					<span class="input-group-btn">
						<?php echo $apply_coupon_button; ?>
					</span>
                        <?php echo $coupon_code; ?>
                    </div>
                </div>

                <div class="container-fluid cart_total">
                    <div class="col-md-6 cart-info totals pull-right table-responsive">
                        <table id="totals_table" class="table table-striped table-bordered">
                        </table>
                    </div>
                </div>

            <?php } ?>
        </div>
    </div>
    <?php
    if (!$error_warning) {
        ?>
        <div class="panel-footer col-xs-12">
            <div class="text-center">
                <button class="btn btn-primary lock-on-click">
                    <i class="fa fa-save fa-fw"></i> <?php echo $button_create_order; ?>
                </button>
                <a class="btn btn-default" href="<?php echo $cancel; ?>">
                    <i class="fa fa-arrow-left fa-fw"></i> <?php echo $form['cancel']->text; ?>
                </a>
            </div>
        </div>
    <?php } ?>
    </form>

</div><!-- <div class="tab-content"> -->

<?php echo $this->html->buildElement(
    [
        'type'        => 'modal',
        'id'          => 'add_product_modal',
        'modal_type'  => 'lg',
        'data_source' => 'ajax',
    ]);
?>

<script type="text/javascript">

    $(function () {

        $('#add_product').chosen({'width': '100%', 'white-space': 'nowrap'});
        $('#add_product').on('change', addProduct);

        $("#products input").aform({triggerChanged: false});
        $('#products input[type*="text"]').each(function () {
            $.aform.styleGridForm(this);
        });

        $('a.add').click(function () {
            addProduct();
            return false;
        });


        $('a.edit_product').click(function () {
            addProduct($(this).attr('data-order-product-id'));
            return false;
        });
    });

    function addProduct(order_product_id) {
        var id = '';
        if (order_product_id > 0) {
            id = '&order_product_id=' + order_product_id;
        } else {
            var vals = $("#add_product").chosen().val();
            $("#add_product").val('').trigger("chosen:updated");
            ;
            if (vals) {
                id = '&product_id=' + vals[0];
            }
        }

        if (id.length > 0) {
            $('#add_product_modal')
                .modal({keyboard: false})
                .find('.modal-content')
                .load('<?php echo $add_product_url; ?>' + id, function () {
                    formOnExit();
                    bindCustomEvents('#orderProductFrm');
                    spanHelp2Toggles();
                });
        }
    }

    $('#content #language_id').on('change', function () {
        location = '<?php echo $this->html->getSecureUrl('sale/order/createOrder');?>&language_id=' + $(this).val();
    });
    $('#content #order_currency').on('change', function () {
        location = '<?php echo $this->html->getSecureUrl('sale/order/createOrder');?>&order_currency=' + $(this).val();
    });


    //load total with AJAX call
    function display_totals() {
        var shipping_method = $('#shipping_method :selected').val();
        var coupon = $("#coupon_code input[name=coupon_code]").val();

        if (!shipping_method) {
            shipping_method = '';
        }
        $.ajax({
            type: 'POST',
            url: '<?php echo $recalc_totals_url;?>',
            dataType: 'json',
            data: 'shipping_method_key=' + shipping_method + '&coupon=' + coupon + '&shipping_method=' + $('#shipping_method :selected').text(),
            beforeSend: function () {
                var html = '';
                html += '<tr>';
                html += '<td><div class="progress progress-striped active"><div class="bar" style="width: 100%;"></div></div></td>';
                html += '</tr>';
                $('table#totals_table').html(html);
            },
            complete: function () {
            },
            success: function (data) {
                if (data && data.totals.length) {
                    var html = '';
                    for (var i = 0; i < data.totals.length; i++) {
                        var grand_total = '';
                        if (data.totals[i].id == 'total') {
                            grand_total = 'totalamout';
                        }
                        html += '<tr>';
                        html += '<td><span class="extra bold ' + grand_total + '">' + data.totals[i].title + '</span></td>';
                        html += '<td><span class="bold ' + grand_total + '">' + data.totals[i].text + '</span></td>';
                        html += '</tr>';
                    }
                    $('table#totals_table').html(html);
                }
            }
        });
    };

    function getShippings() {
        var shipping_address_id = $('#shipping_address_id').val();
        if (!shipping_address_id) {
            shipping_address_id = '';
        }
        $.ajax({
            type: 'POST',
            url: '<?php echo $get_shippings_url;?>',
            dataType: 'json',
            data: 'shipping_address_id=' + shipping_address_id,
            beforeSend: function () {
                var html = '';
                html += '<div class="progress progress-striped active"><div class="bar" style="width: 100%;"></div></div>';
                $('#shipping_method_container .afield').html(html);
            },
            complete: function () {
            },
            success: function (data) {
                if (data) {
                    $('#shipping_method_container>label').html(data.title);
                    $('#shipping_method_container .afield').html(data.html);

                    if (data.title.length > 0) {
                        $('#shipping_method_container').show();
                    } else {
                        $('#shipping_method_container').hide();
                    }
                    getPayments();
                }
            }
        });
    };

    function getPayments() {
        var payment_address_id = $('#payment_address_id').val();
        if (!payment_address_id) {
            payment_address_id = '';
        }
        $.ajax({
            type: 'POST',
            url: '<?php echo $get_payments_url;?>',
            dataType: 'json',
            data: 'payment_address_id=' + payment_address_id,
            beforeSend: function () {
                var html = '';
                html += '<div class="progress progress-striped active"><div class="bar" style="width: 100%;"></div></div>';
                $('#payment_method_container .afield').html(html);
            },
            success: function (data) {
                if (!data.error) {
                    $('#payment_method_container>label').html(data.title);
                    $('#payment_method_container .afield').html(data.html);
                    if (data.title.length > 0) {
                        $('#payment_method_container').show();
                    } else {
                        $('#payment_method_container').hide();
                    }
                    $('.panel-footer').show();
                } else {
                    $('.panel-footer').hide();
                    $('#payment_method_container .afield').html('<div class="alert-danger">' + data.html + '</div>');
                }
            },
            complete: function () {
                display_totals();
            },
        });
    };

    $('#apply_coupon_button').on('click',
        function () {
            $.ajax({
                type: 'POST',
                url: '<?php echo $apply_coupon_url;?>',
                dataType: 'json',
                data: 'coupon=' + $('#coupon_code').val(),
                success: function (data) {
                    display_totals();
                }
            });
        });

    var fieldsInit = function () {
        <?php
        if(!$error_warning){ ?>
        getShippings();
        <?php } ?>
    };

    $('#shipping_address_id').on('change', fieldsInit);
    $('#payment_address_id').on('change', function () {
        getPayments();
    });

    $(document).ready(fieldsInit);
</script>