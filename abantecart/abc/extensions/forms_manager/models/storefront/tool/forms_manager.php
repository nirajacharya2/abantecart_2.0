<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2023 Belavier Commerce LLC
 *
 * This source file is subject to Open Software License (OSL 3.0)
 * License details is bundled with this package in the file LICENSE.txt.
 * It is also available at this URL:
 * <http://www.opensource.org/licenses/OSL-3.0>
 *
 * UPGRADE NOTE:
 * Do not edit or add to this file if you wish to upgrade AbanteCart to newer
 * versions in the future. If you wish to customize AbanteCart for your
 * needs please refer to http://www.abantecart.com for more information.
 */

namespace abc\extensions\forms_manager\models\storefront\tool;

use abc\core\engine\Model;
use H;

class ModelToolFormsManager extends Model
{
    public $error = [];

    public function getFormFullInfo($form_id)
    {
        $result = [];

        if ($form_id) {
            $result = $this->getForm($form_id);
            if (!empty($result)) {
                $result['fields'] = $this->getFields($form_id);
            }
        }

        return $result;
    }

    public function getForm($form_id)
    {

        if ($form_id) {
            $sql = 'SELECT f.*, fd.language_id, fd.description
				FROM ' . $this->db->table_name("forms") . ' f
				LEFT JOIN ' . $this->db->table_name("form_descriptions") . ' fd
				ON f.form_id = fd.form_id
				WHERE f.form_id = "' . (int)$form_id . '"
				AND fd.language_id = "' . (int)$this->config->get('storefront_language_id') . '"';

            $results = $this->db->query($sql);
            return $results->row;
        }
        return [];
    }

    public function getFields($form_id)
    {

        $fields = [];

        $query = $this->db->query("
            SELECT f.*, fd.name, fd.description
            FROM " . $this->db->table_name("fields") . " f
            LEFT JOIN " . $this->db->table_name("field_descriptions") . " fd
                    ON ( f.field_id = fd.field_id 
                            AND fd.language_id = '" . (int)$this->config->get('storefront_language_id') . "' )
            WHERE f.form_id = '" . (int)$form_id . "'
                AND f.status = 1
            ORDER BY f.sort_order"
        );

        if ($query->num_rows) {
            foreach ($query->rows as $row) {
                if (H::has_value($row['settings'])) {
                    $row['settings'] = unserialize($row['settings']);
                }

                $fields[$row['field_id']] = $row;
                $query = $this->db->query("
					SELECT *
					FROM " . $this->db->table_name("field_values") . "
					WHERE field_id = '" . $row['field_id'] . "'
						AND language_id = '" . (int)$this->config->get('storefront_language_id') . "'"
                );
                if ($query->num_rows) {
                    $fields[$row['field_id']]['values'] = $query->rows;
                }
            }
        }

        return $fields;
    }

    public function getRequiredFields($form_id)
    {

        $query = $this->db->query("
            SELECT field_id, field_name
            FROM " . $this->db->table_name("fields") . "
            	WHERE form_id = '" . (int)$form_id . "'
                AND status = 1
                AND required = 'Y'
            ORDER BY sort_order"
        );

        if ($query->num_rows) {
            return $query->rows;
        }
        return [];
    }

    public function getFieldTypes($form_id)
    {
        $query = $this->db->query(
            'SELECT field_id, field_name, element_type 
                FROM ' . $this->db->table_name("fields") . '
				WHERE form_id = "' . (int)$form_id . '"
				    AND status = 1
			    ORDER BY sort_order'
        );

        if ($query->num_rows) {
            return $query->rows;
        }

        return [];
    }

}