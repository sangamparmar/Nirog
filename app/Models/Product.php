<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'purchase_id', 'name', 'price',
        'discount', 'description', 'stock_quantity',
        'category_id', 'image', 'barcode',
        'expiry_date'
    ];

    public function purchase(){
        return $this->belongsTo(Purchase::class);
    }
    
    public function category(){
        return $this->belongsTo(Category::class);
    }
    
    /**
     * Get the product name - either from this model directly or from purchase
     */
    public function getProductNameAttribute()
    {
        if (!empty($this->name)) {
            return $this->name;
        }
        
        if ($this->purchase) {
            return $this->purchase->product;
        }
        
        return 'Unknown Product';
    }
    
    /**
     * Get current stock quantity - either from this model directly or from purchase
     */
    public function getCurrentStockAttribute()
    {
        if (isset($this->stock_quantity)) {
            return $this->stock_quantity;
        }
        
        if ($this->purchase) {
            return $this->purchase->quantity;
        }
        
        return 0;
    }
}
