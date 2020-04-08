<?php

namespace Sypo\Dutytax\Console\Commands;

use Illuminate\Console\Command;
use Aero\Catalog\Models\Variant;
use Sypo\Dutytax\Models\Dutytax;
use Symfony\Component\Console\Helper\ProgressBar;

class Calculate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sypo:dutytax:calculate';

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
		$progressBar = new ProgressBar($this->output, $variants->count());
		$d = new Dutytax;
		foreach($variants as $variant){
			$d->calc_duty_paid_price($variant);
			$progressBar->advance();
		}
		$processed = $d->total_processed();
		#force reindexing
		$d->checkIndexing(true);
		$progressBar->finish();
		if($processed == 1){
			$this->info('Successfully updated '.$processed.' item');
		}
		elseif($processed > 1){
			$this->info('Successfully updated '.$processed.' items');
		}
		else{
			$this->info('Process complete');
		}
    }
}
