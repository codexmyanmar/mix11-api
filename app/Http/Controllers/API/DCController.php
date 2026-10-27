<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\ReferLog;
use App\Models\PaymentMethod;
use App\Models\TopUp;
use App\Models\Withdraw;
use App\Models\UserBalanceTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DCController extends Controller
{
    public function info($type)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        // Find who referred this user (The Agent)
        $referLog = ReferLog::where("referred_user_id", $user->id)->first();

        if (!$referLog) {
            return response()->json([
                'success' => false,
                'message' => 'Referrer/Agent not found for this user.'
            ], 404);
        }

        $referrer_user_id = $referLog->referrer_user_id;

        // Decide types of payment methods to show
        // 1: Bank, 2: Crypto/Wallet, 0: Others
        if ($type === "deposit") {
            // User wants to deposit, show Agent's receiving accounts
            $types = [1, 2, 0];
        } else {
            // User wants to withdraw, usually they provide their own info, 
            // but if looking for available channels:
            $types = [1, 2, 0];
        }

        $data = PaymentMethod::where("user_id", $referrer_user_id)
            ->whereIn("type", $types)
            ->where("status", 1)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function depositStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'slip_image' => 'nullable|image|max:2048', 
            'note' => 'nullable|string|max:255',
            'latest_number' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();

        $referLog = ReferLog::where("referred_user_id", $user->id)->first();
        if (!$referLog) {
            return response()->json([
                'success' => false,
                'message' => 'Agent not found'
            ], 404);
        }
        $agentId = $referLog->referrer_user_id;

        $imagePath = null;
        if ($request->hasFile('slip_image')) {
            $imagePath = $request->file('slip_image')->store('topup_slips', 'public');
        }

        $payemntMethod = PaymentMethod::where("id",$request->payment_method_id)->first();

        $topUp = TopUp::create([
            'user_id' => $user->id,
            'agent_id' => $agentId,
            'payment_method' => $payemntMethod->bank_name,
            'account_name' => $payemntMethod->name,
            'account_no' => $payemntMethod->account_no,
            'amount' => $request->amount,
            'payemnt_slip' => $imagePath,
            'reason' => $request->note,
            'status' => 0,
            'latest_six_number' => $request->latest_number
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Deposit request submitted successfully. Please wait for agent approval.',
            'data' => $topUp
        ]);
    }

    public function withdrawStore(Request $request)
    {
        // 1. Validation
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
            'bank_name' => 'required|string',
            'account_number' => 'required|string',
            'account_name' => 'required|string',
            'note' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $amount = $request->amount;

        // 2. Check User Balance
        if ($user->balance < $amount) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient balance.'
            ], 400);
        }

        /* 
        Optional: Check for existing agent if required logic depends on it.
        If you aren't storing agent_id in the Withdraw model (it's not in fillable),
        you might not need this check unless you want to ensure the user has an upline.
        */
        // $referLog = ReferLog::where("referred_user_id", $user->id)->first();
        // if (!$referLog) {
        //     return response()->json(['success' => false, 'message' => 'Agent not found'], 404);
        // }

        DB::beginTransaction();

        try {
            // 3. Deduct from User immediately to lock funds
            $user->decrement('balance', $amount);

            // 4. Create Transaction Log (Reduction)
            // Ensure UserBalanceTransaction model exists and has these fields
            UserBalanceTransaction::create([
                'user_id' => $user->id,
                'type' => 2, // Or use class constant if available
                'amount' => $amount,
                'reason' => "Withdraw Request",
                'note' => $request->note, // Changed 'comment' to 'note' if that is your column, or keep 'comment'
                'status' => 0, 
                'which_type' => 'withdraw_request',
            ]);

            // 5. Create Withdraw Request Record
            $withdraw = Withdraw::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'payment_method' => $request->bank_name, // Mapped bank_name to payment_method
                'account_no' => $request->account_number, // Mapped account_number to account_no
                'account_name' => $request->account_name,
                'reason' => $request->note, // Mapped note to reason
                'status' => Withdraw::STATUS_PENDING, // Use the constant defined in model (0)
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Withdraw request submitted successfully.',
                'data' => $withdraw
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Transaction failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function dcLog()
    {
        $user_id = Auth::id();

        // Fetch Topups
        $topups = TopUp::where("user_id", $user_id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                $item->log_type = 'deposit';
                // Map status integers to readable strings if necessary
                return $item;
            });

        // Fetch Withdraws
        $withdraws = Withdraw::where("user_id", $user_id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                $item->log_type = 'withdraw';
                return $item;
            });

        // Merge and Sort
        $logs = $topups->concat($withdraws)
            ->sortByDesc('created_at')
            ->values();

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }
}