@extends('admin::layouts.main')

@section('content')
    <div class="flex pb-2 mb-4">
        <h2 class="flex-1 m-0 p-0">
		<a href="{{ route('admin.modules') }}" class="btn mr-4">&#171; Back</a>
		<span class="flex-1">Duty-paid price settings</span>
		</h2>
    </div>
    @include('admin::partials.alerts')
	<form action="{{ route('admin.modules.dutytax') }}" method="post" class="flex flex-wrap">
		@csrf
		<div class="card mt-4 w-full">
			<h3>Duty-paid price settings</h3>
			<div class="mt-4 w-full">
			<label for="enabled" class="block">
			<label class="checkbox">
			@if(setting('Dutytax.enabled'))
			<input type="checkbox" id="enabled" name="enabled" checked="checked" value="1">
			@else
			<input type="checkbox" id="enabled" name="enabled" value="1">
			@endif
			<span></span>
			</label>Enabled
			</label>
			</div>
			<div class="mt-4 w-full">
			<label for="litre_calc" class="block">Rates per X litre</label>
			<input type="text" id="litre_calc" name="litre_calc" autocomplete="off" required="required" class="w-full " value="{{ setting('Dutytax.litre_calc') }}">
			</div>
			<div class="mt-4 w-full">
			<label for="still_wine_rate" class="block">Still Wine Rate</label>
			<input type="text" id="still_wine_rate" name="still_wine_rate" autocomplete="off" required="required" class="w-full " value="{{ setting('Dutytax.still_wine_rate') }}">
			</div>
			<div class="mt-4 w-full">
			<label for="sparkling_wine_rate" class="block">Sparkling Wine Rate</label>
			<input type="text" id="sparkling_wine_rate" name="sparkling_wine_rate" autocomplete="off" required="required" class="w-full " value="{{ setting('Dutytax.sparkling_wine_rate') }}">
			</div>
			<div class="mt-4 w-full">
			<label for="fortified_wine_rate" class="block">Fortified Wine Rate</label>
			<input type="text" id="fortified_wine_rate" name="fortified_wine_rate" autocomplete="off" required="required" class="w-full " value="{{ setting('Dutytax.fortified_wine_rate') }}">
			</div>
		</div>
		
		<!--<div class="card mt-4 p-4 w-full flex flex-wrap"><button type="submit" class="btn btn-secondary">Save</button> </div>-->
		<div class="card mt-4 p-4 w-full flex flex-wrap"><p>Please contact SYPO to update these settings.</p></div>
	</form>
		
	
	
	<form action="{{ route('admin.modules.dutytax.calculate') }}" method="get" class="flex flex-wrap mb-8">
		@csrf
		
		<div class="card mt-4 p-4 w-full flex flex-wrap">
			<h3 class="w-full">Calculate Duty Paid prices</h3>
			<p><strong>NOTE:</strong> this is run via an automated routine every X minutes. Press the button below to run the routine manually.</p>
		</div>
		<div class="card mt-4 p-4 w-full flex flex-wrap">
			<button type="submit" class="btn btn-secondary">Process</button>
		</div>
	</form>
@endsection
