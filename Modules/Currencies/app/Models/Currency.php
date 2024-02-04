<?php

namespace Modules\Currencies\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Currencies\Database\factories\CurrencyFactory;

class Currency extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];
    
    protected static function newFactory(): CurrencyFactory
    {
        //return CurrencyFactory::new();
    }
}
