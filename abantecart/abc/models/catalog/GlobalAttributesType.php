<?php

namespace abc\models\catalog;

use abc\models\BaseModel;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class GlobalAttributesType
 *
 * @property int $attribute_type_id
 * @property string $type_key
 * @property string $controller
 * @property int $sort_order
 * @property int $status
 *
 * @package abc\models
 */
class GlobalAttributesType extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = ['descriptions'];
    protected $primaryKey = 'attribute_type_id';
    public $timestamps = false;

    protected $casts = [
        'sort_order' => 'int',
        'status'     => 'int',
    ];

    protected $fillable = [
        'type_key',
        'controller',
        'sort_order',
        'status',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function descriptions()
    {
        return $this->hasMany(GlobalAttributesTypeDescription::class, 'attribute_type_id');
    }
}
