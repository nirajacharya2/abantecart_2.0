<?php if ($this->document->renderJsVars()) { ?>
<script>
	<?php echo $this->document->renderJsVars(); ?>
</script>
<?php } ?>
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

	.v-text-field__details {
		margin-top: 3px;
	}

	ul {
		margin-bottom: 0px !important;
	}

	.tox-notifications-container {
		display: none !important;
	}
</style>

<div id="app">
	<v-app>
		<v-container fluid>
			<!-- <pre>{{ schema }}</pre>   -->
			<v-form v-model="formValid" ref="form" :name="schema.form_name" :action="schema.url">
				<v-layout row wrap>
					<div class="form-title">
						<span class="headline">{{schema.title}}</span>
					</div>
					<v-flex md12 xs12>

						<v-alert
								v-model="alert"
								:value="true"
								type="success"
								v-if="formSuccess"
								icon="check_circle"
								outline
								dismissible
						>
							{{ formSuccess }}
						</v-alert>
					</v-flex>
					<v-flex md12 xs12>
						<v-alert
								v-model="alert_error"
								:value="true"
								type="error"
								outline
								dismissible
								v-if="formError"
						>
							{{formError}}
							<ul>
								<li v-for="error in errors.all()">{{ error }}</li>
							</ul>
						</v-alert>
					</v-flex>

					<v-flex v-for="(field_options, field_name, index) in schema.form_fields"
					        :key="index"
					        v-if="field_types.includes(field_options.type)"
					        v-bind="field_options.v_flex_props">
						<v-container fluid>
							<v-dialog
									:ref="'dialog-' + field_name"
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
										:data-vv-as=field_options.title
										persistent-hint
										readonly
								></v-text-field>
								<v-date-picker v-model="field_options.value" scrollable>
									<v-spacer></v-spacer>
									<v-btn flat color="primary" @click="field_options.modal = false">Cancel</v-btn>
									<v-btn flat color="primary" @click="applyDate('dialog-' + field_name, field_options.value)">OK</v-btn>
								</v-date-picker>
							</v-dialog>

							<v-subheader v-if="field_options.type == 'field_title'"
							             ref="refObj"
							             v-bind="field_options.props"
							             v-html="field_options.value"
							             :name="field_name"
							             :id="field_name"
							>
							</v-subheader>

							<v-text-field
									ref="refObj"
									:name="field_name"
									:id="field_name"
									key="field_name"
									:label="field_options.title"
									v-bind="field_options.props"
									v-model="field_options.value"
									v-if="field_options.type == 'input'"
									v-validate=field_options.validate
									:data-vv-as=field_options.title
									:error-messages="errors.first(field_name)"
							></v-text-field>


							<v-radio-group
									ref="refObj"
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

							<v-switch v-if="field_options.type == 'switch'" v-model="field_options.value" :label="field_options.title"
							          ref="refObj"
							></v-switch>

							<v-checkbox v-if="field_options.type == 'checkbox'"
							            ref="refObj"
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
							     :id="field_name"
							     ref="refObj"
							>
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
									ref="refObj"
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
									ref="refObj"
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

							<div v-if="field_options.type == 'editor'">
								<v-subheader v-html="field_options.title">
								</v-subheader>
								<editor ref="editor"
										:init="editorConfig"
								        :name="field_name"
								        :id="field_name"
								        v-bind="field_options.props"
								        v-model="field_options.value"
								        v-validate=field_options.validate>
								</editor>
							</div>
						</v-container>
					</v-flex>
					<v-flex>
						<v-btn small color="primary" @click="saveForm()">
							<v-icon small>save</v-icon>
							Save
						</v-btn>
						<v-btn small flat @click="cancelForm()">
							<v-icon small>keyboard_backspace</v-icon>
							Cancel
						</v-btn>
					</v-flex>
				</v-layout>
			</v-form>
		</v-container>
	</v-app>
</div>
</body>
<script>
	var unwatchers = [];

	if (typeof abc !== 'undefined' && typeof abc.form !== 'undefined') {

		if (typeof abc.form.form_fields !== 'undefined') {
			abc.form.form_fields = [].sort.call(abc.form.form_fields, function (a, b) {
				console.log(a);
				return a.sort_order - b.sort_order;
			});
		}

		var myScema = abc.form;

		Vue.use(VeeValidate);

		tinyConfig = {
			height: 250,
			menubar: false,
			plugins: [
				'advlist autolink lists link image charmap print preview anchor ',
				'searchreplace visualblocks code fullscreen',
				'insertdatetime media table paste code help wordcount'
			],
			toolbar: 'undo redo | formatselect fontselect fontsizeselect | link table searchreplace| bold italic forecolor backcolor ' +
				'| alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | code fullscreen',
			fontsize_formats: '11px 12px 14px 16px 18px 24px 36px 48px',
			content_css: []
		};

		var vm = new Vue({
			el: '#app',
			components: {
				'editor': Editor
			},
			data: {
				editorConfig: tinyConfig,
				refObj: [],
				alert: true,
				alert_error: true,
				formSuccess: '',
				formError: '',
				fieldErrors: [],
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
					'textarea',
					'editor'
				]
			},
			mounted() {
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
				},
				saveForm() {
					this.formError = this.formSuccess = '';
					this.alert = true;
					this.alert_error = true;

					this.$validator.validateAll().then((result) => {
						if (result === true) {
							var param = {};
							param.saveForm = true;
							param.fields = {};
							for (var field in myScema.form_fields) {
								param.fields[field] = myScema.form_fields[field].value;
							}

							axios.post(myScema.url, param)
								.then(response => (this.saveResponse(response)))
								.catch(function (error) {
									alert(error);
								});
						} else {
							this.formError = ' ';
						}
						$([document.documentElement, document.body]).animate({
							scrollTop: $(".form-title").offset().top
						}, 500);
					}).catch(() => {
						return false
					});
				},
				saveResponse(response) {
					if (typeof response.data.success_message !== 'undefined') {
						this.formSuccess = response.data.success_message;
						this.formError = '';
					}
					if (typeof response.data.error !== 'undefined') {
						this.formError = response.data.error;
					}
					if (typeof response.data.errors !== 'undefined') {
						this.formError = ' ';
						this.fieldErrors = response.data.errors;
						for (var field in this.fieldErrors) {
							const error = {
								field: field,
								msg: this.fieldErrors[field][0],
							}
							this.errors.add(error);
						}
					}
					if (typeof response.data.csrf !== 'undefined') {
						myScema.form_fields.csrfinstance = response.data.csrf.csrfinstance;
						myScema.form_fields.csrftoken = response.data.csrf.csrftoken;
					}
				},
				cancelForm() {
					if (typeof myScema.back_url !== 'undefined') {
						window.location = myScema.back_url;
					} else {
						window.history.back();
					}
				},
				applyDate(ref, value) {
					this.$refs[ref][0].save(value);
				}

			},
		});


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
			param.fields = myScema.form_fields;

			let axiosConfig = {
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				}
			};


			if (typeof unwatchers[param.relatedTo] !== 'undefined') {
				unwatchers[param.relatedTo]();
			}

			axios.post(data.ajax_params.ajax_url, param)
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

					console.log("mceRepaint");
					tinyMCE.execCommand("mceRepaint");
				})
				.catch(function (error) {
					alert(error);
				});
		}

	} else {
		alert('Form data is empty!');
	}


</script>
