<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
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

        // Generate unique user_id function (ppauser + 6 random digits)
        $generateUniqueUserId = function() {
            do {
                $randomNumber = random_int(100000, 999999);
                $userId = 'ppauser' . $randomNumber;
                $exists = DB::table('users')->where('user_id', $userId)->exists();
            } while ($exists);
            return $userId;
        };

        // 1. Create roles in spatie permission tables
        $roles = ['admin', 'super', 'senior', 'master', 'agent', 'user'];
        
        foreach ($roles as $roleName) {
            DB::table('roles')->updateOrInsert(
                ['name' => $roleName, 'guard_name' => 'web'],
                ['name' => $roleName, 'guard_name' => 'web', 'updated_at' => now()]
            );
        }

        // 2. Create Admin User (Top level - ID 1)
        $adminId = DB::table('users')->where('email', 'admin@example.com')->value('id');
        
        if (!$adminId) {
            $adminId = DB::table('users')->insertGetId([
                'user_id' => $generateUniqueUserId(),
                'name' => 'System Admin',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'refer_code' => $generateUniqueCode(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // Assign admin role
        $roleId = DB::table('roles')->where('name', 'admin')->value('id');
        DB::table('model_has_roles')->updateOrInsert(
            ['role_id' => $roleId, 'model_type' => 'App\\Models\\User', 'model_id' => $adminId],
            ['role_id' => $roleId, 'model_type' => 'App\\Models\\User', 'model_id' => $adminId]
        );

        // 3. Create Super User (referred by admin)
        $superId = DB::table('users')->where('email', 'super@example.com')->value('id');
        
        if (!$superId) {
            $superId = DB::table('users')->insertGetId([
                'user_id' => $generateUniqueUserId(),
                'name' => 'Super User',
                'email' => 'super@example.com',
                'password' => Hash::make('password'),
                'refer_code' => $generateUniqueCode(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // Assign super role
        $roleId = DB::table('roles')->where('name', 'super')->value('id');
        DB::table('model_has_roles')->updateOrInsert(
            ['role_id' => $roleId, 'model_type' => 'App\\Models\\User', 'model_id' => $superId],
            ['role_id' => $roleId, 'model_type' => 'App\\Models\\User', 'model_id' => $superId]
        );
        
        // Create referral log: admin referred super
        DB::table('refer_logs')->updateOrInsert(
            ['referrer_user_id' => $adminId, 'referred_user_id' => $superId],
            ['referrer_user_id' => $adminId, 'referred_user_id' => $superId, 'created_at' => now(), 'updated_at' => now()]
        );

        // 4. Create Senior User (referred by super)
        $seniorId = DB::table('users')->where('email', 'senior@example.com')->value('id');
        
        if (!$seniorId) {
            $seniorId = DB::table('users')->insertGetId([
                'user_id' => $generateUniqueUserId(),
                'name' => 'Senior User',
                'email' => 'senior@example.com',
                'password' => Hash::make('password'),
                'refer_code' => $generateUniqueCode(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // Assign senior role
        $roleId = DB::table('roles')->where('name', 'senior')->value('id');
        DB::table('model_has_roles')->updateOrInsert(
            ['role_id' => $roleId, 'model_type' => 'App\\Models\\User', 'model_id' => $seniorId],
            ['role_id' => $roleId, 'model_type' => 'App\\Models\\User', 'model_id' => $seniorId]
        );
        
        // Create referral log: super referred senior
        DB::table('refer_logs')->updateOrInsert(
            ['referrer_user_id' => $superId, 'referred_user_id' => $seniorId],
            ['referrer_user_id' => $superId, 'referred_user_id' => $seniorId, 'created_at' => now(), 'updated_at' => now()]
        );

        // 5. Create Master User (referred by senior)
        $masterId = DB::table('users')->where('email', 'master@example.com')->value('id');
        
        if (!$masterId) {
            $masterId = DB::table('users')->insertGetId([
                'user_id' => $generateUniqueUserId(),
                'name' => 'Master User',
                'email' => 'master@example.com',
                'password' => Hash::make('password'),
                'refer_code' => $generateUniqueCode(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // Assign master role
        $roleId = DB::table('roles')->where('name', 'master')->value('id');
        DB::table('model_has_roles')->updateOrInsert(
            ['role_id' => $roleId, 'model_type' => 'App\\Models\\User', 'model_id' => $masterId],
            ['role_id' => $roleId, 'model_type' => 'App\\Models\\User', 'model_id' => $masterId]
        );
        
        // Create referral log: senior referred master
        DB::table('refer_logs')->updateOrInsert(
            ['referrer_user_id' => $seniorId, 'referred_user_id' => $masterId],
            ['referrer_user_id' => $seniorId, 'referred_user_id' => $masterId, 'created_at' => now(), 'updated_at' => now()]
        );

        // 6. Create Agent User (referred by master)
        $agentId = DB::table('users')->where('email', 'agent@example.com')->value('id');
        
        if (!$agentId) {
            $agentId = DB::table('users')->insertGetId([
                'user_id' => $generateUniqueUserId(),
                'name' => 'Agent User',
                'email' => 'agent@example.com',
                'password' => Hash::make('password'),
                'refer_code' => $generateUniqueCode(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // Assign agent role
        $roleId = DB::table('roles')->where('name', 'agent')->value('id');
        DB::table('model_has_roles')->updateOrInsert(
            ['role_id' => $roleId, 'model_type' => 'App\\Models\\User', 'model_id' => $agentId],
            ['role_id' => $roleId, 'model_type' => 'App\\Models\\User', 'model_id' => $agentId]
        );
        
        // Create referral log: master referred agent
        DB::table('refer_logs')->updateOrInsert(
            ['referrer_user_id' => $masterId, 'referred_user_id' => $agentId],
            ['referrer_user_id' => $masterId, 'referred_user_id' => $agentId, 'created_at' => now(), 'updated_at' => now()]
        );

        // 7. Create Regular User (referred by agent)
        $userId = DB::table('users')->where('email', 'user@example.com')->value('id');
        
        if (!$userId) {
            $userId = DB::table('users')->insertGetId([
                'user_id' => $generateUniqueUserId(),
                'name' => 'Regular User',
                'email' => 'user@example.com',
                'password' => Hash::make('password'),
                'refer_code' => $generateUniqueCode(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // Assign user role
        $roleId = DB::table('roles')->where('name', 'user')->value('id');
        DB::table('model_has_roles')->updateOrInsert(
            ['role_id' => $roleId, 'model_type' => 'App\\Models\\User', 'model_id' => $userId],
            ['role_id' => $roleId, 'model_type' => 'App\\Models\\User', 'model_id' => $userId]
        );
        
        // Create referral log: agent referred user
        DB::table('refer_logs')->updateOrInsert(
            ['referrer_user_id' => $agentId, 'referred_user_id' => $userId],
            ['referrer_user_id' => $agentId, 'referred_user_id' => $userId, 'created_at' => now(), 'updated_at' => now()]
        );

        // Display results in console
        $this->command->info('=== Referral System Seeded Successfully ===');
        $this->command->info('');
        
        $users = DB::table('users')->whereIn('email', [
            'admin@example.com', 'super@example.com', 'senior@example.com', 
            'master@example.com', 'agent@example.com', 'user@example.com'
        ])->get();
        
        $this->command->info('Users and their details:');
        foreach ($users as $user) {
            $this->command->info("  {$user->name} ({$user->email}): user_id={$user->user_id}, refer_code={$user->refer_code}");
        }
        
        $this->command->info('');
        $this->command->info('Referral Hierarchy:');
        $this->command->info('  admin@example.com (top level)');
        $this->command->info('    ↓ referred by admin');
        $this->command->info('  super@example.com');
        $this->command->info('    ↓ referred by super');
        $this->command->info('  senior@example.com');
        $this->command->info('    ↓ referred by senior');
        $this->command->info('  master@example.com');
        $this->command->info('    ↓ referred by master');
        $this->command->info('  agent@example.com');
        $this->command->info('    ↓ referred by agent');
        $this->command->info('  user@example.com');
    }
}