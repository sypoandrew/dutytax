<?php

namespace Sypo\Dutytax\Http\Controllers;

use Illuminate\Http\Request;
use Aero\Admin\Facades\Admin;
use Aero\Admin\Http\Controllers\Controller;
use Sypo\Dutytax\Models\Dutytax;
use Spatie\Valuestore\Valuestore;

class ModuleController extends Controller
{
    protected $data = []; // the information we send to the view

    /**
     * Show main settings form
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return view('dutytax::dutytax', $this->data);
    }
    
	/**
     * Update settings
     *
     * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
		$res = ['success'=>false,'data'=>false,'error'=>[]];
		
        $validator = \Validator::make($request->all(), [
            'still_wine_rate' => 'required|numeric|between:0,99.99',
            'sparkling_wine_rate' => 'required|numeric|between:0,99.99',
            'fortified_wine_rate' => 'required|numeric|between:0,99.99',
            'litre_calc' => 'required|int',
        ]);
		
		if($validator->fails()){
			$res['error'] = $validator->errors()->all();
			return redirect()->back()->withErrors($res['error']);
		}
		
		$valuestore = Valuestore::make(storage_path('app/settings/Dutytax.json'));
		$valuestore->put('enabled', (int) $request->input('enabled'));
		$valuestore->put('still_wine_rate', $request->input('still_wine_rate'));
		$valuestore->put('sparkling_wine_rate', $request->input('sparkling_wine_rate'));
		$valuestore->put('fortified_wine_rate', $request->input('fortified_wine_rate'));
		$valuestore->put('litre_calc', $request->input('litre_calc'));
		
		return redirect()->back()->with('message', 'Settings updated.');
    }
    
	/**
     * Duty Paid calculator
     *
     * @return void
     */
    public function calculate(Request $request)
    {
    	\Artisan::call('sypo:dutytax:calculate');
		
		return redirect()->back()->with('message', 'You have successfully run the Duty Paid calculator.');
    }
}
