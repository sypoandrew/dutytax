<?php

namespace Sypo\Dutytax\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Aero\Admin\Facades\Admin;
use Aero\Admin\Http\Controllers\Controller;
use Spatie\Valuestore\Valuestore;

class ModulesController extends Controller
{
    protected $data = []; // the information we send to the view

    /**
     * Show main settings form
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $valuestore = Valuestore::make(storage_path('app/dutytax.json'));
		$this->data['valuestore'] = $valuestore->all();
		
		return view('modules.dutytax', $this->data);
    }
    
	/**
     * Update settings
     *
     * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
		$formdata = $request->json()->all();
		Log::debug($formdata);
		
		/* $valuestore = Valuestore::make(storage_path('app/dutytax.json'));
		$valuestore->put('enabled', '1');
		$valuestore->put('still_wine_rate', '26.78');
		$valuestore->put('sparkling_wine_rate', '34.30');
		$valuestore->put('fortified_wine_rate', '35.70');
		$valuestore->put('litre_calc', '9'); */
		
		
        return redirect(route('admin.modules.dutytax'));
    }
}
