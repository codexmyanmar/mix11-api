<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoleTransaction extends Model
{
    protected $fillable = ['user_id', 'transaction_id', 'order_id', 'type', 'amount', 
                          'balance_before', 'balance_after', 'status', 'metadata'];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
