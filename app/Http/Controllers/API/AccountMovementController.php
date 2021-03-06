<?php

namespace App\Http\Controllers\API;

use App\Taxpayer;
use App\Cycle;
use App\AccountMovement;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AccountMovementController extends Controller
{
    public function start(Request $request)
    {
        $transactionData = array();
        $cycle = null;

        //Process Transaction by 100 to speed up but not overload.

        $chunkedData = $request;

        if (isset($chunkedData)) {
            $data = collect($chunkedData);
            $groupData = $data->groupBy(function ($q) {
                return Carbon::parse($q["Date"])->format('Y');
            });
            $i = 0;

            //groupby function group by year.
            foreach ($groupData as $groupedRow) {
                if ($groupedRow->first()['Type'] == 2) {
                    $taxPayer = $this->checkTaxPayer($groupedRow->first()['SupplierTaxID'], $groupedRow->first()['SupplierName']);
                } else if ($groupedRow->first()['Type'] == 1) {
                    $taxPayer = $this->checkTaxPayer($groupedRow->first()['CustomerTaxID'], $groupedRow->first()['CustomerName']);
                }

                //check and create cycle
                $firstDate = Carbon::parse($groupedRow->first()["Date"]);
                $cycle = Cycle::My($taxPayer, $firstDate)->first();

                if (!isset($cycle)) {
                    $cycle = $this->checkCycle($taxPayer, $firstDate);
                }

                foreach ($groupedRow as $data) {
                    try {
                        $data = $this->processTransaction($data, $taxPayer, $cycle);
                        $data["Message"] = "Success";
                        $transactionData[$i] = $data;
                        $i = $i + 1;
                    } catch (\Exception $e) {
                        $data["Message"] = "Error loading transaction: " . $e;
                        $transactionData[$i] = $data;
                    }
                }
            }
        }

        return response()->json(collect($transactionData));
    }

    public function processTransaction($data, Taxpayer $taxPayer, Cycle $cycle)
    {
        $accMovement = $this->processMovement($data, $taxPayer, $cycle);
        $data['cloud_id'] = $accMovement->id;
        //Return account movement if not null.
        return $data;
    }

    //Simple movements from one account to another. Maybe this should create two movements to demonstrate how it goes from one account into another.
    public function processMovement($data, $taxPayer, $cycle)
    {
        $chartId = $this->checkChartAccount($data['AccountName'], $taxPayer, $cycle);
        $date = $this->convert_date($data['Date']);
        //Check currency rate based on date. if nothing found use default from api. TODO this should be updated to buy and sell rates.
        $rate = 1;
        if ($data['CurrencyRate'] ==  '') {
            $rate = $this->checkCurrencyRate($data['CurrencyCode'], $taxPayer, $data['Date']) ?? 1;
        } else {
            $rate = $data['CurrencyRate'] ?? 1;
        }

        $accMovement = AccountMovement::where('taxpayer_id', $taxPayer->id)
            ->where('chart_id', $chartId)
            ->where('currency', $data['CurrencyCode'])
            ->where('rate', $rate)
            ->where('date', $date)
            ->first()
            ?? new AccountMovement();

        $accMovement->chart_id = $chartId;
        $accMovement->taxpayer_id = $taxPayer->id;
        $accMovement->currency = $data['CurrencyCode'];
        $accMovement->rate = $rate;

        $accMovement->date = $date;
        $accMovement->credit = $data['Credit'] * $accMovement->rate ?? 0;
        $accMovement->debit = $data['Debit'] * $accMovement->rate ?? 0;
        $accMovement->comment = $data['Comment'];
        $accMovement->save();

        return $accMovement;
    }
}
