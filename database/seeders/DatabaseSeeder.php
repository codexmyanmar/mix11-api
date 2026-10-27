<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    // Remove this line: use WithoutModelEvents;

    public function run(): void
    {
        // Call RoleSeeder first
        $this->call(RoleSeeder::class);
        
       
        
        // Generate unique 6-digit referral code function
     $generateUniqueCode = function() {
            do {
                // Generate 6 character alphanumeric code (0-9, A-Z)
                $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                $code = '';
                for ($i = 0; $i < 6; $i++) {
                    $code .= $characters[random_int(0, strlen($characters) - 1)];
                }
                $exists = DB::table('users')->where('refer_code', $code)->exists();
            } while ($exists);
            return $code;
        };
        
   
        
        // Display all users summary
        $this->command->info('');
        $this->command->info('=== All Users Summary ===');
        $allUsers = DB::table('users')->select('id', 'name', 'email', 'refer_code')->get();
        
        foreach ($allUsers as $user) {
            $this->command->info("ID: {$user->id} | Name: {$user->name} | Email: {$user->email} | Refer Code: {$user->refer_code} || Password : password");
        }
        
        // Display referral statistics
        $this->command->info('');
        $this->command->info('=== Referral Statistics ===');
        $referralCounts = DB::table('refer_logs')
            ->select('referrer_user_id', DB::raw('count(*) as total_referrals'))
            ->groupBy('referrer_user_id')
            ->get();
        
        foreach ($referralCounts as $stat) {
            $referrer = DB::table('users')->where('id', $stat->referrer_user_id)->first();
            if ($referrer) {
                $this->command->info("{$referrer->email} referred {$stat->total_referrals} user(s)");
            }
        }
        
        
         $this->call(ImportantDataSeeder::class);
    }
}