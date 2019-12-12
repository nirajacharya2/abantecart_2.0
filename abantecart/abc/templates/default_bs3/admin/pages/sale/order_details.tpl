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
                <a class="btn btn-white tooltips" target="_invoice" href="<?php echo $invoice_url; ?>"
                   data-toggle="tooltip"
                   title="<?php echo $text_invoice; ?>" data-original-title="<?php echo $text_invoice; ?>">
                    <i class="fa fa-file-alt"></i>
                </a>
            </div>
        </div>

        <?php include($tpl_common_dir.'content_buttons.tpl'); ?>
    </div>

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
                        <div class="input-group afield col-sm-7">
                            <p class="form-control-static">
                                <?php if ($invoice_id) {
                                    echo $invoice_id;
                                } else {
                                    $button_invoice->style = 'btn btn-info';
                                    echo $button_invoice;
                                } ?>
                            </p></div>
                    </div>
                <?php } ?>
                <div class="form-group">
                    <label class="control-label col-sm-5"><?php echo $entry_customer; ?></label>
                    <div class="input-group afield col-sm-7">
                        <p class="form-control-static">
                            <?php if ($customer_href) { ?>
                                <a class="btn btn-default"
                                   data-toggle="modal"
                                   data-target="#viewport_modal"
                                   href="<?php echo $customer_vhref; ?>"
                                   data-fullmode-href="<?php echo $customer_href ?>">
                                    <i class="fa fa-eye"></i>
                                    <?php echo $firstname.' '.$lastname; ?></a>
                                <?php
                            } else {
                                echo $firstname.' '.$lastname;
                            } ?>
                        </p>
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
                <td></td>
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
                <tr <?php if ($order_product['disable_edit']) { ?>class="alert alert-warning"<?php } ?>>
                    <td>
                        <?php if ( !$order_product['disable_edit']) { ?>
                            <a class="edit_product btn btn-xs btn-info-alt tooltips"
                               data-original-title="<?php echo $text_edit; ?>"
                               data-order-product-id="<?php echo $oid; ?>">
                                <i class="fa fa-pencil-alt"></i>
                            </a>
                        <?php } ?>
                    </td>
                    <td class="align-left" data-order-product-id="<?php echo $oid; ?>">
                        <a target="_blank"
                           href="<?php echo $order_product['href']; ?>">
                            <?php
                            echo $order_product['name'].($order_product['model'] ? '('.$order_product['model']
                                    .')' : '');
                            echo ' - '.$order_product['order_status']; ?>
                        </a>
                        <input type="hidden"
                               name="product[<?php echo $oid; ?>][order_product_id]"
                               value="<?php echo $oid; ?>"/>
                        <input type="hidden"
                               name="product[<?php echo $oid; ?>][product_id]"
                               value="<?php echo $order_product['product_id']; ?>"/>
                        <input type="hidden"
                               name="product[<?php echo $oid; ?>][order_status_id]"
                               value="<?php echo $order_product['order_status_id']; ?>"/>
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
                                        <input type="hidden"
                                               name="product[<?php echo $oid; ?>][option][<?php echo $option['product_option_id']; ?>]"
                                               value="<?php echo $option['product_option_value_id']; ?>"/>
                                    </dd>
                                <?php } ?>
                            </dl>
                        <?php } ?></td>
                    <td class="align-center">
                        <?php echo $order_product['quantity']; ?>
                        <input class="afield no-save" type="hidden"
                               name="product[<?php echo $oid; ?>][quantity]"
                               value="<?php echo $order_product['quantity']; ?>"/>
                    </td>
                    <td class="align-center">
                        <?php echo $order_product['price']; ?>
                        <input class="afield no-save" type="hidden"
                               name="product[<?php echo $oid; ?>][price]"
                               value="<?php echo $order_product['price_value']; ?>"/>
                    </td>
                    <td class="align-center">
                        <?php echo $order_product['total']; ?>
                        <input class="afield no-save" type="hidden"
                               name="product[<?php echo $oid; ?>][total]"
                               value="<?php echo $order_product['total_value']; ?>"/>
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
                    <input id="original_total" type="hidden" value="<?php echo $order_info['total'] ?>" disabled>
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
                        <?php //ADD NEW TOTAL ?>
                        <tr>
                            <td id="manual_totals" class="align-right"></td>
                            <td>
                                <a class=" hidden add_totals btn btn-xs btn-success tooltips"
                                   data-original-title="<?php echo $text_add; ?>"
                                   data-order-id="<?php echo $order_id; ?>">
                                    <i class="fa fa-plus-circle"></i>
                                </a>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <?php if ($add_product) { ?>
            <div class="container-fluid form-inline">
                <div class="list-inline col-sm-12"><?php echo $entry_add_product; ?></div>
                <div class="list-inline input-group afield col-sm-7 col-xs-9">
                    <?php echo $add_product; ?>
                </div>
            </div>
        <?php } ?>
    </div>

    <div class="panel-footer col-xs-12">
        <div id="balance-alert" class="warning alert alert-error alert-danger hidden text-center">
            Total amount not equal previous value. You have balance disabled in the settings.
            So you cannot to save order, because needs to create transaction.
            <?php echo $warning_balance_disabled; ?></div>
        <div id="submit-buttons" class="text-center">
            <button class="btn btn-primary lock-on-click">
                <i class="fa fa-save fa-fw"></i> <?php echo $button_save; ?>
            </button>
            <a class="btn btn-default" href="<?php echo $cancel; ?>">
                <i class="fa fa-arrow-left fa-fw"></i> <?php echo $form['cancel']->text; ?>
            </a>
        </div>
    </div>
    </form>

</div><!-- <div class="tab-content"> -->

<?php echo $this->html->buildElement(
    [
        'type'        => 'modal',
        'id'          => 'add_product_modal',
        'modal_type'  => 'lg',
        'data_source' => 'ajax',
        'js_onclose'  => '$("#add_product_modal").find("div.modal-content").html("");',
    ]);
?>

<?php
//ADD MANUAL ORDER TOTAL

$modal_html = '
    <div class="mb20">'.$manual_totals.'</div>
    <div class="content container-fluid mb20">
    
        <div id="add_manual_coupon" class="manual-total form-inline hidden">
            <label class="checkbox">'.$entry_coupon_code.'</label>
            '.$manual_coupon_code_field.'
        </div>
        '.$this->getHookVar('order_edit_manual_total_var').'
    </div>
    <div class="text-center mb20">
        <button class="btn btn-primary">
        <i class="fa fa-save fa-fw"></i>'.$button_add.'
        </button>
        <button class="btn btn-default" type="button" data-dismiss="modal" aria-hidden="true">
        <i class="fa fa-arrow-left fa-fw"></i> '.$button_cancel.'
        </button>
    </div>';
echo $this->html->buildElement(
    [
        'type'       => 'modal',
        'id'         => 'add_order_total',
        'modal_type' => 'md',
        'title'      => $text_order_total_add,
        'content'    => $modal_html,
    ]);
?>

<script type="text/javascript">

    var decimal_point = '<?php echo $decimal_point; ?>';
    var decimal_place = '<?php echo $currency['decimal_place']; ?>';
    var thousand_point = '<?php echo $thousand_point; ?>';

    var currency_symbol = '<?php echo $currency['symbol_left'] ?? $currency['symbol_right']; ?>';
    var currency_location = '<?php echo $currency['symbol_left'] ? 'left' : 'right'; ?>';

    $(function () {

        $('#add_product').chosen({'width': '100%', 'white-space': 'nowrap'});
        $('#add_product').on('change', ProductModal);

        $("#products input").aform({triggerChanged: false});
        $('#products input[type*="text"]').each(function () {
            $.aform.styleGridForm(this);
        });


        $(document).on('click', '#products a.remove', function () {
            var id = $(this).attr('data-order-product-row');
            $('#product_' + id).remove();
            recalculateTotals();
            return false;
        });

        $('a.add').click(function () {
            ProductModal();
            return false;
        });


        $(document).on('click', 'a.edit_product', function () {
            ProductModal($(this).attr('data-order-product-id'));
            return false;
        });


    });

    function recalculateTotals() {

        $.ajax({
            url: '<?php echo $recalculate_totals_url; ?>',
            dataType: 'json',
            type: 'post',
            data: $('#orderFrm').serialize(),
            beforeSend: function () {
                //    $('#generate_invoice').attr('disabled', 'disabled');
            },
            complete: function () {
                //$('#generate_invoice').attr('disabled', '');
            },
            success: function (data) {

                var totals = $('table>tbody#totals');
                if ($('#original-totals').length == 0) {
                    var clone = totals.clone();
                    clone.attr('id', 'original-totals').css('opacity', 0.3);
                    clone.appendTo($('.original-totals-table'));
                }
                totals.html('');
                $('#submit-buttons').show();

                if (data.hasOwnProperty('totals')) {
                    var totalKeys = [],
                        cancel_order = false;
                    if (data.totals.length === 0) {
                        data.totals['0'] = {id: 'total', title: 'Total', text: '0.00', value: 0.0};
                        cancel_order = true;
                    }
                    $.each(data.totals, function (index, row) {
                        totalKeys[index] = row.key;
                        var new_row = $('<tr><td id="total-row-' + row.id + '" class="pull-right">'
                            + row.title + '</td><td>' + row.text + '</td></tr>'
                        );
                        if (row.id === 'total') {
                            new_row.find('td:eq(1)').html('<b class="total">' + row.text + '</b>');
                        }
                        $.each(row, function (idx, val) {
                            $('<input type="hidden" name="order_totals[' + row.id + '][' + idx + ']" >')
                                .val(val)
                                .appendTo(new_row.find('#total-row-' + row.id));
                        });
                        new_row.appendTo(totals);
                    });
                    if (!cancel_order) {
                        //show button to add additional total such as coupon
                        $('a.add_totals').removeClass('hidden');
                    }

                    //compare two totals (current and calculated) and mark disbalance
                    var new_total = $('input[name="order_totals\[total\]\[value\]"]').val();
                    var old_total = $('#original_total').val();

                    var cssClass = '';
                    if (old_total > new_total) {
                        cssClass = "alert-danger";
                    } else if (old_total < new_total) {
                        cssClass = "alert-warning";
                    }

                    $('tbody#totals').find('td>b.total').parent().addClass(cssClass);
                    <?php if($balance_disabled){ ?>
                    if (cssClass.length > 0) {
                        $('#submit-buttons').hide();
                        $('#balance-alert').removeClass('hidden');
                    } else {
                        $('#submit-buttons').show();
                        $('#balance-alert').addClass('hidden');
                    }
                    <?php } ?>
                }else if(data.hasOwnProperty('error')){
                    var error_text = $('<tr id="totals_error"><td class="col-sm-12"><div class="alert-danger">' + data.error.text + '</div></td></tr>');
                    error_text.appendTo(totals);
                    $('#submit-buttons').hide();
                }
            }
        });
        if (event) {
            event.stopPropagation();
        }
    }

    $('#generate_invoice').click(function () {
        var that = $(this).parents('p');
        $.ajax({
            url: '<?php echo $invoice_generate; ?>&order_id=<?php echo $order_id; ?>',
            dataType: 'json',
            beforeSend: function () {
                $('#generate_invoice').attr('disabled', 'disabled');
            },
            complete: function () {
                $('#generate_invoice').attr('disabled', '');
            },
            success: function (data) {
                if (data.hasOwnProperty('invoice_id')) {
                    $('#generate_invoice').fadeOut('slow', function () {
                        that.html(data.invoice_id).fadeIn();
                    });
                }
            }
        });
        return false;
    });

    function ProductModal(order_product_id) {
        var queryParams = '';
        if (order_product_id > 0) {
            queryParams = '&order_product_id=' + order_product_id;
        } else {
            var vals = $("#add_product").chosen().val();
            $("#add_product").val('').trigger("chosen:updated");
            if (vals) {
                queryParams = '&product_id=' + vals[0];
            } else {
                return false;
            }
        }

        var order_status_id = $('input[name="product\[' + order_product_id + '\]\[order_status_id\]"\]').val();
        if(order_status_id !== undefined) {
            queryParams += '&order_status_id=' + order_status_id;
        }
        queryParams += '&quantity='+$('input[name="product\[' + order_product_id + '\]\[quantity\]"\]').val();;

        if (queryParams.length > 0) {
            $('#add_product_modal')
                .modal({keyboard: false})
                .find('.modal-content')
                .load('<?php echo $add_product_url; ?>' + queryParams, function () {
                    //formOnExit();
                    bindCustomEvents('#orderProductFrm');
                    spanHelp2Toggles();
                });
        }
    }

    var newRowCounter = 0;

    function AddProductToForm(data) {
        if (data.form.length === 0) {
            return false;
        }
        var newRow,
            oid = data.order_product_id;
        var edit_mode = (oid > 0);

        if (!edit_mode) {
            oid = 'new' + newRowCounter;
            newRow = $('#products tbody').first().clone()
                .prop('id', 'product_' + oid)
                .addClass('alert-success');
        } else {
            newRow = $('#products tbody#product_' + oid);
        }

        var td = newRow.find('td:eq(0)');

        if (!edit_mode) {
            td.find('a.edit_product').remove();
            $(
                '<a class="remove btn btn-xs btn-danger-alt tooltips" ' +
                'data-original-title="<?php echo $button_delete; ?>" ' +
                'data-order-product-row="' + oid + '"><i class="fa fa-minus-circle"></i></a>'
            ).prependTo(td);
        } else {
            td.find('a.edit_product').prop('data-order-product-id', oid);
        }

        //product name with options
        td = newRow.find('td:eq(1)');
        td.find('a')
            .prop('href', data.product_url)
            .text(data.product_name);
        td.find('input').remove();
        td.find('dl').remove();
        $('<input type="hidden" name="product[' + oid + '][order_product_id]" value="' + data.order_product_id + '">').appendTo(td);
        $('<input type="hidden" name="product[' + oid + '][product_id]" value="' + data.product_id + '">').appendTo(td);
        $('<input type="hidden" name="product[' + oid + '][order_status_id]" value="' + data.order_status_id + '">').appendTo(td);

        var options = $('<dl class="dl-horizontal product-options-list-sm"></dl>'), product_data = {};

        $.each(data.form,
            function (index, value) {
                if (value.name.startsWith("option[")) {
                    $('<input ' +
                        'type="hidden" ' +
                        'name="product[' + oid + ']' + value.name.replace('option[', '[option][') + '" ' +
                        'value="' + value.value + '">').appendTo(td);

                    $('<dt>' +
                        '<small>- ' + value.text + '</small>' +
                        '</dt>' +
                        '<dd>' +
                        '<small>' + (value.value_text ? value.value_text : value.value) +
                        '</small></dd>').appendTo(options);
                } else {
                    product_data[value.name] = value.value;
                }
            }
        );
        options.appendTo(td);
        //quantity
        newRow.find('td:eq(2)').html(
            product_data.quantity +
            '<input ' +
            'type="hidden" ' +
            'name="product[' + oid + '][quantity]" ' +
            'value="' + product_data.quantity + '">'
        );
        //price
        newRow.find('td:eq(3)').html(
            product_data.price +
            '<input ' +
            'type="hidden" ' +
            'name="product[' + oid + '][price]" ' +
            'value="' + currencyToNumber(product_data.price, thousand_point, decimal_point, currency_symbol) + '">'
        );
        //total
        newRow.find('td:eq(4)').html(
            product_data.total +
            '<input ' +
            'type="hidden" ' +
            'name="product[' + oid + '][total]" ' +
            'value="' + currencyToNumber(product_data.total, thousand_point, decimal_point, currency_symbol) + '">'
        );

        <?php echo $this->getHookVar('extend_js'); ?>

        if (product_data.quantity > 0) {
            newRow.addClass('alert-warning');
        } else {
            newRow.addClass('alert-danger');
        }

        if (!edit_mode) {
            newRow.appendTo('#products');
        }

        if(data.editable) {
            recalculateTotals();
        }

        newRowCounter++;
        $('#orderFrm').prop('changed', 'submit').attr('data-confirm-exit', 'false');
    }

    $('a.add_totals').click(function () {
        $('#add_order_total').modal({keyboard: false});
        return false;
    });

    <?php // "ADD MANUAL" TOTAL JS ?>

    $("#orderFrm_manual_total").on("change", function () {
        var div = $("#add_order_total").find("div.manual-total");
        div.addClass("hidden");
        div.find("input, select, textarea").attr("disabled", "disabled");

        if ($(this).val() !== "") {
            div = $("#add_order_total").find("div#add_manual_" + $(this).val());
            div.removeClass("hidden");
            div.find("input, select, textarea").removeAttr("disabled");
        }
    });

    $("#add_order_total").find("button.btn-primary").on("click", function (e) {
        var div = $("#add_order_total").find("div.manual-total").not(".hidden");

        if (div.length > 0 && div.attr("id").length > 0) {
            try {
                //call function dynamically
                // its name must be equal to div ID
                window[div.attr("id")]();
            } catch (e) {
                alert("cannot find function " + div.attr("id") + "!")
            }
        } else {

        }
        e.stopPropagation();
        $('#orderFrm').prop('changed', 'submit').attr('data-confirm-exit', 'false');
    });

    var add_manual_coupon = function () {
        $.ajax(
            {
                url: "<?php echo $validate_coupon_url; ?>&coupon_code=" + $("#orderFrm_coupon_code").val(),
                dataType: 'json',
                type: 'post',
                data: $('#orderFrm').serialize(),
                success: function (data) {
                    $('input[name="manual_totals[coupon][coupon_code]"]').remove();
                    $('<input type="hidden" name="manual_totals[coupon][coupon_code]" value="' + data.code + '">')
                        .appendTo($("#manual_totals"));
                    recalculateTotals();

                    $("#add_order_total").modal("hide");
                    $("#orderFrm_manual_total").val('').change();
                }
            }
        );
    };

    $('#orderFrm').on('submit', function () {
        $(this).prop('changed', 'submit').attr('data-confirm-exit', 'false');
    });

</script>