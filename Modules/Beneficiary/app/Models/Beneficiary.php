<?php

namespace Modules\Beneficiary\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Beneficiary\Database\factories\BeneficiaryFactory;

class Beneficiary extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = [];

    protected $casts = [
        "address" => "array",
        "beneficiary" => "array",
        "payment_object" => "array"
    ];
    
    protected static function newFactory(): BeneficiaryFactory
    {
        //return BeneficiaryFactory::new();
    }
}
