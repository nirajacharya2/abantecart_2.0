<?php
/**
 * AbanteCart, Ideal Open Source Ecommerce Solution
 * http://www.abantecart.com
 *
 * Copyright 2011-2022 Belavier Commerce LLC
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
namespace abc\models\user;

use abc\models\BaseModel;
use abc\core\lib\AException;
use Carbon\Carbon;

/**
 * Class AntMessage
 *
 * @property string         $id
 * @property int            $priority
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property Carbon $viewed_date
 * @property int            $viewed
 * @property string         $title
 * @property string         $description
 * @property string         $html
 * @property string         $url
 * @property string         $language_code
 * @property Carbon $date_modified
 *
 * @package abc\models
 */
class AntMessage extends BaseModel
{
    public $primaryKey = 'id';
    public $timestamps = false;

    protected $casts = [
        'priority'      => 'int',
        'viewed'        => 'int',
        'start_date'    => 'datetime',
        'end_date'      => 'datetime',
        'viewed_date'   => 'datetime',
        'date_modified' => 'datetime'
    ];

    protected $dates = [
        'start_date',
        'end_date',
        'viewed_date',
        'date_modified',
    ];

    protected $fillable = [
        'priority',
        'start_date',
        'end_date',
        'viewed_date',
        'viewed',
        'title',
        'description',
        'html',
        'url',
        'date_modified',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        if (!$this->isUser()) {
            throw new AException ('Error: permission denied to access '.__CLASS__, AC_ERR_LOAD);
        }
    }
}
