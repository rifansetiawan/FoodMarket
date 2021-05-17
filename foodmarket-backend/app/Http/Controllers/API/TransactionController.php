<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Midtrans\Config;
use Midtrans\Snap;

class TransactionController extends Controller
{
    public function all(Request $request)
    {
        $id = $request->input('id');
        $status = $request->input('status');
        $food_id = $request->input('food_id');
        $limit = $request->input('limit', 6);

        if($id)
        {
            $transaction = Transaction::with(['user','food'])->find($id);
            if($transaction)
            {
                return ResponseFormatter::success($transaction,
                'Data transaksi berhasil diambil');
            }
            else
            {
                return ResponseFormatter::error(null,
                'Data transaksi tidak ada', 404 );
            }
        }

        $transaction = Transaction::with(['user', 'food'])->where('user_id', Auth::user()->id);

        if($food_id)
        {
            $transaction -> where('food_id', $food_id);
        }
        if($status)
        {
            $transaction -> where('status', $status);
        }

        return ResponseFormatter::success($transaction->paginate($limit), 'Data Transaksi berhasil diambil');

    }
    public function update(Request $request, $id)
    {
        $transaction = Transaction::findOrFail($id);
        $transaction -> update($request->all());
        return ResponseFormatter::success($transaction, 'Transaksi berhasil diupdate');
    }

    public function checktou(Request $request)
    {
        $request->validate([
            'food_id' => 'required|exists:food,id',
            'user_id' => 'required|exists:user, id',
            'quantity' => 'requiered',
            'total' => 'required',
            'status' => 'required'
        ]);

        $transaction = Transaction::create([
            'food_id' => $request->food_id,
            'user_id' => $request->user_id,
            'quantity' => $request->quantity,
            'total' => $request->total,
            'status' => $request->status
        ]);


        Config::$serverKey = config('services.midtrans.serverKey');
        Config::$isProduction = config('services.midtrans.isProduction');
        Config::$isSanitized = config('services.midtrans.isSanitized');
        Config::$is3ds = config('services.midtrans.is3ds');

        $transaction = Transaction::with(['user', 'food'])->find($transaction->id);

        $transactioninMidtrans = [
            "transaction_details"=> [
                "order_id"=> $transaction->id,
                "gross_amount"=> (int)$transaction->total
              ],
              "customer_details" => [
                "first_name"=> $transaction->user->name,
                "email"=> $transaction->user->email
            ],
            'enabled_payments'=> ["gopay", "bca_va"],
            'vtweb'=> []
        ];

        try{
            $paymentUrl = Snap::createTransaction($transactioninMidtrans)->redirect_url;
            $transaction->payment_url = $paymentUrl;
            $transaction->save();

            return ResponseFormatter::success($transaction, 'Transaksi Berhasil');
        }
        catch(Exception $e){
            return ResponseFormatter::error($e->getMessage(), 'Transaksi Gagal');
        }
        

    }
}
