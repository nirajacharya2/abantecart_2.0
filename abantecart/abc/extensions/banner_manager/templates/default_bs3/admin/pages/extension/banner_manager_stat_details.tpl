<?php
include($tpl_common_dir . 'action_confirm.tpl'); ?>
<div id="content" class="panel panel-default">
    <div class="panel-heading col-xs-12">
        <div class="primary_content_actions pull-left">
            <div class="btn-group mr10 toolbar">
                <a class="btn btn-primary actionitem tooltips"
                   title="<?php echo $delete_button->title; ?>"
                   href="<?php echo $delete_button->href; ?>"
                   data-confirmation="delete"
                ><i class="fa fa-trash fa-fw"></i> <?php echo $delete_button->title; ?></a>
            </div>
            <div class="btn-group mr10 toolbar">
                <?php echo $select_range; ?>
            </div>
        </div>
        <?php include($tpl_common_dir . 'content_buttons.tpl'); ?>
    </div>

    <div class="panel-body panel-body-nopadding tab-content col-xs-12">
        <div id="report" style="width: 700px; height: 480px; margin: auto;"></div>
    </div>
</div>

<?php include($tpl_common_dir . 'action_confirm.tpl'); ?>

<script type="text/javascript"
        src="<?php echo $this->templateResource('vendor/components/chart/Chart.min.js'); ?>"></script>

<script type="text/javascript">
    function getSalesChart(range) {
        $.ajax({
            type: 'GET',
            url: '<?php echo $chart_url; ?>&range=' + range,
            dataType: 'json',
            async: false,
            success: function (json) {
                const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
                const weekdayNames = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
                $("#report").html('<canvas id="statisticsChart"></canvas>');

                var viewed = json.viewed;
                var viewed_labels = [];
                var viewed_values = [];

                for (var k in viewed.data) {
                    if (viewed.data.length == 12) {
                        viewed_labels.push(monthNames[k]);
                    } else if (viewed.data.length == 7) {
                        viewed_labels.push(weekdayNames[k]);
                    } else {
                        viewed_labels.push(viewed.data[k][0]);
                    }
                    viewed_values.push(viewed.data[k][1]);
                }

                var clicked = json.clicked;
                var clicked_labels = [];
                var clicked_values = [];

                for (var k in clicked.data) {
                    if (clicked.data.length == 12) {
                        clicked_labels.push(monthNames[k]);
                    } else if (clicked.data.length == 7) {
                        clicked_labels.push(weekdayNames[k]);
                    } else {
                        clicked_labels.push(clicked.data[k][0]);
                    }
                    clicked_labels.push(clicked.data[k][0]);
                    clicked_values.push(clicked.data[k][1]);
                }

                var ctx = document.getElementById('statisticsChart').getContext('2d');
                var chart = new Chart(ctx, {
                    // The type of chart we want to create
                    type: 'line',

                    // The data for our dataset
                    data: {
                        labels: viewed_labels,
                        datasets: [{
                            label: viewed.label,
                            backgroundColor: '#1CAF9A',
                            borderColor: '#1CAF9A',
                            data: viewed_values,
                        },
                            {
                                label: clicked.label,
                                backgroundColor: '#428BCA',
                                borderColor: '#428BCA',
                                data: clicked_values,
                            },
                        ]
                    },

                });
            }
        });
    }

    getSalesChart($('#range').val());

    $('#range').change(function () {
        getSalesChart($(this).val());
    });
</script>
