<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerDocument extends Model
{
    protected $fillable = [
        'customer_id', 'document_type', 'document_number',
        'file_name', 'file_path', 'file_type', 'file_size', 'notes',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}