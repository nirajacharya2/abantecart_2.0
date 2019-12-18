<?php

namespace abc\models\system;

use abc\core\engine\Registry;
use abc\models\BaseModel;
use H;
use Illuminate\Validation\Rule;

class EmailTemplate extends BaseModel
{
    protected $fillable = [
        'text_id', //Unique text identifier
        'status',
        'headers',
        'subject',
        'html_body',
        'text_body',
        'language_id',
        'allowed_placeholders',
    ];

    protected $rules = [
        'text_id'     => [
            'checks'   => [
                'string',
                'sometimes',
                'required',
                'max:254',
                'regex:/(^[\w\d]+)$/i',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is required and must be up to 254 characters! One word. Charsets: A-Z a-z and _ ',
                ],
            ],
        ],
        'headers'     => [
            'checks'   => [
                'string',
                'max:254',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute must be up to 254 characters!',
                ],
            ],
        ],
        'subject'     => [
            'checks'   => [
                'string',
                'required',
                'max:254',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is required and must be up to 254 characters!',
                ],
            ],
        ],
        'html_body'   => [
            'checks'   => [
                'required',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is required!',
                ],
            ],
        ],
        'text_body'   => [
            'checks'   => [
                'required',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is required!',
                ],
            ],
        ],
        'language_id' => [
            'checks'   => [
                'integer',
                'sometimes',
                'required',
                'min:1',
            ],
            'messages' => [
                '*' => [
                    'default_text' => ':attribute is required!',
                ],
            ],
        ],
    ];

    public static function getEmailTemplates(array $data)
    {
        $db = Registry::db();
        $arSelect = [
            $db->raw($db->raw_sql_row_count().' '.$db->table_name('email_templates').'.id'),
            'email_templates.status',
            'email_templates.language_id',
            'email_templates.text_id',
            'email_templates.headers',
            'email_templates.subject',
            'languages.name',
        ];

        $query = self::select($arSelect);

        $query->leftJoin('languages', 'languages.language_id', '=', 'email_templates.language_id');

        $limit = 20;
        if (H::has_value($data['rows']) && (int)$data['rows'] <= 50) {
            $limit = (int)$data['rows'];
        }

        $page = H::has_value($data['page']) ? (int)$data['page'] : 1;
        $start = $page * $limit - $limit;

        $query->limit($limit)->offset($start);

        $allowedSortFields = [
            'text_id'  => 'email_templates.text_id',
            'language' => 'languages.name',
            'subject'  => 'email_templates.subject',
            'status'   => 'email_templates.status',
        ];

        if (H::has_value($data['sidx']) && H::has_value($data['sord']) && $allowedSortFields[$data['sidx']]) {
            $query->orderBy($allowedSortFields[$data['sidx']], $data['sord']);
        }

        $allowedSearchFields = [
            'text_id'  => 'email_templates.text_id',
            'language' => 'languages.name',
            'subject'  => 'email_templates.subject',
        ];

        if (isset($data['_search']) && $data['_search'] == 'true') {
            $filters = json_decode($data['filters'], true);
            foreach ((array)$filters['rules'] as $filter) {
                if (!$allowedSearchFields[$filter['field']]) {
                    continue;
                }
                $query->where($allowedSearchFields[$filter['field']], 'LIKE', '%'.$filter['data'].'%');
            }
        }

        Registry::extensions()->hk_extendQuery(new static, __FUNCTION__, $query, func_get_args());
        $result = $query->get();
        if ($result) {
            return [
                'items' => $result->toArray(),
                'total' => $db->sql_get_row_count(),
                'page'  => $page,
                'limit' => $limit,
            ];
        }
        return [
            'items' => [],
            'total' => 0,
            'page'  => $page,
            'limit' => $limit,
        ];
    }

}