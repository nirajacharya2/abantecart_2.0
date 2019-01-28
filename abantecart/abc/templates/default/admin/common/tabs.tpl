
<ul class="nav nav-tabs nav-justified nav-profile">
	<?php foreach($groups as $group=>$item){?>
		<li class="nav-item <?php echo ( $active == $group ? 'active' : '' ) ?>">
			<a class="nav-link" href="<?php echo $item['href']; ?>"><span><?php echo $item['text']; ?></span></a>
		</li>
	<?php } ?>
	<?php echo $this->getHookVar('extension_tabs'); ?>
</ul>
