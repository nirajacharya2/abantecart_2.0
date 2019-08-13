<div class="modal-header">
	<h4 class="modal-title"><?php echo $text_title ?></h4>
	<button aria-hidden="true" data-dismiss="modal" class="close" type="button">&times;</button>
</div>

<div id="ct_form" class="tab-content">
	<?php echo $form['form_open']; ?>
	<div class="panel-body panel-body-nopadding">
		<div class="row">
			<div class="col-md-4"><a href="<?php echo $product_href;?>" target="_blank"><?php	echo $image['thumb_html']; ?></a></div>
			<div class="col-md-8">
				<?php if ($options) { ?>
				<label class="h4 heading"><?php echo $tab_option; ?></label>
				<div class="optionsbox">
					<fieldset>
						<?php foreach ($options as $option) { ?>
							<div class="form-group">
								<?php if ($option['html']->type != 'hidden') { ?>
                                    <label class="control-label col-sm-5"
                                           data-option-name=<?php abc_js_echo($option['html']->name); ?>
                                    ><?php echo $option['name']; ?></label>
								<?php } ?>
								<div class="input-group afield col-sm-6">
									<?php echo $option['html']; ?>
								</div>
							</div>
						<?php } ?>

						<?php echo $this->getHookVar('extended_product_options'); ?>

					</fieldset>
				</div>
				<?php } ?>
				<label class="h4 heading"><?php echo $column_total?></label>
				<?php foreach ($form['fields'] as $name => $field) { ?>

					<div class="form-group ">
						<label class="control-label col-sm-5 col-xs-12" for="<?php echo $field->element_id; ?>"><?php echo ${'column_' . $name}; ?></label>
						<div class="input-group afield col-sm-6 col-xs-12">
							<?php echo $field; ?>
						</div>
					</div>
						<?php }  ?>
                <label class="h4 heading"><?php echo $text_order_status ?></label>
                <div class="form-group ">
                    <label class="control-label col-sm-5 col-xs-12"></label>
                    <div class="input-group afield col-sm-6 col-xs-12">
                        <?php echo $form['order_status_id']; ?>
                    </div>
                </div>
            </div>
        </div>

	</div>

	<div class="panel-footer">
		<div class="row">
			<div class="col-sm-6 col-sm-offset-3 center">

				<button class="btn btn-primary lock-on-click">
					<i class="fa fa-save"></i> <?php echo $form['submit']->text; ?>
				</button>
				&nbsp;
				<a class="btn btn-default" data-dismiss="modal" href="<?php echo $cancel; ?>">
                    <i class="fa fa-refresh"></i> <?php echo $form['cancel']->text; ?>
				</a>

			</div>
		</div>
	</div>

	</form>
</div>

<script type="application/javascript">

    $('#orderProductFrm input, #orderProductFrm select,  #orderProductFrm textarea')
        .not('#orderProductFrm_order_status_id')
        .on('change', display_total_price);

    $('#orderProductFrm_quantity')
        .on('keyup', display_total_price);

    $('#orderProductFrm_order_status_id').on(
        'change',
        function () {
            var cancel_statuses = ['<?php echo implode("','", $cancel_statuses)?>'];

            if ($.inArray($(this).val(), cancel_statuses) !== -1) {
                $('#orderProductFrm_quantity').val(0).attr('readonly', 'readonly');
                $('#orderProductFrm_total').val(0);

            } else {
                if ($('#orderProductFrm_quantity').val() == 0) {
                    $('#orderProductFrm_quantity')
                        .val($('#orderProductFrm_quantity').attr('data-orgvalue'))
                        .keyup()
                        .removeAttr('readonly');
                }
            }
        });


	function display_total_price() {
		var data = $("#orderProductFrm").serialize();
        data = data.replace(new RegExp("product%5Boption%5D", 'g'), 'option'); <?php // data format for storefront response-controller ?>
        data = data.replace(new RegExp("product%5Bquantity%5D", 'g'), 'quantity'); <?php // data format for storefront response-controller ?>
		$.ajax({
			type: 'POST',
			url: '<?php echo $total_calc_url;?>',
			dataType: 'json',
			data: data,
			success: function (data) {
				if (data.total) {
                    $('#orderProductFrm_price').val(data.price);
                    $('#orderProductFrm_total').val(data.total);
				}
			}
		});
	}

	display_total_price();

    var modal_mode = '<?php echo $modal_mode; ?>';
    <?php //if need to pass js-data back to main page => ?>
    if (modal_mode === 'json') {
        $('#orderProductFrm').on('submit', function (e) {
            var that = $(this);
            var output = {form: that.serializeArray()};
            $.each(output.form, function (index, value) {
                var label = that.find('label[data-option-name="' + value.name + '"]');
                if (label.length > 0) {
                    output.form[index].text = label.html();
                }

                var field = that.find('[name="' + value.name + '"]');
                var tag = field.prop('tagName');

                if (tag === 'SELECT') {
                    output.form[index].value_text = field.find('option[value="' + value.value + '"]').text().trim();
                } else if (tag === "INPUT") {
                    if (field.prop('type') === 'radio') {
                        field = field.filter('.changed');
                        output.form[index].value_text = that.find('label[for="' + field.prop('id') + '"]').text().trim();
                    }
                    if (field.prop('type') === 'checkbox') {
                        field = field.filter('[value="' + value.value + '"]');
                        output.form[index].value_text = that.find('label[for="' + field.prop('id') + '"]').text().trim();
                    }
                }
            });
            output.image_url = '<?php echo $image['thumb_url']?>';
            output.product_id = '<?php echo $product_id; ?>';
            output.product_name = '<?php echo $product_name; ?> - ' + $('#orderProductFrm_order_status_id  option:selected').text().trim();
            output.product_url = '<?php echo $product_url; ?>';
            output.order_product_id = '<?php echo $order_product_id; ?>';
            output.order_status_id = $('#orderProductFrm_order_status_id').val();


            AddProductToForm(output);

            e.preventDefault();
            $('#add_product_modal').modal('toggle');
        });
    }
</script>