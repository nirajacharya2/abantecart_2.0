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

/**
 * Class Audit
 *
 * @property string $user_type
 * @property int $user_id
 * @property string $user_name
 * @property int $alias_id
 * @property string $alias_name
 * @property string $event
 * @property string $request_id
 * @property string $session_id
 * @property string $main_auditable_model
 * @property int $main_auditable_id
 * @property string $auditable_model
 * @property int $auditable_id
 * @property string $old_value
 * @property string $new_value
 *
 *
 * @package abc\models
 */
class Audit extends BaseModel
{
    protected $primaryKey = 'id';
        public $timestamps = false;

    protected $fillable = [
        'user_type',
        'user_id',
        'user_name',
        'alias_id',
        'alias_name',
        'event',
        'request_id',
        'session_id',
        'main_auditable_model',
        'main_auditable_id',
        'auditable_model',
        'auditable_id',
        'old_value',
        'new_value',
    ];

    public static $auditingEnabled = false;
    public static $auditEvents = [];

    public function user()
    {
        return $this->morphTo();
    }

    public function auditable() {
        return $this->morphTo();
    }
}
