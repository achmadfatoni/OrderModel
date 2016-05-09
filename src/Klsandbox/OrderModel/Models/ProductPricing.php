<?php

namespace Klsandbox\OrderModel\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Klsandbox\OrderModel\Models\ProductPricing
 *
 * @property integer $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property integer $role_id
 * @property integer $product_id
 * @property float $price
 * @property integer $site_id
 * @property-read \Klsandbox\OrderModel\Models\Product $product
 * @property-read \Klsandbox\RoleModel\Role $role
 * @method static \Illuminate\Database\Query\Builder|\Klsandbox\OrderModel\Models\ProductPricing whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\Klsandbox\OrderModel\Models\ProductPricing whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Klsandbox\OrderModel\Models\ProductPricing whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Klsandbox\OrderModel\Models\ProductPricing whereRoleId($value)
 * @method static \Illuminate\Database\Query\Builder|\Klsandbox\OrderModel\Models\ProductPricing whereProductId($value)
 * @method static \Illuminate\Database\Query\Builder|\Klsandbox\OrderModel\Models\ProductPricing wherePrice($value)
 * @method static \Illuminate\Database\Query\Builder|\Klsandbox\OrderModel\Models\ProductPricing whereSiteId($value)
 */
class ProductPricing extends Model
{

    protected $table = 'product_pricings';
    public $timestamps = true;

    /**
     * Attributes that are mass assignable
     *
     * @var array
     */
    protected $fillable = [
        'price'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function role()
    {
        return $this->belongsTo(\Klsandbox\RoleModel\Role::class);
    }

    public static function RestockPricingId()
    {
        return ProductPricing::where('product_id', '=', Product::RestockId())->first()->id;
    }

    public function groups()
    {
        return $this->belongsToMany(\App\Models\Group::class, 'group_product_pricing');
    }
}
