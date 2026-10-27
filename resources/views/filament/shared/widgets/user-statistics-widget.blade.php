<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-4">
            <!-- Header -->
            <div class="flex items-center justify-between flex-wrap gap-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    အသုံးပြုသူစာရင်းအင်း
                </h3>
                
                <!-- Filters -->
                <form method="GET" class="flex flex-wrap gap-3" id="filterForm">
                    <!-- Date Range Filter -->
                    <select 
                        name="date_range" 
                        class="rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm focus:border-primary-500 focus:ring-primary-500"
                        onchange="toggleDateRange()"
                        id="dateRange"
                    >
                        <option value="all" {{ $dateRange == 'all' ? 'selected' : '' }}>အားလုံး</option>
                        <option value="today" {{ $dateRange == 'today' ? 'selected' : '' }}>ယနေ့</option>
                        <option value="yesterday" {{ $dateRange == 'yesterday' ? 'selected' : '' }}>မနေ့က</option>
                        <option value="week" {{ $dateRange == 'week' ? 'selected' : '' }}>ဤအပတ်</option>
                        <option value="month" {{ $dateRange == 'month' ? 'selected' : '' }}>ဤလ</option>
                        <option value="year" {{ $dateRange == 'year' ? 'selected' : '' }}>ဤနှစ်</option>
                        <option value="custom" {{ $dateRange == 'custom' ? 'selected' : '' }}>စိတ်ကြိုက်</option>
                    </select>
                    
                    <!-- Custom Date Range -->
                    <div id="customDateRange" class="flex gap-2 {{ $dateRange == 'custom' ? '' : 'hidden' }}">
                        <input 
                            type="date" 
                            name="start_date" 
                            value="{{ $startDate }}"
                            class="rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm"
                        />
                        <span class="text-gray-500">မှ</span>
                        <input 
                            type="date" 
                            name="end_date" 
                            value="{{ $endDate }}"
                            class="rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm"
                        />
                    </div>
                    
                    <!-- Status Filter -->
                    <select 
                        name="status_filter" 
                        class="rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm focus:border-primary-500 focus:ring-primary-500"
                        onchange="this.form.submit()"
                    >
                        <option value="all" {{ $statusFilter == 'all' ? 'selected' : '' }}>အားလုံး</option>
                        <option value="active" {{ $statusFilter == 'active' ? 'selected' : '' }}>အသုံးပြုသူများ</option>
                        <option value="banned" {{ $statusFilter == 'banned' ? 'selected' : '' }}>ပိတ်သိမ်းခံထားရသူများ</option>
                    </select>
                    
                    <button 
                        type="submit"
                        class="inline-flex items-center justify-center rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500"
                    >
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        စစ်ထုတ်မည်
                    </button>
                    
                    <a 
                        href="{{ url()->current() }}"
                        class="inline-flex items-center justify-center rounded-md bg-gray-500 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-400"
                    >
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        ပြန်လည်စတင်မည်
                    </a>
                </form>
            </div>
            
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <!-- Total Users -->
                <div class="rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">စုစုပေါင်းအသုံးပြုသူ</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">
                                {{ number_format($stats['total_users']) }}
                            </p>
                        </div>
                        <div class="rounded-full bg-blue-100 dark:bg-blue-900/20 p-3">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <!-- Active Users -->
                <div class="rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">အသုံးပြုသူများ</p>
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">
                                {{ number_format($stats['active_users']) }}
                            </p>
                        </div>
                        <div class="rounded-full bg-green-100 dark:bg-green-900/20 p-3">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <!-- Banned Users -->
                <div class="rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">ပိတ်သိမ်းခံထားရသူများ</p>
                            <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">
                                {{ number_format($stats['banned_users']) }}
                            </p>
                        </div>
                        <div class="rounded-full bg-red-100 dark:bg-red-900/20 p-3">
                            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <!-- Total Balance -->
                <div class="rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">စုစုပေါင်းငွေလက်ကျန်</p>
                            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-1">
                                {{ number_format($stats['total_balance']) }}
                            </p>
                        </div>
                        <div class="rounded-full bg-purple-100 dark:bg-purple-900/20 p-3">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <!-- Total TopUp -->
                <div class="rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">စုစုပေါင်းဖြည့်သွင်းငွေ</p>
                            <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">
                                {{ number_format($stats['total_topup']) }}
                            </p>
                        </div>
                        <div class="rounded-full bg-yellow-100 dark:bg-yellow-900/20 p-3">
                            <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <!-- Total Withdraw -->
                <div class="rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">စုစုပေါင်းထုတ်ယူငွေ</p>
                            <p class="text-2xl font-bold text-orange-600 dark:text-orange-400 mt-1">
                                {{ number_format($stats['total_withdraw']) }}
                            </p>
                        </div>
                        <div class="rounded-full bg-orange-100 dark:bg-orange-900/20 p-3">
                            <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

<script>
    function toggleDateRange() {
        const dateRange = document.getElementById('dateRange').value;
        const customDateRange = document.getElementById('customDateRange');
        
        if (dateRange === 'custom') {
            customDateRange.classList.remove('hidden');
        } else {
            customDateRange.classList.add('hidden');
            document.getElementById('filterForm').submit();
        }
    }
</script>