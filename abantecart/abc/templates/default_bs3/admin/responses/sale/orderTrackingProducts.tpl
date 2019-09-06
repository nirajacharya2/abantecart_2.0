<?php include($tpl_common_dir . 'action_confirm.tpl'); ?>

<div class="modal-header">
    <button aria-hidden="true" data-dismiss="modal" class="close" type="button">&times;</button>
    <h4 class="modal-title"><?php  echo $title; ?></h4>
</div>

<div class="modal-body">
    <div id="content" class="tab-content">

    <?php echo $form['form_open']; ?>

    <div class="panel-body panel-body-nopadding tab-content">
        <table id="orderProducts" class="table ">
            <thead>
            <tr>
                <td class="align-left"><?php echo $column_product; ?></td>
                <td class="align-right"><?php echo $column_status; ?></td>
                <td class="align-right"><?php echo $column_quantity; ?></td>
                <td class="align-right"><?php echo $column_price; ?></td>
                <td class="align-right"><?php echo $column_total; ?></td>
                <?php /*?>
                <td class="align-right"><?php echo $column_action; ?></td>
 <?php */ ?>
            </tr>
            </thead>

            <?php foreach ($order_products as $order_product) {
                $oid = $order_product['order_product_id']; ?>
                <tbody id="product_<?php echo $oid; ?>">
                <tr <?php if (!$order_product['product_status']
                || $order_product['disable_edit']) { ?>class="alert alert-warning"<?php } ?>>
                    <td class="align-left" data-order-product-id="<?php echo $oid; ?>">
                        <a target="_blank"
                           href="<?php echo $order_product['href']; ?>">
                            <?php
                            echo $order_product['name'].($order_product['model'] ? '('.$order_product['model'].')' : ''); ?>
                        </a>
                        <input type="hidden"
                               name="product[<?php echo $oid; ?>][order_product_id]"
                               value="<?php echo $oid; ?>"/>
                        <input type="hidden"
                               name="product[<?php echo $oid; ?>][product_id]"
                               value="<?php echo $order_product['product_id']; ?>"/>

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
                    <td>
                        <?php echo $order_product['order_status_id']; ?>
                    </td>
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
    </div>

    <div class="panel-footer">
        <div class="center">
            <a class="btn btn-primary on_save_close lock-on-click">
                <i class="fa fa-save fa-fw"></i> <?php echo $button_save; ?>
            </a>&nbsp;
            &nbsp;
            <a class="btn btn-default" data-dismiss="modal" href="<?php echo $cancel; ?>">
                <i class="fa fa-window-close fa-fw"></i> <?php echo $button_close; ?>
            </a>
        </div>
    </div>
    </form>

</div>
</div>

<script type="text/javascript">

        $('#orderTrackProductFrm select').each(function () {
            $.aform.styleGridForm(this);
        });

    //save and close mode
    $('.on_save_close').on('click', function(e){
        var $btn = $(this);
        save_order_products_changes();
        $btn.closest('.modal').modal('hide');
        e.preventDefault();
        return false;
    });

    function save_order_products_changes(){
        $.ajax({
            url: '<?php echo $form['form_open']->action; ?>',
            type: 'POST',
            data: $('#orderTrackProductFrm').serializeArray(),
            dataType: 'json',
            success: function (data) {
                    success_alert(data.result_text);
            }
        });
        return false;
    }

    $('#orderTrackProductFrm select').on('change', function(){
        var recalc_statuses = <?php echo json_encode($cancel_statuses); ?>;
        if($.inArray(parseInt($(this).val()), recalc_statuses) >= 0){
            if(confirm(<?php abc_js_echo($redirect_confirm_text);?>)){
                $('#orderTrackProductFrm').attr('data-confirm-exit', false);
                location = '<?php echo $order_edit_url?>';
            }else{
                $(this).val($(this).find('option[selected=selected]').attr('value')).change();
            }
        }
    });

</script>