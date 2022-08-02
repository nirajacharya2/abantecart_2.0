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
 * Class Job
 *
 * @property int $job_id
 * @property string $job_name
 * @property int $status
 * @property string $configuration
 * @property Carbon $start_time
 * @property Carbon $last_time_run
 * @property int $last_result
 * @property int $actor_type
 * @property int $actor_id
 * @property string $actor_name
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @package abc\models
 */
class Job extends BaseModel
{
    use SoftDeletes;

    protected $primaryKey = 'job_id';
    public $timestamps = false;

    protected $casts = [
        'status'      => 'int',
        'last_result' => 'int',
        'actor_type'  => 'int',
        'actor_id'    => 'int',
    ];

    protected $dates = [
        'start_time',
        'last_time_run',
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'status',
        'configuration',
        'start_time',
        'last_time_run',
        'last_result',
        'actor_type',
        'actor_id',
        'actor_name',
        'date_added',
        'date_modified',
    ];
}
