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
        <?php $field = $conditions_relation['fields']['if']; ?>
        <div class="form-group form-inline">
            <div class="col-sm-3 col-xs-12 form-inline">
                <label class="control-label col-sm-5 col-xs-6"
                       for="<?php echo $field['field']->element_id; ?>"><?php echo $field['label']; ?></label>
                <div class="input-group afield col-sm-4 col-xs-6"><?php echo $field['html']; ?></div>
            </div>
            <?php $field = $conditions_relation['fields']['value']; ?>
            <div class="col-sm-5 col-xs-12 form-inline">
                <label class="control-label col-sm-7 col-xs-12"
                       for="<?php echo $field['field']->element_id; ?>"><?php echo $field['label']; ?></label>
                <div class="input-group afield col-sm-5 col-xs-6"><?php echo $field['html']; ?></div>
            </div>
        </div>
        <hr>
        <div id="conditions_list">
            <?php
            foreach ((array)$form['fields'] as $items) {
                foreach ((array)$items as $fieldArr) {
                    ?>
                    <div class="form-group" data-row_id="<?php echo $fieldArr['id'] ?>">
                        <label class="control-label col-sm-3 col-xs-12"><?php echo $fieldArr['label']; ?></label>
                        <div class="form-inline afield">
                            <?php echo $fieldArr['html']; ?>
                            &nbsp;<a class="btn btn-danger remove_cond" data-confirmation="delete"
                                     onclick="removeCondition(this);"><i class="fa fa-minus"></i></a>
                        </div>
                    </div>
                    <?php
                }
            } ?>
        </div>
    </div>

    <?php //conditions list ?>

    <div class="panel-body panel-body-nopadding tab-content col-xs-12">
        <hr>
        <label class="h4 heading"><?php echo $condition_object['label']; ?></label>
        <div class="form-group form-inline">
            <div class="text-right col-sm-3 col-xs-12">
                <?php echo $condition_object['type']; ?>
                <input id="condType" type="hidden" name="conditions[condition_type]" value="">
            </div>
            <div class="input-group afield col-sm-3 col-xs-12">
                <?php echo $condition_object['html']; ?>
            </div>
            <div class="input-group afield col-sm-3 col-xs-12">
                <a id="add_condition" class="btn btn-success"><i class="fa fa-plus"></i></a>
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

    $(document).ready(function () {
        $('.chosen-container-multi, .chosen-container-single').css('width', '40%');
        if ($('#incentiveFrm_condition_type').attr('disabled') !== 'disabled') {
            $('#incentiveFrm_condition_type').change();
        }
        $('#condType').val($('#incentiveFrm_condition_type').val());
    });

    var idx = $('#conditions_list div.form-group').length + 1;
    $('#add_condition').click(function () {
        if ($('#incentiveFrm_condition_object').val() == '' ||
            $('#' + $('#incentiveFrm_condition_object').val()).length > 0
        ) {
            return null;
        }

        $.ajax({
            url: '<?php echo $condition_subform_url; ?>',
            type: 'POST',
            dataType: 'json',
            data: {'condition_id': $('#incentiveFrm_condition_object').val(), 'idx': idx},
            success: function (data) {
                $('#conditions_list').append(
                    '<div class="form-group">' +
                    '<label class="control-label col-sm-3 col-xs-12">' + data.label + '</label>' +
                    '<div class="form-inline afield col-sm-7 col-xs-12">' + data.html + '&nbsp;' +
                    '<a class="btn btn-danger remove_cond" data-confirmation="delete" onclick="removeCondition(this);">' +
                    '<i class="fa fa-minus"></i></a></div></div>'
                );
                $("#incentiveFrm").prop('changed', 'true');
                idx++;

                $('#incentiveFrm_condition_object').val('').change();
                $('.chosen-container-multi, .chosen-container-single').css('width', '40%');
                $('#incentiveFrm_condition_type').attr('disabled', 'disabled');
                $('#incentiveFrm').find("input, select, textarea").aform({triggerChanged: true, showButtons: false});
            }
        });
    });

    $('#incentiveFrm_condition_type').on(
        'change',
        function () {
            $.ajax({
                url: '<?php echo $condition_object['url']; ?>&section=' + $(this).val(),
                type: 'GET',
                dataType: 'html',
                success: function (data) {
                    var parent = $('#incentiveFrm_condition_object').parent();
                    $('#incentiveFrm_condition_object').remove();
                    parent.html(data);
                    $('#incentiveFrm_condition_object').aform({showButtons: false});
                }
            });
            $('#condType').val($(this).val());
        }
    );

    var removeCondition = function (elm) {
        $(elm).parents('.form-group').remove();
        $("#incentiveFrm").prop('changed', 'true');
        $('#incentiveFrm_condition_object').val('').change();
        if ($('#conditions_list').children().length == 0) {
            $('#incentiveFrm_condition_type').removeAttr('disabled');
        }
    }
</script>