<?php
use abc\core\ABC;
include($tpl_common_dir.'action_confirm.tpl'); ?>

<?php echo $summary_form; ?>
<?php echo $product_tabs ?>

<div id="content" class="panel panel-default">
	<div class="panel-heading col-xs-12">
		<div class="primary_content_actions pull-left form-inline">
            <?php if (sizeof($options->options)) { ?>
				<div class="form-group">
					<div class="input-group input-group-sm">
						<label><?php echo $tab_option; ?></label>
					</div>
				</div>
				<div class="form-group">
					<div class="input-group input-group-sm">
                        <?php echo $options; ?>
					</div>
				</div>
            <?php } ?>
			<div class="btn-group ml10 toolbar">
                <?php if($text_new_option){?>
				<a class="btn btn-white tooltips" href="#"
				   title="<?php echo $text_new_option; ?>"
				   data-original-title="<?php echo $text_new_option; ?>"
				   data-target="#option_modal" data-toggle="modal">
					<i class="fa fa-plus"></i>
				</a>
                <?php } ?>
			</div>
		</div>
        <?php include($tpl_common_dir.'content_buttons.tpl'); ?>
	</div>

	<div class="panel-body panel-body-nopadding tab-content col-xs-12" id="option_values">
        <?php //# Options HTML loaded from response controller rt=product/product/load_option ?>
	</div>
</div>

<?php
$modal_content = '<div class="add-option-modal" >
    <div class="panel panel-default">
        <div id="collapseTwo" >
            '.$form['form_open'].'
            <div class="panel-body panel-body-nopadding">
                '.$attributes.'
                <div class="mt10 options_buttons" id="option_name_block">
                    <div class="form-group '.(!empty($error['status']) ? "has-error" : "").'">
                        <label class="control-label col-sm-3 col-xs-12" 
                                for="'.$status->element_id.'">'
                        .$entry_status
                        .'</label>
                        <div class="input-group afield ">
                            '.$status.'
                        </div>
                    </div>
                    <div class="form-group '.(!empty($error['option']) ? "has-error" : "").'">
                        <label class="control-label col-sm-3 col-xs-12" 
                                for="'.$option_name->element_id.'">'
                        .$entry_option.'</label>
                        <div class="input-group afield ">
                            '.$option_name.'
                        </div>
                    </div>
                    <div class="form-group '.(!empty($error['element_type']) ? "has-error" : "").'">
                        <label class="control-label col-sm-3 col-xs-12" 
                                for="'.$element_type->element_id.'">'
                        .$entry_element_type.'</label>
                        <div class="input-group afield ">
                            '.$element_type.'
                        </div>
                    </div>
                    <div class="form-group '.(!empty($error['sort_order']) ? "has-error" : "").'">
                        <label class="control-label col-sm-3 col-xs-12" for="'.$sort_order->element_id.'">'
                        .$entry_sort_order.'</label>
                        <div class="input-group afield ">
                            '.$sort_order.'
                        </div>
                    </div>
                    <div class="form-group '.(!empty($error['required']) ? "has-error" : "").'">
                        <label class="control-label col-sm-3 col-xs-12" for="'.$required->element_id.'">'
                        .$entry_required.'</label>
                        <div class="input-group afield ">
                            '.$required.'
                        </div>
                    </div>
                </div>
            </div>
            <div class="panel-footer">
                <div class="row">
                   <div class="center">
                     <button class="btn btn-primary lock-on-click">
                     <i class="fa fa-save"></i> '.$form['submit']->text.'
                     </button>&nbsp;
                     <button type="button" class="btn btn-default" data-dismiss="modal">
                     <i class="fa fa-times"></i> '.$form['cancel']->text.'
                     </button>
                   </div>
                </div>
            </div>
            </form>
        </div>
    </div>
</div>';

echo $this->html->buildElement(
    [
        'type'       => 'modal',
        'id'         => 'option_modal',
        'modal_type' => 'lg',
        'title'      => $text_add_new_option,
        'content'    => $modal_content,
    ]);
?>

<?php echo $resources_scripts; ?>
<script type="text/javascript">

    var setRLparams = function (attr_val_id) {
        urls = {
            resource_library: '<?php echo $rl_resource_library; ?>&object_id=' + attr_val_id,
            resources: '<?php echo $rl_resources; ?>&object_id=' + attr_val_id,
            resource_single: '<?php echo $rl_resource_single; ?>&object_id=' + attr_val_id,
            map: '<?php echo $rl_map; ?>&object_id=' + attr_val_id,
            unmap: '<?php echo $rl_unmap; ?>&object_id=' + attr_val_id,
            del: '<?php echo $rl_delete; ?>&object_id=' + attr_val_id,
            download: '<?php echo $rl_download; ?>&object_id=' + attr_val_id,
            upload: '<?php echo $rl_upload; ?>&object_id=' + attr_val_id,
            resource: '<?php echo ABC::env('HTTPS_DIR_RESOURCES'); ?>'
        };

        urls.attr_val_id = attr_val_id;
	};

    var text = {
        error_attribute_not_selected: <?php abc_js_echo($error_attribute_not_selected); ?>,
        text_expand: <?php abc_js_echo($text_expand); ?>,
        text_hide: <?php abc_js_echo($text_hide); ?>
    };
    var opt_urls = {
        load_option: <?php abc_js_echo($url['load_option']); ?>,
        update_option: <?php abc_js_echo($url['update_option']); ?>,
        get_options_list: <?php abc_js_echo($url['get_options_list']) ?>
    };
    var current_option_id = null;
    var row_id = 1;

    jQuery(function ($) {

        $(document).on('change', '#new_option_form_attribute_id', function () {
            var current_opt_attr_id = $(this).val();
            if (current_opt_attr_id != 'new') {
                $("#option_name_block").hide();
            } else {
                $("#option_name_block").show();
                $("#option_name_reset").show();
            }

        });

        $("#product_form").submit(function () {

            if ($("#new_option_form_attribute_id").val() == 'new'
                && ($("#new_option_form_option_name").val() == '' || $("#new_option_form_element_type").val() == '')
            ){
                if ($("#new_option_form_option_name").val() == '') {
                    $("#new_option_form_option_name").focus();
                    $("#new_option_form_option_name").closest("span").next().next().show();
                } else {
                    $("#new_option_form_option_name").closest("span").next().next().hide();
                }

                if ($("#new_option_form_element_type").val() == '') {
                    $("#new_option_form_element_type").focus();
                    $("#new_option_form_element_type").closest("span").next().next().show();
                } else {
                    $("#new_option_form_element_type").closest("span").next().next().hide();
                }
                return false;
            } else if ($("#new_option_form_attribute_id").val() != 'new') {
                $("#new_option_form_option_name").val('');
                $("#new_option_form_element_type").val('');
            }
        });

        var updateOptions = function () {
            $.ajax({
                url: opt_urls.get_options_list,
                type: 'GET',
                dataType: 'json',
                success: function (json) {
                    $("#option option").remove();
                    for (var key in json) {
                        var selected = '';
                        if (key === current_option_id) {
                            selected = ' selected ';
                        }
                        $("#option").append($('<option value="' + key + '"' + selected + '>' + json[key] + '</option>'));
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    error_alert(errorThrown);
                },
                complete: function () {
                    bindCustomEvents("#option");
                }
            });
        };

        //save option form details.
        var editOption = function (id) {
            $('#notify_error').remove();
            var senddata = $('#option_edit_form').find('input,select,textarea').serialize() + '&option_id=' + current_option_id;
            $.ajax({
                url: opt_urls.update_option,
                data: senddata,
                type: 'GET',
                success: function (html) {
                    $('#option_name').html($('#name').val());
                    updateOptions();
                    //Reset changed values marks
                    resetAForm($("input,select,textarea", '#option_edit_form'));
                    success_alert(<?php abc_js_echo($text_success_option); ?>, true);
                },
                global: false,
                error: function (jqXHR, textStatus, errorThrown) {
                    error_alert(errorThrown);
                }
            });
            return false;
        };

        $(document).on('click', "#option_values_tbl a.remove", function () {
            if ($(this).closest('tr').find('input[name^=product_option_value_id]').val() === 'new') {
                //remove new completely
                $(this).closest('tr').next().remove();
                $(this).closest('tr').remove();
            } else {
                //mark for delete and set disabled
                $(this).closest('tr').toggleClass('toDelete').toggleClass('transparent');
            }
            return false;
        });
        var lock_a = false;
        $(document).on('click', "#option_values_tbl a.expandRow", function () {
            var row_id = $(this).parents('tr').attr('id');
            var additional_row = $('#add_' + row_id + 'div.additionalRow');
            var icon = $(this).find('i');

            <?php //do recursive call and close all expanded additional rows ?>
            if (lock_a === false) {
                lock_a = true;
                $.each($('#option_values_tbl tr').find('a.expandRow'),
                    function (k, v) {
                        v = $(v);
                        if (row_id !== v.parents('tr').attr('id') && v.find('i').hasClass('fa-compress')) {
                            v.click();
                        }
                    }
                );
                lock_a = false;
            }

            if (icon.hasClass("fa-expand")) {
                $(this).attr('title', text.text_hide);
                icon.removeClass('fa-expand').addClass('fa-compress');
                //if row is not new

                if (row_id.search("new") < 0) {
                    setRLparams($(this).attr('id'));
                    $('#panel_image>div.panel-heading>div.panel-btns').remove();
                    loadMedia('image', '#add_' + row_id + ' div.type_blocks');

                    $('#add_' + row_id)
                        .find('#type_image')
                        .show()
                        .find('#panel_image')
                        .show();
                    //do not show images for new rows
                } else {
                    $('#add_' + row_id).find('#panel_image').hide();
                }
            } else {
                $(this).attr('title', text.text_expand);
                icon.removeClass('fa-compress').addClass('fa-expand');
                additional_row.find('div.add_resource').html();
            }

            return false;
        });

        $(document).on('click', '.open_newtab', function () {
            var href = $(this).attr('link');
            top.open(href, '_blank');
            return false;
        });


        $(document).on('click', '.uncheck', function () {
            $("input[name='default_value']").removeAttr('checked');
            return false;
        });

        $(document).on('click', "#add_option_value", function () {
            var new_rows = $('#new_row_table').find('tr').clone();
            var opt_row = $(new_rows).first();
            opt_row.attr('id', 'new' + row_id)
            .find('a.expandRow')
            .attr('data-target', '#add_new' + row_id+'_row');

            var additional = $(new_rows).last();
            additional
                .attr('id', 'new' + row_id+'_add')
                .find('#add_new_row')
                .attr('id', 'add_new' + row_id+'_row')
                //disable image for new option value. Image can be added only after option is created
                .find("#new_row_rl").hide();

            //Mark rows to be new
            opt_row.find('#new' + row_id + ' input[name=default_value]')
                .val('new' + row_id)
                .attr('id', 'option_value_form_default_new' + row_id)
                .removeAttr('checked')
                .parent('label')
                .attr('for', 'option_value_form_default_new' + row_id);
            opt_row.find('input[name^=product_option_value_id]').val('new');
            new_rows.find("input, textarea, select").each(function() {
                var new_name = $(this).attr('name');
                new_name = new_name.replace("[]", "[new" + row_id + "]");
                $(this).attr('name', new_name);
            });

            //find next sort order number
            var so = $('#option_values_tbl').find("input[name^='sort_order']");
            var highest = 0;
            if (so.length > 0) {
                so.each(function () {
                    highest = Math.max(highest, Number(this.value));
                });
                highest++;
            }
            opt_row.find("input[name^='sort_order']").val(highest);

            if ($('#option_values_tbl tbody').length) {
                //add one more row
                $('#option_values_tbl tbody tr:last-child').after(new_rows);
            } else {
                //we insert first row
                $('#option_values_tbl tr:last-child').after(new_rows);
            }
            bindAform($("input, textarea, select", new_rows));

            row_id++;
            return false;
        });

        $('#option').change(function () {
            current_option_id = $(this).val();
            $.ajax({
                url: opt_urls.load_option,
                type: 'GET',
                data: {option_id: current_option_id},
                success: function (html) {
                    if (html.length > 0) {
                        $('#option_values').html(html);
                    } else {
                        $('#option_values').html('');
                        $('select#option').parents('.primary_content_actions').find('label').remove();
                        $('select#option').remove();
                    }
                },
                global: false,
                error: function (jqXHR, textStatus, errorThrown) {
                    error_alert(errorThrown);
                },
                complete: function () {
                    bindAform($("input, textarea, select", '#option_edit_form'));
                    bindAform($("input, textarea, select", '#update_option_values'));
                    bindCustomEvents('#option_values');
                }
            });
        });


        //select option and load data for it
        var $selected = $('#option option:selected');
        if ($selected.length) {
            $selected.change();
        } else {
            $('#option option:first-child').attr("selected", "selected").change();
        }

        $(document).on('click', '#update_option', function () {
            editOption('#update_option');
        });

        $(document).on('click', '#reset_option', function () {
            $('#option').change();
            return false;
        });

        //save option values
        $(document).on('click', '#option_values button[type="submit"]', function () {
            //Mark rows to be deleted
            $('#option_values_tbl .toDelete input[name^=product_option_value_id]').val('delete');
            editOption('#update_option');
            var that = this;
            $.ajax({
                url: $(that).closest('form').attr('action'),
                type: 'POST',
                data: $(that).closest('form').serializeArray(),
                beforeSend: function () {
                    $('#option_values_save_btn').button('loading');
                },
                success: function (html) {
                    $('#option_values').html(html);
                },
                global: false,
                error: function (jqXHR, textStatus, errorThrown) {
                    error_alert(errorThrown);
                },
                complete: function () {
                    bindAform($("input, textarea, select", '#option_edit_form'));
                    bindAform($("input, textarea, select", '#update_option_values'));
                    bindCustomEvents('#option_values');
                    resetLockBtn();
                }
            });
            return false;
        });
    });

    // Function to delete option. NOTE. Needs to be here (global)
    function optionDelete(url) {
        $.ajax({
            url: url,
            type: 'GET',
            dataType: "html",
            success: function (html) {
                //remove option and reload the section
                $('#option option:selected').remove();
                $("#option").trigger("change");
                success_alert(html, true);
            },
            global: false,
            error: function (jqXHR, textStatus, errorThrown) {
                error_alert(errorThrown);
            },
            complete: function () {
                bindCustomEvents("#option");
            }
        });
        return false;
    }

</script>