<?php

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
    public $timestamps = false;

    protected $casts = [
        'tax_exempt' => 'bool',
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
