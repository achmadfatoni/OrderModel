<?php

namespace Klsandbox\OrderModel\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Klsandbox\RoleModel\Role;
use Klsandbox\SiteModel\Site;

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
 *
 * @method static \Illuminate\Database\Query\Builder|\Klsandbox\OrderModel\Models\ProductPricing whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\Klsandbox\OrderModel\Models\ProductPricing whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Klsandbox\OrderModel\Models\ProductPricing whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Klsandbox\OrderModel\Models\ProductPricing whereRoleId($value)
 * @method static \Illuminate\Database\Query\Builder|\Klsandbox\OrderModel\Models\ProductPricing whereProductId($value)
 * @method static \Illuminate\Database\Query\Builder|\Klsandbox\OrderModel\Models\ProductPricing wherePrice($value)
 * @method static \Illuminate\Database\Query\Builder|\Klsandbox\OrderModel\Models\ProductPricing whereSiteId($value)
 *
 * @property string $sku
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Group[] $groups
 *
 * @method static \Illuminate\Database\Query\Builder|\Klsandbox\OrderModel\Models\ProductPricing whereSku($value)
 * @mixin \Eloquent
 *
 * @property float $price_east
 *
 * @method static \Illuminate\Database\Query\Builder|\Klsandbox\OrderModel\Models\ProductPricing wherePriceEast($value)
 *
 * @property float $delivery
 *
 * @method static \Illuminate\Database\Query\Builder|\Klsandbox\OrderModel\Models\ProductPricing whereDelivery($value)
 *
 * @property float $delivery_east
 *
 * @method static \Illuminate\Database\Query\Builder|\Klsandbox\OrderModel\Models\ProductPricing whereDeliveryEast($value)
 */
class ProductPricing extends Model
{
    public $timestamps = true;

    /**
     * Attributes that are mass assignable
     *
     * @var array
     */
    protected $fillable = [
        'price',
        'price_east',
        'delivery',
        'delivery_east',
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
        return self::where('product_id', '=', Product::Restock()->id)->first()->id;
    }

    public function groups()
    {
        return $this->belongsToMany(\App\Models\Group::class, 'group_product_pricing');
    }

    public static function getAvailableProductPricingList(User $user, $targetRole = null)
    {
        $list = self::with('product', 'groups', 'product.bonusCategory')->get();

        if ($targetRole) {
            $targetRole = Role::findByName($targetRole);
            Site::protect($targetRole, 'role');
        }

        if (!config('group.enabled')) {
            return $list;
        }

        $userGroups = $user->groups()->get()->pluck('id')->all();

        $list = $list->filter(function ($productPricing) use ($userGroups) {
            if (!$productPricing->product->is_available) {
                return false;
            }

            if ($productPricing->price <= 0) {
                return false;
            }

            foreach ($productPricing->groups as $group) {
                if (in_array($group->id, $userGroups)) {
                    return true;
                }

                return false;
            }
        });

        if ($targetRole) {
            $list = $list->filter(function ($productPricing) use ($targetRole) {
                return $productPricing->role_id == $targetRole->id;
            });
        }

        if (!$user->hasDropshipAccess() && $user->account_status == 'Approved') {
            $product = Product::DropshipMembership();

            $product->productPricing->load([
                'product', 'groups', 'product.bonusCategory',
            ]);

            //if group is not enabled product pricing is not a collection
            if (!config('group.enabled')) {
                $list = $list->merge([$product->productPricing]);
            } else {
                $list = $list->merge($product->productPricing);
            }
        }

        $list = $list->all();
        $list = array_values($list);

        return $list;
    }

    public function getPriceAndDelivery($user, $customer, &$price, &$delivery)
    {
        if ($customer) {
            if ($customer->pricingArea() == 'east') {
                $price = $this->price_east;
                $delivery = $this->delivery_east;
            } else {
                $price = $this->price;
                $delivery = $this->delivery;
            }
        } else {
            $user = auth()->user();
            if ($user->pricingArea() == 'east') {
                $price = $this->price_east;
                $delivery = $this->delivery_east;
            } else {
                $price = $this->price;
                $delivery = $this->delivery;
            }
        }
    }
}
