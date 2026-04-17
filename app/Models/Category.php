<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Product;
use App\Models\Category;

class Category extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'parent_id',
        'name',
        'name_en',
        'description',
        'description_en',
        'slug',
        'icono',
        'imagen',
        'banner',
        'orden',
        'status'
    ];

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'category_product', 'category_id', 'product_id');
    }

    // public function allProducts()
    // {
    //     $allProducts = collect([]);

    //     $mainCategoryProducts = $this->products;

    //     $allProducts = $allProducts->concat($mainCategoryProducts);

    //     if($this->children->isNotEmpty()) {

    //         foreach($this->children as $child) {
    //             $allProducts = $allProducts->concat($child->products);
    //         }

    //     }

    //     return $allProducts;
    // }
}
