<?php

namespace App\Http\Controllers;

use App\Taxpayer;
use App\Cycle;
use App\Chart;
use App\FixedAsset;
use Carbon\Carbon;
use Spatie\QueryBuilder\QueryBuilder;
use App\Http\Resources\GeneralResource;
use Illuminate\Http\Request;

class FixedAssetController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Taxpayer $taxPayer, Cycle $cycle)
    {
        $query = FixedAsset::where('taxpayer_id', $taxPayer->id)
            ->with('chart');

        return GeneralResource::collection(
            QueryBuilder::for($query)
                ->allowedFilters('name', 'serial')
                ->paginate(50)
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Taxpayer $taxPayer, Cycle $cycle)
    {
        $fixedAsset = FixedAsset::firstOrNew(['id' => $request->id]);
        $fixedAsset->chart_id = $request->chart['id'];
        $fixedAsset->taxpayer_id = $taxPayer->id;
        $fixedAsset->currency = $request->currency ?? $taxPayer->currency;
        $fixedAsset->rate = $request->rate;
        $fixedAsset->serial = $request->serial;
        $fixedAsset->name = $request->name;
        $fixedAsset->purchase_date = $request->purchase_date;
        $fixedAsset->purchase_value = $request->purchase_value;
        $fixedAsset->current_value = $request->current_value;
        $fixedAsset->quantity = $request->quantity;
        $fixedAsset->save();

        return response()->json('Ok', 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\FixedAsset  $transaction
     * @return \Illuminate\Http\Response
     */
    public function show(Taxpayer $taxPayer, Cycle $cycle, $fixedAssetId)
    {
        return new GeneralResource(
            FixedAsset::where('id', $fixedAssetId)
                ->where('taxpayer_id', $taxPayer->id)
                ->with('chart')
                ->first()
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\FixedAsset  $fixedAsset
     * @return \Illuminate\Http\Response
     */
    public function destroy(Taxpayer $taxPayer, Cycle $cycle, $id)
    {
        FixedAsset::where('id', $id)->delete();
        return response()->json('Ok', 200);
    }

    public function depreciate(Taxpayer $taxPayer, Cycle $cycle)
    {
        $assets = FixedAsset::where('taxpayer_id', $taxPayer->id)->get();
        foreach ($assets as $fixedAsset) 
        {
            $fixedAssetGroup = Chart::find($fixedAsset->chart_id);
            $fixedAsset['depricate'] = 0;
            if (isset($fixedAssetGroup) && ($fixedAsset->purchase_value > 0) && ($fixedAssetGroup->asset_years > 0)) {
                // get the difference in date between now and the purchase date.
                $diffInDays = Carbon::now()->diffInDays($fixedAsset->purchase_date);
                // calculate in days.
                $dailyDepreciation = $fixedAsset->purchase_value / ($fixedAssetGroup->asset_years * 365);
                // use the difference in time to calculate percentage reduction from purchase value.
                $fixedAsset->current_value = $fixedAsset->purchase_value - ($dailyDepreciation * $diffInDays);
                $fixedAsset['depricate'] = ($dailyDepreciation * $diffInDays);
                $fixedAsset->save();
            }
        }
        foreach ($assets->groupBy('chart_id') as $groupedAssets)
        {
            $asset = $charts->where('id', $groupedAssets->first()->chart_id)->first();

            if (isset($asset))
            {
                $journal = new \App\Journal();
                $journal->cycle_id = $cycle->id;
                $journal->date =Carbon::now();
                $journal->comment = 'Deprication Entry';
                $journal->is_automatic = 1;
                $journal->module_id = ;
                $journal->save();

                $detail = $journal->details()->firstOrNew(['chart_id' => $asset->id]);
                $detail->debit = $groupedAssets->sum('depricate');;
                $detail->chart_id = $asset->id;
                $journal->details()->save($detail);
            }
        }
       

    }
}
