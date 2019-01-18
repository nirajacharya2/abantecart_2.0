<?php
	$this->document->addScript('https://cdn.jsdelivr.net/npm/vue/dist/vue.js');
	$this->document->addScript('https://cdn.jsdelivr.net/npm/vuetify/dist/vuetify.js');
	$this->document->addScript('https://cdnjs.cloudflare.com/ajax/libs/babel-polyfill/7.2.5/polyfill.min.js');
	$this->document->addScript('https://cdn.jsdelivr.net/npm/axios@0.12.0/dist/axios.min.js');
	$this->document->addScript('https://cdn.jsdelivr.net/npm/lodash@4.13.1/lodash.min.js');
	$this->document->addStyle([
		'href' => 'https://fonts.googleapis.com/css?family=Roboto:300,400,500,700|Material+Icons',
		'rel'  => 'stylesheet',
	]);
	$this->document->addStyle([
		'href' => 'https://cdn.jsdelivr.net/npm/vuetify/dist/vuetify.min.css',
		'rel'  => 'stylesheet',
	]);
?>

<style>
	.ellipsis {
		overflow: auto !important;
	}
	.v-dialog {
		overflow: hidden;
	}
	.v-input {
		margin-right: 15px;
		margin-left: 15px;
	}
</style>

<?php include($tpl_common_dir . 'action_confirm.tpl'); ?>

<div class="tab-content">
	<div id="audit-log-container">
	</div>
</div>
<div id="app">
	<v-app>
		<v-content>
			<template>
				<v-container fluid>
					<v-layout row wrap align-center>
						<v-flex xs12 sm6>
							<v-dialog
									ref="dialog"
									v-model="modal"
									:return-value.sync="date_from"
									persistent
									lazy
									full-width
									width="290px"
							>
								<v-text-field
										slot="activator"
										v-model="date_from"
										:allowed-dates="allowedDate()"
										label="Date from"
										readonly
								></v-text-field>
								<v-date-picker v-model="date_from" scrollable>
									<v-spacer></v-spacer>
									<v-btn flat color="primary" @click="modal = false">Cancel</v-btn>
									<v-btn flat color="primary" @click="$refs.dialog.save(date_from)">OK</v-btn>
								</v-date-picker>
							</v-dialog>
						</v-flex>
						<v-flex xs12 sm6>
							<v-dialog
									ref="dialog2"
									v-model="modal2"
									:return-value.sync="date_to"
									persistent
									lazy
									full-width
									width="290px"
							>
								<v-text-field
										slot="activator"
										v-model="date_to"
										:allowed-dates="allowedDate()"
										label="Date to"
										readonly
								></v-text-field>
								<v-date-picker v-model="date_to" scrollable>
									<v-spacer></v-spacer>
									<v-btn flat color="primary" @click="modal2 = false">Cancel</v-btn>
									<v-btn flat color="primary" @click="$refs.dialog2.save(date_to)">OK</v-btn>
								</v-date-picker>
							</v-dialog>
						</v-flex>
						<v-flex xs12 sm4>
							<v-select
									v-model="selected_data_object"
									:items="data_objects"
									label="Auditable Objects"
									hint="Pick Auditable Object"
									persistent-hint
									@input="dataObjectChange()"
									hide-selected
									attach
							></v-select>
							<div id="id_selected_data_object"></div>
						</v-flex>

						<v-flex xs12 sm4>
							<v-text-field
									name="data_object_id"
									v-model="data_object_id"
									v-bind:disabled="isDataObjectIdDisabled"
									label="Data Object ID"
									single-line
									mask="###########"
									hint="Input Data Object ID"
							></v-text-field>
						</v-flex>

						<v-flex xs12 sm4>
							<v-select
									:items="available_fields"
									v-model="selected_fields"
									label="Changet field"
									multiple
									hint="Pick changet field"
									persistent-hint
									v-bind:disabled="isSelectedFieldsDisabled"
									@input="selectedFieldsChange()"
									attach
							></v-select>
							<div id="id_selected_fields"></div>
						</v-flex>
						<v-flex xs12 sm12>
							<v-chip light close small
							        v-for="item in arFilter"
							        :key="arFilter.indexOf(item)"
							        v-model="item.isOpen"
							        @click="selectChip(item)"
							        @input="removeChip(item)"
							>
								{{item.auditable_type}}
								 <span v-if="item.auditable_id" > ({{item.auditable_id}}) </span>
								= {{item.attribute_name}}
							</v-chip>
						</v-flex>
						<v-flex xs12 sm4 style="text-align: center;">
								<v-btn small @click="addFilter()" v-bind:disabled="isAddDisabled" >Add</v-btn>
						</v-flex>
						<v-flex xs12 sm4 style="text-align: center;">
						<v-btn small color="warning" @click="clearSelected()" v-bind:disabled="clearSelectedDisabled">Clear</v-btn>
						</v-flex>
						<v-flex xs12 sm4 style="text-align: center;">
						<v-btn small color="error" @click="clearFilter()" v-bind:disabled="clearFilterDisabled" >Clear Filter</v-btn>
						</v-flex>


					</v-layout>
				</v-container>
				<div>
					<v-data-table
							:headers="table_headers"
							:items="table_items"
							:pagination.sync="pagination"
							:total-items="table_total"
							:loading="loading"
							class="elevation-1"
							attach
					>
						<template slot="items" slot-scope="props">
							<tr @click="expandFunction(props)" style="background-color: #E5E5E5">
								<td v-for="table_header in table_headers">
									{{ props.item[table_header.value] }}
								</td>
							</tr>
						</template>
						<template slot="expand" slot-scope="props">
							<v-card flat>
								<v-card-text>

									<v-flex row>
										<v-flex xs12 sm6>
											<p><strong>Event:</strong>  {{props.item.event}} </p>
											<p><strong>Data object:</strong>  {{props.item.auditable_type}} </p>
											<p><strong>Data object ID:</strong>  {{props.item.auditable_id}} </p>
										</v-flex>
										<v-flex xs12 sm6>
											<p><strong>User:</strong>  {{props.item.user_name}} </p>
											<p><strong>Alias:</strong>  {{props.item.alias_name}} </p>
											<p><strong>Date modify:</strong>  {{props.item.date_added}} </p>
										</v-flex>
									</v-flex>

									<v-data-table
											:headers="expand_headers"
											:items="expand_items"
											:pagination.sync="expand_pagination"
											:total-items="expand_table_total"
											:pagination.sync="{ rowsPerPage: -1 }"
											hide-actions
									>
										<template slot="items" slot-scope="expand_props">
											<tr>
												<td v-for="expand_header in expand_headers">
													{{ expand_props.item[expand_header.value] }}
												</td>
											</tr>
										</template>
									</v-data-table>
								</v-card-text>
							</v-card>
						</template>
					</v-data-table>
				</div>
			</template>
		</v-content>
	</v-app>
</div>




<script type="text/x-template" id="select-template">

</script>


<script>
	var data_objects =  <?php echo $data_objects; ?>;

	var vm = new Vue({ el: '#app',
	data: {
		arFilter: [],
		objectsInArFilter: [],
		isSelectedFieldsDisabled: true,
		isAddDisabled: true,
		available_fields: [],
		data_objects: data_objects.classes,
		const_data_objects: data_objects.classes,
		selected_data_object: '',
		selected_fields: [],
		date_from: '',
		date_to: '',
		modal: '',
		modal2: '',
		chip: '',
		clearSelectedDisabled: true,
		table_items: [],
		table_total: 0,
		loading: true,
		pagination: { },
		table_headers: [
			{
				text: 'User Name',
				align: 'left',
				value: 'user_name'
			},
			{ text: 'Data Object', value: 'auditable_type' },
			{ text: 'Event', value: 'event' },
			{ text: 'Date Change', value: 'date_added' },
		],
		expand_items: [],
		expand_headers: [
			{ text: 'Attribute', value: 'attribute_name', sortable: false, },
			{ text: 'Old Value', value: 'old_value', sortable: false, },
			{ text: 'New Value', value: 'new_value', sortable: false, },
		],
		expand_pagination: { },
		expand_table_total: 0,
		data_object_id:'',
		isDataObjectIdDisabled: true,
	},

		computed: {
			clearFilterDisabled: function () {
				if (this.arFilter.length > 0
					|| this.date_from.length > 0
					|| this.date_to.length > 0
					) {
					return false;
				}
				return true;
			}
		},

		watch: {
			arFilter: function (newVal, oldVal) {
				this.getDataFromApi();
			},
			selected_data_object: function (newVal, oldVal) {
				this.clearSelectedDisabled = true;
				if (newVal.length > 0) {
					this.clearSelectedDisabled = false;
				}
			},
			pagination: function () {
				this.getDataFromApi();
			},
			date_from: function () {
				this.getDataFromApi();
			},
			date_to: function () {
				this.getDataFromApi();
			}
		},
		mounted () {
		//	this.getDataFromApi();
		},
		methods: {
			dataObjectChange: function () {
				this.available_fields = data_objects[this.selected_data_object].table_columns;
				this.isSelectedFieldsDisabled = false;
				this.isDataObjectIdDisabled = false;
			},
			selectedFieldsChange: function() {
				if (this.selected_fields.length > 0) {
					this.isAddDisabled = false;
				} else {
					this.isAddDisabled = true;
				}
			},
			clearSelected: function () {
				this.selected_data_object = '';
				this.available_fields = [];
				this.selected_fields = [];
				this.isSelectedFieldsDisabled = true;
				this.isDataObjectIdDisabled = true;
				this.isAddDisabled = true;
				this.data_object_id = '';
			},
			addFilter: function () {
				var filterItem = {
					'auditable_type': this.selected_data_object,
					'attribute_name': this.selected_fields,
					'auditable_id': this.data_object_id,
				};
				this.arFilter.push(filterItem);
				this.objectsInArFilter.push(this.selected_data_object);
				this.removeAddedFromSelect(this.selected_data_object);
				this.clearSelected();
			},
			clearFilter: function () {
				this.arFilter = [];
				this.date_from = '';
				this.date_to = '';
				for (i=0; i<this.objectsInArFilter.length; i++) {
					var index = this.data_objects.indexOf(this.objectsInArFilter[i]);
					if (index == -1) {
						this.data_objects.push(this.objectsInArFilter[i]);
					}
				}
			},
			removeAddedFromSelect: function(item){
				var index = this.data_objects.indexOf(item);
				if (index > -1) {
					this.data_objects.splice(index, 1);
				}
			},
			allowedDate: function () {
				if (this.date_to == '' || this.date_to > this.date_from) {
					return false;
				}
				return true;
			},
			selectChip: function (item) {
				//this.data_objects.push(item.auditable_type);
				//this.selected_data_object = item.auditable_type;
				//this.data_object_id = item.auditable_id;
				//this.selected_fields = item.attribute_name;
			},
			removeChip: function (item) {
				var index = -1;
				for (i=0;i<this.arFilter.length;i++) {
					if (this.arFilter[i].auditable_type == item.auditable_type) {
						index=i;
					}
				}
				if (index > -1) {
					this.arFilter.splice(index, 1);
				}
				this.data_objects.push(item.auditable_type);
			},
			applyFilter: function () {
				this.getDataFromApi();
			},
			getDataFromApi () {
				this.loading = true;
				var param =  this.pagination;
				param.filter = this.arFilter;
				param.date_from = this.date_from;
				param.date_to = this.date_to;
				var promise =  axios.get('<?php echo $ajax_url; ?>', {params: param })
					.then(function (response) {
						vm.table_items = response.data.items;
						vm.table_total = response.data.total;
						vm.loading = false;
					})
					.catch(function (error) {
						vm.loading = false;
						alert('Ошибка! Не могу связаться с API. ' + error);
					});
			},
			expandFunction: function(props){
				if (!props.expanded) {
					this.getExpandDataFromApi(props);
				}
				props.expanded = !props.expanded;
			},
			getExpandDataFromApi(props) {
				this.loading = true;
				var filter = {
					'auditable_type': props.item.auditable_type,
					'date_added': props.item.date_added,
					'auditable_id': props.item.auditable_id,
					'user_id': props.item.user_id
				};
				var param = {
					filter: filter,
					getDetail:  true,
				}
				var promise =  axios.get('<?php echo $ajax_url; ?>', {params: param })
					.then(function (response) {
						vm.expand_items = response.data.items;
						vm.expand_table_total = response.data.total;
						vm.loading = false;
					})
					.catch(function (error) {
						vm.loading = false;
						alert('Ошибка! Не могу связаться с API. ' + error);
					});
			}
		}
	});
</script>