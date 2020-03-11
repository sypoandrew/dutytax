<?php

namespace Sypo\Dutytax\Models;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Spatie\Valuestore\Valuestore;
use Aero\Catalog\Models\Product;

class Dutytax extends Product
{
	
    public function calc_duty_paid_price($id, $set_price = false)
    {
		#Log::debug('in getDeliveredSellingPrice ' . $this->getId());
		
		$p = self::find($id);
		
		#need get the price of the in-bond variant here...
		$in_bond_found = false;
		$v = $p->variants();
		foreach($v as $variant){
			
			$in_bond_found = true;
		}
		
		if($in_bond_found){
			$price = $this->getPrice();
			
			$attr = $p->attributes();
			$tags = $p->getAllTagsAttribute();
			
			$DutyStatus = strtoupper($_p->getAttributeText('duty_status'));
			$BottleSize = $_p->getAttributeText('bottle_size');
			$PackSize = $_p->getAttributeText('pack_size');
			$WineType = strtolower($_p->getAttributeText('wine_type'));
			
			$TotalCaseLitres = self::calc_bottle_unit($BottleSize, $PackSize);

			#Log::debug('duty_status:'. $DutyStatus .' | bottle_size:'.  $BottleSize .' | wine_type:'.  $WineType .' | pack_size:'.  $PackSize .' | price:'.  $price .' | TotalCaseLitres:'.  $TotalCaseLitres );
			
			$deliveredPrice = $price;
			
			
			if($DutyStatus == 'EX'){
				
				//just add the VAT
				
				$deliveredPrice = $price;
			}
			elseif($DutyStatus == 'DP'){
				
				//duty already paid
				
				$deliveredPrice = $price;
			}
			else{
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
			}
			
			$deliveredPrice = number_format($deliveredPrice, 2, ".", ",");
			#Log::debug("deliveredPrice $deliveredPrice");
			
			return $deliveredPrice;
		}
		return;
    }
	
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
