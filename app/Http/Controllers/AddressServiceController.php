<?php

namespace App\Http\Controllers;

use App\Models\AddressService;
use App\Http\Requests\StoreAddressServiceRequest;
use App\Http\Requests\UpdateAddressServiceRequest;
use Illuminate\Http\Request;

class AddressServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $address = AddressService::create([
            'an_address' => $request->an_address,
            'demande_id' => $request->demande_id,
            'rue' => $request->rue,
            'numero' => $request->numero,
            'box' => $request->box,
            'zip_code' => $request->zip_code,
            'localite' => $request->localite,
            'pays' => $request->pays,
        ]);
        return response()->json(['address_id'=>$address->id,'address'=>$address]);
    }

    /**
     * Display the specified resource.
     */
    public function show( $idDemande)
    {
        $adresse = AddressService::where('demande_id', $idDemande)->orderBy('id', 'desc')->first();
        return response()->json(['address'=>$adresse]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AddressService $addressService)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAddressServiceRequest $request, AddressService $addressService)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AddressService $addressService)
    {
        //
    }
}
