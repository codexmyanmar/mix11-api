<form method="GET" class="mb-6 bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700">
    <div class="flex flex-wrap gap-3 items-end">
        <!-- Date Range Filter -->
        <div class="flex-1 min-w-[150px]">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ရက်စွဲအပိုင်းအခြား</label>
            <select name="date_range" class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm" onchange="toggleDateRange()" id="dateRange">
                <option value="all" {{ request('date_range', 'all') == 'all' ? 'selected' : '' }}>အားလုံး</option>
                <option value="today" {{ request('date_range') == 'today' ? 'selected' : '' }}>ယနေ့</option>
                <option value="yesterday" {{ request('date_range') == 'yesterday' ? 'selected' : '' }}>မနေ့က</option>
                <option value="week" {{ request('date_range') == 'week' ? 'selected' : '' }}>ဤအပတ်</option>
                <option value="month" {{ request('date_range') == 'month' ? 'selected' : '' }}>ဤလ</option>
                <option value="year" {{ request('date_range') == 'year' ? 'selected' : '' }}>ဤနှစ်</option>
                <option value="custom" {{ request('date_range') == 'custom' ? 'selected' : '' }}>စိတ်ကြိုက်</option>
            </select>
        </div>
        
        <!-- Custom Date Range -->
        <div id="customDateRange" class="flex gap-2 {{ request('date_range') == 'custom' ? '' : 'hidden' }}">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">မှစတင်၍</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">မှ</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm">
            </div>
        </div>
        
        <!-- Status Filter -->
        <div class="flex-1 min-w-[150px]">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">အခြေအနေ</label>
            <select name="status_filter" class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm">
                <option value="all" {{ request('status_filter', 'all') == 'all' ? 'selected' : '' }}>အားလုံး</option>
                <option value="active" {{ request('status_filter') == 'active' ? 'selected' : '' }}>အသုံးပြုသူများ</option>
                <option value="banned" {{ request('status_filter') == 'banned' ? 'selected' : '' }}>ပိတ်သိမ်းခံထားရသူများ</option>
            </select>
        </div>
        
        <!-- Buttons -->
        <div class="flex gap-2">
            <button type="submit" class="inline-flex items-center justify-center rounded-md bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                စစ်ထုတ်မည်
            </button>
            <a href="{{ url()->current() }}" class="inline-flex items-center justify-center rounded-md bg-gray-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-400">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                ပြန်လည်စတင်မည်
            </a>
        </div>
    </div>
</form>

<script>
    function toggleDateRange() {
        const dateRange = document.getElementById('dateRange').value;
        const customDateRange = document.getElementById('customDateRange');
        if (dateRange === 'custom') {
            customDateRange.classList.remove('hidden');
        } else {
            customDateRange.classList.add('hidden');
        }
    }
</script>