<?php

namespace Sypo\Dutytax\Models;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Aero\Catalog\Events\ProductCreated;
use Aero\Catalog\Events\ProductUpdated;
use Aero\Catalog\Models\Variant;
use Aero\Catalog\Models\Tag;
use Aero\Catalog\Models\Price;
use Aero\Catalog\Models\Product;
use Aero\Catalog\Models\Attribute;
use Aero\Common\Models\Currency;
use Aero\Common\Models\TaxRate;

class Dutytax
{
    /**
     * @var string
     */
    protected $language;
    protected $attributes;
    protected $currency;
    protected $vat_rate;
    /**
     * Storage of products that have been processed.
     *
     * @var array
     */
    protected $products = ['created' => [], 'updated' => []];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->language = config('app.locale');
        $this->currency = Currency::where('code', 'GBP')->first();
        $this->vat_rate = TaxRate::where('name', 'like', '%VAT%')->first()->rate;
	}
	
    /**
     * Get Attribute in array format
     *
     * @return array
     */
    public function get_attributes()
    {
		if($this->attributes){
			return $this->attributes;
		}
		
		$this->attributes = [];
		$attributes = Attribute::select('id', 'name')->get();
		foreach($attributes as $a){
			$this->attributes[$a->name] = $a->id;
		}
		
		return $this->attributes;
	}
	
    /**
     * Calculate the duty paid variant price
     *
     * @var \Aero\Catalog\Models\Variant $variant
     * @var boolean $set_price
     * @return null|float
     */
    public function calc_duty_paid_price(\Aero\Catalog\Models\Variant $inbond_variant, $set_price = true)
    {
		#Log::debug($inbond_variant->toJson());
		$sku = $inbond_variant->sku;
		
		$add_to_indexing = false;
		
		$attr = $this->get_attributes();
		
		$price_det = $inbond_variant->getPriceForQuantity(1);
		if($price_det){
			$bond_price = $price_det->value_inc; #price in Aero stored as integer
			
			$tags = Product::select('tag_groups.name as tag_group', 'tags.name as value')->join('product_tag', 'product_tag.product_id', '=', 'products.id')->join('tags', 'tags.id', '=', 'product_tag.tag_id')->join('tag_groups', 'tag_groups.id', '=', 'tags.tag_group_id')->where('products.id', $inbond_variant->product_id)->whereIn("tag_groups.name->{$this->language}", ['Bottle Size', 'Case Size', 'Wine Type'])->get();
			#dd($tags->get());
			$arr = [];
			foreach($tags as $t){
				$tag_group = json_decode($t->tag_group);
				$tag_value = json_decode($t->value);
				$arr[$tag_group->en] = $tag_value->en;
			}
			#Log::debug($arr);
			
			if(isset($arr['Bottle Size']) and isset($arr['Case Size'])){
				$BottleSize = $arr['Bottle Size'];
				$PackSize = $arr['Case Size'];
				$WineType = (isset($arr['Wine Type'])) ? $arr['Wine Type'] : '';
				
				$TotalCaseLitres = self::calc_bottle_unit($BottleSize, $PackSize);

				#Log::debug($sku. ' | bottle_size:'.  $BottleSize .' | wine_type:'.  $WineType .' | pack_size:'.  $PackSize .' | price:'.  $bond_price .' | TotalCaseLitres:'.  $TotalCaseLitres );
				
				$rate = 0;
				if($WineType == 'Sparkling'){
					$rate = setting('Dutytax.sparkling_wine_rate');
				}
				elseif($WineType == 'Fortified'){
					$rate = setting('Dutytax.fortified_wine_rate');
				}
				else{
					//catch all- assume still wine
					$rate = setting('Dutytax.still_wine_rate');
				}
				
				$litre_calc = setting('Dutytax.litre_calc');
				
				#Log::debug("rate $rate litre_calc $litre_calc");
				
				$rate_per_litre = $rate / $litre_calc;
				
				$duty = ($TotalCaseLitres * $rate_per_litre) * 100; # Aero stores price as int
				$dutypaid = ($bond_price + $duty) * (1 + ($this->vat_rate / 100));
				$dutypaid = round($dutypaid, 1);
				
				#$dutypaid = number_format($deliveredPrice, 2, ".", ",");
				#Log::debug("$sku bond price $bond_price");
				#Log::debug("$sku duty $duty");
				#Log::debug("$sku duty paid $dutypaid");
				
				$dp = Variant::where('sku', str_replace('IB', 'DP', $inbond_variant->sku))->first();
				if($dp !== null){
					#found current duty paid variant - get the price object
					$dpprice = $dp->getPriceForQuantity(1);
					if($dpprice !== null){
						#Log::debug("$sku current dp price {$dpprice->value_inc}");
						
						if($dp->stock_level != $inbond_variant->stock_level){
							$dp->stock_level = $inbond_variant->stock_level;
							$dp->save();
							
							$add_to_indexing = true;
						}
						
						if($set_price and (float) $dpprice->value_inc != (float) $dutypaid){
							$dpprice->value = $dutypaid;
							$dpprice->save();
							#dd("{$dpprice->value_inc} != {$dutypaid}");
							
							$add_to_indexing = true;
						}
					}
					else{
						#we shouldn't really be here, but create price anyways...
						dd($dp);
						#create the qty price
						if($set_price){
							
							#add the variant price
							$duty_price = new Price([
								'variant_id' => $dp->product_id,
								'product_tax_group_id' => $dp->product_tax_group_id,
								'product_id' => $dp->product_id,
								'quantity' => 1,
								'currency_code' => $this->currency->code,
							]);
							
							$duty_price->value = $dutypaid;
							
							if($duty_price->save()){
								#Log::debug('variant price for '.$dp->sku.' created successfully');
								$add_to_indexing = true;
							}
							else{
								Log::warning('variant price for '.$dp->sku.' failed to create');
							}
						}
					}
				}
				else{
					#Log::debug('create duty paid variant for '.str_replace('IB', 'DP', $inbond_variant->sku));
					
					if($set_price){
						#create DP item
						
						$dp = new Variant;
						$dp->product_id = $inbond_variant->product_id;
						$dp->stock_level = $inbond_variant->stock_level;
						$dp->minimum_quantity = $inbond_variant->minimum_quantity;
						$dp->product_tax_group_id = 1; #taxable
						$dp->sku = str_replace('IB', 'DP', $inbond_variant->sku);
						if($dp->save()){
							$dp->attributes()->syncWithoutDetaching([$attr['Duty Paid'] => ['sort' => $dp->attributes()->count()]]);
							
							#add the variant price
							$duty_price = new Price([
								'variant_id' => $dp->product_id,
								'product_tax_group_id' => $dp->product_tax_group_id,
								'product_id' => $dp->product_id,
								'quantity' => 1,
								'currency_code' => $this->currency->code,
							]);
							
							$duty_price->value = $dutypaid;
							
							if($duty_price->save()){
								#Log::debug('variant price created successfully');
							}
							else{
								Log::warning('variant price failed to create');
							}
							
							$add_to_indexing = true;
						}
						else{
							Log::warning('duty paid variant failed to create');
						}
					}
				}
				
				if($set_price and $add_to_indexing){
					$p = $dp->product()->first();
					$this->addToProducts($p);
				}
				
				return $dutypaid;
			}
			else{
				#Log::warning("SKU $sku missing data to calculate Duty Paid price");
			}
		}
		else{
			#Log::warning("SKU $sku missing price data");
		}
		
		return;
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

    /**
     * Add a product to the queue to be indexed.
     *
     * @param $product
     */
    protected function addToProducts($product): void
    {
        if ($product) {
            if ($product->wasRecentlyCreated) {
                $product->wasRecentlyCreated = false;
                $this->products['created'][$product->id] = $product;
            } else {
                $this->products['updated'][$product->id] = $product;
            }
        }
    }

    /**
     * Check stored products to index.
     *
     * @param bool $force
     */
    public function checkIndexing($force = false): void
    {
        if ($force || count($this->products['created']) > 5) {
            foreach ($this->products['created'] as $key => $product) {
                event(new ProductCreated($product));
                unset($this->products['created'][$key]);
            }

            $this->products['created'] = [];
        }

        if ($force || count($this->products['updated']) > 5) {
            foreach ($this->products['updated'] as $key => $product) {
                event(new ProductUpdated($product));
                unset($this->products['updated'][$key]);
            }

            $this->products['updated'] = [];
        }
    }
}
