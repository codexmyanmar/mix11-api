<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use App\Models\ReferLog; 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log; 

class AuthController extends Controller
{
    
    public function balance(){
        $user = Auth::user();
        return $user ? $user->balance : 0;
    }
    
    public function user_id(){
         $user = Auth::user();
        return $user ? $user->user_id : 0;
    }
    
    public function login(Request $request)
    {
        Log::info($request->all());

        $request->validate([
            'login' => 'required|string', 
            'password' => 'required|string',
        ]);
        
        // Determine login type
        $loginType = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'user_id';

        // Find user
        $user = User::where($loginType, $request->login)->first();

        // Check credentials
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid login details'
            ], 401);
        }

        // Check Role
        if (! $user->hasRole('user')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access. Only standard users can login here.'
            ], 403);
        }

        // Check Status
        if ($user->status == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Account is deactivated'
            ], 403);
        }

        // Create Token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user // Optionally return user info
        ]);
    }


    public function logout(Request $request)
    {
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'User not found or already logged out'
        ], 401);
    }

    public function register(Request $request)
    {
        Log::info($request->all());
        try {
            // 1. Validate Input manually to catch errors in the same block if needed
            // NOTE: In Postman, ensure you send header 'Accept: application/json'
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'phone' => 'required|string|unique:users,phone',
                'password' => 'required|string|min:6|confirmed', // expects password_confirmation field
                'referral_code' => 'nullable|string|exists:users,refer_code', // Changed from user_id to refer_code
            ]);
    
            DB::beginTransaction(); // Start transaction
    
            // 2. Generate Unique User ID
            $prefix = env('APP_USER_PREFIX', 'mix');
            $generatedId = null;
            do {
                $tempId = $prefix . 'user' . mt_rand(100000, 999999);
                if (!User::where('user_id', $tempId)->exists()) {
                    $generatedId = $tempId;
                }
            } while (!$generatedId);
    
            // 3. Generate Unique Referral Code (6 character alphanumeric)
            $referCode = null;
            do {
                $tempCode = $this->generateUniqueReferCode();
                if (!User::where('refer_code', $tempCode)->exists()) {
                    $referCode = $tempCode;
                }
            } while (!$referCode);
    
            // 4. Create User
            $user = User::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'user_id' => $generatedId,
                'status' => 1, // Active by default
                'balance' => 0,
                'refer_code' => $referCode, // Generated unique alphanumeric code
            ]);
    
            // 5. Assign Role
            // Ensure Spatie permissions are set up correctly
            $user->assignRole('user');
    
            // 6. Handle Referral Logic
            // If code provided, use it. If NOT, use default system user (ID 1)
            $referrer = null;
    
            if ($request->referral_code) {
                $referrer = User::where('refer_code', $request->referral_code)->first();
            } else {
                // FALLBACK: Use default user (e.g., ID 1) as referrer if no code provided
                $referrer = User::find(1); 
            }
    
            if ($referrer) {
                ReferLog::create([
                    'referrer_user_id' => $referrer->id,
                    'referred_user_id' => $user->id,
                    // 'created_at' and 'updated_at' are handled automatically by Eloquent
                ]);
            }
    
            DB::commit(); // Commit changes
    
            // 7. Auto Login (Generate Token)
            $token = $user->createToken('auth_token')->plainTextToken;
    
            return response()->json([
                'success' => true,
                'message' => 'Registration successful',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user
            ], 201);
    
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            // Log the error for server-side debugging
            Log::error("Registration Error: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Registration failed. Please try again.',
                'debug_message' => $e->getMessage() // Remove this in production!
            ], 500);
        }
    }


    private function generateUniqueReferCode()
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        $length = 6;
        
        for ($i = 0; $i < $length; $i++) {
            $randomIndex = random_int(0, strlen($characters) - 1);
            $code .= $characters[$randomIndex];
        }
        
        return $code;
    }



    public function profile(){
        $user_id = Auth::user()->id;
        $data = User::where("id",$user_id)->first();
        return response()->json([
            'success' => true,
            'message' => $data
        ], 200);
    }

    public function passwordChange(Request $request)
    {
        try {
            // Validate input
            $validated = $request->validate([
                'old_password' => 'required|string',
                'new_password' => 'required|string|min:6|confirmed', 
                // expects new_password_confirmation field
            ]);

            // Get logged in user
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            // Check old password
            if (!Hash::check($request->old_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Old password is incorrect'
                ], 400);
            }

            // Prevent same password reuse
            if (Hash::check($request->new_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'New password cannot be same as old password'
                ], 400);
            }

            // Update password
            $user->update([
                'password' => Hash::make($request->new_password)
            ]);

            // OPTIONAL: logout all tokens
            $user->tokens()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully. Please login again.'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error("Password Change Error: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Password change failed'
            ], 500);
        }
    }


}