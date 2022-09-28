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
namespace abc\models\system;

use abc\models\BaseModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Message
 *
 * @property int $msg_id
 * @property string $title
 * @property string $message
 * @property string $status
 * @property int $viewed
 * @property int $repeated
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @package abc\models
 */
class Message extends BaseModel
{
    use SoftDeletes;

    protected $primaryKey = 'msg_id';
    public $timestamps = false;

    protected $casts = [
        'viewed'        => 'int',
        'repeated'      => 'int',
        'date_added'    => 'datetime',
        'date_modified' => 'datetime'
    ];

    protected $fillable = [
        'title',
        'message',
        'status',
        'viewed',
        'repeated',
        'date_added',
        'date_modified',
    ];
}
