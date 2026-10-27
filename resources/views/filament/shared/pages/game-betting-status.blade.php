<x-filament-panels::page>
    {{-- Page Title --}}
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('ဂိမ်းအမြတ်အစွန်းအစီရင်ခံစာ') }}</h2>
    </div>

    {{-- Date Range Filter --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 p-4 bg-white dark:bg-gray-800 rounded-lg shadow">
        <div>
            <label class="block text-sm font-medium text-white dark: mb-2">{{ __('စတင်ရက်') }}</label>
            <input 
                type="date" 
                wire:model.live="start_date" 
                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500"
            >
        </div>
        <div>
            <label class="block text-sm font-medium text-white dark: mb-2">{{ __('နောက်ဆုံးရက်') }}</label>
            <input 
                type="date" 
                wire:model.live="end_date" 
                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500"
            >
        </div>
    </div>
    
    {{-- Summary Table --}}
    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-white uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th class="px-6 py-3 text-white">{{ __('ပလက်ဖောင်း') }}</th>
                    <th class="px-6 py-3 text-right text-white">{{ __('စုစုပေါင်းထိုးငွေ') }}</th>
                    <th class="px-6 py-3 text-right text-white">{{ __('စုစုပေါင်းအနိုင်ရငွေ') }}</th>
                    <th class="px-6 py-3 text-right text-white">{{ __('အမြတ်/အရှုံး') }}</th>
                </td>
            </thead>
            <tbody>
                @forelse($this->getSummaryData() as $row)
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-white whitespace-nowrap">
                            {{ strtoupper($row->provider) }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            {{ number_format($row->total_bet, 2) }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            {{ number_format($row->total_user_win, 2) }}
                        </td>
                        <td class="px-6 py-4 text-right font-semibold {{ $row->net_profit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ number_format($row->net_profit, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            {{ __('အချက်အလက်မရှိပါ') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if($this->getSummaryData()->count() > 0)
            <tfoot class="bg-gray-100 dark:bg-gray-700 font-bold">
                <tr>
                    <td class="px-6 py-3 text-gray-900 dark:text-white">{{ __('စုစုပေါင်း') }}</td>
                    <td class="px-6 py-3 text-right text-gray-900 dark:text-white">
                        {{ number_format($this->getTotalSummary()->total_bet, 2) }}
                    </td>
                    <td class="px-6 py-3 text-right text-gray-900 dark:text-white">
                        {{ number_format($this->getTotalSummary()->total_user_win, 2) }}
                    </td>
                    <td class="px-6 py-3 text-right {{ $this->getTotalSummary()->net_profit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ number_format($this->getTotalSummary()->net_profit, 2) }}
                    </td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>

    {{-- Footer Calculation Summary --}}
    @php
        $summaryData = $this->getSummaryData();
        $totalSummary = $this->getTotalSummary();
    @endphp

 
</x-filament-panels::page>