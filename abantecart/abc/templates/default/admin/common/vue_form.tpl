<style>
	.v-input--selection-controls:not(.v-input--hide-details) .v-input__slot,
	.v-input__slot {
		margin-bottom: 0 !important;
	}
	.v-input--selection-controls {
		margin-top: 0 !important;
	}
	.flex .container.fluid {
		padding: 0 !important;
	}
	.form-title {
		margin-bottom: 20px;
	}
	label.v-label {
		margin-bottom: 0 !important;
	}
	.v-chip, .v-chip .v-chip__content {
		border-radius: 4px !important;
	}
</style>

<div id="app">
	<v-app>
		<v-container fluid>
			<v-form v-model="formValid" :name="schema.form_name" :action="schema.url">
				<v-layout row wrap>
					<div class="form-title">
						<span class="headline">{{schema.title}}</span>
					</div>
					<v-flex v-for="(field_options, field_name, index) in schema.form_fields"
					        :key="index"
					        v-if="field_types.includes(field_options.type)"
					        v-bind="field_options.v_flex_props">
						<v-container fluid>
							 {{ field_options.value }}
							<v-dialog
									ref="dialog"
									v-model="field_options.modal"
									:return-value.sync="field_options.value"
									persistent
									lazy
									full-width
									width="290px"
									v-if="field_options.type == 'date'"
							>
								<v-text-field
										slot="activator"
										v-model="field_options.value"
										:label="field_options.title"
										:hint="field_options.hint"
										v-validate=field_options.validate
										:data-vv-as=field_options.title
										persistent-hint
										readonly
								></v-text-field>
								<v-date-picker v-model="field_options.value" scrollable>
									<v-spacer></v-spacer>
									<v-btn flat color="primary" @click="field_options.modal = false">Cancel</v-btn>
									<v-btn flat color="primary" @click="$refs.dialog[index].save(field_options.value)">OK</v-btn>
								</v-date-picker>
							</v-dialog>

							<v-subheader v-if="field_options.type == 'field_title'"
							     v-bind="field_options.props"
							     v-html="field_options.value"
							     :name="field_name"
							     :id="field_name"
							>
							</v-subheader>

							<v-text-field
									:name="field_name"
									:id="field_name"
									:label="field_options.title"
									v-bind="field_options.props"
									v-if="field_options.type == 'input'"
									v-validate=field_options.validate
									:data-vv-as=field_options.title
									:error-messages="errors.first(field_name)"
							></v-text-field>


							<v-radio-group
									:name="field_name"
									:id="field_name"
									v-model="field_options.value"
									v-if="field_options.type == 'radio'"
									v-bind="field_options.props"
									v-validate=field_options.validate
									:data-vv-as=field_options.title
									:error-messages="errors.first(field_name)"
							>
								<v-radio
										v-for="item in field_options.props.items"
										:key="item.value"
										:label="item.text"
										:value="item.value"
								></v-radio>
							</v-radio-group>

							<v-switch v-if="field_options.type == 'switch'" v-model="field_options.value" :label="field_options.title"></v-switch>

							<v-checkbox v-if="field_options.type == 'checkbox'"
							            :name="field_name"
							            :id="field_name"
							            v-model="field_options.value"
							            :label="field_options.title"
							            v-bind="field_options.props"
							            v-validate=field_options.validate
							            :data-vv-as=field_options.title
							            :error-messages="errors.first(field_name)"
							></v-checkbox>

							<div v-if="field_options.type == 'checkboxgroup'"
							     :name="field_name"
							     :id="field_name">
								<v-checkbox
										v-model="field_options.value"
										v-for="(value, key) in field_options.options"
										:key="key"
										:label="value"
										:value="key"
										v-bind="field_options.props"
								></v-checkbox>
							</div>

							<v-select
									:name="field_name"
									:id="field_name"
									:label="field_options.title"
									v-model="field_options.value"
									v-if="field_options.type == 'selectbox'"
									v-bind="field_options.props"
									v-validate=field_options.validate
									:data-vv-as=field_options.title
									:error-messages="errors.first(field_name)"
									attach
							></v-select>
							<v-textarea
									v-if="field_options.type == 'textarea'"
									:name="field_name"
									:id="field_name"
									:label="field_options.title"
									v-model="field_options.value"
									v-bind="field_options.props"
									v-validate=field_options.validate
									:data-vv-as=field_options.title
									:error-messages="errors.first(field_name)"
							></v-textarea>
						</v-container>
					</v-flex>
				</v-layout>
			</v-form>
		</v-container>
	</v-app>
</div>
</body>
<script>
	var unwatchers = [];

	if (typeof abc.form !== 'undefined') {

		if (typeof abc.form.form_fields !== 'undefined') {
			abc.form.form_fields = [].sort.call(abc.form.form_fields, function (a, b) {
				console.log(a);
				return a.sort_order - b.sort_order;
			});
		}

		var myScema = abc.form;

		Vue.use(VeeValidate);

		var vm = new Vue({
			el: '#app',
			data: {
				dialog: [],
				schema: myScema,
				formValid: false,
				field_types: [
					'input',
					'checkbox',
					'selectbox',
					'date', 
					'radio', 
				    'switch', 
					'checkboxgroup',
					'field_title',
					'textarea'
				]
			},
			methods: {
				validate() {
					if (this.$refs.form.validate()) {
						this.snackbar = true
					}
				},
				reset() {
					this.$refs.form.reset()
				},
				resetValidation() {
					this.$refs.form.resetValidation()
				}
			},
		});


		console.log(myScema.form_fields);

		for (var form_field in myScema.form_fields) {
			if (typeof myScema.form_fields[form_field].ajax_params !== 'undefined' &&
				myScema.form_fields[form_field].ajax_params != null &&
				typeof myScema.form_fields[form_field].ajax_params.ajax_url !== 'undefined' &&
				typeof myScema.form_fields[form_field].ajax_params.relatedTo !== 'undefined') {
				unwatchers[form_field] = vm.$watch('schema.form_fields.' + form_field,
					function (newValue, oldValue) {
						requestToServer(newValue)
					},
						{deep: true});
			}
		}

		var requestToServer = function (data) {
			var param = {};
			param.relatedTo = data.ajax_params.relatedTo;
			param.field = data.name;
			param.field_value = data.value;

			if (typeof unwatchers[param.relatedTo] !== 'undefined') {
				unwatchers[param.relatedTo]();
			}

			axios.get(data.ajax_params.ajax_url, {params: param})
				.then(function (response) {
					myScema.form_fields = response.data;

					var form_field = response.data[data.ajax_params.relatedTo];
					if (typeof form_field !== 'undefined' &&
						typeof form_field.ajax_params !== 'undefined' &&
						form_field.ajax_params != null &&
						typeof form_field.ajax_params.ajax_url !== 'undefined' &&
						typeof form_field.ajax_params.relatedTo !== 'undefined') {

						if (typeof unwatchers[form_field.name] !== 'undefined') {
							unwatchers[form_field.name]();
						}

						unwatchers[form_field.name] = vm.$watch('schema.form_fields.' + form_field.name,
							function (newValue, oldValue) {
								requestToServer(newValue)
							},
								{deep: true});
					}
				})
				.catch(function (error) {
					alert(error);
				});
		}
	} else {
		alert('Form data is empty!');
	}


</script>
