<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Beneficiary\app\Models\Beneficiary;

class Withdraw extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded  = [];
    
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'deleted_at'
    ];

    protected $casts =  [
        'data' => 'array'
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class)
            ->where('meta_id', '=', $this->getKey())
            ->where('meta_type', '=', 'payouts');
    }

    public function beneficiary()
    {
        return $this->belongsTo(Beneficiary::class);
    }
}
