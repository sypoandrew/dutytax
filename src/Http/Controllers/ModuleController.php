<?php

namespace Sypo\Dutytax\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Aero\Admin\Facades\Admin;
use Aero\Admin\Http\Controllers\Controller;
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
		
        return redirect(route('admin.modules.dutytax'));
    }
}
