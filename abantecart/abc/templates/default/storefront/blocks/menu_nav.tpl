<ul id="menu_nav_<?php echo $instance_id; ?>" class="menu_nav_<?php echo $instance_id; ?>">
	<?php
		    //NOTE:
		    //HTML tree builded in helper/html.php
		    //To controll look and style of the menu use CSS in styles.css
		?>
	<?php echo \abc\core\helper\AHelperHtml::buildStoreFrontMenuTree( $storemenu ); ?>
</ul>