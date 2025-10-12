@php
    $url = url('/horizon');
@endphp
<div class="p-6">
    <div class="rounded-lg border bg-white dark:bg-gray-900 dark:border-gray-800 p-6">
        <h2 class="text-lg font-semibold mb-2">Laravel Horizon</h2>
        <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
            View queue metrics, jobs, and throughput in Horizon. Opens in a new tab.
        </p>
        <a href="{{ $url }}" target="_blank" rel="noopener"
           class="inline-flex items-center px-4 py-2 rounded-md bg-primary-600 text-white hover:bg-primary-700">
            Open Horizon
        </a>
    </div>
</div>
