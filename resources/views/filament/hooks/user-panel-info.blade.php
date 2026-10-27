<div class="flex items-center gap-x-4">
    {{-- Balance Display --}}
    <div class="hidden sm:flex items-center gap-x-2 px-3 py-1 bg-gray-100 dark:bg-white/5 rounded-lg border border-gray-200 dark:border-white/10">
        <x-heroicon-o-wallet class="w-4 h-4 text-primary-500" />
        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
            {{ __('Balance') }}: 
            <span class="text-primary-600 dark:text-primary-400 font-bold">
                {{ number_format(auth()->user()?->balance ?? 0, 2) }}
            </span>
        </span>
    </div>

    {{-- Language Switcher --}}
    <div class="flex items-center gap-x-2 text-sm font-medium text-gray-500 dark:text-gray-400">
        <a href="/lang/en" 
           class="hover:text-primary-500 {{ app()->getLocale() === 'en' ? 'text-primary-600 font-bold underline' : '' }}">
            EN
        </a>
        <span>|</span>
        <a href="/lang/my" 
           class="hover:text-primary-500 {{ app()->getLocale() === 'my' ? 'text-primary-600 font-bold underline' : '' }}">
            MM
        </a>
    </div>
</div>