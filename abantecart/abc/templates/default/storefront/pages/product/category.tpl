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

$('.wishlist_change').on('click', function () {
	product_id = $(this).attr('data-product-id');
	in_wishlist = $(this).attr('data-in-wishlist');
	item = $(this);
	var url = '';
	if (in_wishlist == 1) {
		url = $(this).attr('data-remove-url');;
	} else {
		url = $(this).attr('data-add-url');
	}

	var dismiss = '<button type="button" class="close" data-dismiss="alert">&times;</button>';
	$.ajax({
		type: 'POST',
		url: url,
		dataType: 'json',
		beforeSend: function () {
			$('.success, .warning').remove();
		//	$(this).hide();
			$('.wishlist').after('<div class="wait"><i class="fa fa-spinner fa-spin"></i> <?php echo $text_wait; ?></div>');
		},
		complete: function () {
			$('.wait').remove();
		},
		error: function (jqXHR, exception) {
			var text = jqXHR.statusText + ": " + jqXHR.responseText;
			$('.wishlist .alert').remove();
			$('.wishlist').after('<div class="alert alert-error alert-danger">' + dismiss + text + '</div>');
			$(this).show();
		},
		success: function (data) {
			if (data.error) {
				$('.wishlist .alert').remove();
				$('.wishlist').after('<div class="alert alert-error alert-danger">' + dismiss + data.error + '</div>');
				$(this).show();
			} else {
				$('.wishlist .alert').remove();
				if (in_wishlist == 1) {
					item.attr('data-in-wishlist', 0);
					item.html('<i class="fa fa-heart-o fa-fw"></i>');
				} else {
					item.attr('data-in-wishlist', 1);
					item.html('<i class="fa fa-heart fa-fw"></i>');
				}
			}
		}
	});
	event.preventDefault();
});



</script>