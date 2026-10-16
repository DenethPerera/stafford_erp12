<section>
    <header class="mb-4">
        <h2 class="h5 fw-bold text-dark mb-1">
            {{ __('Delete Account') }}
        </h2>
        <p class="text-secondary fw-semibold small mb-0">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </p>
    </header>

    <button type="button" class="btn btn-danger fw-bolder px-4 py-2 shadow-sm rounded-3 text-uppercase" style="letter-spacing: 0.05em;" data-bs-toggle="modal" data-bs-target="#confirmUserDeletionModal">
        {{ __('Delete Account') }}
    </button>

    <!-- Modal -->
    <div class="modal fade {{ $errors->userDeletion->isNotEmpty() ? 'show' : '' }}" id="confirmUserDeletionModal" tabindex="-1" aria-labelledby="confirmUserDeletionModalLabel" aria-hidden="true" {!! $errors->userDeletion->isNotEmpty() ? 'style="display: block;"' : '' !!}>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <form method="post" action="{{ route('profile.destroy') }}">
                    @csrf
                    @method('delete')
                    
                    <div class="modal-header border-bottom-0 pb-0">
                        <h5 class="modal-title fw-bold text-dark" id="confirmUserDeletionModalLabel">
                            {{ __('Are you sure you want to delete your account?') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <div class="modal-body py-3">
                        <p class="text-secondary fw-semibold small mb-4">
                            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                        </p>

                        <div class="mb-2">
                            <label for="password" class="visually-hidden">{{ __('Password') }}</label>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                class="form-control rounded-3 {{ $errors->userDeletion->has('password') ? 'is-invalid' : '' }}"
                                placeholder="{{ __('Password') }}"
                            />
                            @error('password', 'userDeletion')
                                <div class="invalid-feedback fw-semibold mt-1">
                                    <i class="bi bi-exclamation-circle-fill me-1"></i>{{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="modal-footer border-top-0 pt-0 gap-2">
                        <button type="button" class="btn btn-light fw-bold text-secondary rounded-3 px-4 py-2 border" data-bs-dismiss="modal">
                            {{ __('Cancel') }}
                        </button>

                        <button type="submit" class="btn btn-danger fw-bolder px-4 py-2 shadow-sm rounded-3">
                            {{ __('Delete Account') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    @if($errors->userDeletion->isNotEmpty())
        <div class="modal-backdrop fade show"></div>
    @endif
</section>
