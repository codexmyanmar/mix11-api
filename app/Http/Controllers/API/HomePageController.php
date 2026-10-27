<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Banner;
use App\Models\User;
use App\Models\Announcement;
use App\Models\PromotionADS;
use App\Models\Game;
use Illuminate\Support\Facades\Log;

class HomePageController extends Controller
{
    /**
     * Centralized provider configuration
     */
    private function getProviderConfig()
{
    return [
        'allowed_providers' => [
            // 'JILI', 'PP', 'PG', 'KINGKONG', 'ACEWIN', 'FC', 'GTF', 'RICH88',
            // 'LIVE22', 'BOLEGAMING', 'JDB', 'MIMI', 'YL', 'SPADE', 'DREAMGAMING',
            // 'BG', 'CASH88', 'DRAGOONSOFT', 'EVOLUTION', 'KINGMAKER', 'LADYLUCK',
            // 'YESBINGO', 'SPRIBE', 'SMARTSOFT', 'AB',
            // 'BTG', 'CG', 'CREEDROOMZ', 'FASTSPIN', 'FIVEG', 'HACKSAW', 
            // 'JOKER', 'OCTOPLAY', 'PLAY8', 'PLAYSTAR', 'RELAX', 'ROYALGAMING', 
            // 'YGGDRASIL'
            'JILI', 'PP',  'KINGKONG', 'ACEWIN', 'FC', 'GTF', 'RICH88',
            'LIVE22', 'BOLEGAMING', 'JDB', 'MIMI', 'YL', 'SPADE', 'DREAMGAMING',
            'BG', 'CASH88', 'DRAGOONSOFT', 'EVOLUTION', 'KINGMAKER', 'LADYLUCK',
            'YESBINGO', 'SPRIBE', 'SMARTSOFT', 'AB',
            'BTG', 'CG', 'CREEDROOMZ', 'FASTSPIN', 'FIVEG', 'HACKSAW', 
            'JOKER', 'OCTOPLAY', 'PLAY8', 'PLAYSTAR', 'RELAX', 'ROYALGAMING', 
            'BUFFALO789',
            'YGGDRASIL'
        ],
        'provider_images' => [
            'KINGKONG' => 'https://space-tech.sgp1.cdn.digitaloceanspaces.com/pg8688/ka%20gaming.gif',
            'RICH88' => config('app.url') . '/game_image/provider/1.gif',
            'JILI' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRk2jn8lGE9IedWoUi0KmFmYhCd9uPu96t7iw&s',
            'PP' => 'https://casino101.uk/wp-content/uploads/2023/01/PPLogo.jpeg',
            'AB' => 'https://zvz-commerce.com/game_image/provider/5000.gif',
            'PG' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSv7iEeKbj-XuzwCllfYpUJPby2BPabk6h3wg&s',
            'ACEWIN' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQzjfWzeL-7oyZxAV9lHSZ9WobUKM3T_oBj-w&s',
            'FC' => 'https://yt3.googleusercontent.com/ZVrRiDFCge0fPTknLNVyFGCq7NzxXojFshYaObF-TVsjMsM3Qz94XOHWxYRdyHCVXTfGh0Hm1jI=s900-c-k-c0x00ffffff-no-rj',
            'GTF' => 'https://space-tech.sgp1.cdn.digitaloceanspaces.com/pg8688/jdb.gif',
            'CQ9' => config('app.url') . '/game_image/provider/2.gif',
            'JDB' => 'https://ground.mpsimg.com/theme/images/gameIcon/JDB/KoiTrio.webp?t=1763377973824',
            'MIMI' => 'https://mimi-gaming.com/favicon.ico',
            'BOLEGAMING' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQLyajqWZFHoJjuiGWGK9KQcckXqMgZqlucqw&s',
            'LIVE22' => 'https://zvz-commerce.com/game_image/provider/LIVE22.jpg',
            'YL' => 'https://resources.egoffice4u.com/products/yl-fishing.png',
            'SPADE' => 'https://spade-gaming.org/wp-content/uploads/2025/11/image.png',
            'DREAMGAMING' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR_WaVjdduuA2PMLRzH9UMaDKqOuxNFyRG8vQ&s',
            'BG' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTPS1bliOCh79vyz_za4cbjXxOTYPGVgAsOPQ&s',
            'CASH88' => 'https://gameart.net/wp-content/uploads/2018/08/88Riches_main_web-1024x683.jpg',
            'DRAGOONSOFT' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTocvQcr0BiQwcReza7GCsUJRXkNwFEST_RTg&s',
            'EVOLUTION' => 'https://www.roger.com/wp-content/uploads/2019/11/evolution__logo-700x320.webp',
            'KINGMAKER' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ2CJA01G6PhnNipz2HH-Y-uc1ZvtT0-GFF3A&s',
            'LADYLUCK' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR2lITx8N_kZOBAqsOHfOQjkBqJmztS_19yqA&s',
            'YESBINGO' => 'https://www.yellowbat.com/wp-content/uploads/2024/08/Yellow-Bat-Hawkplays-Recommendation-Discover-Yellow-Bat%E2%84%A2.webp',
            'SPRIBE' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRXzE5_Srj42fkT_yNYGytgEDfIKHIEYg0MSg&s',
            'SMARTSOFT' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQFdmRlQoemPJueCsgZ1QYpB-NE0F_CNXvR5w&s',
            // Add images for new providers (you can use placeholder or actual images)
            'BTG' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTmxoNtZ4VCiLZYgRXWjENzbhuR1ngBIYJerM60LfQ9jA&s=10', // Add image URL for BTG
            'CG' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRqIZrqfwg0zdq8BWMU4KSMBh25PZFsdXPQe2-koXkKg2XdBQDn0UupDOVB&s=10', // Add image URL for CG
            'CREEDROOMZ' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTxTUOlRhpKuZKN4h1X26bqQjdOKjjk5EIzTpD-OxTrR6Xt8OIQfffBw9Yb&s=10', // Add image URL for CREEDROOMZ
            'FASTSPIN' => 'https://www.demoslot.com/wp-content/uploads/fastspin-banner.webp', // Add image URL for FASTSPIN
            'FIVEG' => 'https://www.568win.com/img/provider_img/5g-games/slot-5g-games-logo.png', // Add image URL for FIVEG
            'HACKSAW' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSYTlJTkBnYCSXAQl0_USqc3zo29t-Q2DlK9ZKV5RHwEZEHwa8C233zrgw&s=10', // Add image URL for HACKSAW
            'JOKER' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR9j-zFCi94m2Ph0T3ucnoQhXb6AF1bwABnkQ74l9kgiA&s=10', // Add image URL for JOKER
            'OCTOPLAY' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRRR4i-GRXwF0hz4tEUBXYoUBplvC3sigGlFCBxeHYdL4FcyxeQZr1pypI&s=10', // Add image URL for OCTOPLAY
            'PLAY8' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSbYceI8tQBZgQ601cXLt2yyOrB4EVzjeaJwJJefv43ig&s', // Add image URL for PLAY8
            'PLAYSTAR' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS-fowCKLo3hv0gyIHSEXuTDQO3Omgmh4ETNMhjSu-lS-vUXk8o3oH9sZw&s=10', // Add image URL for PLAYSTAR
            'RELAX' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSBuhFZPh-x0JzZeXPWPKTNBj3nDuXwGpfQQtRW5y5-4qHE4fmljxd62R4&s=10', // Add image URL for RELAX
            'ROYALGAMING' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTcL_JMfGSra5vlkG5i9yXf37lGrjV53d18w-uv1AGebA&s=10', // Add image URL for ROYALGAMING
            'YGGDRASIL' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQGQjkJHqwqCY19GAIPB6xaSCv-Af7NXPX3O66cwKCCxQ&s=10', // Add image URL for YGGDRASIL
            'BUFFALO789' => "https://sc.filehippo.net/images/t_app-icon-l/p/2d5e50e6-fa0c-4692-8d35-35b623a70d7e/2010454637/african-buffalo-logo",
        ],
        'provider_sort_order' => [
            'PP', 'JILI', 'BUFFALO789', 'AB', 'PG', 'ACEWIN', 'RICH88', 'FC', 'GTF', 
             'KINGKONG', 'BOLEGAMING', 'JDB', 'MIMI', 'YL', 'SPADE',
            'DREAMGAMING', 'BG', 'CASH88', 'DRAGOONSOFT', 'EVOLUTION',
            'KINGMAKER', 'LADYLUCK', 'YESBINGO', 'SPRIBE', 'SMARTSOFT',
            // Add new providers to sort order
            'BTG', 'CG', 'CREEDROOMZ', 'FASTSPIN', 'FIVEG', 'HACKSAW', 
            'JOKER', 'OCTOPLAY', 'PLAY8', 'PLAYSTAR', 'RELAX', 'ROYALGAMING', 
            'YGGDRASIL'
        ],
        'category_mapping' => [
            'slots' => ['Slot', 'Slots', 'vpslot', 'SLOT'],
            'fishing' => ['Fishing', 'fish', 'fh'],
            'fh' => ['Fishing', 'fish', 'fh'],
            'casino' => ['ECasino', 'LIVE', 'FH', 'TABLE'],
            'arcade' => ['Arcade', 'EGAME'],
            'rngtable' => ['TABLE'],
            'cards' => ['Poker', 'Poker/扑克', 'Mahjong', 'shan_bu'],
            'cardgame' => ['Poker', 'Poker/扑克', 'Mahjong', 'shan_bu'],
            'buffalo' => ['buffalo','buffalo_two'],
            'dice' => ['3'],
            'musthitby' => ['68'],
            'crash' => ['Crash'],
            'scratchcard' => ['5', '9'],
            'mine' => ['animal'],
            'bingo' => ['Bingo'],
            'mahjong' => ['Mahjong'],
            'mini game' => ['Mini Game'],
            'funky' => ['Funky'],
        ]
    ];
}

    /**
     * Get base game query with allowed providers
     */
    private function getBaseGameQuery()
    {
        $config = $this->getProviderConfig();
        return Game::whereIn('game_provider', $config['allowed_providers'])
            ->where('status', 1);
    }

    /**
     * Format game data for response
     */
    private function formatGameData($game)
    {
         
        if ($game->game_provider === 'BUFFALO789') {
                $providerName = 'BUFFALO789';
            } else {
                // For other providers, remove digits
                $providerName = preg_replace('/[0-9]/', '', $game->game_provider);
            }
       
        // Special case for AB -> BUFFALO
    if ($providerName === 'AB') {
        $providerName = 'BUFFALO';
    }

        return [
            'id' => $game->id,
            'title' => $game->game_name,
            'game_name' => $game->game_name,
            'category' => $game->game_type ? strtolower($game->game_type) : 'other',
            'type' => $game->game_type,
            'game_type' => $game->game_type,
            'image' => $game->image1 ?? $game->image2 ?? null,
            'image1' => $game->image1,
            'image2' => $game->image2,
            'provider' => $providerName,
            'game_provider' => $game->game_provider,
            'code' => $game->game_code,
            'game_code' => $game->game_code,
            'provider_game_id' => $game->provider_game_id ?? $game->game_code,
            'free_spin' => $game->free_spin ?? 0,
            'alias' => $game->game_alias ?? '-',
            'status' => $game->status,
            'game_specail' => $game->game_specail ?? null,
            'order' => $game->order ?? 0
        ];
    }

public function banner($user_code = null)
{
    try {
        $banners = collect();$domain = '';

        if ($user_code) {$user_id = $this->getUserIdFromUserCode($user_code);

            if ($user_id) {
                $banners = Banner::where('user_id',$user_id)
                    ->where('status', 1)
                    ->orderBy('id', 'desc')
                    ->get();

                if ($banners->isNotEmpty()) {$domain = 'https://agent.zvz-commerce.com/';
                }
            }
        }

        if ($banners->isEmpty()) {$banners = Banner::where('user_role', 1)
                ->where('status', 1)
                ->orderBy('id', 'desc')
                ->get();

            $domain = 'https://admin.zvz-commerce.com/';
        }

        // Properly modify each item in the Eloquent Collection
        $banners->each(function ($banner) use ($domain) {
            if (!empty($banner->image) && !str_starts_with($banner->image, 'http')) {
                // If you want to replace the image path directly:
                $banner->image = rtrim($domain, '/') . '/storage/' . ltrim($banner->image, '/');
                
                // OR if your model uses an accessor/appended attribute like `image_url`, 
                // you can set it here instead.
            }
        });

        return response()->json([
            'success' => true,
            'data' => $banners
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch banners',
            'data' => []
        ]);
    }
}

    public function announcement($user_code = null)
    {
        try {
            $announcements = collect();

            if ($user_code) {
                $user_id = $this->getUserIdFromUserCode($user_code);

                if ($user_id) {
                    $announcements = Announcement::where('user_id', $user_id)
                        ->where('status', 1)
                        ->orderBy('id', 'desc')
                        ->get();
                }
            }

            if ($announcements->isEmpty()) {
                $announcements = Announcement::where('user_role', 1)
                    ->where('status', 1)
                    ->orderBy('id', 'desc')
                    ->get();
            }

            return response()->json([
                'success' => true,
                'data' => $announcements
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch announcements',
                'data' => []
            ]);
        }
    }

    public function promotion($user_code = null)
    {
        try {
            $promotions = collect();

            if ($user_code) {
                $user_id = $this->getUserIdFromUserCode($user_code);

                if ($user_id) {
                    $promotions = PromotionADS::where('user_id', $user_id)
                        ->active()
                        ->sorted()
                        ->orderBy('id', 'desc')
                        ->get();
                }
            }

            if ($promotions->isEmpty()) {
                $promotions = PromotionADS::where('user_role', 1)
                    ->active()
                    ->sorted()
                    ->orderBy('id', 'desc')
                    ->get();
            }

            return response()->json([
                'success' => true,
                'data' => $promotions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch promotions',
                'data' => []
            ]);
        }
    }

    public function games(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 50);
            $category = $request->get('category', 'all');
            $provider = $request->get('provider');

            $query = $this->getBaseGameQuery();

            if ($category && $category !== 'all') {
                $config = $this->getProviderConfig();
                $categoryMapping = $config['category_mapping'];
                
                if (isset($categoryMapping[$category])) {
                    $query->whereIn('game_type', $categoryMapping[$category]);
                } else {
                    $query->whereRaw('LOWER(game_type) = ?', [strtolower($category)]);
                }
            }
            
            if ($provider) {
                $query->where('game_provider', strtoupper($provider));
            }

            $games = $query->inRandomOrder()->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Game list fetched successfully',
                'data' => collect($games->items())->map(fn($game) => $this->formatGameData($game))->toArray(),
                'pagination' => [
                    'current_page' => $games->currentPage(),
                    'last_page' => $games->lastPage(),
                    'per_page' => $games->perPage(),
                    'total' => $games->total(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch games',
                'data' => [],
                'pagination' => []
            ], 500);
        }
    }

    public function providers(Request $request)
    {
        try {
            $category = $request->get('category', 'all');
            $config = $this->getProviderConfig();
            
            $query = $this->getBaseGameQuery();
            
            if ($category && $category !== 'all') {
                $categoryMapping = $config['category_mapping'];
                if (isset($categoryMapping[$category])) {
                    $query->whereIn('game_type', $categoryMapping[$category]);
                } else {
                    $query->whereRaw('LOWER(game_type) = ?', [strtolower($category)]);
                }
            }
            
            $games = $query->get();
            
            $providers = [];
            foreach ($games as $game) {
                $provider = strtoupper(trim($game->game_provider));
                if (!isset($providers[$provider])) {
                    $providers[$provider] = [
                        'name' => $provider,
                        'image' => $config['provider_images'][$provider] ?? '',
                        'game_count' => 0
                    ];
                }
                $providers[$provider]['game_count']++;
            }
            
            $providersList = array_values($providers);
            $customOrder = $config['provider_sort_order'];
            
            usort($providersList, function($a, $b) use ($customOrder) {
                $posA = array_search($a['name'], $customOrder);
                $posB = array_search($b['name'], $customOrder);
                $posA = $posA === false ? count($customOrder) : $posA;
                $posB = $posB === false ? count($customOrder) : $posB;
                return $posA - $posB;
            });
            
            return response()->json([
                'success' => true,
                'data' => $providersList,
                'message' => 'Providers fetched successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch providers',
                'data' => []
            ], 500);
        }
    }

    public function providersByCategory(Request $request)
    {
        Log::info("providersByCategory");
        Log::info($request->all());
        
        try {
            $category = strtolower($request->get('category', 'all'));
            $config = $this->getProviderConfig();
            $categoryMapping = $config['category_mapping'];
            
            $query = $this->getBaseGameQuery();
            
            if ($category && $category !== 'all') {
                if (isset($categoryMapping[$category])) {
                    $query->whereIn('game_type', $categoryMapping[$category]);
                } else {
                    $query->whereRaw('LOWER(game_type) = ?', [strtolower($category)]);
                }
            }
            
            $providerCounts = $query->select('game_provider', \DB::raw('count(*) as game_count'))
                ->groupBy('game_provider')
                ->get();
            
            $providersList = [];
            foreach ($providerCounts as $provider) {
                $providerName = strtoupper(trim($provider->game_provider));
                $displayName = $providerName;
                $imageKey = $providerName;
                
                if ($providerName === 'AB') {
                    $displayName = 'BUFFALO';
                    $imageKey = 'AB';
                }
                
                $providersList[] = [
                    'name' => $displayName,
                    'image' => $config['provider_images'][$imageKey] ?? '',
                    'game_count' => $provider->game_count
                ];
            }
            
            $providerOrder = [
                'BUFFALO789','BUFFALO', 'JILI', 'PP', 'JDB', 'PG', 'ACEWIN', 'FC', 'GTF', 
                'LIVE22', 'RICH88', 'KINGKONG', 'BOLEGAMING', 'MIMI', 'YL', 
                'SPADE', 'DREAMGAMING', 'BG', 'CASH88', 'DRAGOONSOFT', 'EVOLUTION',
                'KINGMAKER', 'LADYLUCK', 'YESBINGO', 'SPRIBE', 'SMARTSOFT'
            ];
            
            usort($providersList, function($a, $b) use ($providerOrder) {
                $posA = array_search($a['name'], $providerOrder);
                $posB = array_search($b['name'], $providerOrder);
                $posA = $posA === false ? count($providerOrder) : $posA;
                $posB = $posB === false ? count($providerOrder) : $posB;
                return $posA - $posB;
            });
            
            return response()->json([
                'success' => true,
                'data' => $providersList,
                'message' => 'Providers fetched successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch providers: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    public function category()
    {
        try {
            return response()->json([
                [
                    'id' => 'buffalo',
                    'name_mm' => 'ကျွဲဂိမ်း',
                    'name_en' => 'buffalo',
                    'type' => "game",
                    'aliases' => ['buffalo'],
                    'icon' => 'https://zvz-commerce.com/public/game_image/provider/buffalo.png'
                ],
                [
                    'id' => 'casino',
                    'name_mm' => 'ဖဲ',
                    'name_en' => 'Casino',
                    'type' => "casino",
                    'aliases' => ['shan_bu'],
                    'icon' => 'https://space-tech.sgp1.cdn.digitaloceanspaces.com/hulk333/general/icon_g_casino.webp'
                ],
                [
                    'id' => 'slot',
                    'name_mm' => 'စလော့',
                    'name_en' => 'Slot',
                    'aliases' => ['slot', 'slots', 'Slot', 'SLOT'],
                    'type' => "game",
                    'icon' => 'https://space-tech.sgp1.cdn.digitaloceanspaces.com/hulk333/general/icon_g_slot.webp'
                ],
                [
                    'id' => 'fish',
                    'name_mm' => 'ငါးပစ်',
                    'name_en' => 'Fish',
                    'aliases' => ['fish', 'fishing', 'Fish', 'FISH'],
                    'type' => "game",
                    'icon' => 'https://space-tech.sgp1.cdn.digitaloceanspaces.com/hulk333/general/icon_g_fish.webp'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([]);
        }
    }

    public function gamesByProvider(Request $request)
    {
        Log::info("gamesByProvider");
        Log::info($request->all());

        try {
            $perPage = $request->get('per_page', 30);
            $page = $request->get('page', 1);
            $provider = $request->get('provider');
            $category = $request->get('category');
            $config = $this->getProviderConfig();
            
            if (!$provider) {
                return response()->json([
                    'success' => false,
                    'message' => 'Provider is required'
                ], 400);
            }
            
            $dbProvider = strtoupper($provider);
            if ($dbProvider === 'BUFFALO') {
                $dbProvider = 'AB';
            }
            
            $query = $this->getBaseGameQuery()
                ->where('game_provider', $dbProvider);
            
            if ($category) {
                $categoryLower = strtolower(trim($category));
                $categoryMapping = $config['category_mapping'];
                
                if (isset($categoryMapping[$categoryLower])) {
                    $query->whereIn('game_type', $categoryMapping[$categoryLower]);
                } else {
                    $query->where('game_type', $category);
                }
            }
            
            $games = $query->orderBy('order', 'asc')
                ->orderByRaw('CASE WHEN image1 IS NOT NULL AND image1 != "" THEN 0 ELSE 1 END')
                ->orderBy('id', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);
            
            return response()->json([
                'success' => true,
                'message' => 'Games fetched successfully',
                'data' => collect($games->items())->map(fn($game) => $this->formatGameData($game))->toArray(),
                'pagination' => [
                    'current_page' => $games->currentPage(),
                    'last_page' => $games->lastPage(),
                    'per_page' => $games->perPage(),
                    'total' => $games->total(),
                    'has_more' => $games->currentPage() < $games->lastPage()
                ],
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch games: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    public function gamesByCategory(Request $request)
    {
        Log::info("gamesByCategory");
        Log::info($request->all());
        
        try {
            $perPage = $request->get('per_page', 28);
            $category = strtolower($request->get('category', 'all'));
            $provider = $request->get('provider');
            $config = $this->getProviderConfig();
            $categoryMapping = $config['category_mapping'];
            
            $query = $this->getBaseGameQuery();
            
            if ($category && $category !== 'all') {
                if (isset($categoryMapping[$category])) {
                    $query->whereIn('game_type', $categoryMapping[$category]);
                } else {
                    $query->whereRaw('LOWER(game_type) = ?', [strtolower($category)]);
                }
            }
            
            if ($provider) {
                $query->where('game_provider', $provider);
            }
            
            $query->orderByRaw('CASE WHEN image1 IS NOT NULL AND image1 != "" THEN 0 ELSE 1 END')
                  ->orderBy('id', 'desc');
            
            $games = $query->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'message' => 'Games fetched successfully by category',
                'data' => collect($games->items())->map(fn($game) => $this->formatGameData($game))->toArray(),
                'current_category' => $category,
                'pagination' => [
                    'current_page' => $games->currentPage(),
                    'last_page' => $games->lastPage(),
                    'per_page' => $games->perPage(),
                    'total' => $games->total(),
                    'has_more' => $games->currentPage() < $games->lastPage(),
                ],
            ]);
            
        } catch (\Exception $e) {
            Log::error("Error in gamesByCategory: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch games by category',
                'error' => $e->getMessage(),
                'data' => [],
                'pagination' => []
            ], 500);
        }
    }

    public function gamesByCasino(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 28);
            $gameCodes = ['twone', 'jdnn'];
            
            $query = $this->getBaseGameQuery()
                ->whereIn('game_code', $gameCodes)
                ->orderByRaw("FIELD(game_code, 'twone', 'jdnn')")
                ->orderByRaw('CASE WHEN image1 IS NOT NULL AND image1 != "" THEN 0 ELSE 1 END')
                ->orderBy('id', 'desc');
            
            $games = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Games fetched successfully by casino',
                'data' => collect($games->items())->map(fn($game) => $this->formatGameData($game))->toArray(),
                'game_codes' => $gameCodes,
                'pagination' => [
                    'current_page' => $games->currentPage(),
                    'last_page' => $games->lastPage(),
                    'per_page' => $games->perPage(),
                    'total' => $games->total(),
                    'has_more' => $games->currentPage() < $games->lastPage(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch games by casino',
                'error' => $e->getMessage(),
                'data' => [],
                'pagination' => []
            ], 500);
        }
    }

    private function getUserIdFromUserCode($refer_code)
    {
        try {
            $user = User::where('user_id', $refer_code)->first();
            return $user ? $user->id : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function asia_royal_home()
    {
        try {
            $categories = [
                'hot' => [
                    'name' => 'Hot Game',
                    'type' => 'hot',
                    'limit' => 16,
                    'game_types' => []
                ],
                'buffalo' => [
                    'name' => 'Buffalo Game',
                    'type' => 'buffalo',
                    'limit' => 16,
                    'game_types' => []
                ],
                'fishing' => [
                    'name' => 'Fishing Game',
                    'type' => 'fishing',
                    'limit' => 16,
                    'game_types' => ['Fishing', 'fish', 'fh', 'FISH']
                ],
                'slot' => [
                    'name' => 'Slot Game',
                    'type' => 'slot',
                    'limit' => 16,
                    'game_types' => ['Slot', 'Slots', 'vpslot', 'SLOT']
                ],
                'popular' => [
                    'name' => 'Popular Game',
                    'type' => 'popular',
                    'limit' => 16,
                    'game_types' => []
                ],
            ];

            $responseData = [];

            foreach ($categories as $key => $category) {
                $query = $this->getBaseGameQuery();

                if (!empty($category['game_types'])) {
                    $query->whereIn('game_type', $category['game_types']);
                }

                switch ($key) {
                    case 'hot':
                        $query->orderByRaw('CASE WHEN game_specail IS NOT NULL AND game_specail != "" THEN 0 ELSE 1 END')
                              ->orderByRaw('CASE WHEN image1 IS NOT NULL AND image1 != "" THEN 0 ELSE 1 END')
                              ->inRandomOrder();
                        break;
                    case 'popular':
                        $query->orderBy('order', 'asc')->inRandomOrder();
                        break;
                    case 'buffalo':
                        $query->where(function ($q) {
                            $q->where('game_name', 'LIKE', '%Buffalo%')
                              ->orWhere('game_name', 'LIKE', '%buffalo%');
                        })
                        ->orderByRaw("CASE WHEN game_provider = 'AB' THEN 0 ELSE 1 END")
                        ->orderByRaw('CASE WHEN image1 IS NOT NULL AND image1 != "" THEN 0 ELSE 1 END')
                        ->orderBy('id', 'desc');
                        break;
                    case 'fishing':
                        $query->orderByRaw('CASE WHEN image1 IS NOT NULL AND image1 != "" THEN 0 ELSE 1 END')
                              ->orderBy('id', 'desc');
                        break;
                    case 'slot':
                        $query->orderByRaw('CASE WHEN image1 IS NOT NULL AND image1 != "" THEN 0 ELSE 1 END')
                              ->inRandomOrder();
                        break;
                    default:
                        $query->orderBy('id', 'desc');
                        break;
                }

                $games = $query->limit($category['limit'])->get();

                $responseData[] = [
                    'category_id' => $key,
                    'category_name' => $category['name'],
                    'type' => $category['type'],
                    'games' => collect($games)->map(fn($game) => $this->formatGameData($game))->toArray(),
                    'total_games' => count($games)
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Asia Royal Home data fetched successfully',
                'data' => $responseData
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in asia_royal_home: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch Asia Royal Home data: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }
}