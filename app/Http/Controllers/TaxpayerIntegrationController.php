<?php

namespace App\Http\Controllers;

use App\TaxpayerIntegration;
use App\TaxpayerSetting;
use App\Taxpayer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class TaxpayerIntegrationController extends Controller
{
    /**
    * Display a listing of the resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function index($teamID, $userID)
    {
        $taxPayerIntegration = TaxpayerIntegration::MyTaxPayers($teamID)
        ->leftJoin('taxpayer_favs', 'taxpayer_favs.taxpayer_id', 'taxpayers.id')
        ->select('taxpayer_integrations.id as id',
        'taxpayers.country',
        'taxpayers.name',
        'taxpayers.alias',
        'taxpayers.taxid',
        'taxpayer_favs.id as is_favorite')
        ->get();

        return $taxPayerIntegration;
    }

    /**
    * Display the specified resource.
    *
    * @param  \App\TaxpayerIntegration  $taxpayerIntegration
    * @return \Illuminate\Http\Response
    */
    public function show($taxPayer,$cycle,$taxpayerIntegrationID)
    {
        $taxPayerIntegration = TaxpayerIntegration::where('id', $taxpayerIntegrationID)
        ->with(['taxpayer'])
        ->first();

        return view('taxpayer')->with('Integration', $taxPayerIntegration);
    }

    /**
    * Update the specified resource in storage.
    *
    * @param  \Illuminate\Http\Request  $request
    * @param  \App\TaxpayerIntegration  $taxpayerIntegration
    * @return \Illuminate\Http\Response
    */
    public function store(Request $request)
    {
        
        $taxPayerIntegration = taxPayerIntegration::where('taxpayer_id', $request->id)->first();
        
       
        $taxPayerIntegration->type = $request->type ?? 1; //Default to 1 if nothing is selected
        $taxPayerIntegration->notification_monthly = $request->notification_monthly == true ? 1 : 0;
        $taxPayerIntegration->notification_quarterly = $request->notification_quarterly == true ? 1 : 0;
        $taxPayerIntegration->notification_semesterly = $request->notification_semesterly == true ? 1 : 0;
        $taxPayerIntegration->notification_yearly = $request->notification_yearly == true ? 1 : 0;
        $taxPayerIntegration->notification_sync = $request->notification_sync == true ? 1 : 0;
        $taxPayerIntegration->save();
        
        if (isset($taxPayerIntegration))
        {
            $taxPayer = Taxpayer::where('id', $taxPayerIntegration->taxpayer_id)->first();

            if (isset($taxPayer))
            {
                $taxPayer->alias = $request->alias;
                $taxPayer->address = $request->address;
                $taxPayer->telephone = $request->telephone;
                $taxPayer->email = $request->email;
                $taxPayer->regime_type = $request->setting_regime ? 1 : 0;
                $taxPayer->agent_name = $request->setting_agent;
                $taxPayer->agent_taxid = $request->setting_agenttaxid;
                $taxPayer->show_inventory = $request->setting_inventory ? 1 : 0;
                $taxPayer->show_production = $request->setting_production ? 1 : 0;
                $taxPayer->show_fixedasset = $request->setting_fixedasset ? 1 : 0;
                $taxPayer->is_company = $request->setting_is_company ? 1 : 0;
                $taxPayer->does_import = $request->does_import ? 1 : 0;
                $taxPayer->does_export = $request->does_export ? 1 : 0;
                $taxPayer->save();

             
               return redirect()->route('/');
               

                
            }
        }
        
       return view('/home');
    }

    /**
    * Remove the specified resource from storage.
    *
    * @param  \App\TaxpayerIntegration  $taxpayerIntegration
    * @return \Illuminate\Http\Response
    */
    public function destroy(TaxpayerIntegration $taxpayerIntegration)
    {
        //
    }
}
