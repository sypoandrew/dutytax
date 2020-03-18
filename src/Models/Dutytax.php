<?php

namespace Sypo\Dutytax\Models;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Spatie\Valuestore\Valuestore;
use Aero\Catalog\Models\Variant;
use Aero\Catalog\Models\Tag;
use Aero\Catalog\Models\Product;

class Dutytax
{
    /**
     * Calculate the duty paid variant price
     *
     * @var \Aero\Catalog\Models\Variant $variant
     * @var boolean $set_price
     * @return null|float
     */
    public static function calc_duty_paid_price(\Aero\Catalog\Models\Variant $variant, $set_price = false)
    {
		Log::debug($variant->toJson());
		$price_det = $variant->getPriceForQuantity(1);
		$price = $price_det->value_inc / 100; #price in Aero stored as integer
		
		
		$sku = $variant->sku;
		$p = $variant->product()->first();
		#Log::debug($p->toJson());
		
		$tags = Product::select('tag_groups.name as tag_group', 'tags.name as value')->join('product_tag', 'product_tag.product_id', '=', 'products.id')->join('tags', 'tags.id', '=', 'product_tag.tag_id')->join('tag_groups', 'tag_groups.id', '=', 'tags.tag_group_id')->where('products.id', $variant->product_id)->where(function($q){
			$q->where(function($q){
				$q->where('tag_groups.name', 'like', '%Bottle Size%');
			});
			$q->orWhere(function($q){
				$q->where('tag_groups.name', 'like', '%Case Size%');
			});
			$q->orWhere(function($q){
				$q->where('tag_groups.name', 'like', '%Wine Type%');
			});
		})->get();
		#dd($tags->get());
		$arr = [];
		foreach($tags as $t){
			$tag_group = json_decode($t->tag_group);
			$tag_value = json_decode($t->value);
			$arr[$tag_group->en] = $tag_value->en;
		}
		Log::debug($arr);
		
		$BottleSize = $arr['Bottle Size'];
		$PackSize = $arr['Case Size'];
		#$WineType = strtolower($arr['Wine Type']);
		$WineType = '';
		
		$TotalCaseLitres = self::calc_bottle_unit($BottleSize, $PackSize);

		Log::debug($sku. ' | bottle_size:'.  $BottleSize .' | wine_type:'.  $WineType .' | pack_size:'.  $PackSize .' | price:'.  $price .' | TotalCaseLitres:'.  $TotalCaseLitres );
		
		$deliveredPrice = $price;
		
		//catch all - assume IB
		#Log::debug("assume IB");
		
		$valuestore = Valuestore::make(storage_path('app/dutytax.json'));
		
		$rate = 0;
		if($WineType == 'sparkling'){
			$rate = $valuestore->get('sparkling_wine_rate');
		}
		elseif($WineType == 'fortified'){
			$rate = $valuestore->get('fortified_wine_rate');
		}
		else{
			//catch all- assume still wine
			$rate = $valuestore->get('still_wine_rate');
		}
		
		$litre_calc = $valuestore->get('litre_calc');
		
		#Log::debug("rate $rate litre_calc $litre_calc");
		
		$rate_per_litre = $rate / $litre_calc;
		
		$duty = $TotalCaseLitres * $rate_per_litre;
		
		#Log::debug("duty $duty");
		
		$deliveredPrice += $duty;
		
		$deliveredPrice = number_format($deliveredPrice, 2, ".", ",");
		Log::debug("deliveredPrice $deliveredPrice");
		
		return $deliveredPrice;
    }
	
    /**
     * Calculate the literage of the product
     *
     * @var string $BottleSize
     * @var int $PackSize
     * @return float
     */
	public static function calc_bottle_unit($BottleSize, $PackSize){
		//returns bottle size in litres
		
		$BottleSize = (string) $BottleSize;
		$PackSize = (int) $PackSize;
		
		if(substr("$BottleSize", -2) == 'ml'){
			//unit in millilitres
			
			$bottleUnit = (substr("$BottleSize", 0, -2) / 1000);
		}
		elseif(substr("$BottleSize", -2) == 'cl'){
			//unit in centilitres
			
			$bottleUnit = (substr("$BottleSize", 0, -2) / 100);
		}
		elseif(substr("$BottleSize", -1) == 'l'){
			//unit in litres
			
			$bottleUnit = substr("$BottleSize", 0, -1);
		}
		else{
			//just in case
			
			if($BottleSize == ''){
				$BottleSize = 0;
			}
			
			$bottleUnit = (float) $BottleSize;
		}
		
		#Log::debug("bottleUnit $bottleUnit BottleSize $BottleSize PackSize $PackSize");
		
		if($PackSize == 0){
			$PackSize = 1;
		}
		
		$units = $bottleUnit * $PackSize;
		
		
		return $units;
	}
}
