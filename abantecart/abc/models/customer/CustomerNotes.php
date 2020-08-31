<?php

namespace abc\models\customer;

use abc\models\BaseModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerNotes extends BaseModel
{
    use SoftDeletes;

    protected $primaryKey = 'note_id';

    protected $mainClassName = Customer::class;
    protected $mainClassKey = 'customer_id';

    protected $casts = [
        'customer_id' => 'int',
        'user_id'      => 'int',
        'stage_id'      => 'int',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'customer_id',
        'user_id',
        'note',
        'date_added',
        'date_modified',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * @param int $customerId
     *
     * @return Collection|false
     */
    public static function getNotes(int $customerId)
    {
        if (!$customerId) {
            return false;
        }
        /** @var Collection $notes */
        $notes = self::select([
            'customer_notes.note',
            'customer_notes.date_added as note_added',
            'customer_notes.customer_id',
            'users.lastname',
            'users.firstname',
            'users.username',
        ]) ->leftJoin('users', 'customer_notes.user_id', '=', 'users.user_id')
            ->where('customer_id', '=', $customerId)
            ->orderBy('note_added')
            ->get();

        return $notes;
    }
}