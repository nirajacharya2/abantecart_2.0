<?php
use abc\core\ABC;

if (!file_exists(ABC::env('DIR_PUBLIC').'vendor/components/jqgrid/js/i18n/grid.locale-'.$locale.'.js')) {
	$locale = 'en';
}

$this->document->addScript('vendor/components/jqgrid/js/i18n/grid.locale-'.$locale.'.js');
$jss = glob(ABC::env('DIR_PUBLIC').'vendor/components/jqgrid/js/minified/*.js');
foreach($jss as $js){
    $this->document->addScript('vendor/components/jqgrid/js/minified/'.basename($js));
}

?>
<div class="ui-jqgrid-wrapper" id="<?php echo $data['table_id'] ?>_wrapper">
	<form class="form-inline" id="<?php echo $data['table_id'] ?>_form" action="<?php echo $data["editurl"] ?>" method="POST" role="form">
		<table id="<?php echo $data['table_id'] ?>"></table>
		<div id="<?php echo $data['table_id'] ?>_pager"></div>
		<div class="no_results"><?php echo $text_no_results; ?></div>
		<?php if ($data['multiselect'] == 'true' && !$data['multiselect_noselectbox']) { ?>
		<div class="multiactions <?php echo $data['multiaction_class']; ?>" id="<?php echo $data['table_id'] ?>_multiactions" align="right">
			<select id="<?php echo $data['table_id'] ?>_selected_action" name="<?php echo $data['table_id'] ?>_action">
			<?php
				if (sizeof($multiaction_options) > 1) { ?>
					<option value=""><?php echo $text_choose_action; ?></option>
				<?php
				}
				foreach ($multiaction_options as $value => $text) {	?>
					<option value="<?php echo $value; ?>"><?php echo $text; ?></option>
				<?php
				} ?>
			</select>
			<a id="<?php echo $data['table_id'] ?>_go" class="btn btn-xs btn-default"><?php echo $text_go; ?></a>
		</div>
	<?php } ?>
	</form>
</div>
<?php
	//load quick view port modal
	echo $this->html->buildElement(
		array(
				'type' => 'modal',
				'id' => 'viewport_modal',
				'modal_type' => 'lg',
				'data_source' =>'ajax',
				'js_onload' => "
						var url = $(this).data('bs.modal').options.fullmodeHref;
						$('#viewport_modal .modal-header a.btn').attr('href',url);
						",
				'js_onclose' => "$('#".$data['table_id']."').trigger('reloadGrid',[{current:true}]);"
		)
	);
?>

<script type="text/javascript" src="vendor/components/jqgrid/plugins/jquery.tablednd.js"></script>
<script type="text/javascript">

var initGrid_<?php echo $data['table_id'] ?> = function ($) {

	var text_choose_action = <?php abc_js_echo($text_choose_action); ?>;
	var text_select_items = <?php abc_js_echo($text_select_items); ?>;
	var _table_id = '<?php echo $data['table_id'] ?>';
	var table_id = '#<?php echo $data['table_id'] ?>';
	var jq_names = [<?php
		foreach($data['colNames'] as $col_name){
			abc_js_echo($col_name);
			echo ',';
		}
		//echo "'" . implode("','", $data['colNames']) . "'"
		?>];
	var jq_model = [<?php
	$i = 1;
	foreach ($data['colModel'] as $m) {
		$col = array('resizable: false', 'title: false', 'searchoptions: { sopt:[\'cn\'] }');
		foreach ($m as $k => $v) {
			if (is_string($v)) {
                if ($k == 'cellattr') {
                    $col[] = "$k: ".$v;
                } else {
                    $col[] = "$k: '".addslashes($v)."'";
                }
			} else if (is_int($v)) {
				$col[] = "$k: " . (int)($v);
			} else if (is_bool($v)) {
				$col[] = "$k: " . ($v ? 'true' : 'false');
			}
		}
		echo "{" . implode(',', $col) . "}";
		if ($i < sizeof($data['colModel'])) {
			echo ',';
			$i++;
		}
		echo "\r\n";
	}
	?>];

	var gridFirstLoad = true;

	var updatePerPage = function(records, limit) {
		var html='',
			rowList = [<?php echo implode(',', $data['rowList']) ?>];
		for (var i = 0; i < rowList.length; i++) {
			if (records > rowList[i]) {
				html += '<option '
					+ (rowList[i] == limit ? ' selected="selected" ' : '')
					+'value="' + rowList[i] + '">'
					+ rowList[i] + '</option>';
			}
		}
		if (records <= 100) {
			html += '<option '+ (records == limit ? ' selected="selected" ' : '') +'value="' + records + '"><?php echo $text_all ?></option>';
		}
		$(table_id + '_pager_center .ui-pg-selbox').html(html);
	}

	<?php if( $data["drag_sort_column"] ) { ?>
	var startpos;
	$(table_id).tableDnD({
		onDragClass: 'drag_row',
		onDrop: function(table, row) {
			var sort_direction = $(table_id).jqGrid('getGridParam','sortorder');
			var ids_order = [];
			var rows = table.tBodies[0].rows;
			var draged_id = row.id;
			var newpos = $('#'+row.id).position();
			//slip if row was not moved (single click of incomplete drag fix)
			if (newpos.top == startpos.top && newpos.left == startpos.left) {
				return false;
			}

			//check for depth if this is a nested tree greed.
			var depth = $(table_id).getNodeDepth( $(table_id).getRowData(draged_id) );
			if (depth > 0) {
				//build sort for only children
				var parent = $(table_id).getNodeParent( $(table_id).getRowData(draged_id) );
				if(!parent){
					return;
				}
				var children = $(table_id).getNodeChildren( parent );
				var children_ids = [];
				for (var i=0; i<children.length; i++) {
					children_ids.push(children[i]._id_);
				}
				//presave the order and build ids list
				for (var i=1; i<rows.length; i++) {
					if ( $.inArray(rows[i].id, children_ids) >= 0 ) {
						ids_order.push(rows[i].id);
					}
				}
			} else {
				for (var i=1; i<rows.length; i++) {
						ids_order.push(rows[i].id);
				}
			}
			//save new sorting and reload the grid
			save_new_sorting(ids_order, sort_direction);
		},
		onDragStart: function(table, row) {
			var rowid = row.id;
			$('#'+rowid).css('width',$(table).css('width'));
			$(table_id).find('tr.jqgfirstrow').addClass('nodrop');
			startpos = $('#'+row.id).position();
		}
	});
	<?php } ?>

	$(table_id).jqGrid({
		url: '<?php echo $data["url"] ?>',
		editurl: '<?php echo $data["editurl"] ?>',
		datatype: "json",
		mtype: "POST",
		contentType: "application/json; charset=utf-8",
		colNames: jq_names,
		colModel: jq_model,
		rowNum: <?php echo $data['rowNum'] ?>,
		rowList: [<?php echo implode(',', $data['rowList']) ?>],
		pager: table_id + '_pager',
		multiselect: <?php echo $data['multiselect'] ?>,
		hoverrows: <?php echo $data['hoverrows'] ?>,
		viewrecords: true,
		altRows: <?php echo $data['altRows'] ?>,
		height: '100%',
		width:  '100%',// memory leak in damn msie
		shrinkToFit: false,
		autowidth: true,
		sortname: '<?php echo $data['sortname'] ?>',
		sortorder: '<?php echo $data['sortorder'] ?>',
		<?php if($data['expand_column']) { ?>
		treeGrid: true,
		treeGridModel: 'adjacency',
		ExpandColClick: true,
		ExpandColumn: '<?php echo $data['expand_column']; ?>',
		<?php } ?>
		gridComplete: function() {
			<?php if( $data["drag_sort_column"] ) { ?>
			//enable row drag/drop sorting only if sorting present and used
			var sort_by = $(table_id).jqGrid('getGridParam','sortname');
			if (sort_by == '<?php echo $data["drag_sort_column"]; ?>') {
				$(table_id + " tr").removeClass("nodrag nodrop");
				$(table_id).tableDnDUpdate();
			}
			<?php } ?>
		},
		loadComplete:function (data) {
			if(!gridFirstLoad){
				save_grid_search_form_parameters();
			}

			gridFirstLoad = false;

			if(data!=undefined){
				if (data.userdata!=undefined && data.userdata.classes != null) {
					for (var id in data.userdata.classes) {
						$('#' + id).addClass(data.userdata.classes[id]);
					}
				}
			}

			// check loaded records count
			var reccount = $(table_id).jqGrid('getGridParam', 'reccount');
			var records = $(table_id).jqGrid('getGridParam', 'records');
			var rowNum = $(table_id).jqGrid('getGridParam', 'rowNum');
			if (!reccount) {
				$(table_id + '_pager').hide();
				$(table_id + '_wrapper .no_results').show();
			} else {
				$(table_id + '_pager').show();
				$(table_id + '_wrapper .no_results').hide();
			}

			//Hide Pager on treeGreed
			if($(table_id).find(".tree-wrap").length){
				$(table_id + '_pager .ui-paging-info').hide();
			}

			//add wrappers to the fields
			$(table_id).find("input:not( input.cbox ), textarea, select").each(function () {
				$.aform.styleGridForm(this);
				$(this).aform({triggerChanged:false});
			});

			// init datepicker for fields
			if ($('.date').length > 0) {
				$('.date').datepicker({dateFormat:'yy-mm-dd'});
			}

			//uncheck multiselect checkbox
			$('#cb_' + _table_id).change();

			// apply form transformation to all elements except grid row checkboxes
			$("input:not( input.cbox ), textarea, select", table_id).not('.no-save').aform({
				triggerChanged:true,
				buttons:{
					save:'Save',
					reset:'Refresh'
				},
				save_url:'<?php echo $data["update_field"] ?>'
			});

			$("input.cbox", table_id).aform({
				triggerChanged:false
			});

			//add grid filter fields for saved state
			var gridInfo = $.parseJSON(Cookies.get("grid_params"));
			if(gridInfo && gridInfo.postData && gridInfo.postData.filters){
				var $filters = $.parseJSON(gridInfo.postData.filters);
				$.each ($filters.rules, function( index, value ){
					$('#gs_'+value.field).val(value.data);
				});
			}

			var actions = '';
			var actions_urls = {};
		<?php
		if (!empty($data['actions'])) {
			foreach ($data['actions'] as $type => $action) {
				$html_string = '';
				$href = 'href="'.(H::has_value($action['href']) ? $action['href'] : '#').'"';

				$html_string .= "actions_urls['".$type."'] = '".$href."';\n";
				$html_string .= ' actions += \'';
				$has_children = isset($action['children']) && $action['children'];
				$html_btn = '<a class="btn btn-xs btn_grid tooltips grid_action_' . $type . '" title="' . htmlentities($action['text'],ENT_QUOTES,ABC::env('APP_CHARSET')) . '" data-action-type="'.$type.'"';
				if($has_children){
					$html_btn .= ' data-toggle="dropdown" aria-expanded="false"';
				}
				switch ($type) {
					case 'edit':
						$html_btn .= ' '.$href.' rel="%ID%"><i class="fa fa-edit fa-lg"></i>';
						break;
					case 'delete':
						if($href!='href="#"'){
							$html_btn .= ' '.$href.' rel="%ID%" data-confirmation="delete"><i class="fa fa-trash-alt fa-lg"></i>';
						}else{
							$html_btn .= ' '.$href.' rel="%ID%"><i class="fa fa-trash-alt fa-lg"></i>';
						}
						break;
					case 'save':
						$html_btn .= ' '.$href.' rel="%ID%"><i class="fa fa-save fa-lg"></i>';
						break;
					case 'expand':
						$html_btn .= ' '.$href.' rel="%ID%"><i class="fa fa-plus-square-o fa-lg"></i>';
						break;
					case 'restart':
						$html_btn .= ' '.$href.' rel="%ID%"><i class="fa fa-repeat fa-lg"></i>';
						break;
					case 'run':
						$html_btn .= ' '.$href.' rel="%ID%"><i class="fa fa-play fa-lg"></i>';
						break;
					case 'approve':
                        $html_btn .= ' '.$href.' rel="%ID%"><i class="fa fa-check-square fa-lg"></i>';
						break;
					case 'actonbehalfof':
						$html_btn .= ' '.$href.' target="_blank" rel="%ID%"><i class="fa fa-male fa-lg"></i>';
						break;
					case 'clone':
						$html_btn .= ' '.$href.' rel="%ID%"><i class="fa fa-clone fa-lg"></i>';
						break;
					case 'remote_install':
						$html_btn .= ' '.$href.' rel="%ID%"><i class="fa fa-play fa-lg"></i>';
						break;
					case 'install':
						$html_btn .= ' '.$href.' rel="%ID%"><i class="fa fa-play fa-lg"></i>';
						break;
					case 'uninstall':
						$html_btn .= ' '.$href.' rel="%ID%" data-confirmation="delete"><i class="fa fa-times fa-lg"></i>';
						break;
					case 'view':
						$html_btn .= ' '.$href.' rel="%ID%"><i class="fa fa-eye fa-lg"></i>';
						break;
					default:
						$html_btn .= ' '.$href.' id="action_' . $type . '_%ID%"  ' . (!empty($action['target']) ? 'target="' . $action['target'] . '"' : '') . '><i class="fa fa-' . $type . ' fa-lg"></i>';
				}

				if($has_children){
					$html_btn .= '<span class="caret"></span>';
				}
				$html_btn .= '</a>';

				//for dropdown
				if($action['children']){
					$html_children = '<div class="dropdown-menu dropdown-menu-sm dropdown-menu-right" role="menu"><h5 class="title">'.htmlentities($text_select_from_list,ENT_QUOTES,ABC::env('APP_CHARSET')).'</h5><ul class="dropdown-list grid-dropdown">';
					foreach($action['children'] as $id => $child){
						$li_class = '';
						$href = H::has_value($child['href']) ? $child['href'] : '#';
						//for viewport mode
						if($child['vhref']){
							$href = 'data-toggle="modal" data-target="#viewport_modal" href="'.$child['vhref'].'" data-fullmode-href="'.$href.'"';
						} else {
							$href = 'href="'.$href.'"';
						}
						$html_children .= '<li class="'.$li_class.' '.$id.'"><a '.$href.' rel="'.$id.'">'.htmlentities($child['text'],ENT_QUOTES,ABC::env('APP_CHARSET')).'</a></li>';
					}
					$html_children .= '</ul></div>';
					$html_btn = '<div class="btn-group">'.$html_btn.''.$html_children.'</div>';
				}

				echo $html_string.$html_btn."'; \r\n";
			}

		} // end of action
		?>
			if (actions != '') {
				var ids = jQuery(table_id).jqGrid('getDataIDs');
				for (var i = 0; i < ids.length; i++) {
					if (ids[i] != 'null') {
						var _a = actions.replace(/%ID%/g, ids[i]);
						jQuery(table_id).jqGrid('setRowData', ids[i], {action:_a});
					}
				}

				$(table_id + '_wrapper a[class*=grid_action_]')
						.not('.grid_action_delete[href="#"], .grid_action_delete[href=""], .grid_action_save, .grid_action_expand')
						.click(function () {

					if($(this).attr('href')!='#'){
						return;
					}

					var btn_type = $(this).attr('data-action-type');

					if($(table_id + '_selected_action').length>0){
						$(table_id).jqGrid('resetSelection').jqGrid('setSelection', $(this).attr('rel'));
						$(table_id + '_selected_action').val(btn_type);
						$(table_id + "_go").click();
					}else{
						var id =  $(this).attr('rel');
						var data = {id: id, oper: btn_type};
						var URL = actions_urls[btn_type] ? actions_urls[btn_type].replace(/%ID%/g, id) : '<?php echo $data["editurl"] ?>';

						$.ajax({
							url: URL,
							type:'POST',
							data: data,
							success:function (msg) {
								if (msg == '' || msg==null) {
									jQuery(table_id).trigger("reloadGrid",[{current:true}]);
								} else {
									alert(msg);
								}
							},
							error:function (jqXHR, textStatus, errorThrown) {
								alert(textStatus + ": " + errorThrown);
							}
						});
					}
					return false;
				});

				$(table_id + '_wrapper a.grid_action_delete')
						.not(table_id + '_wrapper a.grid_action_delete[data-confirmation="delete"]')
						.click(function () {
					$(table_id)
						.jqGrid('resetSelection')
						.jqGrid('setSelection', $(this).attr('rel'));
					$(table_id + '_selected_action').val('delete');
					$(table_id + "_go").click();
					return false;
				});

				$(table_id + '_wrapper a.grid_action_save').click(function () {
					$(table_id)
						.jqGrid('resetSelection')
						.jqGrid('setSelection', $(this).attr('rel'));
					$(table_id + '_selected_action').val('save');
					$(table_id + "_go").click();
					return false;
				});

				$(table_id + '_wrapper a.grid_action_expand').click(function () {
					var new_url = '<?php echo $data["url"] ?>&' + $(this).attr('rel');
					$(table_id)
						.jqGrid('setGridParam', {url:new_url})
						.trigger("reloadGrid",[{current:true}]);
					return false;
				});
			}

			if (!$(table_id + '_wrapper tr th.ui-state-highlight').length) {
				var sortColumnName = $(table_id).jqGrid('getGridParam', 'sortname');
				if (sortColumnName) {
					var colModel = $(table_id).jqGrid('getGridParam', 'colModel');
					for (var i = 0; i < colModel.length; i++) {
						if (colModel[i].index === sortColumnName) {
							$(table_id + '_wrapper tr.ui-jqgrid-labels th:eq(' + i + ')').addClass('ui-state-highlight');
							$(table_id + '_wrapper tr.ui-search-toolbar th:eq(' + i + ')').addClass('ui-state-highlight');
							break;
						}
					}
				}
			}

			updatePerPage(records, rowNum);

			// select rows after load by user data array
			if ($(table_id).getGridParam('datatype') === "json") {
				var userdata = $(table_id).getGridParam('userData');
				var curPage = $(table_id).getGridParam('page');
				if (userdata.selId) {
					$.each(userdata.selId, function (k, row_id) {
						$(table_id).setSelection(row_id, true);
					});
				}
			}

			$(document).ready(function() {
				<?php if( $data['grid_ready'] ){ echo $data['grid_ready']; } ?>
				//rebind events after grid reload
				bindCustomEvents();
			});
			//end of grid load complete
		},
		onSelectRow:function (id, status) {
			if (status) {
				$('#jqg_' + _table_id + '_' + id).parents('.afield').addClass($.aform.defaults.checkedClass);
			} else {
				$('#jqg_' + _table_id + '_' + id).parents('.afield').removeClass($.aform.defaults.checkedClass);
			}
		},
		onSelectAll:function (ids, status) {
			checkAll('jqg_' + _table_id, status);
		},
		onSortCol:function (index, iCol, sortorder){
			$(table_id + '_wrapper tr th.ui-th-column').removeClass('ui-state-highlight');
			$(table_id + '_wrapper tr.ui-jqgrid-labels th:eq(' + iCol + ')').addClass('ui-state-highlight');
			$(table_id + '_wrapper tr.ui-search-toolbar th:eq(' + iCol + ')').addClass('ui-state-highlight');
			<?php if( $data["drag_sort_column"] ) { ?>
			//enable disable drag/drop rows
			if (index == '<?php echo $data["drag_sort_column"]; ?>') {
				$(table_id + " tr").removeClass("nodrag nodrop");
				$(table_id).tableDnDUpdate();
			} else {
				$(table_id + " tr").addClass("nodrag nodrop");
				$(table_id + " tr").unbind("mousedown");
				$(table_id + " tr").css('cursor', 'default');
			}
			<?php } ?>
			$(table_id).jqGrid('setGridParam').trigger("reloadGrid",[{current:true}]);
		},
		ondblClickRow:function (row_id) {
			//Disable Double click on grid
		<?php if ($data['disable_dblClick']) { ?>
				return false;
			<?php } ?>
			// quickview modal
			var lnk = $('#' + row_id).find("td[aria-describedby$='_action']").find('[data-fullmode-href]');
			//edit link
			if(lnk.length == 0){
				lnk = $('#' + row_id).find("td[aria-describedby$='_action']").find('.dropdown-menu [data-action-type="edit"]');
			}
			//view link
			if(lnk.length == 0){
				lnk = $('#' + row_id).find("td[aria-describedby$='_action']").find('.dropdown-menu [data-action-type="view"]');
			}

			if(lnk.length>0){
				if(lnk.attr('data-toggle').length){
					lnk.click();
				}else {
					var newhref = lnk.attr('href');
					if(newhref.length>0) {
						location = lnk.attr('href');
					}else{
						console.log('listing_grid double click issue. New href is empty.');
						console.log(lnk);
					}
				}
			}

			return false;
		},
		beforeRequest: function() {
			<?php if($this->request->get['saved_list'] == $data['table_id']) { ?>
				//apply saved grid for initial grid load only
				var grid = $(table_id);
				if(gridFirstLoad === true) {
					if (Cookies.get("grid_params") != null && Cookies.get("grid_params") != "") {
						var gridInfo = $.parseJSON(Cookies.get("grid_params"));
						//TODO: merge this calls
						grid.jqGrid('setGridParam', {sortname: gridInfo.sortname});
						grid.jqGrid('setGridParam', {sortorder: gridInfo.sortorder});
						grid.jqGrid('setGridParam', {selrow: gridInfo.selrow});
						grid.jqGrid('setGridParam', {page: gridInfo.page});
						grid.jqGrid('setGridParam', {rowNum: gridInfo.rowNum});
						//purge nodeid (tree leaf expand parameter)
						gridInfo.postData.nodeid = '';
						grid.jqGrid('setGridParam', {postData: gridInfo.postData});
						grid.jqGrid('setGridParam', {search: gridInfo.search});

						//do we have external search form?
						var search_data = $.parseJSON(Cookies.get("grid_search_form"));
						if (search_data != null && search_data.table_id == _table_id) {
							var $form = $(table_id + '_search');
							var new_url = '<?php echo $data["url"] ?>&' + $form.serialize();
							grid.jqGrid('setGridParam', {url: new_url});
						}
					}
				} else {
					save_grid_parameters($(table_id));
				}
			<?php } else { ?>
				//reset grid search for initial load only
				if(gridFirstLoad === true) {
					Cookies.set("grid_search_form", "");
				}
				save_grid_parameters($(table_id));
			<?php } ?>
		}
	});
	$(table_id).jqGrid('navGrid',
						table_id + '_pager',
						{   edit:false,
							add:false,
							del:false,
							search:false,
							refreshstate: "current"
						});

<?php	if ($data['hidden_head']) { ?>
	$('.ui-jqgrid-hdiv').hide();
<?php }

if ($custom_buttons) {
	foreach ($custom_buttons as $custom_button) { ?>
		$(table_id).jqGrid('navGrid', table_id + '_pager').navButtonAdd(table_id + '_pager', {
			caption:"<?php echo $custom_button['caption']; ?>",
			buttonicon:"<?php echo $custom_button['buttonicon']; ?>",
			onClickButton:  <?php echo $custom_button['onClickButton']; ?>,
			position:"<?php echo $custom_button['position']; ?>",
			title:"<?php echo $custom_button['title']; ?>",
			cursor:"<?php echo $custom_button['cursor']; ?>"});
	<?php }
} ?>

<?php if ($data['columns_search']) { ?>
	$(table_id).jqGrid('filterToolbar', { stringResult:true});
	<?php } ?>

<?php if ($data['search_form']) { ?>
	//process grid search from submit
	$(table_id + '_search').submit(function () {
		var params = $(this).serialize();
		var new_url = '<?php echo $data["url"] ?>&' + params;
		//save search request
		save_grid_search_form_parameters();
		$(table_id)
			.jqGrid('setGridParam', {url:new_url, page:1})
			.trigger("reloadGrid",[{current:true}]);
		return false;
	});
	//reset
	$(table_id + '_search button[type="reset"]').click(function () {
		//reset pre-saved cookies and search form fields
		Cookies.set("grid_search_form", "");
		Cookies.set("grid_params", "");
		$(table_id + '_search').find("input[type=text]").attr('value', '');
		$(table_id + '_search').find("select").each(function () {
			$s = $(this);
			$s.find('option').each(function () {
				if ( $(this).attr('selected') === "selected" ) {
					$(this).removeAttr('selected');
				}
				//reset chosen if any
				if ($s.parent().find(".chosen-select").length > 0) {
					$s.parent().find(".chosen-select").trigger("chosen:updated");
				}
			});
		});
		var new_url = '<?php echo $data["url"] ?>';
		$(table_id)
			.jqGrid('setGridParam', {url:new_url})
			.trigger("reloadGrid",[{current:true}]);
		return false;
	});
<?php } ?>

<?php if ($data['multiselect'] == 'true') { ?>
	$(table_id + '_multiactions').appendTo($(table_id + '_pager_right'));
	$(table_id + "_go").click(function () {
		//get all selected rows based on multi-select
		var ids = $(table_id).jqGrid('getGridParam', 'selarrrow');
		//get single selected row
		ids.push( $(table_id).jqGrid('getGridParam', 'selrow') );
		if (!ids.length) {
			alert(text_select_items);
			return;
		}

		switch ($(table_id + '_selected_action').val()) {
			case 'delete':
				$(table_id).jqGrid(
					'delGridRow',
					ids,
					{	reloadAfterSubmit: true,
						beforeShowForm:function ($form) {
							$("td.delmsg", $form[0]).html(<?php abc_js_echo($text_delete_confirm); ?>);
							var dlgDiv = $("#delmod" + _table_id);
							var parentDiv = $(table_id + '_wrapper');
							$('#dData', dlgDiv).show();
							selRowId = $(table_id).jqGrid('getGridParam', 'selrow'),
							selRowCoordinates = $('#' + selRowId).offset();
							dlgDiv.css('top', selRowCoordinates.top);
							dlgDiv.css('left', Math.round((parentDiv.width() - dlgDiv.width()) / 2) + "px");
						},
						afterSubmit:function (response, postdata) {
							if (response.responseText != '') {
								var dlgDiv = $("#delmod" + _table_id);
								$('#dData', dlgDiv).hide();
								return [false, response.responseText];
							} else {
								return [true, ''];
							}
						},
						errorTextFormat:function (response, postdata) {
							if (response.responseText != '') {
								var error_obj = eval('(' + response.responseText + ')');
								$("td.ui-state-error").html(error_obj.error_text);
							}
						}
					}
				);
				break;
			case 'save':
				var form_data = $(table_id + '_form').serializeArray();
				form_data.push({name:'id', value:ids});
				form_data.push({name:'oper', value:'save'});
				$.ajax({
					url:'<?php echo $data["editurl"] ?>',
					type:'POST',
					data:form_data,
					success:function (msg) {
						if (msg == '') {
							jQuery(table_id).trigger("reloadGrid",[{current:true}]);
						} else {
							alert(msg);
						}
					},
					error:function (jqXHR, textStatus, errorThrown) {
						alert(textStatus + ": " + errorThrown);
					}
				});
				break;
			case '':
				alert(text_choose_action);
				return;
			default:
				break;
		}
	});
	<?php } ?>

	$("input, textarea, select", table_id + '_wrapper, .filter').not('.no-save').aform({
		triggerChanged:false
	});

	function save_new_sorting (ids, sort_direction) {
		if(ids.length) {
			var form_data = $(table_id + '_form').serializeArray();
			form_data.push({name:'id', value:ids});
			form_data.push({name:'sort_direction', value:sort_direction});
			form_data.push({name:'resort', value:'yes'});
			form_data.push({name:'oper', value:'save'});
			$.ajax({
				url:'<?php echo $data["editurl"] ?>',
				type:'POST',
				data:form_data,
				success:function (msg) {
					if (msg == '') {
						$(table_id).trigger("reloadGrid",[{current:true}]);
					} else {
						alert(msg);
					}
				},
				error:function (jqXHR, textStatus, errorThrown) {
					alert(textStatus + ": " + errorThrown);
				}
			});
		}
	}

	//save grid settings into cookie
	function save_grid_parameters($grid) {
		var gridInfo = new Object();
		gridInfo.table_id =  $grid.jqGrid('getGridParam', 'id');
		gridInfo.sortname = $grid.jqGrid('getGridParam', 'sortname');
		gridInfo.sortorder = $grid.jqGrid('getGridParam', 'sortorder');
		gridInfo.selrow = $grid.jqGrid('getGridParam', 'selrow');
		gridInfo.page = $grid.jqGrid('getGridParam', 'page');
		gridInfo.rowNum = $grid.jqGrid('getGridParam', 'rowNum');
		gridInfo.postData = $grid.jqGrid('getGridParam', 'postData');
		gridInfo.search = $grid.jqGrid('getGridParam', 'search');
		Cookies.set("grid_params", JSON.stringify(gridInfo));
	}

	function save_grid_search_form_parameters(){
		var params = $(table_id + '_search').serialize();
		var searchInfo = new Object();
		searchInfo.params = params;
		searchInfo.table_id = _table_id;
		Cookies.set("grid_search_form", JSON.stringify(searchInfo));
	}

	//resize jqgrid
	var resize_the_grid = function() {
		// Get width of parent contentpanel
		$targetContainer = $(table_id).closest('.contentpanel');
		var width = $targetContainer.width() - 20;
		if(width < 750) {
			//min grid width is 750px;
			width = 750;
		}
		if (width > 0 && Math.abs(width - $(table_id).width()) > 5) {
			$(table_id).setGridWidth(width, true);
		}
	}

	//resize on load
	resize_the_grid();
	$(window).bind('resize', function() {
		//resize grid width on window resize
		resize_the_grid();
	}).trigger('resize');
	//resize on left panel
	$('body').bind('leftpanelChanged', function() {
		resize_the_grid();
	});

	// fix cursor on non-sortable columns
	var cm = $(table_id)[0].p.colModel;
	$.each($(table_id)[0].grid.headers, function (index, value) {
		var cmi = cm[index], colName = cmi.name;
		if (!cmi.sortable && colName !== 'rn' && colName !== 'cb' && colName !== 'subgrid') {
			$('div.ui-jqgrid-sortable', value.el).css({cursor:"default"});
		}
	});

	$(table_id + '_pager').find("input, select").each(function () {
		$.aform.styleGridForm(this);
		$(this).aform({triggerChanged:false});
	});

	$(table_id + '_selected_action').aform({triggerChanged:false});

	$('tr.ui-search-toolbar').find("input, select").each(function () {
		var index = $(this).parent().parent().parent().children().index($(this).parent().parent());
	<?php if ($data['multiselect'] == 'true') { ?>
		index--;
		<?php }?>
		var align = '';
		if (!jq_model[index]) {
			align = 'middle';
		} else {
			align = jq_model[index].align;
		}
		$(this).parent().css('text-align', align);
		$.aform.styleGridForm(this);
	});
	//remove reset button in search
	$('tr.ui-search-toolbar').find(".ui-search-clear").remove();

};
<?php
//run initialization if initialization on load enabled
if ($init_onload) {
?>
initGrid_<?php echo $data['table_id'] ?>($);
<?php } ?>

//adjust grid pager:
$('#<?php echo $data['table_id'] ?>_pager_center').css({width: ''});

</script>
