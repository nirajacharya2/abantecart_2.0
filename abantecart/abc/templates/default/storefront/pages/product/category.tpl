<h1 class="heading1">
  <span class="maintext"><?php echo $heading_title; ?></span>
  <span class="subtext"></span>
</h1>

<div class="contentpanel">

	<?php if ($description) { ?>
	<div style="margin-bottom: 15px;"><?php echo $description; ?></div>
	<?php } ?>
	<?php if (!$categories && !$products) { ?>
	<div class="content"><?php echo $text_error; ?></div>
	<?php } ?>
	<?php if ($categories) { ?>
	<ul class="thumbnails row">
	    <?php for ($i = 0; $i < sizeof($categories); $i++) { ?>
	     <li class="col-md-2 col-sm-2 col-xs-6 align_center">
	    	<a href="<?php echo $categories[$i]['href']; ?>">
	    		<?php echo $categories[$i]['thumb']['thumb_html']; ?>
	    	</a>
	    	<div class="mt10 align_center" style="height: 40px;">
	    	<a href="<?php echo $categories[$i]['href']; ?>"><?php echo $categories[$i]['name']; ?></a>
	    	</div>
	    </li>
	    <?php } ?>
	</ul>
	<?php } ?>

	<?php if ($products) { ?>
	<div class="sorting well">
	  <form class=" form-inline pull-left">
	    <?php echo $text_sort; ?>&nbsp;&nbsp;<?php echo $sorting; ?>
	  </form>
	  <div class="btn-group pull-right">
	    <button class="btn" id="list"><i class="fa fa-th-list"></i>
	    </button>
	    <button class="btn btn-orange" id="grid"><i class="fa fa-th"></i></button>
	  </div>
	</div>

	<?php include( $this->templateResource('pages/product/product_listing.tpl', 'file') ) ?>
		
	<div class="sorting well">
		<?php echo $pagination_bootstrap; ?>
		<div class="btn-group pull-right">
		</div>
	</div>
	
<?php } ?>		
		
</div>

<script type="text/javascript">

$('#sort').change(function () {
	Resort();
});

function Resort() {
	url = '<?php echo $url; ?>';
	url += '&sort=' + $('#sort').val();
	url += '&limit=' + $('#limit').val();
	location = url;
}

function wishlist_add(product_wishlist_add_url, product_id) {
	var dismiss = '<button type="button" class="close" data-dismiss="alert">&times;</button>';
	$.ajax({
		type: 'POST',
		url: product_wishlist_add_url,
		dataType: 'json',
		beforeSend: function () {
			$('.success, .warning').remove();
			$('#wishlist_add'+product_id).hide();
			$('.wishlist').after('<div class="wait"><i class="fa fa-spinner fa-spin"></i> <?php echo $text_wait; ?></div>');
		},
		complete: function () {
			$('.wait').remove();
		},
		error: function (jqXHR, exception) {
			var text = jqXHR.statusText + ": " + jqXHR.responseText;
			$('.wishlist .alert').remove();
			$('.wishlist').after('<div class="alert alert-error alert-danger">' + dismiss + text + '</div>');
			$('#wishlist_add'+product_id).show();
		},
		success: function (data) {
			if (data.error) {
				$('.wishlist .alert').remove();
				$('.wishlist').after('<div class="alert alert-error alert-danger">' + dismiss + data.error + '</div>');
				$('#wishlist_add'+product_id).show();
			} else {
				$('.wishlist .alert').remove();
				$('#wishlist_remove'+product_id).show();
			}
		}
	});
}

function wishlist_remove(product_wishlist_remove_url, product_id) {
	var dismiss = '<button type="button" class="close" data-dismiss="alert">&times;</button>';
	$.ajax({
		type: 'POST',
		url: product_wishlist_remove_url,
		dataType: 'json',
		beforeSend: function () {
			$('.success, .warning').remove();
			$('#wishlist_remove'+product_id).hide();
			$('.wishlist').after('<div class="wait"><i class="fa fa-spinner fa-spin"></i> <?php echo $text_wait; ?></div>');
		},
		complete: function () {
			$('.wait').remove();
		},
		error: function (jqXHR, exception) {
			var text = jqXHR.statusText + ": " + jqXHR.responseText;
			$('.wishlist .alert').remove();
			$('.wishlist').after('<div class="alert alert-error alert-danger">' + dismiss + text + '</div>');
			$('#wishlist_remove'+product_id).show();
		},
		success: function (data) {
			if (data.error) {
				$('.wishlist .alert').remove();
				$('.wishlist').after('<div class="alert alert-error alert-danger">' + dismiss + data.error + '</div>');
				$('#wishlist_remove'+product_id).show();
			} else {
				$('.wishlist .alert').remove();
				//$('.wishlist').after('<div class="alert alert-success">' + dismiss + data.success + '</div>');
				$('#wishlist_add'+product_id).show();
			}
		}
	});
}
</script>