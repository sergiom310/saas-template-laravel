<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Shop;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ImageProduct;
use App\Models\Tagp;

class Product extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'description', 'cost', 'price', 'minimum',
        'name_en', 'description_en', 'cost_usd', 'price_usd',
        'brand_id', 'stock', 'sku', 'barcode',
        'cover_img', 'alcohol_percentage', 'expiry_date',
        'size', 'weight', 'unit_type', 'show_price', 'is_featured',
        'status', 'stock_visible', 'allow_backorder', 'discount_active', 
        'discount_percent', 'min_order_qty', 'max_order_qty', 'show_related', 'allow_reviews'
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_product', 'product_id', 'category_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ImageProduct::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tagp::class, 'product_tag');
    }

    public function ventaDetalles(): HasMany
    {
        return $this->hasMany(VentaDetalle::class, 'producto_id');
    }
}
