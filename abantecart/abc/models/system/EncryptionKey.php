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
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class EncryptionKey
 *
 * @property int $key_id
 * @property string $key_name
 * @property int $status
 * @property string $comment
 *
 * @package abc\models
 */
class EncryptionKey extends BaseModel
{
    use SoftDeletes;
    protected $primaryKey = 'key_id';
    public $timestamps = false;

    protected $casts = [
        'status' => 'int',
    ];

    protected $fillable = [
        'key_name',
        'status',
        'comment',
    ];
}
