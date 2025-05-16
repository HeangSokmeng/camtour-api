<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_no',
        'user_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address_to_receive',
        'city',
        'state',
        'payment_method',
        'payment_status',
        'discount_amount',
        'total_amount',
        'currency',
        'status',
        'notes',
        'order_date',
        'create_uid',
        'update_uid',
        'is_deleted',
        'deleted_uid',
        'delete_notes',
    ];

    protected $casts = [
        'order_date' => 'datetime',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    // Relationships

    public function orderDetails()
{
    return $this->hasMany(OrderDetail::class);
}
public function user()
{
    return $this->belongsTo(User::class, 'create_uid');
}

}
