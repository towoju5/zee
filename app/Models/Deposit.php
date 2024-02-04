<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Deposit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'currency_from',
        'currency_to',
        'amount_from',
        'amount_to',
        'exchange_rate',
        'payment_gateway_id',
        'meta',
    ];
    
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'deleted_at'
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class)
            ->where('meta_id', '=', $this->getKey())
            ->where('meta_type', '=', 'deposit');
    }
}
