<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Http;

class SystemHealthWidget extends Widget
{
    protected string $view = 'filament.widgets.system-health';

    protected int|string|array $columnSpan = 'full';

    public ?array $health = null;

    public function mount(): void
    {
        $this->refreshHealth();
    }

    public function refreshHealth(): void
    {
        $this->health = [
            'safehaven' => $this->probe(url('/health/safehaven')),
            'pawapay' => $this->probe(url('/health/pawapay')),
            'oxr' => $this->probe(url('/health/oxr')),
            'queue' => $this->queueSummary(),
        ];
    }

    protected function probe(string $url): array
    {
        try {
            $resp = Http::timeout(5)->get($url);
            return [
                'ok' => $resp->successful(),
                'status' => $resp->status(),
                'body' => $resp->json() ?? $resp->body(),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    protected function queueSummary(): array
    {
        // Placeholder; Horizon API could be queried here in future
        return [
            'ok' => true,
            'note' => 'TODO: integrate Horizon metrics',
        ];
    }
}
