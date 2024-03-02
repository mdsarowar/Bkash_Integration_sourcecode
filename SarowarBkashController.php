<?php

namespace App\Http\Controllers;

use App\Bkash\BkashPayment;
use App\FailedTranscations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class SarowarBkashController extends Controller
{
    public function createPayment(Request $request){
        $bkashobj=new BkashPayment();

        $inv = uniqid();
        $request['intent'] = 'sale';
        $request['mode'] = '0011'; //0011 for checkout
        $request['payerReference'] = $inv;
        $request['currency'] = 'BDT';
        $request['amount'] =  round(Crypt::decrypt($request->amount), 2);
        $request['merchantInvoiceNumber'] = $inv;
        $request['callbackURL'] = config("bkash.callbackURL");

        $request_data_json = json_encode($request->all());

        $response=$bkashobj->requestPayment($request_data_json);
//        dd($response);              // Output data submit bkash website for original creditionals
        if (isset($response['bkashURL'])){
            return redirect()->away($response['bkashURL']);
        }else{
            return redirect()->back()->with('error',$response['statusMessage']);
        }
    }

    public function callBack(Request $request){
        $execute=new BkashPayment();

        if ($request->status == 'success'){
            $response= $execute->executePayment($request['paymentID']);
            if (!isset($response)){
                $response=$execute->queryPayment($request['paymentID']);
            }
//            dd($response); // Output data submit bkash website for original creditionals
            if (isset($response['statusCode']) && $response['statusCode'] == "0000" && $response['transactionStatus'] == "Completed"){
                $trxID = $response['trxID'];
                $payment_method='Bkash Gateway';
                $payment_status='yes';
                $order_id=$response['customerMsisdn'].date("h:i:sa");
                $customer_number=$response['customerMsisdn'];

                $checkout = new PlaceOrderController();
                return $checkout->placeorder($trxID, $payment_method,$order_id, $payment_status,$customer_number);
            }else{
//                return $response;
                notify()->error($response['statusMessage']);
                $failedTranscations = new FailedTranscations();
                $failedTranscations->txn_id = 'Bkash' . Str::uuid();
                $failedTranscations->user_id = auth()->id();
                $failedTranscations->save();
                return redirect(route('order.review'));
            }
        }else{
            notify()->error($request->status);
            return redirect(route('order.review'));
        }
    }


    public function bkashSearch($trxID)
    {
        $bkash=new BkashPayment();
       $findpayment= $bkash->searchPayment($trxID);
        //return BkashPaymentTokenize::searchTransaction($trxID,1); //last parameter is your account number for multi account its like, 1,2,3,4,cont..
        return $findpayment;
    }
}
