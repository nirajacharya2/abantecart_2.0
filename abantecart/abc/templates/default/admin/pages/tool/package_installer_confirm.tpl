<?php include( $tpl_common_dir.'action_confirm.tpl' ); ?>

<div class="tab-content">


	<?php echo $form['form_open'].( is_array( $form['hidden'] ) ? implode( '', $form['hidden'] ) : $form['hidden'] ); ?>
	<div class="panel-body panel-body-nopadding">
		<label class="h4 heading"><?php echo $heading_title; ?></label>
		<div >
			<div class="col-sm-7 col-sm-offset-3">
				<?php
				if ( $check_results['critical'] ) { ?>
					<div class="warning alert alert-error alert-danger"><i class="fa fa-exclamation-triangle fa-fw"></i><br>
						<?php
							foreach($check_results['critical'] as $msgs){
								echo implode( "<br>", $msgs )."<br>";
							}
					  ?>
					</div>
				<?php } else {
					if ( $check_results['messages'] ) { ?>
						<div class="info alert alert-info"><i class="fa fa fa-info-circle fa-fw"></i>
							<div><?php
								foreach($check_results['messages'] as $msgs){
									echo implode( "<br>", $msgs )."<br>";
								}
						  ?></div>
						</div>
					<?php }
					if ( $check_results['warnings'] ) { ?>
						<div class="info alert alert-warning"><i class="fa fa fa-exclamation-triangle fa-fw"></i>
							<div><?php
									foreach($check_results['warnings'] as $msgs){
										echo implode( "<br>", $msgs )."<br>";
									}
							?></div>
						</div>
					<?php
					}
					if( $license_text) { ?>
						<div class="text-uppercase mb20" ><?php echo $text_license_agreement;?></div>
						<div class="col-sm-12" class="pre-scrollable mb20" style="height: 400px; overflow-y: auto"><?php echo $license_text; ?></div>
					<?php
					}
					if( $release_notes) { ?>
						<div class="mt10 text-uppercase mb20" ><?php echo $text_release_notes;?></div>
						<div class="col-sm-12" class="pre-scrollable" style="height: 400px; overflow-y: auto"><?php echo $release_notes; ?></div>
					<?php
					}
				}
				?>
			</div>
		</div>
	</div>
	<?php
	if ( ! $check_results['critical'] ) { ?>
		<div class="panel-footer">
			<div class="text-center">
				<button id="agree_btn" class="btn btn-primary">
					<i class="fa fa-check"></i> <?php echo $form['submit']->text; ?>
				</button>
				<?php echo $form['nbg']; ?>
				&nbsp;
				<a class="btn btn-default" href="<?php echo $form['disagree_button']->href; ?>"><?php echo $button_cancel; ?></a>
			</div>
		</div>
	<?php } ?>
	</form>

</div><!-- <div class="tab-content"> -->

<script type="text/javascript">
	$('#agree_btn').click(function () {
		$('#Frm_<?php echo $form['agree']->name?>').val(1);
	});
</script>