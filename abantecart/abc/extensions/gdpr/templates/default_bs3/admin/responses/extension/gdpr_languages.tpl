<?php
/**
 * @var AController $this
 */

use abc\core\engine\AController;
use abc\core\engine\ALanguage;
use abc\core\engine\Registry;

$registry = Registry::getInstance();
$registry->get('load')->model('localisation/language_definitions');
//load storefront side language
$language = new ALanguage($registry, 'en', 0);
$language->load('gdpr/gdpr');

$this->load->model('localisation/language_definitions');
$filter_data = [
    'language_id'   => 1,
    'filter'        => [
        'section' => 'storefront',
    ],
    'sort'          => 'language_key',
    'order'         => 'ASC',
    'subsql_filter' => "LOWER(`block`) = 'gdpr_gdpr' ",
];
$definitions = $this->model_localisation_language_definitions->getLanguageDefinitions($filter_data);

?>
<div class="table-responsive" style="max-height: 500px; overflow: auto;">
	<table class="table table-striped table-bordered table-hover">
        <?php
        foreach ($definitions as $definition) {
            if (str_replace('gdpr_', '', $definition['language_key']) == '') {
                continue;
            }
            $a = '<a
			title="'.$text_edit.'"
			data-toggle="modal"
			data-target="#message_modal"
			href="'.$this->html->getSecureURL(
                    'localisation/language_definition_form/update',
                    '&language_definition_id='.$definition['language_definition_id']).'">';
            ?>
			<tr id="lang_def_<?php echo $definition['language_definition_id']; ?>">
				<td class="text-left"><?php echo $a.str_replace('gdpr_', '', $definition['language_key']); ?></a></td>
				<td class="text-left"><?php echo $a.$definition['language_value']; ?></a></td>
				<td class="text-center"><?php echo $a; ?><i class="fa fa-pencil"></i></a></td>
			</tr>
        <?php } ?>
	</table>
</div>
