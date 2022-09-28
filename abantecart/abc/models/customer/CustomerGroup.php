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

namespace abc\models\customer;

use abc\models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class CustomerGroup
 *
 * @property int $customer_group_id
 * @property string $name
 * @property bool $tax_exempt
 *
 * @method static CustomerGroup find(int $customer_group_id) CustomerGroup
 * @package abc\models
 */
class CustomerGroup extends BaseModel
{
    use SoftDeletes;

    protected $primaryKey = 'customer_group_id';

    protected $casts = [
        'tax_exempt'    => 'bool',
        'date_added'    => 'datetime',
        'date_modified' => 'datetime'
    ];

    protected $fillable = [
        'name',
        'tax_exempt',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_group_id');
    }
}
