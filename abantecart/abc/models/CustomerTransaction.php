<?php

namespace abc\models;

use abc\models\AModelBase;

/**
 * Class AcCustomerTransaction
 *
 * @property int                    $customer_transaction_id
 * @property int                    $customer_id
 * @property int                    $order_id
 * @property int                    $created_by
 * @property int                    $section
 * @property float                  $credit
 * @property float                  $debit
 * @property string                 $transaction_type
 * @property string                 $comment
 * @property string                 $description
 * @property \Carbon\Carbon         $date_added
 * @property \Carbon\Carbon         $date_modified
 *
 * @property \abc\models\AcCustomer $customer
 *
 * @package abc\models
 */
class CustomerTransaction extends AModelBase
{
    protected $primaryKey = 'customer_transaction_id';
    public $timestamps = false;

    protected $casts = [
        'customer_id' => 'int',
        'order_id'    => 'int',
        'created_by'  => 'int',
        'section'     => 'int',
        'credit'      => 'float',
        'debit'       => 'float',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'customer_id',
        'order_id',
        'created_by',
        'section',
        'credit',
        'debit',
        'transaction_type',
        'comment',
        'description',
        'date_added',
        'date_modified',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
