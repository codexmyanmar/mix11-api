<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium">System Notifications</h3>
                <x-filament::badge color="primary">
                    {{ $notifications->count() }} New
                </x-filament::badge>
            </div>
            
            @if($notifications->count() > 0)
                <div class="space-y-3">
                    @foreach($notifications as $notification)
                        <div class="flex items-start space-x-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-800">
                            <div class="flex-1">
                                <p class="text-sm">{{ $notification->data['message'] ?? 'New notification' }}</p>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ $notification->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-6">
                    <p class="text-gray-500">No new notifications</p>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>