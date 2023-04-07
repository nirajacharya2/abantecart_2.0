<?php include($tpl_common_dir . 'action_confirm.tpl');
echo $tabs;
?>

<div id="content" class="panel panel-default">

    <div class="panel-heading col-xs-12">
        <?php include($tpl_common_dir . 'content_buttons.tpl'); ?>
    </div>

    <?php echo $form['form_open']; ?>
    <div class="panel-body panel-body-nopadding tab-content col-xs-12">
        <label class="h4 heading"><?php echo $form_title; ?></label>
        <div id="bonuses_list">
            <?php
            foreach ($form['fields'] as $name => $field_arr) { ?>
                <div class="form-group" data-row_id="<?php echo $field_arr['id'] ?>">
                    <label class="control-label col-sm-3 col-xs-12"
                           for="<?php echo $field->element_id; ?>"><?php echo $field_arr['label']; ?></label>
                    <div class="form-inline afield col-sm-7 col-xs-12">
                        <?php echo $field_arr['html']; ?>
                        &nbsp;<a class="btn btn-danger" data-confirmation="delete" onclick="removeBonus(this);"><i
                                class="fa fa-minus"></i></a>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>

    <div class="panel-body panel-body-nopadding tab-content col-xs-12">
        <label class="h4 heading"><?php echo $bonus_object['text']; ?></label>
        <div class="form-group form-inline">
            <label class="control-label col-sm-3 col-xs-12"></label>
            <div class="input-group afield col-sm-3 col-xs-12">
                <?php echo $bonus_object['field']; ?>
            </div>
            <div class="input-group afield col-sm-3 col-xs-12 col-">
                <a id="add_bonus" class="btn btn-success"><i class="fa fa-plus"></i></a>
            </div>
        </div>

        <div class="panel-footer col-xs-12">
            <div class="text-center">
                <button class="btn btn-primary lock-on-click">
                    <i class="fa fa-save fa-fw"></i> <?php echo $form['submit']->text; ?>
                </button>
                <a class="btn btn-default" href="<?php echo $cancel_href; ?>">
                    <i class="fa fa-arrow-left fa-fw"></i> <?php echo $form['cancel']->text; ?>
                </a>
            </div>
        </div>
    </div>
    </form>
</div>


<script type="text/javascript">
    var wrap = function () {
        let multi = $('div.chosen-container-multi');
        if (multi) {
            multi.css('width', '90%').addClass('mt10');
        }
        $('#incentiveFrm').find("input, select, textarea").aform({triggerChanged: true, showButtons: false});
    }

    $('#incentiveFrm').on('submit', function () {
        $('input[id*="productsquantity"]').not('.chosen-choices input[id*="productsquantity"]').attr('disabled', 'disabled');
    });


    var idx = $('#bonuses_list div.form-group').length + 1;
    $('#add_bonus').click(function () {
        var row_id = $('#incentiveFrm_bonus_object').val();
        if (row_id === '' || $('div[data-row_id="' + row_id + '"]').length > 0) {
            return null;
        }

        $.ajax({
            url: '<?php echo $bonus_url; ?>',
            type: 'POST',
            dataType: 'json',
            data: {'bonus_id': row_id, 'idx': idx},
            success: function (data) {
                $('#bonuses_list').append(
                    '<div class="form-group" data-row_id="' + row_id + '">' +
                    '<label class="control-label col-sm-3 col-xs-12">' + data.label + '</label>' +
                    '<div class="form-inline afield col-sm-9 col-xs-12">' + data.html + '&nbsp;<a class="btn btn-danger remove_cond" data-confirmation="delete" onclick="removeBonus(this);">' +
                    '<i class="fa fa-minus"></i></a></div></div>'
                );

                $("#incentiveFrm").prop('changed', 'true');
                idx++;

                $('#incentiveFrm_bonus_object').val('').change();
                wrap();
            }
        });
    });

    var removeBonus = function (elm) {
        $(elm).parents('.form-group').remove();
        $("#incentiveFrm").prop('changed', 'true');
        $('#incentiveFrm_bonus_object').val('').change();
    }
</script>