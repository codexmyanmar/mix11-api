<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportantDataSeeder extends Seeder
{
    public function run(): void
    {
        // ==================== ANNOUNCEMENTS ====================
        $this->command->info('Seeding announcements...');
        
        DB::statement("INSERT INTO `announcements` (`id`, `user_id`, `sort`, `user_role`, `text`, `status`, `created_at`, `updated_at`) VALUES
            (1, 2, 99, 1, 'PG8688 မှကြိုဆိုပါသည်။ အကောင့်အသစ်များအတွက် အထူးသီးသန့် 50% ဘောနပ်စ်ကို ရယူလိုက်ပါ။', 1, '2026-02-15 23:22:25', '2026-02-15 23:22:25'),
            (2, 5, 99, 4, 'PG8688 မှကြိုဆိုပါသည်။ အကောင့်အသစ်များအတွက် အထူးသီးသန့် 50% ဘောနပ်စ်ကို ရယူလိုက်ပါ။', 1, '2026-02-15 23:22:44', '2026-02-15 23:22:44')");

        // ==================== BANNERS ====================
        $this->command->info('Seeding banners...');
        
        DB::statement("INSERT INTO `banners` (`id`, `user_id`, `sort`, `user_role`, `image`, `status`, `created_at`, `updated_at`) VALUES
            (1, 2, 999, 1, 'banners/01KHJCG6A6QXSKS8VM8JMFP4HA.jpg', 1, '2026-02-15 22:19:21', '2026-02-15 22:19:29'),
            (2, 5, 99, 4, 'banners/01KHJCHG0VJT88VNYEWMW8CSSN.jpg', 1, '2026-02-15 22:20:03', '2026-02-15 22:20:03'),
            (3, 2, 998, 1, 'banners/01KHJHZD408E57YYW4DY49WYK4.webp', 1, '2026-02-15 23:55:02', '2026-02-15 23:55:02')");

        // ==================== GAMES (Import from SQL file) ====================
        $this->command->info('Seeding games from SQL file...');
        
        // Path to the SQL file in public/seeders directory
        $sqlPath = public_path('seeders/games.sql');
        
        // Check if file exists
        if (File::exists($sqlPath)) {
            // Read the SQL file
            $sql = File::get($sqlPath);
            
            // Split multiple queries if needed (for safety)
            // Remove comments and empty lines
            $sql = preg_replace('/--.*$/m', '', $sql); // Remove SQL comments
            $sql = preg_replace('/\s+/', ' ', $sql); // Remove extra whitespace
            $sql = trim($sql);
            
            // Execute the SQL
            try {
                DB::unprepared($sql);
                $this->command->info('Games imported successfully from games.sql!');
            } catch (\Exception $e) {
                $this->command->error('Error importing games: ' . $e->getMessage());
                
                // Alternative: Try to execute line by line if the file has multiple INSERTs
                $this->command->info('Trying alternative method...');
                $queries = explode(';', $sql);
                foreach ($queries as $query) {
                    $query = trim($query);
                    if (!empty($query)) {
                        try {
                            DB::unprepared($query);
                        } catch (\Exception $e) {
                            $this->command->error('Query failed: ' . $e->getMessage());
                        }
                    }
                }
            }
        } else {
            $this->command->error('games.sql file not found at: ' . $sqlPath);
            $this->command->info('Please place your games.sql file in: ' . public_path('seeders/'));
        }

        // ==================== PAYMENT METHODS ====================
        $this->command->info('Seeding payment methods...');
        
        DB::statement("INSERT INTO `payment_methods` (`id`, `user_id`, `bank_name`, `icon`, `type`, `status`, `name`, `account_no`, `min_amount`, `max_amount`, `created_at`, `updated_at`) VALUES
            (1, 5, 'KBZ Pay', 'payment-methods/01KH1KMST0X8W6G2PBQHE7MP8T.png', '2', 1, 'Thaw Zin Soe', '09403077739', '5000', '1000000', '2026-02-09 09:57:06', '2026-02-09 09:57:06'),
            (2, 5, 'Wave Money', 'payment-methods/01KH1KNJS1TX9A84PJDD982606.jpeg', '2', 1, 'Thaw Zin Soe', '09403077739', '5000', '1000000', '2026-02-09 09:57:32', '2026-02-09 09:57:32')");

        $this->command->info('====================================');
        $this->command->info('All important data seeded successfully!');
        $this->command->info('====================================');
    }
}