<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-4">
            <!-- Header -->
            <div>
               
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                   Registration Link</p>
            </div>
            
            <!-- Referral Link Box -->
            <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-4">
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                    <div class="flex-1">
                        <div class="flex items-center rounded-md bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 px-3 py-2">
                            <span class="text-sm text-gray-500 dark:text-gray-400 mr-2">လင့်ခ်:</span>
                            <code class="flex-1 text-sm text-blue-600 dark:text-blue-400 break-all" id="referralUrl">
                                {{ $referralUrl }}
                            </code>
                        </div>
                    </div>
                    <div>
                        <button 
                            type="button"
                            onclick="var urlText = document.getElementById('referralUrl').innerText; var tempInput = document.createElement('input'); tempInput.value = urlText; document.body.appendChild(tempInput); tempInput.select(); document.execCommand('copy'); document.body.removeChild(tempInput); alert('လင့်ခ် ကူးယူပြီးပါပြီ။');"
                            class="inline-flex items-center justify-center rounded-md bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path>
                            </svg>
                            ကူးယူမည်
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>