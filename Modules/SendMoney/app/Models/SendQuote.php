<?php

namespace Modules\SendMoney\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\SendMoney\Database\factories\SendQuoteFactory;

class SendQuote extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = [];

    protected $casts = [
        'action' => 'string',
        'send_amount' => 'float',
        'receive_amount' => 'float',
        'send_gateway' => 'string',
        'receive_gateway' => 'string',
        'send_currency' => 'string',
        'receive_currency' => 'string',
        'transfer_purpose' => 'string',
        'rate' => 'float',
        'user_id' => 'int',
        'beneficiary_id' => 'int',
        'total_amount' => 'float',
        'raw_data' => 'object',
    ];

    public function details()
    {
        return $this->hasMany(SendMoney::class, 'quote_id')->latest()->where('status', 'pending')->orderBy('created_at');
    }

    protected static function newFactory(): SendQuoteFactory
    {
        //return SendQuoteFactory::new();
    }
}
