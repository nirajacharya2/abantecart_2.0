<ul class="nav nav-tabs nav-justified nav-profile">
	<li class="nav-item" ><a class="nav-link <?php echo ( $active == 'general' ? 'active' : '' ) ?>" href="<?php echo $link_general; ?>"><strong><?php echo $tab_general; ?></strong></a></li>
	<li class="nav-item" ><a class="nav-link <?php echo ( $active == 'images' ? 'active' : '' ) ?>" href="<?php echo $link_images; ?>"><span><?php echo $tab_media; ?></span></a></li>
	<li class="nav-item" ><a class="nav-link <?php echo ( $active == 'options' ? 'active' : '' ) ?>" href="<?php echo $link_options; ?>"><span><?php echo $tab_option; ?></span></a></li>
	<li class="nav-item" ><a class="nav-link <?php echo ( $active == 'files' ? 'active' : '' ) ?>" href="<?php echo $link_files; ?>"><span><?php echo $tab_files; ?></span></a>
	<li class="nav-item" ><a class="nav-link <?php echo ( $active == 'relations' ? 'active' : '' ) ?>" href="<?php echo $link_relations; ?>"><span><?php echo $tab_relations; ?></span></a></li>
	<li class="nav-item" ><a class="nav-link <?php echo ( $active == 'promotions' ? 'active' : '' ) ?>" href="<?php echo $link_promotions; ?>"><span><?php echo $tab_promotions; ?></span></a></li>
	<li class="nav-item" ><a class="nav-link <?php echo ( $active == 'layout' ? 'active' : '' ) ?>" href="<?php echo $link_layout; ?>"><span><?php echo $tab_layout; ?></span></a></li>
	<?php echo $this->getHookVar('extension_tabs'); ?>
</ul>
