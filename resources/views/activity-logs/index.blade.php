@extends('layouts.app')

@section('header', 'Activity Logs')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h5 fw-bolder text-dark mb-0">System Activity</h2>
    <div class="badge bg-white text-secondary border rounded-pill px-3 py-2 shadow-sm fw-semibold">
        Showing {{ $activities->firstItem() ?? 0 }} to {{ $activities->lastItem() ?? 0 }} of {{ $activities->total() }} records
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="py-3 px-4 text-muted text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Time</th>
                    <th class="py-3 px-4 text-muted text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">User</th>
                    <th class="py-3 px-4 text-muted text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Action</th>
                    <th class="py-3 px-4 text-muted text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Subject</th>
                    <th class="py-3 px-4 text-muted text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Changes</th>
                </tr>
            </thead>
            <tbody>
                @forelse($activities as $activity)
                    <tr>
                        <td class="py-3 px-4 text-nowrap">
                            <div class="text-secondary small fw-semibold">{{ $activity->created_at->format('Y-m-d H:i') }}</div>
                            <div class="text-muted" style="font-size: 10px;">{{ $activity->created_at->diffForHumans() }}</div>
                        </td>
                        <td class="py-3 px-4">
                            @if($activity->causer)
                                <span class="fw-bold text-dark small">{{ $activity->causer->name }}</span>
                            @else
                                <span class="fw-bold text-muted small fst-italic">System</span>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            @php
                                $badgeClass = match($activity->description) {
                                    'created' => 'bg-success bg-opacity-10 text-success',
                                    'updated' => 'bg-warning bg-opacity-25 text-dark',
                                    'deleted' => 'bg-danger bg-opacity-10 text-danger',
                                    default => 'bg-light border text-secondary',
                                };
                            @endphp
                            <span class="badge rounded-pill px-2 py-1 text-uppercase fw-bolder {{ $badgeClass }}" style="font-size: 10px;">
                                {{ $activity->description }}
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <span class="fw-bold text-secondary small">
                                {{ class_basename($activity->subject_type) }} #{{ $activity->subject_id }}
                            </span>
                        </td>
                        <td class="py-3 px-4 small text-secondary" style="max-width: 300px;">
                            @if(isset($activity->properties['attributes']))
                                <div class="bg-light p-2 rounded-3 border font-monospace text-truncate" style="font-size: 11px;" title="{{ json_encode($activity->properties, JSON_PRETTY_PRINT) }}">
                                    @foreach($activity->properties['attributes'] as $key => $value)
                                        @if(isset($activity->properties['old']) && array_key_exists($key, $activity->properties['old']))
                                            <div class="text-truncate">
                                                <span class="fw-bold text-muted">{{ $key }}:</span> 
                                                <span class="text-danger text-decoration-line-through">{{ $activity->properties['old'][$key] }}</span> 
                                                <i class="bi bi-arrow-right text-muted mx-1"></i> 
                                                <span class="text-success">{{ is_array($value) ? json_encode($value) : $value }}</span>
                                            </div>
                                        @else
                                            <div class="text-truncate">
                                                <span class="fw-bold text-muted">{{ $key }}:</span> 
                                                <span class="text-success">{{ is_array($value) ? json_encode($value) : $value }}</span>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @else
                                <span class="fst-italic text-muted">No properties recorded</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-5 text-center text-muted fw-semibold small">
                            No activity logged yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($activities->hasPages())
        <div class="card-footer bg-white border-top p-3">
            {{ $activities->links() }}
        </div>
    @endif
</div>
@endsection
