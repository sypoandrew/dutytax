<?php

namespace Sypo\Dutytax\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Aero\Admin\Facades\Admin;
use Aero\Admin\Http\Controllers\Controller;
use Spatie\Valuestore\Valuestore;
use Sypo\Dutytax\Models\Dutytax;

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
        #$valuestore = Valuestore::make(storage_path('app/dutytax.json'));
		#$this->data['valuestore'] = $valuestore->all();
		
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
			return response()->json($res);
		}
		
		$formdata = $request->json()->all();
		Log::debug($formdata);
		
		/* $valuestore = Valuestore::make(storage_path('app/dutytax.json'));
		$valuestore->put('enabled', $formdata['enabled']);
		$valuestore->put('still_wine_rate', $formdata['still_wine_rate']);
		$valuestore->put('sparkling_wine_rate', $formdata['sparkling_wine_rate']);
		$valuestore->put('fortified_wine_rate', $formdata['fortified_wine_rate']);
		$valuestore->put('litre_calc', $formdata['litre_calc']); */
		
		
        return redirect(route('admin.modules.dutytax'));
    }
}
