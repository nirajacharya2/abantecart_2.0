<style>
	.container {
		padding: 5px;
		padding-right: 15px;
		padding-left: 15px;
	}
</style>
<?php include($tpl_common_dir . 'action_confirm.tpl'); ?>

<?php if($modal_mode) { ?>
<div class="panel-heading">
	<div class="panel-btns">
		<a class="panel-close" onclick="$('#viewport_modal').modal('hide');" >×</a>
	</div>
	<h4 class="panel-title">Audit Log</h4>
</div>
<?php } ?>

<div class="tab-content">
	<div id="app">
	<v-app>
		<v-content>
			<template>
				<v-container>
					<v-layout row wrap align-center>
						<v-flex xs12 sm4>
							<v-container fluid>
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
							</v-container>
						</v-flex>
						<v-flex xs12 sm4>
							<v-container fluid>
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
							</v-container>
						</v-flex>
						<v-flex xs12 sm4>
							<v-container fluid>
							<v-text-field
									name="user_name"
									v-model="user_name"
									label="User/Alias Name"
									single-line
									hint="Input User/Alias Name"
							></v-text-field>
							</v-container>
						</v-flex>
					</v-layout>
					<v-layout row wrap align-center v-if="!isConcreteObject">
						<v-flex xs12 sm4>
							<v-container fluid>
							<v-select
									v-model="selected_data_object"
									:items="data_objects"
									v-bind:disabled="isDataObjectDisabled"
									label="Auditable Objects"
									hint="Pick Auditable Object"
									persistent-hint
									@input="dataObjectChange()"
									hide-selected
									attach
							></v-select>
							</v-container>
							<div id="id_selected_data_object"></div>
						</v-flex>

						<v-flex xs12 sm4>
							<v-container fluid>
							<v-text-field
									name="data_object_id"
									v-model="data_object_id"
									v-bind:disabled="isDataObjectIdDisabled"
									label="Data Object ID"
									single-line
									mask="###########"
									hint="Input Data Object ID"
							></v-text-field>
							</v-container>
						</v-flex>
						<v-flex xs12 sm4>
							<v-container fluid>
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
							</v-container>
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
				<v-container>
					<v-data-table
							:headers="table_headers"
							:items="table_items"
							ref="dTable"
							:pagination.sync="pagination"
							:total-items="table_total"
							:loading="loading"
							class="elevation-1"
							expand
							attach
					>
						<template slot="headers" slot-scope="props">
							<tr>
								<th
										v-for="header in props.headers"
										:key="header.text"
										:class="['column sortable', pagination.descending ? 'desc' : 'asc', header.value === pagination.sortBy ? 'active' : '']"
										@click="changeSort(header.value)"
								>
									{{ header.text }}
									<span v-if="pagination.descending && header.value === pagination.sortBy">
										<i class="material-icons mi-12">arrow_upward</i>
									</span>
									<span v-if="!pagination.descending && header.value === pagination.sortBy">
										<i class="material-icons mi-12">arrow_downward</i>
									</span>
								</th>
								<th class="column" v-if="!expandedAll" @click="expandAll()">
									Expand all
									<v-icon small>unfold_more</v-icon>
								</th>
								<th class="column" v-if="expandedAll" @click="unExpandAll()">
									Expand all
									<v-icon small>unfold_less</v-icon>
								</th>
							</tr>
						</template>
						<template slot="items" slot-scope="props">
							<tr @click="expandFunction(props)" style="background-color: #E5E5E5">
								<td v-for="table_header in table_headers">
									{{ props.item[table_header.value] }}
								</td>
								<td>
									<i aria-hidden="true" class="v-icon material-icons theme--light" style="font-size: 16px;" v-if="!props.expanded">expand_more</i>
									<i aria-hidden="true" class="v-icon material-icons theme--light" style="font-size: 16px;" v-if="props.expanded">expand_less</i>
								</td>
							</tr>
						</template>
						<template slot="expand" slot-scope="props">
							<v-card flat>
								<v-card-text>

								<!--	<v-flex row>
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
									</v-flex> -->

									<v-data-table
											:headers="expand_headers"
											:items="expand_items[props.index]"
											:pagination.sync="expand_pagination[props.index]"
											:total-items="expand_table_total[props.index]"
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
				</v-container>
			</template>
		</v-content>
	</v-app>
</div>
</div>


<script type="text/x-template" id="select-template">

</script>


<script>
	var data_objects =  <?php echo $data_objects; ?>;
	var auditable_type = '<?php echo $auditable_type; ?>';
	var auditable_id = '<?php echo $auditable_id; ?>';

	var vm = new Vue({ el: '#app',
	data: {
		arFilter: [],
		isConcreteObject: false,
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
			{
				text: 'User Alias',
				align: 'left',
				value: 'alias_name'
			},
			{ text: 'Data Object', value: 'main_auditable_model' },
			{ text: 'Data Object ID', value: 'auditable_id' },
			{ text: 'Event', value: 'event' },
			{ text: 'Date Change', value: 'date_added' },
		],
		expand_items: [],
		expand_headers: [
			{ text: 'Attribute', value: 'attribute_name', sortable: false, },
			{ text: 'Old Value', value: 'old_value', sortable: false, },
			{ text: 'New Value', value: 'new_value', sortable: false, },
		],
		expand_pagination: [],
		expand_table_total: [],
		data_object_id:'',
		isDataObjectIdDisabled: true,
		isDataObjectDisabled: false,
		expandedAll: false,
		props: [],
		user_name: '',
	},

		computed: {
			clearFilterDisabled: function () {
				if (this.arFilter.length > 0
					|| this.date_from.length > 0
					|| this.date_to.length > 0
					|| this.user_name.length > 0
					) {
					return false;
				}
				return true;
			}
		},

		created: function () {
			this.debouncedGetDataFromApi = _.debounce(this.getDataFromApi, 500)
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
			},
			user_name: function () {
				this.debouncedGetDataFromApi();
			},
		},
		mounted () {
		//	this.getDataFromApi();
			if (auditable_type != '' && auditable_id != '') {
				this.isConcreteObject = true;
				this.selected_data_object = auditable_type;
				this.data_object_id = auditable_id;
				this.dataObjectChange();
				this.isDataObjectDisabled = true;
				this.isDataObjectIdDisabled = true;
				this.isSelectedFieldsDisabled = false;
				var filterItem = {
					'auditable_type': this.selected_data_object,
					'auditable_id': this.data_object_id,
				};
				if (typeof data_objects[this.selected_data_object] !== 'undefined' ) {
					filterItem['attribute_name'] = data_objects[this.selected_data_object].table_columns;
				}
				this.arFilter.push(filterItem);
			}
		},
		methods: {
			dataObjectChange: function () {
				if (typeof data_objects[this.selected_data_object] !== 'undefined' ) {
					this.available_fields = data_objects[this.selected_data_object].table_columns;
				}
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
				this.user_name = '';
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
				param.user_name = this.user_name;
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
				} else {
					props.expanded = false;
				}
			},
			getExpandDataFromApi(props) {
				this.loading = true;
				//vm.$set(vm.$refs.dTable.expanded, props.item.id, true);
				props.expanded = true;
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
						vm.expand_items[props.index] = response.data.items;
						vm.expand_table_total[props.index] = response.data.total;
						props.expanded = true;
						vm.$set(vm.$refs.dTable.expanded, props.item.id, true);
						vm.loading = false;
					})
					.catch(function (error) {
						vm.loading = false;
						alert('Ошибка! Не могу связаться с API. ' + error);
					});
			},
			expandAll: function () {
				for (let i = 0; i < this.table_items.length; i += 1) {
					const item = this.table_items[i];
					var props = {
						index: i,
						item: item,
					}
					this.getExpandDataFromApi(props);
				}
				this.expandedAll = true;
			},
			unExpandAll: function () {
				for (let i = 0; i < this.table_items.length; i += 1) {
					const item = this.table_items[i];
					var props = {
						index: i,
						item: item,
					}
					vm.$set(vm.$refs.dTable.expanded, props.item.id, false);
				}
				this.expandedAll = false;
			},
			changeSort: function (sortBy) {
				this.unExpandAll();
				this.pagination.sortBy = sortBy;
				this.pagination.descending = !this.pagination.descending;
				this.getDataFromApi();
			}
		}
	});
</script>
