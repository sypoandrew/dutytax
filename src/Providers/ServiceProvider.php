<?php

namespace Sypo\Dutytax\Providers;

use Aero\Admin\AdminModule;
use Aero\Common\Providers\ModuleServiceProvider;
use Aero\Common\Facades\Settings;
use Aero\Common\Settings\SettingGroup;

class ServiceProvider extends ModuleServiceProvider
{
    protected $commands = [
        'Sypo\Dutytax\Console\Commands\Calculate',
    ];
    
    public function register(): void 
    {
        AdminModule::create('Dutytax')
            ->title('Duty Tax')
            ->summary('Duty Tax settings for product variant price calculations')
            ->routes(__DIR__ .'/../../routes/admin.php')
            ->route('admin.modules.dutytax');
        
        $this->commands($this->commands);
    }
	
    public function boot(): void 
    {
        Settings::group('Dutytax', function (SettingGroup $group) {
            $group->boolean('enabled')->default(true);
            $group->string('still_wine_rate')->default(26.78); #this should be a float - to be changed on next Aero release
            $group->string('sparkling_wine_rate')->default(34.30); #this should be a float - to be changed on next Aero release
            $group->string('fortified_wine_rate')->default(35.70); #this should be a float - to be changed on next Aero release
            $group->integer('litre_calc')->default(9);
        });
		
		$this->loadViewsFrom(__DIR__ . '/../../resources/views/', 'dutytax');
    }
}