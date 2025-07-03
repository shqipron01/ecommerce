<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public function items(){
        return $this->hasMany(OrderItem::class);
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime:d M, Y',
        ];
    }
    protected $fillable = [
        'user_id',
        'stripe_session_id',
        'subtotal',
        'payment_method',
        'grand_total',
        'shipping',
        'name',
        'email',
        'mobile',
        'address',
        'city',
        'zip',
        'state',
    ];
}
