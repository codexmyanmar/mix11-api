<x-filament-panels::page>
    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::DASHBOARD_HEADER_START) }}
    
    <div class="space-y-6">
        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::DASHBOARD_HEADER_BEFORE) }}
        
        @if ($this->hasFilters())
            <div class="grid grid-cols-1 gap-4">
                {{ $this->filtersForm() }}
            </div>
        @endif
        
        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::DASHBOARD_HEADER_AFTER) }}
        
        @if ($this->hasHeaderWidgets())
            <div class="space-y-4">
                {{ $this->headerWidgets() }}
            </div>
        @endif
        
        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::DASHBOARD_BODY_BEFORE) }}
        
        <div class="space-y-4">
            @if ($this->hasWidgets())
                <div class="space-y-4">
                    {{ $this->widgets() }}
                </div>
            @endif
        </div>
        
        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::DASHBOARD_BODY_AFTER) }}
        
        @if ($this->hasFooterWidgets())
            <div class="space-y-4">
                {{ $this->footerWidgets() }}
            </div>
        @endif
        
        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::DASHBOARD_FOOTER_AFTER) }}
    </div>
    
    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::DASHBOARD_FOOTER_END) }}
</x-filament-panels::page>