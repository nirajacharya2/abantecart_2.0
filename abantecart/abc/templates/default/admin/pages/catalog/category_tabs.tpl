
<ul class="nav nav-tabs nav-justified nav-profile">
	<?php foreach($groups as $group){?>
		<li class="nav-item" >
			<a class="nav-link <?php echo ( $active == $group ? 'active' : '' ) ?>" href="<?php echo ${'link_'.$group}; ?>"><span><?php echo ${'tab_'.$group}; ?></span></a></li>
	<?php } ?>
	<?php echo $this->getHookVar('extension_tabs'); ?>
</ul>
