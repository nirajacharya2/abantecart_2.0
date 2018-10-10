<div id="content" style="margin: 0; padding: 0;">
	<div class="middle">
		<p><?php echo $description; ?></p>
	</div>
	<div class="middle" style="overflow: auto;">
		<table class="table">
            <?php
            foreach ($data as $table_name => $rows) {
                if (!$rows) {
                    continue;
                } ?>
				<tr style="background-color: lightgrey;">
					<th colspan="2"><?php echo strtoupper($table_name); ?></th>
				</tr>
                <?php
                echo '<tr><th>'.implode('</th><th>', array_keys($rows[0])).'</th></tr>';
                foreach ($rows as $row) {
                    echo '<tr><td>'.implode('</td><td>', $row).'</td></tr>';
                }
            } ?>
		</table>
	</div>
</div>
