<details class="card mb-4">
<summary class="card-header text-primary">Corrections / History</summary>
<div class="card-body">@forelse($history as $entry)<div class="border-bottom py-2">
<strong>{{ $entry->description }}</strong> ? {{ $entry->properties->get('actor','') }} <span class="text-muted">{{ $entry->created_at }}</span>
<details>
<summary>View recorded values</summary>
<pre class="small bg-light p-2" style="max-height:300px;overflow:auto">{{ json_encode($entry->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
</details>
</div>@empty<p class="text-muted mb-0">No changes recorded by this module yet.</p>@endforelse {{ $history->links('pagination::bootstrap-5') }}</div>
</details>
