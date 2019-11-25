<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $direction; ?>" <?php echo $this->getHookVar('hk_html_attribute'); ?>>
<head><?php echo $head; ?></head>
<body>

<!-- page loader -->
<div id="preloader">
	<div id="preloader_status"><i class="fa fa-spinner fa-spin"></i></div>
</div>
<!-- / page loader -->

<div class="wrapper">
	<div class="wrapper_c page_width" style="width: <?php echo $layout_width; ?>">
		<section>

            <?php echo $header; ?>

			<div class="contentpanel">
                <?php echo $content; ?>
			</div><!-- / contentpanel -->

            <?php echo $footer; ?>

		</section>
	</div><!-- / Container wrapper -->
</div><!-- /Page wrapper -->

<?php echo $this->getHookVar('hk_page_footer'); ?>

<?php
//NOTE: More JS loaded in header.tpl, including PHP processed JavaScript
?>

<a id="gotop" href="#"><?php echo $text_on_top; ?></a>

<script type="text/javascript" src="<?php echo $this->templateResource('assets/js/modernizr.min.js'); ?>"></script>
<script type="text/javascript" src="<?php echo $this->templateResource('assets/js/jquery/jquery.sparkline.min.js'); ?>"></script>
<script type="text/javascript" src="<?php echo $this->templateResource('assets/js/jquery/jquery.cookies.js'); ?>"></script>
<script type="text/javascript" src="<?php echo $this->templateResource('assets/js/jquery/chosen.jquery.js'); ?>"></script>
<script type="text/javascript" src="<?php echo $this->templateResource('assets/js/jquery/ajax-chosen.js'); ?>"></script>
<script type="text/javascript" src="<?php echo $this->templateResource('assets/js/bootstrap-growl.min.js'); ?>"></script>
<script type="text/javascript" src="<?php echo $this->templateResource('assets/js/fileinput.min.js'); ?>"></script>
<script type="text/javascript" src="<?php echo $this->templateResource('assets/js/general.js'); ?>"></script>
<script type="text/javascript" src="<?php echo $this->templateResource('assets/js/intl-tel-input/js/intlTelInput.min.js'); ?>"></script>
<?php
if ($scripts_bottom && is_array($scripts_bottom)) {
    foreach ($scripts_bottom as $script) {
        ?>
		<script type="text/javascript" src="<?php echo $script; ?>" defer></script>
        <?php
    }
} ?>

</body>
</html>