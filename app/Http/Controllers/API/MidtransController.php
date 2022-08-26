<?php

namespace App\Http\Controllers\API;

use Midtrans\Config;
use Midtrans\Notification;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Transaction;

class MidtransController extends Controller
{
    public function callback(Request $request){
        Config::$serverKey=config('services.mistrans.serverKey');
        Config::$isProduction=config('services.mistrans.isProduction');
        Config::$isSanitized=config('services.mistrans.isSanitized');
        Config::$is3ds=config('services.mistrans.is3ds');

        $notification = new Notification();

        $status = $notification->transaction_status;
        $type = $notification->payment_type;
        $fraud = $notification->fraud_status;
        $order_id = $notification->order_id;

        $transaction = Transaction::findOrFail($order_id);

        if($status == 'capture'){

            if($type == 'credit_card'){

                if($fraud=='challenge'){
                    $transaction->status = 'PENDING';
                }else{
                    $transaction->status = 'SUCCESS';
                }

            }
        }else if($status == 'settlement'){
            $transaction->status = 'SUCCESS';
        }else if($status=='pending'){
            $transaction->status = 'PENDING';
        }else if($status=='deny'){
            $transaction->status = 'CANCELED';
        }else if($status=='expire'){
            $transaction->status = 'CANCELED';
        }else if($status=='cancel'){
            $transaction->status = 'CANCELED';
        }

        $transaction->save();
    }

    public function success(){
        return view('midtrans.success');
    }
    public function unfinish(){
        return view('midtrans.unfinish');
    }
    public function error(){
        return view('midtrans.error');
    }
}
