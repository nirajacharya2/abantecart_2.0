<style>
	.container {
		padding: 5px;
		padding-right: 15px;
		padding-left: 15px;
		max-width: 100%;
	}
</style>
<?php include($tpl_common_dir.'action_confirm.tpl'); ?>

<?php if ($modal_mode) { ?>
	<div class="panel-heading">
		<div class="panel-btns">
			<a class="panel-close" onclick="$('#viewport_modal').modal('hide');">×</a>
		</div>
		<h4 class="panel-title">Audit Log</h4>
	</div>
<?php } ?>

<div class="tab-content">
	<div id="app">
		<v-app>
			<v-content>
				<template>
					<v-dialog
							v-model="loading"
							hide-overlay
							persistent
							width="300"
							attach="body"
					>
						<v-card
								color="primary"
						>
							<v-card-title>
								Please stand by
								<v-progress-linear
										indeterminate
										color="white"
										class="mb-0"
								></v-progress-linear>
							</v-card-title>
						</v-card>
					</v-dialog>
					<v-container>
						<v-layout row wrap align-center>
							<v-flex xs12 sm3>
								<v-container fluid style="position: relative;">
									<v-menu
											ref="dialog"
											v-model="modal"
											:close-on-content-click="false"
											attach
									>
										<template v-slot:activator="{ on }">
											<v-text-field
													v-model="date_from"
													label="Date From"
													persistent-hint
													readonly
													prepend-icon="event"
													v-on="on"
											></v-text-field>
										</template>
										<v-date-picker v-model="date_from" no-title @input="modal = false"></v-date-picker>
									</v-menu>
								</v-container>
							</v-flex>
							<v-flex xs12 sm3>
								<v-container fluid style="position: relative;">
									<v-menu
											ref="dialog2"
											v-model="modal2"
											:close-on-content-click="false"
											attach
									>
										<template v-slot:activator="{ on }">
											<v-text-field
													v-model="date_to"
													label="Date To"
													persistent-hint
													readonly
													prepend-icon="event"
													v-on="on"
											></v-text-field>
										</template>
										<v-date-picker v-model="date_to" no-title @input="modal2 = false"></v-date-picker>
									</v-menu>
								</v-container>
							</v-flex>
							<v-flex xs12 sm3>
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
							<v-flex xs12 sm3>
								<v-container fluid>
									<v-select
											name="events"
											v-model="events"
											label="Event"
											single-line
											:items="event_items"
											hint="Select Event Name"
											attach
											multiple
									></v-select>
								</v-container>
							</v-flex>
						</v-layout>
						<v-layout row wrap align-center v-if="!isConcreteObject">
							<v-flex xs12 sm3>
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

							<v-flex xs12 sm3>
								<v-container fluid>
									<v-text-field
											name="data_object_id"
											v-model="data_object_id"
											v-bind:disabled="isDataObjectIdDisabled"
											label="Auditable ID"
											single-line
											hint="Input Auditable ID"
									></v-text-field>
								</v-container>
							</v-flex>
							<v-flex xs12 sm3>
								<v-container fluid>
									<v-select
											:items="available_fields"
											v-model="selected_fields"
											label="Auditable field"
											multiple
											hint="Pick Auditable field"
											persistent-hint
											v-bind:disabled="isSelectedFieldsDisabled"
											@input="selectedFieldsChange()"
											attach
									></v-select>
								</v-container>
								<div id="id_selected_fields"></div>
							</v-flex>
							<v-flex xs12 sm3 style="text-align: center;">
								<v-btn @click="addFilter()" v-bind:disabled="isAddDisabled">Add</v-btn>
							</v-flex>
							<v-flex xs12 sm12>
								<v-chip light close small
										v-for="item in arFilter"
										:key="arFilter.indexOf(item)"
										v-model="item.isOpen"
										@click="selectChip(item)"
										@click:close="removeChip(item)"
								>
									{{item.auditable_type}}
									<span v-if="item.auditable_id"> ({{item.auditable_id}}) </span>
									= {{item.field_name}}
								</v-chip>
							</v-flex>
						</v-layout>
						<v-layout>
							<v-flex xs12 sm4 style="text-align: center;">
								<v-btn color="primary" @click="applyFilter()" v-bind:disabled="clearFilterDisabled">Apply Filter</v-btn>
							</v-flex>
							<v-flex xs12 sm4 style="text-align: center;">
								<v-btn color="error" @click="clearFilter()" v-bind:disabled="clearFilterDisabled">Clear Filter</v-btn>
							</v-flex>
							<v-flex xs12 sm4 style="text-align: center;">
								<button id="export_to_excel"
										class="btn btn-default task_run"
										title="Export to Excel"
										:data-run-task-url="dataRunTaskUrl"
										data-complete-task-url="<?php echo $this->html->getSecureUrl('r/common/export_task/complete', '&controller=task/tool/export_audit_log&limit=100'); ?>"
										data-abort-task-url="<?php echo $this->html->getSecureUrl('r/common/export_task/abort', '&controller=task/tool/export_audit_log&limit=100'); ?>"
								><i class="fa fa-file-excel"></i></button>
							</v-flex>
						</v-layout>
					</v-container>
					<v-container>
						<div style="position: relative;">
							<v-data-table
									:headers="table_headers"
									:items="table_items"
									ref="dTable"
									:options.sync="pagination"
									:server-items-length="table_total"
									no-data-text="No data, please change filter props."
									class="elevation-1"
									v-on:item-expanded="expandFunction"
									attach
									hide-default-footer
									:expanded.sync="table_expand"
							>
								<template slot="item" slot-scope="props">
									<tr @click="expandFunction(props)">
										<td v-for="table_header in table_headers" :width="table_header.width" :title="props.item[table_header.value]">
											<span v-html="props.item[table_header.value]"></span>
										</td>
									</tr>
								</template>

								<template slot="expanded-item" slot-scope="{ headers, item }">
									<td :colspan="headers.length">
										<v-card flat>
											<v-card-text>
												<v-data-table
														:headers="expand_headers"
														:items="expand_items[item.id]"
														:options.sync="expand_pagination[props.id]"
														:server-items-length="expand_table_total[props.id]"
														hide-default-footer
												>
													<template slot="item" slot-scope="expand_props">
														<tr>
															<td v-for="expand_header in expand_headers" :title="expand_props.item[expand_header.value]">
																<span v-html="expand_props.item[expand_header.value]"></span>
															</td>
														</tr>
													</template>
												</v-data-table>
											</v-card-text>
										</v-card>
									</td>
								</template>
							</v-data-table>
							<v-layout row justify-center align-end mt-4>
								<v-flex xs6 md6 justify-center>
									<v-pagination v-model="pagination.page"
												  total-visible="7"
												  @input="applyFilter"
												  v-if="Math.ceil(table_total/pagination.rowsPerPage) > 1"
												  :length="Math.ceil(table_total/pagination.rowsPerPage)"
									></v-pagination>
								</v-flex>
								<v-flex xs3 offset-xs6 offset-md4 md1 justify-end>
									<v-select
											:items="rowsPerPage"
											v-model="pagination.rowsPerPage"
											@input="applyFilter"
											label="Rows"
											dense
											hide-details
											attach
									></v-select>
								</v-flex>
							</v-layout>
						</div>
					</v-container>
				</template>
			</v-content>
		</v-app>
	</div>
</div>


<script type="text/x-template" id="select-template">

</script>


<script>
	let data_objects = <?php echo $data_objects; ?>;
	let auditable_type = '<?php echo $auditable_type; ?>';
	let auditable_id = '<?php echo $auditable_id; ?>';
	let auditable_fields = '<?php echo $auditable_fields; ?>';

	let curDate = new Date();
	curDate.setDate(curDate.getDate() - 10)
	curDate = curDate.toISOString().substr(0, 10);

	let vm = new Vue({
		el: '#app',
		vuetify: new Vuetify(),
		data: {
			dataRunTaskUrlBase: '<?php echo $this->html->getSecureUrl('r/common/export_task/buildTask', '&controller=task/tool/export_audit_log&limit=100'); ?>',
			dataRunTaskUrl: this.dataRunTaskUrlBase,
			rowsPerPage: [10, 20, 30, 40, 50],
			arFilter: [],
			table_expand: [],
			isConcreteObject: false,
			objectsInArFilter: [],
			isSelectedFieldsDisabled: true,
			//	isAddDisabled: true,
			available_fields: [],
			data_objects: data_objects.classes,
			const_data_objects: data_objects.classes,
			selected_data_object: '',
			selected_fields: [],
			date_from: curDate,
			date_from1: '',
			date_to: '',
			modal: '',
			modal2: '',
			chip: '',
			clearSelectedDisabled: true,
			table_items: [],
			table_total: 0,
			loading: true,
			pagination: {
				rowsPerPage: 20,
				descending: true,
				sortBy: ['date_added'],
				sortDesc: [true],
			},
			events: [],
			event_items: ['Created', 'Updating', 'Deleted', 'Restored'],
			table_rows_per_page_items: [10, 20, 30, 40, 50, 60, 70, 80, 90, 100],
			table_headers: [
				/*{ text: 'Expand', value: 'data-table-expand', width: '5%'}, */
				{
					text: 'User Name',
					align: 'left',
					value: 'user_name',
					width: '20%'
				},
				/*{
					text: 'User Alias',
					align: 'left',
					value: 'alias_name'
				},*/
				{
					text: 'Auditable Object', value: 'main_auditable_model',
					width: '10%'
				},
				{
					text: 'Auditable ID', value: 'main_auditable_id',
					width: '10%'
				},
				{
					text: 'Event', value: 'event',
					width: '10%'
				},
				{
					text: 'Description', value: 'description', sortable: false,
					width: '20%'
				},
				{
					text: 'IP', value: 'ip',
					width: '10%'
				},
				{
					text: 'Date Change', value: 'date_added',
					width: '15%'
				},
			],
			expand_items: [],
			expand_headers: [
				{text: 'Model', value: 'auditable_model', sortable: false, width: '20%'},
				{text: 'Field', value: 'field_name', sortable: false, width: '20%'},
				{text: 'Old Value', value: 'old_value', sortable: false, width: '30%'},
				{text: 'New Value', value: 'new_value', sortable: false, width: '30%'},
			],
			expand_pagination: [],
			expand_table_total: [],
			data_object_id: '',
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
					|| this.events.length > 0
				) {
					return false;
				}
				return true;
			},
			isAddDisabled: function () {
				if (this.selected_data_object.length > 0
					|| this.data_object_id.length > 0
					|| this.selected_fields.length > 0) {
					return false;
				}
				return true;
			}
		},

		created: function () {
			this.debouncedGetDataFromApi = _.debounce(this.getDataFromApi, 500)
		},
		watch: {
			selected_data_object: function (newVal, oldVal) {
				this.clearSelectedDisabled = true;
				if (newVal.length > 0) {
					this.clearSelectedDisabled = false;
				}
			},
			pagination: function () {
				this.getDataFromApi();
			},
		},
		mounted() {
			if (auditable_type != '' && auditable_id != '') {
				//this.isConcreteObject = true;
				this.selected_data_object = auditable_type;
				this.data_object_id = auditable_id;
				this.dataObjectChange();
				//this.isDataObjectDisabled = true;
				//this.isDataObjectIdDisabled = true;
				//this.isSelectedFieldsDisabled = false;
				let filterItem = {
					'auditable_type': this.selected_data_object,
					'auditable_id': this.data_object_id,
					'field_name': auditable_fields
				};
				if (this.data_object_id.length > 0) {
					filterItem.auditable_id = this.data_object_id.split(',')
					filterItem.auditable_id.forEach(function (el, index) {
						this[index] = el.trim()
					})
				}
				if (auditable_fields.length > 0) {
					filterItem.field_name = auditable_fields.split(',')
					filterItem.field_name.forEach(function (el, index) {
						this[index] = el.trim()
					})
				}
				this.arFilter.push(filterItem);
			}
			this.debouncedGetDataFromApi();
		},
		methods: {
			applyFilter: function () {
				this.debouncedGetDataFromApi();
			},
			dataObjectChange: function () {
				if (typeof data_objects[this.selected_data_object] !== 'undefined') {
					this.available_fields = data_objects[this.selected_data_object].table_columns;
				}
				this.isSelectedFieldsDisabled = false;
				this.isDataObjectIdDisabled = false;
			},
			selectedFieldsChange: function () {
				/*if (this.selected_fields.length > 0) {
					this.isAddDisabled = false;
				} else {
					this.isAddDisabled = true;
				}*/
			},
			clearSelected: function () {
				this.selected_data_object = '';
				this.available_fields = [];
				this.selected_fields = [];
				this.isSelectedFieldsDisabled = true;
				this.isDataObjectIdDisabled = true;
				this.data_object_id = '';
			},
			addFilter: function () {
				let filterItem = {
					'auditable_type': this.selected_data_object,
					'field_name': this.selected_fields,
					'auditable_id': this.data_object_id,
				};
				if (this.data_object_id.length > 0) {
					filterItem.auditable_id = this.data_object_id.split(',')
					filterItem.auditable_id.forEach(function (el, index) {
						this[index] = el.trim()
					})
				}
				this.arFilter.push(filterItem);
				this.objectsInArFilter.push(this.selected_data_object);
				this.removeAddedFromSelect(this.selected_data_object);
				this.clearSelected();
			},
			clearFilter: function () {
				if (!this.isConcreteObject) {
					this.arFilter = [];
					for (i = 0; i < this.objectsInArFilter.length; i++) {
						let index = this.data_objects.indexOf(this.objectsInArFilter[i]);
						if (index == -1) {
							this.data_objects.push(this.objectsInArFilter[i]);
						}
					}
				}
				this.date_from = '';
				this.date_to = '';
				this.events = [];
				this.user_name = '';
				this.debouncedGetDataFromApi();
			},
			removeAddedFromSelect: function (item) {
				let index = this.data_objects.indexOf(item);
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
			},
			removeChip: function (item) {
				let index = -1;
				for (i = 0; i < this.arFilter.length; i++) {
					if (this.arFilter[i].auditable_type == item.auditable_type) {
						index = i;
					}
				}
				if (index > -1) {
					this.arFilter.splice(index, 1);
				}
				this.data_objects.push(item.auditable_type);
			},
			getDataFromApi() {
				this.loading = true;
				this.table_expand = []
				let param = this.pagination;
				param.filter = this.arFilter;
				param.date_from = this.date_from;
				param.date_to = this.date_to;
				param.user_name = this.user_name;
				param.events = this.events;
				this.unExpandAll();
				this.dataRunTaskUrl = this.dataRunTaskUrlBase + '&' + $.param(param)
				let promise = axios.get('<?php echo $ajax_url; ?>', {params: param})
					.then(function (response) {
						vm.table_items = response.data.items;
						vm.table_total = response.data.total;
						vm.loading = false;
					})
					.catch(function (error) {
						vm.loading = false;
						alert('No connection to API. ' + error);
					});
			},
			expandFunction: function (props) {
				if (!this.table_expand.includes(props.item)) {
					this.table_expand.push(props.item)
				} else {
					this.table_expand.splice( this.table_expand.indexOf(props.item), 1 );
				}
				if (typeof vm.expand_items[props.item.id] == "undefined") {
					this.getExpandDataFromApi(props);
				}
			},
			getExpandDataFromApi(props) {
				this.loading = true;
				let filter = {
					'audit_event_id': props.item.id,
				};
				let param = {
					filter: filter,
					getDetail: true,
				}
				let promise = axios.get('<?php echo $ajax_url; ?>', {params: param})
					.then(function (response) {
						vm.expand_items[props.item.id] = response.data.items;
						vm.expand_items.splice(props.item.id, 1, response.data.items)
						vm.expand_table_total[props.item.id] = response.data.total;
						vm.loading = false;
					})
					.catch(function (error) {
						vm.loading = false;
						alert('No connection to API. ' + error);
					});
			},
			expandAll: function () {
				for (let i = 0; i < this.table_items.length; i += 1) {
					const item = this.table_items[i];
					let props = {
						index: i,
						item: item,
					}
					this.expandFunction(props);
				}
				this.expandedAll = true;
			},
			unExpandAll: function () {
				for (let i = 0; i < this.table_items.length; i += 1) {
					const item = this.table_items[i];
					let props = {
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
