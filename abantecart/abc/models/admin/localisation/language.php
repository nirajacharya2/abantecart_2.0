<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2017 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  License details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>

 UPGRADE NOTE:
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.AbanteCart.com for more information.
------------------------------------------------------------------------------*/

namespace abc\models\admin;

use abc\core\ABC;
use abc\core\engine\Model;
use abc\core\lib\AException;
use abc\core\lib\AMenu_Storefront;
use abc\core\lib\ATaskManager;

class ModelLocalisationLanguage extends Model
{
    public $errors = [];

    /**
     * @param $data
     *
     * @return int
     * @throws AException
     */
    public function addLanguage($data)
    {
        $this->db->query("INSERT INTO ".$this->db->table_name("languages")." 
                            SET name = '".$this->db->escape($data['name'])."',
                                CODE = '".$this->db->escape($data['code'])."',
                                locale = '".$this->db->escape($data['locale'])."',
                                DIRECTORY = '".$this->db->escape($data['directory'])."',
                                filename = '".$this->db->escape($data['directory'])."',
                                sort_order = '".$this->db->escape($data['sort_order'])."',
                                STATUS = '".(int)$data['status']."'");

        $this->cache->flush('localization');

        $language_id = $this->db->getLastId();

        //add menu items for new language
        $menu = new AMenu_Storefront();
        $menu->addLanguage((int)$language_id);

        //language data is copied/translated in a separate process.
        return $language_id;
    }

    /**
     * @param int $language_id
     * @param array $data
     *
     * @throws \Exception
     */
    public function editLanguage($language_id, $data)
    {
        $update_data = [];
        foreach ($data as $key => $val) {
            $update_data[] = "`$key` = '".$this->db->escape($val)."' ";
        }
        $this->db->query("UPDATE ".$this->db->table_name("languages")." SET ".implode(',',
                $update_data)." WHERE language_id = '".(int)$language_id."'");

        $this->cache->flush('localization');
    }

    /**
     * @param int $language_id
     *
     * @throws AException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function deleteLanguage($language_id)
    {
        $this->db->query("DELETE FROM ".$this->db->table_name("languages")." WHERE language_id = '".(int)$language_id."'");

        $this->language->deleteAllLanguageEntries($language_id);

        //too many changes and better clear all cache
        $this->cache->flush('*');

        //delete menu items for given language
        $menu = new AMenu_Storefront();
        $menu->deleteLanguage((int)$language_id);
    }

    /**
     * @param int $language_id
     *
     * @return array
     * @throws \Exception
     */
    public function getLanguage($language_id)
    {
        $query = $this->db->query("SELECT DISTINCT * FROM ".$this->db->table_name("languages")." WHERE language_id = '".(int)$language_id."'");
        $result = $query->row;
        if ( ! $result['image']) {
            if (file_exists(ABC::env('DIR_ROOT').'/admin/languages/'.$result['directory'].'/flag.png')) {
                $result['image'] = ABC::env('HTTP_ABANTECART').'admin/languages/'.$result['directory'].'/flag.png';
            }
        } else {
            $result['image'] = ABC::env('HTTP_ABANTECART').$result['image'];
        }

        return $query->row;
    }

    /**
     * @param array $data
     * @param string $mode
     *
     * @return int|array
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getLanguages($data = [], $mode = 'default')
    {
        if ($data || $mode == 'total_only') {
            $filter = (isset($data['filter']) ? $data['filter'] : []);
            if ($mode == 'total_only') {
                $sql = "SELECT count(*) AS total FROM ".$this->db->table_name("languages")." ";
            } else {
                $sql = "SELECT * FROM ".$this->db->table_name("languages")." ";
            }

            if (isset($filter['status']) && ! is_null($filter['status'])) {
                $sql .= " WHERE `status` = '".$this->db->escape($filter['status'])."' ";
            } else {
                $sql .= " WHERE `status` like '%' ";
            }

            if (isset($filter['name']) && ! is_null($filter['name'])) {
                $sql .= " AND `name` LIKE '%".$this->db->escape($filter['name'], true)."%' ";
            }

            if ( ! empty($data['subsql_filter'])) {
                $sql .= " AND ".$data['subsql_filter'];
            }

            //If for total, we done building the query
            if ($mode == 'total_only') {
                $query = $this->db->query($sql);

                return (int)$query->row['total'];
            }

            $sort_data = [
                'name',
                'code',
                'sort_order',
            ];

            if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
                $sql .= " ORDER BY ".$data['sort'];
            } else {
                $sql .= " ORDER BY sort_order, name";
            }

            if (isset($data['order']) && (strtoupper($data['order']) == 'DESC')) {
                $sql .= " DESC";
            } else {
                $sql .= " ASC";
            }

            if (isset($data['start']) || isset($data['limit'])) {
                if ($data['start'] < 0) {
                    $data['start'] = 0;
                }

                if ($data['limit'] < 1) {
                    $data['limit'] = 20;
                }

                $sql .= " LIMIT ".(int)$data['start'].",".(int)$data['limit'];
            }

            $query = $this->db->query($sql);
            $result = $query->rows;
            foreach ($result as $i => $row) {
                if (empty($row['image'])) {
                    if (file_exists(ABC::env('DIR_ROOT').'/public/templates/'.(ABC::env('adminTemplate') ? ABC::env('adminTemplate') : 'default' ).'/admin/assets/images/languages/'.$result['directory'].'/flag.png')) {
                        $result[$i]['image'] = 'templates/'.(ABC::env('adminTemplate') ? ABC::env('adminTemplate') : 'default' ).'/admin/assets/images/languages/'.$result['directory'].'/flag.png';
                    }
                } else {
                    $result[$i]['image'] = $row['image'];
                }
            }

            return $result;
        } else {
            $language_data = $this->cache->get('localization.language.admin');

            if ($language_data === null) {
                $query = $this->db->query("SELECT *
                                            FROM ".$this->db->table_name("languages")." 
                                            ORDER BY sort_order, name");

                foreach ($query->rows as $result) {
                    if (empty($result['image'])) {
                        if (file_exists(ABC::env('DIR_ROOT').'/public/templates/'.(ABC::env('adminTemplate') ? ABC::env('adminTemplate') : 'default' ).'/admin/assets/images/languages/'.$result['directory'].'/flag.png')) {
                            $result['image'] = 'templates/'.(ABC::env('adminTemplate') ? ABC::env('adminTemplate') : 'default' ).'/admin/assets/images/languages/'.$result['directory'].'/flag.png';
                        }
                    }

                    $language_data[$result['code']] = [
                        'language_id' => $result['language_id'],
                        'name'        => $result['name'],
                        'code'        => $result['code'],
                        'locale'      => $result['locale'],
                        'image'       => $result['image'],
                        'directory'   => $result['directory'],
                        'filename'    => $result['filename'],
                        'sort_order'  => $result['sort_order'],
                        'status'      => $result['status'],
                    ];
                }
                $this->cache->put('localization.language.admin', $language_data);
            }

            return $language_data;
        }
    }

    /**
     * @param array $data
     *
     * @return array|int
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getTotalLanguages($data = [])
    {
        return $this->getLanguages($data, 'total_only');
    }

    /**
     * @param string $task_name
     * @param array $data
     *
     * @return array|bool
     * @throws \Exception
     */
    public function createTask($task_name, $data = [])
    {

        if ( ! $task_name) {
            $this->errors[] = 'Can not to create task. Empty task name has been given.';
        }

        //get URIs of recipients
        $tables = $this->_get_tables_info($data['source_language']);
        $task_controller = 'task/localisation/language/translate';

        if ( ! $tables) {
            $this->errors[] = 'No tables info!';

            return false;
        }

        $total_desc_count = 0;
        foreach ($tables as $table_name => $table) {
            $total_desc_count += $table['description_count'];
        }

        //numbers of translations per task step
        $divider = 30;
        //timeout in seconds for one item translation
        $time_per_item = 4;
        $tm = new ATaskManager();

        //create new task
        $task_id = $tm->addTask(
            [
                'name'               => $task_name,
                'starter'            => 1, //admin-side is starter
                'created_by'         => $this->user->getId(), //get starter id
                'status'             => $tm::STATUS_READY,
                'start_time'         => date('Y-m-d H:i:s',
                    mktime(0, 0, 0, date('m'), date('d') + 1, date('Y'))),
                'last_time_run'      => '0000-00-00 00:00:00',
                'progress'           => '0',
                'last_result'        => '1', // think all fine until some failed step will set 0 here
                'run_interval'       => '0',
                //think that task will execute with some connection errors
                'max_execution_time' => ($total_desc_count * $time_per_item * 2),
            ]
        );
        if ( ! $task_id) {
            $this->errors = array_merge($this->errors, $tm->errors);

            return false;
        }

        $tm->updateTaskDetails($task_id,
            [
                'created_by' => $this->user->getId(),
                'settings'   => [
                    'descriptions_count' => $total_desc_count,
                ],
            ]
        );

        //create steps
        $sort_order = 1;
        $eta = [];
        foreach ($tables as $table_name => $info) {

            if ( ! $info['primary_keys']) {
                continue;
            }

            $settings = [];
            //get all indexes of descriptions of the table
            $sql = "SELECT ".implode(', ', $info['primary_keys'])."
                    FROM ".$table_name."
                    WHERE language_id = ".$data['source_language'];
            $result = $this->db->query($sql);

            if ($divider >= $info['description_count']) {
                $items = [];
                foreach ($result->rows as $row) {
                    foreach ($row as $k => $v) {
                        $items[$k][] = $v;
                    }
                }

                $settings[0] = [
                    'src_language_id'  => $data['source_language'],
                    'language_id'      => $data['language_id'],
                    'translate_method' => $data['translate_method'],
                    'table'            =>
                        [
                            'table_name'  => $table_name,
                            'items_count' => $info['description_count'],
                            'indexes'     => $items,
                        ],
                ];
            } else {
                $slices = array_chunk($result->rows, $divider);

                foreach ($slices as $slice) {
                    $items = [];
                    foreach ($slice as $row) {
                        foreach ($row as $k => $v) {
                            $items[$k][] = $v;
                        }
                    }
                    $settings[] = [
                        'src_language_id'  => $data['source_language'],
                        'language_id'      => $data['language_id'],
                        'translate_method' => $data['translate_method'],
                        'table'            =>
                            [
                                'table_name'  => $table_name,
                                'items_count' => sizeof($slice),
                                'indexes'     => $items,
                            ],
                    ];
                }
            }

            foreach ($settings as $s) {
                $step_id = $tm->addStep([
                    'task_id'            => $task_id,
                    'sort_order'         => $sort_order,
                    'status'             => 1,
                    'last_time_run'      => '0000-00-00 00:00:00',
                    'last_result'        => '0',
                    //think that task will execute with some connection errors
                    'max_execution_time' => ($time_per_item * $divider * 2),
                    'controller'         => $task_controller,
                    'settings'           => $s,
                ]);
                $eta[$step_id] = $time_per_item * $divider * 2;
                $sort_order++;
            }
        }

        $task_details = $tm->getTaskById($task_id);

        if ($task_details) {
            foreach ($eta as $step_id => $estimate) {
                $task_details['steps'][$step_id]['eta'] = $estimate;
                //remove settings from output json array. We will take it from database on execution.
                unset($task_details['steps'][$step_id]['settings']);
            }

            return $task_details;
        } else {
            $this->errors[] = 'Can not to get task details for execution';
            $this->errors = array_merge($this->errors, $tm->errors);

            return false;
        }

    }

    protected function _get_tables_info($src_language_id = 0)
    {
        $output = [];
        $lang_tables = $this->language->getLanguageBasedTables();
        if ( ! $lang_tables) {
            return false;
        }

        $src_language_id = (int)$src_language_id;
        if ( ! $src_language_id) {
            return false;
        }
        $excludes = [
            $this->db->table_name('languages'),
            $this->db->table_name('language_definitions'),
            $this->db->table_name('orders'),
        ];
        foreach ($lang_tables as $table) {
            $table_name = $table['table_name'];
            if (in_array($table_name, $excludes)) {
                continue;
            }

            $sql = "SELECT COUNT(*) AS cnt
                    FROM ".$table_name."
                    WHERE language_id = ".$src_language_id;
            $result = $this->db->query($sql);
            $row_cnt = (int)$result->row['cnt'];
            if ($row_cnt) {
                $pkeys = $this->language->getPrimaryKeys($table_name);
                $lpk = array_search('language_id', $pkeys);
                if (is_int($lpk)) {
                    unset($pkeys[$lpk]);
                }

                $output[$table_name]['primary_keys'] = $pkeys;
                $output[$table_name]['fields'] = $this->language->getTranslatableFields($table_name);
                $output[$table_name]['row_count'] = $result->row['cnt'];
                $output[$table_name]['description_count'] = (int)$result->row['cnt'] * (int)sizeof($output[$table_name]['fields']);
            }
        }

        return $output;
    }

}
