<?php

namespace Sypo\Dutytax\Console\Commands;

use Illuminate\Console\Command;
use Aero\Catalog\Models\Variant;
use Sypo\Dutytax\Models\Dutytax;

class DutyTax extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dutytax:calculate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate and set the duty paid price variant';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $variants = Variant::where('sku', 'like', '%IB')->get();
		foreach($variants as $variant){
			Dutytax::calc_duty_paid_price($variant);
		}
    }
}
