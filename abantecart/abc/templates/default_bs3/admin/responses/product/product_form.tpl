<div class="modal-header">
	<button aria-hidden="true" data-dismiss="modal" class="close" type="button">&times;</button>
	<h4 class="modal-title"><?php echo $text_title ?></h4>
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
                                       data-option-name=<?php abc_js_echo($option['html']->name);?>
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
	var decimal_point = '<?php echo $decimal_point; ?>';
	var decimal_place = '<?php echo $currency['decimal_place']; ?>';
	var thousand_point = '<?php echo $thousand_point; ?>';

	var currency_symbol = '<?php echo $currency['symbol_left'] ?? $currency['symbol_right']; ?>';
	var currency_location = '<?php echo $currency['symbol_left'] ? 'left':'right'; ?>';

	$('#orderProductFrm input, #orderProductFrm select,  #orderProductFrm textarea').on('change', display_total_price);
	$('#orderProductFrm_product0quantity').on('keyup', display_total_price);

	function formatMoney(num, c, d, t) {
		c = isNaN(c = Math.abs(c)) ? 2 : c,
        d = d == undefined ? "." : d,
        t = t == undefined ? "," : t,
        s = num < 0 ? "-" : "",
        i = parseInt(num = Math.abs(+num || 0).toFixed(c)) + "",
        j = (j = i.length) > 3 ? j % 3 : 0;
		return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(num - i).toFixed(c).slice(2) : "");
	}

	function display_total_price() {
		<?php echo $editable_price ? 'return recalculate();':'' ?>

		var data = $("#orderProductFrm").serialize();
		data = data.replace(new RegExp("product%5B0%5D%5Boption%5D",'g'),'option'); <?php // data format for storefront response-controller ?>
		data = data.replace(new RegExp("product%5B0%5D%5Bquantity%5D",'g'),'quantity'); <?php // data format for storefront response-controller ?>
		$.ajax({
			type: 'POST',
			url: '<?php echo $total_calc_url;?>',
			dataType: 'json',
			data: data,
			success: function (data) {
				if (data.total) {
					$('#orderProductFrm_product0price').val(data.price);
					$('#orderProductFrm_product0total').val(data.total);
				}
			}
		});

	}

	function get_currency_str(num) {
		var str;
		if (currency_location === 'left') {
			str = currency_symbol + formatMoney(num, decimal_place, decimal_point, thousand_point);
		} else {
			str = formatMoney(num, decimal_place, decimal_point, thousand_point) + currency_symbol;
		}
		return str;
	}

	function get_currency_num(str) {
		str = str === undefined || str.length === 0 ? '0' : str;
		var final_number = str.replace(thousand_point, '');
		final_number = final_number.replace(currency_symbol, '');
		final_number = final_number.replace(decimal_point, '.');
		final_number = parseFloat(final_number.replace(/[^0-9\-\.]/g, ''));

		return final_number;
	}

<?php if($editable_price){ ?>
	function recalculate() {
		var qty, price, total;
		//update products
		qty = $('#orderProductFrm_product0quantity').val();
		price = get_currency_num($('#orderProductFrm_product0price').val());
		total = qty * price;
		//update last - total
		$('#orderProductFrm_product0total').val(get_currency_str(total));
	}
	$(document).on('keyup',$('#orderProductFrm_product0price'), recalculate );
<?php } ?>

	display_total_price();

	var modal_mode = '<?php echo $modal_mode; ?>';
	<?php //if need to pass js-data back to main page => ?>
	if(modal_mode === 'json'){
        $('#orderProductFrm').on('submit', function(e){
            var that = $(this);
            var output = {form: that.serializeArray()};
            $.each(output.form, function( index, value ) {
                var label = that.find('label[data-option-name="'+value.name+'"]');
                if(label.length>0) {
                    output.form[index].text = label.html();
                }

                var field = that.find('[name="'+value.name+'"]');
                var tag = field.prop('tagName');

                if( tag === 'SELECT'){
                    output.form[index].value_text = field.find('option[value="'+value.value+'"]').text().trim();
                }else if(tag === "INPUT") {
                    if(field.prop('type') === 'radio'){
                        field = field.filter('.changed');
                        output.form[index].value_text = that.find('label[for="'+field.prop('id')+'"]').text().trim();
                    }
                    if(field.prop('type') === 'checkbox'){
                        field = field.filter('[value="'+value.value+'"]');
                        output.form[index].value_text = that.find('label[for="'+field.prop('id')+'"]').text().trim();
                    }
                }
            });
            output.image_url = '<?php echo $image['thumb_url']?>';
            output.order_product_id = '<?php echo $order_product_id; ?>';

            AddProductToForm(output);

            e.preventDefault();
            $('#add_product_modal').modal('toggle');
        });
    }
</script>