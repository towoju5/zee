<?php

namespace Modules\SendMoney\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\SendMoney\Database\factories\SendMoneyFactory;

class SendMoney extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        "quote_id",
        "status",
        "raw_data"
    ];

    protected $casts = [
        'raw_data' => 'array'
    ];

    protected static function newFactory(): SendMoneyFactory
    {
        //return SendMoneyFactory::new();
    }
}
