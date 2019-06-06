<?php use abc\core\ABC; ?>
<header>
	<div class="headerstrip navbar navbar-inverse p-0" role="navigation">
		<div class="container-fluid">
			<nav class="navbar navbar-expand-md navbar-light col-md-12 p-0">
      			<div class="navbar-header1 header-logo1">
        			<button class="navbar-toggler collapsed" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
          				<span class="navbar-toggler-icon"></span>
        			</button>
		 			<?php if (is_file(ABC::env('DIR_RESOURCES') . $logo)) { ?>
						<a class="logo" href="<?php echo $homepage; ?>">
							<img src="resources/<?php echo $logo; ?>" width="<?php echo $logo_width; ?>" height="<?php echo $logo_height; ?>" title="<?php echo $store; ?>" alt="<?php echo $store; ?>"/>
						</a>
					<?php } else if (!empty($logo)) { ?>
	    				<a class="logo" href="<?php echo $homepage; ?>"><?php echo $logo; ?></a>
	    			<?php } ?>	
				</div>		
        		<div class="navbar-collapse collapse" id="navbarCollapse" style="">
					<div class="navbar-right1 headerstrip_blocks ml-auto">
						<div class="block_1 top-blk"><?php echo ${$children_blocks[0]}; ?></div>
						<div class="block_2 top-blk login_link"><?php echo ${$children_blocks[1]}; ?></div>
						<div class="block_3 top-blk top_link"><?php echo ${$children_blocks[2]}; ?></div>
						<!-- header blocks placeholder -->
						<div class="block_5 top-blk"><?php echo ${$children_blocks[4]}; ?></div>			
						<div class="block_6 top-blk blk-currency"><?php echo ${$children_blocks[5]}; ?></div>
						<div class="block_7 top-blk blk-lang"><?php echo ${$children_blocks[6]}; ?></div>
						<div class="block_8 top-blk blk-social"><?php echo ${$children_blocks[7]}; ?></div>
						<!-- header blocks placeholder (EOF) -->
						<div class="block_4 top-blk search_block"><?php echo ${$children_blocks[3]}; ?></div>
					
					</div>
        		</div>
      		</nav>
	<!--<nav class="navbar navbar-toggleable-md navbar-inverse bg-inverse">
	
	
	
	 <div class="navbar-header1 header-logo1">
      <button class="navbar-toggler navbar-toggler-right" type="button" data-toggle="collapse" data-target="#navbarsExampleDefault" aria-controls="navbarsExampleDefault" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      
</div>




      <div class="collapse navbar-collapse" id="navbarsExampleDefault">
        
      </div>
    </nav>-->
	</div>
</div>
</header>