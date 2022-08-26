<?php

namespace App\Http\Controllers\API;

use Exception;
use Midtrans\Snap;
use Midtrans\Config;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function all(Request $request){
        $id = $request->input('id');
        $limit = $request->input('limit',6);
        $food_id = $request->input('food_id');
        $status = $request->input('status');
        

        if($id){
            $transaction = Transaction::with(['food','user'])->find($id);

            if($transaction){
                return ResponseFormatter::success($transaction,'Data Transaction berhasil diambil');
            }
            else{
                return ResponseFormatter::error(null,'Data Transaction tidak ada',404);
            }
            
        }

        $transaction = Transaction::with(['food','user'])->where('user_id',Auth::user()->id);

        if($food_id){
            $transaction->where('food_id',$food_id );
        }
        if($status){
            $transaction->where('status',$food_id );
        }
        

        return ResponseFormatter::success($transaction->paginate($limit),'Data Transaction berhasil diambil');

    }

    public function update(Request $request,$id){
        $transaction = Transaction::findOrFail($id);

        $transaction->update($request->all());
        return ResponseFormatter::success($transaction,'Transaksi berhasil diperbarui');
    }

    public function checkout(Request $request){
        $request->validate([
            'food_id'=>'required|exists:food,id',
            'user_id'=>'required|exists:users,id',
            'quantity'=>'required',
            'total'=>'required',
            'status'=>'required',
        ]);
        $transaction = Transaction::create([
            'food_id'=>$request->food_id,
            'user_id'=>$request->user_id,
            'quantity'=>$request->quantity,
            'total'=>$request->total,
            'status'=>$request->status,
            'payment_url'=>'',
        ]);

        Config::$serverKey=config('services.mistrans.serverKey');
        Config::$isProduction=config('services.mistrans.isProduction');
        Config::$isSanitized=config('services.mistrans.isSanitized');
        Config::$is3ds=config('services.mistrans.is3ds');

        $transaction = Transaction::with(['food','user'])->find($transaction->id);

        $midtrans = [
            'transaction_details'=>[
                'order_id'=>$transaction->id,
                'gross_amount'=>(int)$transaction->total,
            ],
            'customer_details'=>[
                'first_name'=>$transaction->user->name,
                'email'=>$transaction->user->email,
            ],
            'enabled_payments'=>[
                'gopay',
                'bank_transfer'
            ],
            'vtweb'=>[],
        ];
        
        try {
            $paymentUrl=Snap::createTransaction($midtrans)->redirect_url;

            $transaction->payment_url = $paymentUrl;
            $transaction->save();

            return ResponseFormatter::success($transaction,'Transaction berhasil');
        } catch (Exception $err) {
            return ResponseFormatter::error($err->getMessage(),'transaction gagal');
        }


    }
}
