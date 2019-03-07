<script type="text/javascript" src="<?php echo $this->templateResource('assets/js/easyzoom.js'); ?>" defer></script>
<script type="text/javascript" src="<?php echo $this->templateResource('assets/js/jquery.validate.js'); ?>" defer></script>
<script type="text/javascript" src="<?php echo $this->templateResource('assets/js/custom.response.js'); ?>" defer></script>

<?php if ($google_analytics) {
	$ga_data = $this->registry->get('google_analytics_data');
	?>
	<script type="text/javascript">

		var _gaq = _gaq || [];
		_gaq.push(['_setAccount', '<?php echo $google_analytics;?>']);
		_gaq.push(['_trackPageview']);

		<?php if($ga_data){?>
		_gaq.push(['_set', 'currencyCode', '<?php echo $ga_data['currency_code'];?>']);
		_gaq.push(['_addTrans',
			<?php abc_js_echo($ga_data['transaction_id']);?>,
			<?php abc_js_echo($ga_data['store_name']);?>,
			<?php abc_js_echo($ga_data['total']);?>,
			<?php abc_js_echo($ga_data['tax']);?>,
			<?php abc_js_echo($ga_data['shipping']);?>,
			<?php abc_js_echo($ga_data['city']);?>,
			<?php abc_js_echo($ga_data['state']);?>,
			<?php abc_js_echo($ga_data['country']);?>
		]);
		_gaq.push(['_trackTrans']);
		<?php }?>

		(function () {
			var ga = document.createElement('script');
			ga.type = 'text/javascript';
			ga.async = true;
			ga.src = ('https:' === document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
			var s = document.getElementsByTagName('script')[0];
			s.parentNode.insertBefore(ga, s);
		})();

	</script>

<?php } ?>

<?php foreach ($scripts_bottom as $script) { ?>
	<script type="text/javascript" src="<?php echo $script; ?>" defer></script>
<?php } ?>
</body>
</html>