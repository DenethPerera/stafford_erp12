@extends('layouts.app')

@section('header', 'CPSC API Tokens')

@section('content')
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
    <div>
        <h2 class="h5 fw-bolder text-dark mb-1">CPSC API Tokens</h2>
        <p class="text-muted small mb-0">Manage CPSC credentials and API tokens linked to system users</p>
    </div>
    <button type="button" class="btn fw-bold rounded-pill shadow-sm d-flex align-items-center px-4" 
            style="background: linear-gradient(135deg, #124E78, #00A39A); color: white; border: none;"
            data-bs-toggle="modal" data-bs-target="#addTokenModal">
        <i class="bi bi-plus-lg me-2"></i> Add CPSC Token
    </button>
</div>

@if(session('success'))
    <div class="alert alert-success d-flex align-items-center fw-semibold rounded-3 mb-4 border-0 bg-success bg-opacity-10 text-success" role="alert">
        <i class="bi bi-check-circle-fill me-2 fs-5"></i>
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger d-flex align-items-center fw-semibold rounded-3 mb-4 border-0 bg-danger bg-opacity-10 text-danger" role="alert">
        <i class="bi bi-exclamation-octagon-fill me-2 fs-5"></i>
        {{ session('error') }}
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger rounded-3 mb-4 border-0 bg-danger bg-opacity-10 text-danger">
        <div class="d-flex align-items-center fw-semibold mb-1">
            <i class="bi bi-exclamation-octagon-fill me-2 fs-5"></i>
            Please fix the following errors:
        </div>
        <ul class="mb-0 small ps-4">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="py-3 px-4 text-muted text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">User</th>
                    <th class="py-3 px-4 text-muted text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">API Secret</th>
                    <th class="py-3 px-4 text-muted text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Token</th>
                    <th class="py-3 px-4 text-muted text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Expires </th>
                    <th class="py-3 px-4 text-muted text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Created</th>
                    <th class="py-3 px-4 text-muted text-uppercase fw-bolder text-end" style="font-size: 11px; letter-spacing: 1px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tokens as $token)
                    @php
                        $isExpired = $token->expires_at && $token->expires_at->isPast();
                        $isSoon = $token->expires_at && !$isExpired && $token->expires_at->diffInDays(now()) <= 7;
                    @endphp
                    <tr>
                        <td class="py-3 px-4">
                            @if($token->user)
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary fw-bold d-flex align-items-center justify-content-center me-2" style="width: 34px; height: 34px; font-size: 12px;">
                                        {{ strtoupper(substr($token->user->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark small">{{ $token->user->name }}</div>
                                        <div class="text-muted" style="font-size: 11px;">{{ $token->user->email }}</div>
                                    </div>
                                </div>
                            @else
                                <span class="badge bg-light text-secondary rounded-pill px-3 py-1 border fw-semibold">Global / Unassigned</span>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            <div class="d-flex align-items-center gap-2">
                                <span class="font-monospace small text-secondary" id="secret-preview-{{ $token->id }}">
                                    ••••••••••••{{ substr($token->api_secret, -4) }}
                                </span>
                                <button type="button" class="btn btn-sm btn-link text-muted p-0" title="Copy Secret" onclick="navigator.clipboard.writeText('{{ addslashes($token->api_secret) }}'); this.innerHTML='<i class=\'bi bi-check-lg text-success\'></i>'; setTimeout(() => this.innerHTML='<i class=\'bi bi-clipboard\'></i>', 1500);">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                        </td>
                        <td class="py-3 px-4" style="max-width: 250px;">
                            <div class="d-flex align-items-center gap-2">
                                <span class="font-monospace text-truncate small text-dark" style="max-width: 180px;" title="{{ $token->token }}">
                                    {{ Str::limit($token->token, 25, '...') }}
                                </span>
                                <button type="button" class="btn btn-sm btn-link text-muted p-0" title="Copy Token" onclick="navigator.clipboard.writeText('{{ addslashes($token->token) }}'); this.innerHTML='<i class=\'bi bi-check-lg text-success\'></i>'; setTimeout(() => this.innerHTML='<i class=\'bi bi-clipboard\'></i>', 1500);">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                        </td>
                        <td class="py-3 px-4">
                            <div>
                                <span class="fw-semibold text-dark small">
                                    {{ $token->expires_at ? $token->expires_at->format('M d, Y H:i') : 'N/A' }}
                                </span>
                                @if($isExpired)
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill ms-1" style="font-size: 10px;">Expired</span>
                                @elseif($isSoon)
                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill ms-1" style="font-size: 10px;">Expiring Soon</span>
                                @else
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill ms-1" style="font-size: 10px;">Active</span>
                                @endif
                            </div>
                        </td>
                        <td class="py-3 px-4">
                            <span class="text-muted small">{{ $token->created_at ? $token->created_at->format('M d, Y') : '-' }}</span>
                        </td>
                        <td class="py-3 px-4 text-end">
                            <button type="button" 
                                    class="btn btn-sm btn-light text-danger rounded-circle d-inline-flex align-items-center justify-content-center" 
                                    style="width: 32px; height: 32px;"
                                    title="Delete Token"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#confirmModal" 
                                    data-action="{{ route('cpsc-tokens.destroy', $token) }}"
                                    data-message="Are you sure you want to delete this CPSC Token?"
                                    data-submessage="The user will no longer be able to use this credential for CPSC API communication.">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="text-muted">
                                <i class="bi bi-key fs-1 d-block mb-3 text-secondary opacity-50"></i>
                                <h6 class="fw-bold text-dark mb-1">No CPSC Tokens Found</h6>
                                <p class="small text-muted mb-3">Add a new CPSC token and assign it to a user.</p>
                                <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addTokenModal">
                                    <i class="bi bi-plus-lg me-1"></i> Add Token
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($tokens->hasPages())
        <div class="card-footer bg-white border-0 py-3 px-4">
            {{ $tokens->links() }}
        </div>
    @endif
</div>

<!-- Add CPSC Token Modal -->
<div class="modal fade" id="addTokenModal" tabindex="-1" aria-labelledby="addTokenModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 px-4 pt-4 pb-2">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 42px; height: 42px; background: linear-gradient(135deg, rgba(18, 78, 120, 0.15), rgba(0, 163, 154, 0.15)); color: #124E78;">
                        <i class="bi bi-key-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="addTokenModalLabel">Add CPSC API Token</h5>
                        <p class="text-muted small mb-0">Assign CPSC credentials to a user</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('cpsc-tokens.store') }}" method="POST">
                @csrf
                <div class="modal-body px-4 py-3">
                    <!-- User Selection -->
                    <div class="mb-3">
                        <label for="user_id" class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">
                            Select User <span class="text-danger">*</span>
                        </label>
                        <select name="user_id" id="user_id" required class="form-select bg-light border-light fw-semibold text-dark shadow-sm px-3 py-2">
                            <option value="" disabled {{ old('user_id') ? '' : 'selected' }}>-- Choose a user --</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}" {{ old('user_id') == $u->id ? 'selected' : '' }}>
                                    {{ $u->name }} ({{ $u->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- API Secret -->
                    <div class="mb-3">
                        <label for="api_secret" class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">
                            API Secret <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="password" name="api_secret" id="api_secret" value="{{ old('api_secret') }}" required
                                   placeholder="Enter API Secret"
                                   class="form-control bg-light border-light fw-semibold text-dark shadow-sm px-3 py-2">
                            <button class="btn btn-light border-light text-secondary" type="button" id="toggleSecretBtn" onclick="toggleSecretVisibility()">
                                <i class="bi bi-eye" id="toggleSecretIcon"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Token -->
                    <div class="mb-3">
                        <label for="token" class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">
                            API Token <span class="text-danger">*</span>
                        </label>
                        <textarea name="token" id="token" rows="3" required
                                  placeholder="Enter or paste CPSC API Token..."
                                  class="form-control bg-light border-light fw-semibold text-dark shadow-sm px-3 py-2">{{ old('token') }}</textarea>
                    </div>

                    <!-- Expires At -->
                    <div class="mb-3">
                        <label for="expires_at" class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">
                            Expires  <span class="text-danger">*</span>
                        </label>
                        <input type="datetime-local" name="expires_at" id="expires_at" value="{{ old('expires_at') }}" required
                               class="form-control bg-light border-light fw-semibold text-dark shadow-sm px-3 py-2">
                    </div>
                </div>

                <div class="modal-footer border-0 px-4 pb-4 pt-2">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold text-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn fw-bold rounded-pill shadow-sm px-4" style="background: linear-gradient(135deg, #124E78, #00A39A); color: white; border: none;">
                        <i class="bi bi-check-lg me-1"></i> Save Token
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function toggleSecretVisibility() {
        const input = document.getElementById('api_secret');
        const icon = document.getElementById('toggleSecretIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    }
</script>
@endsection
