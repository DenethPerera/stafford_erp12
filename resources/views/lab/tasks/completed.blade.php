@extends('layouts.app')

@section('header', 'Submitted Reports')

@section('content')
<div x-data="labCompletedTasksApp()" x-init="init()" class="d-flex flex-column gap-4">

    <!-- Notification Toast -->
    <div x-show="notification.show" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform translate-y-2"
         class="position-fixed top-0 end-0 p-3" style="z-index: 1060;" x-cloak>
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden" 
             :class="notification.type === 'success' ? 'bg-success text-white' : 'bg-danger text-white'" 
             style="min-width: 320px;">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi fs-5" :class="notification.type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'"></i>
                    <div>
                        <div class="fw-bold" x-text="notification.title"></div>
                        <div class="small opacity-90" x-text="notification.message"></div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-link text-white p-0 border-0" @click="notification.show = false">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <div class="row g-3 align-items-end">
                <!-- Search Input -->
                <div class="col-md-6">
                    <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">
                        <i class="bi bi-search text-primary me-1"></i> Search Task
                    </label>
                    <div class="position-relative">
                        <input type="text" 
                               class="form-control bg-light border-light fw-semibold rounded-pill ps-4 pe-5 py-2" 
                               placeholder="PO #, style, product, factory..." 
                               x-model="searchTerm" 
                               @input.debounce.300ms="filterRecords()">
                        <button x-show="searchTerm" @click="searchTerm = ''; filterRecords()" class="btn btn-sm btn-link text-muted position-absolute end-0 top-50 translate-middle-y me-3 text-decoration-none p-0">
                            <i class="bi bi-x-circle-fill"></i>
                        </button>
                    </div>
                </div>

                <!-- Searchable Sync Status Selector (Type & Select) -->
                <div class="col-md-4 position-relative" x-data="{
                    open: false,
                    query: '',
                    options: [
                        { value: 'all', label: 'All Sync States' },
                        { value: 'rejected', label: 'Rejected by Admin (Needs Revision)' },
                        { value: 'synced', label: 'Synced (Locked)' },
                        { value: 'pending', label: 'Pending Sync (Editable)' },
                        { value: 'failed', label: 'Sync Failed (Editable)' }
                    ],
                    get filteredOptions() {
                        if (!this.query.trim()) return this.options;
                        const q = this.query.toLowerCase().trim();
                        return this.options.filter(o => o.label.toLowerCase().includes(q));
                    },
                    select(val) {
                        syncFilter = val;
                        this.open = false;
                        this.query = '';
                        filterRecords();
                    }
                }" @click.away="open = false">
                    <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">
                        <i class="bi bi-cloud-check text-primary me-1"></i> Sync Status
                    </label>
                    <div class="position-relative">
                        <button type="button" 
                                class="form-control bg-light border-light fw-semibold rounded-pill text-start ps-3 pe-4 py-2 d-flex align-items-center justify-content-between shadow-none" 
                                @click="open = !open; if(open) $nextTick(() => $refs.syncSearchInput?.focus())">
                            <span class="text-truncate" x-text="options.find(o => o.value === syncFilter)?.label || 'All Sync States'"></span>
                            <i class="bi bi-chevron-down text-muted" style="font-size: 11px;"></i>
                        </button>
                    </div>

                    <!-- Searchable Dropdown Popup -->
                    <div x-show="open" 
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 transform scale-95"
                         x-transition:enter-end="opacity-100 transform scale-100"
                         class="dropdown-menu show shadow-lg border-0 rounded-4 p-2 mt-1 w-100 position-absolute z-3" 
                         style="max-height: 250px; overflow: hidden; display: flex; flex-direction: column;" 
                         x-cloak>
                        <div class="p-1 mb-2">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light border-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" 
                                       x-ref="syncSearchInput" 
                                       x-model="query" 
                                       placeholder="Type to filter..." 
                                       class="form-control bg-light border-0 fw-semibold">
                                <button type="button" x-show="query" @click="query = ''" class="btn btn-light border-0 text-muted">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                        </div>

                        <div class="overflow-y-auto flex-grow-1" style="max-height: 180px;">
                            <template x-for="opt in filteredOptions" :key="opt.value">
                                <button type="button" 
                                        class="dropdown-item rounded-3 py-2 fw-semibold d-flex align-items-center justify-content-between mb-1" 
                                        :class="syncFilter === opt.value ? 'bg-primary text-white' : ''"
                                        @click="select(opt.value)">
                                    <span x-text="opt.label"></span>
                                    <i x-show="syncFilter === opt.value" class="bi bi-check-lg ms-2"></i>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Reset Button -->
                <div class="col-md-2 text-md-end">
                    <button type="button" class="btn btn-light border rounded-pill fw-bold px-3 py-2 w-100 shadow-sm" @click="resetFilters()">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Completed Tasks Listing Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bolder text-dark mb-0 d-flex align-items-center gap-2">
                    <span>Submitted Laboratory Tasks</span>
                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1 fw-bold" style="font-size: 12px;" x-text="filteredRecords.length + ' Tasks'"></span>
                </h5>
                <p class="text-muted small mb-0">View submitted qualification reports. Synced records are locked; unsynced reports can be edited and re-saved.</p>
            </div>
            <div>
                <button type="button" class="btn btn-sm btn-light border rounded-pill px-3 fw-bold shadow-sm" @click="fetchTasks()">
                    <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-uppercase text-muted" style="font-size: 11px; letter-spacing: 0.5px;">
                        <th class="py-3 px-4">PO & Certificate</th>
                        <th class="py-3 px-3">Product / Style</th>
                        <th class="py-3 px-3">Factory</th>
                        <th class="py-3 px-3">Test Report Details</th>
                        <th class="py-3 px-3 text-center">Sync Status</th>
                        <th class="py-3 px-4 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="isLoading">
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <div class="spinner-border text-primary mb-2" role="status"></div>
                                <div class="fw-semibold small">Loading submitted laboratory tasks...</div>
                            </td>
                        </tr>
                    </template>

                    <template x-if="!isLoading && filteredRecords.length === 0">
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-clipboard2-x fs-1 text-muted opacity-50 d-block mb-2"></i>
                                <div class="fw-bold">No Submitted Tasks Found</div>
                                <div class="small">Go to "All Details" to add test reports for purchase orders.</div>
                            </td>
                        </tr>
                    </template>

                    <template x-for="task in filteredRecords" :key="task.id">
                        <tr>
                            <!-- PO & Cert -->
                            <td class="py-3 px-4">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white flex-shrink-0" 
                                         style="width: 32px; height: 32px; background: linear-gradient(135deg, #124E78, #00A39A);">
                                        <i class="bi bi-patch-check"></i>
                                    </div>
                                    <div>
                                        <span class="fw-bolder text-dark" x-text="task.poNumber || 'N/A'"></span>
                                        <div class="text-muted small" style="font-size: 10px;">
                                            <span class="fw-semibold text-primary" x-text="task.certNo || task.certType"></span>
                                            <span x-show="task.createdAt"> &bull; <span x-text="task.createdAt"></span></span>
                                        </div>
                                        <template x-if="task.status === 'Rejected'">
                                            <div class="mt-1">
                                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-2 py-0.5 fw-bold d-inline-flex align-items-center gap-1" style="font-size: 10px;">
                                                    <i class="bi bi-x-circle-fill"></i> Rejected by Admin
                                                </span>
                                                <template x-if="task.rejectionReason">
                                                    <div class="text-danger small mt-0.5 fw-semibold" style="font-size: 10px;" :title="task.rejectionReason">
                                                        <i class="bi bi-chat-left-dots me-1"></i><span x-text="task.rejectionReason"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </td>

                            <!-- Product / Style -->
                            <td class="py-3 px-3">
                                <div class="fw-bold text-dark small text-truncate d-inline-block" style="max-width: 200px;" x-text="task.productName || 'N/A'" :title="task.productName"></div>
                                <div class="text-muted small" style="font-size: 10px;">
                                    <span>Style: </span><span class="fw-bold" x-text="task.styleNo || '-'"></span>
                                </div>
                            </td>

                            <!-- Factory -->
                            <td class="py-3 px-3">
                                <span class="fw-semibold text-secondary small text-truncate d-inline-block" style="max-width: 160px;" x-text="task.factoryName || 'N/A'" :title="task.factoryName"></span>
                            </td>

                            <!-- Test Report Details -->
                            <td class="py-3 px-3">
                                <template x-if="task.tests && task.tests.length > 0">
                                    <div>
                                        <div class="fw-bold text-dark small">
                                            <span x-text="task.tests[0].testReportId ? 'ID: ' + task.tests[0].testReportId : 'Report on file'"></span>
                                        </div>
                                        <div class="text-muted small" style="font-size: 10px;">
                                            <span x-show="task.tests[0].testDate">Date: <span x-text="task.tests[0].testDate"></span></span>
                                            <span x-show="task.tests[0].citations && task.tests[0].citations.length > 0">
                                                &bull; <span class="text-primary fw-semibold" x-text="task.tests[0].citations.length + ' citation(s)'"></span>
                                            </span>
                                        </div>
                                        <template x-if="task.tests[0].testReportFile">
                                            <div class="mt-1">
                                                <a :href="getTestFileUrl(task.tests[0])" 
                                                   target="_blank" 
                                                   class="badge bg-light text-primary border border-primary border-opacity-25 text-decoration-none d-inline-flex align-items-center gap-1"
                                                   style="font-size: 10px;">
                                                    <i class="bi bi-paperclip"></i>
                                                    <span x-text="task.tests[0].testReportFileName || 'Attached Report'"></span>
                                                </a>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="!task.tests || task.tests.length === 0">
                                    <span class="text-muted small fst-italic">No test entries</span>
                                </template>
                            </td>

                            <!-- Sync Status -->
                            <td class="py-3 px-3 text-center">
                                <template x-if="task.isSynced">
                                    <span class="badge bg-success text-white rounded-pill px-3 py-1 fw-bold shadow-sm d-inline-flex align-items-center gap-1" style="font-size: 11px;">
                                        <i class="bi bi-lock-fill"></i> Synced (Locked)
                                    </span>
                                </template>
                                <template x-if="!task.isSynced">
                                    <span class="badge bg-warning bg-opacity-25 text-dark rounded-pill px-3 py-1 fw-semibold d-inline-flex align-items-center gap-1" style="font-size: 11px;">
                                        <i class="bi bi-unlock"></i> Pending Sync
                                    </span>
                                </template>
                            </td>

                            <!-- Actions -->
                            <td class="py-3 px-4 text-end">
                                <div class="d-inline-flex align-items-center gap-1">
                                    <!-- Edit or View Details Button -->
                                    <template x-if="task.isSynced">
                                        <button type="button" 
                                                class="btn btn-sm btn-light border rounded-pill px-3 fw-bold text-secondary shadow-sm d-inline-flex align-items-center gap-1"
                                                @click="openModal(task, true)">
                                            <i class="bi bi-eye"></i> View Details
                                        </button>
                                    </template>
                                    <template x-if="!task.isSynced">
                                        <button type="button" 
                                                class="btn btn-sm rounded-pill px-3 fw-bold shadow-sm d-inline-flex align-items-center gap-1"
                                                :class="task.status === 'Rejected' ? 'btn-danger' : 'btn-primary'"
                                                @click="openModal(task, false)">
                                            <i class="bi" :class="task.status === 'Rejected' ? 'bi-arrow-repeat' : 'bi-pencil-square'"></i>
                                            <span x-text="task.status === 'Rejected' ? 'Revise & Resubmit' : 'Edit & Save'"></span>
                                        </button>
                                    </template>

                                    <!-- PDF Export -->
                                    <a :href="`{{ url('stafford-certs') }}/${task.id}/export-pdf`" 
                                       class="btn btn-sm btn-light border rounded-circle d-inline-flex align-items-center justify-content-center text-secondary shadow-sm" 
                                       style="width: 32px; height: 32px;" 
                                       title="Download Certificate PDF">
                                        <i class="bi bi-file-earmark-pdf"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- VIEW / EDIT TASK MODAL -->
    <!-- ========================================================================= -->
    <div class="modal fade" id="taskModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
                <!-- Modal Header -->
                <div class="modal-header border-0 pb-0 px-4 pt-4 bg-white d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white flex-shrink-0" 
                             :style="isReadOnly ? 'background: #64748b;' : 'background: linear-gradient(135deg, #124E78, #00A39A);'"
                             style="width: 44px; height: 44px;">
                            <i class="bi" :class="isReadOnly ? 'bi-lock-fill fs-5' : 'bi-pencil fs-5'"></i>
                        </div>
                        <div>
                            <h5 class="fw-bolder text-dark mb-0">
                                <span x-show="isReadOnly">View Submitted Test Report (Locked)</span>
                                <span x-show="!isReadOnly">Edit Test Report Details</span>
                            </h5>
                            <p class="text-muted small mb-0">
                                <span x-show="isReadOnly">This record has been synced to CPSC/ERP. All fields are locked for compliance.</span>
                                <span x-show="!isReadOnly">Update test report information, standard citations, and certificate options.</span>
                            </p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body px-4 py-3">
                    <!-- Synced Notice Banner -->
                    <div x-show="isReadOnly" class="alert alert-info py-3 px-4 mb-4 border-0 rounded-4 d-flex align-items-center gap-3" style="background-color: #f1f5f9; color: #334155;">
                        <i class="bi bi-shield-lock-fill fs-3 text-primary"></i>
                        <div>
                            <div class="fw-bolder text-dark">Data Immutability Notice</div>
                            <div class="small">This test report has already been synced to CPSC/ERP databases (<span class="fw-bold" x-text="currentTask.apiSyncStatus"></span>). For regulatory compliance, no modifications can be made.</div>
                        </div>
                    </div>

                    <!-- Rejection Notice Banner -->
                    <div x-show="form.status === 'Rejected'" class="alert alert-danger py-3 px-4 mb-4 border-0 rounded-4 d-flex align-items-start gap-3">
                        <i class="bi bi-x-circle-fill fs-3 text-danger flex-shrink-0"></i>
                        <div>
                            <div class="fw-bolder text-danger">Report Rejected by Administrator</div>
                            <template x-if="form.rejectionReason">
                                <div class="small fw-semibold mt-1">
                                    <span class="text-muted">Reason / Feedback:</span>
                                    <span class="text-dark" x-text="form.rejectionReason"></span>
                                </div>
                            </template>
                            <div class="small text-muted mt-1">Please review the test reports and citations below, make required revisions, and click <span class="fw-bold">Save Changes</span> to re-submit this task for approval.</div>
                        </div>
                    </div>

                    <!-- PO Summary Card -->
                    <div class="card border-0 rounded-4 mb-4" style="background: linear-gradient(135deg, #f8fafc, #f1f5f9); border: 1px solid #e2e8f0 !important;">
                        <div class="card-body p-3">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <span class="text-uppercase fw-bold text-muted" style="font-size: 10px; letter-spacing: 0.5px;">PO Number</span>
                                    <div class="fw-bolder text-dark" x-text="form.poNumber || 'N/A'"></div>
                                </div>
                                <div class="col-md-3">
                                    <span class="text-uppercase fw-bold text-muted" style="font-size: 10px; letter-spacing: 0.5px;">Certificate No</span>
                                    <div class="fw-bold text-primary" x-text="form.certNo || 'Generated on save'"></div>
                                </div>
                                <div class="col-md-3">
                                    <span class="text-uppercase fw-bold text-muted" style="font-size: 10px; letter-spacing: 0.5px;">Product / Style</span>
                                    <div class="fw-semibold text-dark text-truncate" x-text="(form.styleNo ? form.styleNo + ' - ' : '') + (form.productName || 'N/A')" :title="(form.styleNo ? form.styleNo + ' - ' : '') + (form.productName || '')"></div>
                                </div>
                                <div class="col-md-3">
                                    <span class="text-uppercase fw-bold text-muted" style="font-size: 10px; letter-spacing: 0.5px;">Factory</span>
                                    <div class="fw-semibold text-secondary text-truncate" x-text="form.factoryName || 'N/A'" :title="form.factoryName"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Certificate & Status Form -->
                    <div class="row g-4 mb-4">
                        <!-- Searchable Certificate Type Selector (Type & Select) -->
                        <div class="col-md-4 position-relative" x-data="{
                            open: false,
                            query: '',
                            options: [
                                { value: 'GCC', label: 'GCC (General Conformity Certificate)', desc: 'Adult apparel & general consumer goods' },
                                { value: 'CPC', label: 'CPC (Children\'s Product Certificate)', desc: 'Children\'s apparel & youth products' }
                            ],
                            get filteredOptions() {
                                if (!this.query.trim()) return this.options;
                                const q = this.query.toLowerCase().trim();
                                return this.options.filter(o => o.value.toLowerCase().includes(q) || o.label.toLowerCase().includes(q) || o.desc.toLowerCase().includes(q));
                            },
                            select(val) {
                                if (isReadOnly) return;
                                form.certType = val;
                                this.open = false;
                                this.query = '';
                            }
                        }" @click.away="open = false">
                            <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Certificate Type*</label>
                            <div class="position-relative">
                                <button type="button" 
                                        :disabled="isReadOnly"
                                        class="form-control bg-light border-light fw-semibold rounded-3 text-start ps-3 pe-4 py-2 d-flex align-items-center justify-content-between shadow-none" 
                                        @click="if(!isReadOnly) { open = !open; if(open) $nextTick(() => $refs.certTypeSearchInput?.focus()); }">
                                    <span class="text-truncate" x-text="options.find(o => o.value === form.certType)?.label || 'Select Certificate Type'"></span>
                                    <i class="bi bi-chevron-down text-muted" style="font-size: 11px;"></i>
                                </button>
                            </div>

                            <div x-show="open && !isReadOnly" 
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 transform scale-95"
                                 x-transition:enter-end="opacity-100 transform scale-100"
                                 class="dropdown-menu show shadow-lg border-0 rounded-4 p-2 mt-1 w-100 position-absolute z-3" 
                                 style="max-height: 280px; overflow: hidden; display: flex; flex-direction: column;" 
                                 x-cloak>
                                <div class="p-1 mb-2">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-0"><i class="bi bi-search text-muted"></i></span>
                                        <input type="text" 
                                               x-ref="certTypeSearchInput" 
                                               x-model="query" 
                                               placeholder="Type to search..." 
                                               class="form-control bg-light border-0 fw-semibold">
                                        <button type="button" x-show="query" @click="query = ''" class="btn btn-light border-0 text-muted">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="overflow-y-auto flex-grow-1" style="max-height: 200px;">
                                    <template x-for="opt in filteredOptions" :key="opt.value">
                                        <button type="button" 
                                                class="dropdown-item rounded-3 py-2 px-3 fw-semibold text-wrap d-flex align-items-center justify-content-between mb-1" 
                                                :class="form.certType === opt.value ? 'bg-primary text-white' : ''"
                                                @click="select(opt.value)">
                                            <div>
                                                <div class="fw-bold" x-text="opt.label"></div>
                                                <div class="small opacity-75" style="font-size: 11px;" x-text="opt.desc"></div>
                                            </div>
                                            <i x-show="form.certType === opt.value" class="bi bi-check-lg ms-2"></i>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Searchable Status Selector (Type & Select) -->
                        <div class="col-md-4 position-relative" x-data="{
                            open: false,
                            query: '',
                            options: [
                                { value: 'Complete', label: 'Complete', desc: 'All required tests performed & recorded' },
                                { value: 'Incomplete', label: 'Incomplete', desc: 'Pending further tests or details' }
                            ],
                            get filteredOptions() {
                                if (!this.query.trim()) return this.options;
                                const q = this.query.toLowerCase().trim();
                                return this.options.filter(o => o.value.toLowerCase().includes(q) || o.label.toLowerCase().includes(q));
                            },
                            select(val) {
                                if (isReadOnly) return;
                                form.status = val;
                                this.open = false;
                                this.query = '';
                            }
                        }" @click.away="open = false">
                            <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Status*</label>
                            <div class="position-relative">
                                <button type="button" 
                                        :disabled="isReadOnly"
                                        class="form-control bg-light border-light fw-semibold rounded-3 text-start ps-3 pe-4 py-2 d-flex align-items-center justify-content-between shadow-none" 
                                        @click="if(!isReadOnly) { open = !open; if(open) $nextTick(() => $refs.statusSearchInput?.focus()); }">
                                    <span class="text-truncate" x-text="options.find(o => o.value === form.status)?.label || 'Select Status'"></span>
                                    <i class="bi bi-chevron-down text-muted" style="font-size: 11px;"></i>
                                </button>
                            </div>

                            <div x-show="open && !isReadOnly" 
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 transform scale-95"
                                 x-transition:enter-end="opacity-100 transform scale-100"
                                 class="dropdown-menu show shadow-lg border-0 rounded-4 p-2 mt-1 w-100 position-absolute z-3" 
                                 style="max-height: 250px; overflow: hidden; display: flex; flex-direction: column;" 
                                 x-cloak>
                                <div class="p-1 mb-2">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-0"><i class="bi bi-search text-muted"></i></span>
                                        <input type="text" 
                                               x-ref="statusSearchInput" 
                                               x-model="query" 
                                               placeholder="Type to filter..." 
                                               class="form-control bg-light border-0 fw-semibold">
                                        <button type="button" x-show="query" @click="query = ''" class="btn btn-light border-0 text-muted">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="overflow-y-auto flex-grow-1" style="max-height: 180px;">
                                    <template x-for="opt in filteredOptions" :key="opt.value">
                                        <button type="button" 
                                                class="dropdown-item rounded-3 py-2 px-3 fw-semibold text-wrap d-flex align-items-center justify-content-between mb-1" 
                                                :class="form.status === opt.value ? 'bg-primary text-white' : ''"
                                                @click="select(opt.value)">
                                            <div>
                                                <div class="fw-bold" x-text="opt.label"></div>
                                                <div class="small opacity-75" style="font-size: 11px;" x-text="opt.desc"></div>
                                            </div>
                                            <i x-show="form.status === opt.value" class="bi bi-check-lg ms-2"></i>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4" x-show="form.certType === 'CPC'">
                            <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Target Age Group</label>
                            <input type="text" x-model="form.intendedUse" :disabled="isReadOnly" class="form-control bg-light border-light fw-semibold px-3 py-2 rounded-3" placeholder="e.g. Ages 3+">
                        </div>

                        <div class="col-md-4" x-show="form.certType === 'CPC'">
                            <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Children's Product Category</label>
                            <input type="text" x-model="form.childrenProduct" :disabled="isReadOnly" class="form-control bg-light border-light fw-semibold px-3 py-2 rounded-3" placeholder="e.g. Toys, Sleepwear">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Manufacture Date</label>
                            <input type="month" x-model="form.manufactureDate" :disabled="isReadOnly" class="form-control bg-light border-light fw-semibold px-3 py-2 rounded-3">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Lot Number</label>
                            <input type="text" x-model="form.lotNumber" :disabled="isReadOnly" class="form-control bg-light border-light fw-semibold px-3 py-2 rounded-3">
                        </div>
                    </div>

                    <!-- Test Reports Section -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="fw-bolder text-dark mb-0">Safety Standard Tests & Citations</h6>
                            <span class="text-muted small">Standard protocol test results and compliance citations.</span>
                        </div>
                        <div class="d-flex gap-2" x-show="!isReadOnly">
                            <button type="button" @click="addTest()" class="btn btn-outline-primary btn-sm fw-bold rounded-pill px-3">
                                <i class="bi bi-plus-lg me-1"></i> Add Lab Test
                            </button>
                            <button type="button" @click="addExclusion()" class="btn btn-outline-secondary btn-sm fw-bold rounded-pill px-3">
                                <i class="bi bi-plus-lg me-1"></i> Add Exclusion
                            </button>
                        </div>
                    </div>

                    <!-- Tests Loop -->
                    <template x-for="(test, index) in form.tests" :key="index">
                        <div class="card border-0 shadow-sm rounded-4 mb-3 position-relative" style="border: 1px solid #e2e8f0 !important;">
                            <button type="button" 
                                    x-show="!isReadOnly" 
                                    @click="removeTest(index)" 
                                    class="btn btn-sm btn-link text-danger position-absolute top-0 end-0 m-3 text-decoration-none" 
                                    title="Remove Test">
                                <i class="bi bi-trash fs-5"></i>
                            </button>

                            <div class="card-body p-4">
                                <!-- Exclusion Form -->
                                <div x-show="test.isExclusion">
                                    <div class="d-flex align-items-center gap-2 mb-2 text-warning fw-bold small">
                                        <i class="bi bi-shield-exclamation fs-5"></i> Safety Standard Exclusion
                                    </div>
                                    <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Exclusion Reason*</label>
                                    <input type="text" x-model="test.exclusionReason" :disabled="isReadOnly" class="form-control bg-light border-light fw-semibold px-3 py-2 rounded-3">
                                </div>

                                <!-- Standard Lab Form -->
                                <div x-show="!test.isExclusion" class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Test Report ID / Ref #*</label>
                                        <input type="text" x-model="test.testReportId" :disabled="isReadOnly" class="form-control bg-light border-light fw-semibold px-3 py-2 rounded-3">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Test Date*</label>
                                        <input type="date" x-model="test.testDate" :disabled="isReadOnly" class="form-control bg-light border-light fw-semibold px-3 py-2 rounded-3">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Lab CPSC ID</label>
                                        <input type="text" x-model="test.labCpscId" :disabled="isReadOnly" class="form-control bg-light border-light fw-semibold px-3 py-2 rounded-3">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Test Report URL (Optional)</label>
                                        <input type="url" x-model="test.testReportUrl" :disabled="isReadOnly" class="form-control bg-light border-light fw-semibold px-3 py-2 rounded-3" placeholder="https://...">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">
                                            <i class="bi bi-file-earmark-arrow-up text-primary me-1"></i> Upload Report File
                                        </label>
                                        
                                        <!-- When no file uploaded yet -->
                                        <div x-show="!test.testReportFile">
                                            <div class="position-relative">
                                                <input type="file" 
                                                       class="form-control bg-light border-light fw-semibold px-3 py-2 rounded-3" 
                                                       accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp,.xls,.xlsx"
                                                       :disabled="isReadOnly || test.isUploadingFile"
                                                       @change="uploadTestFile($event, index)">
                                                <span x-show="test.isUploadingFile" class="spinner-border spinner-border-sm text-primary position-absolute end-0 top-50 translate-middle-y me-3" role="status"></span>
                                            </div>
                                            <div class="text-muted small mt-1" style="font-size: 10px;">PDF, DOC, images (max 20MB)</div>
                                        </div>

                                        <!-- When file is uploaded -->
                                        <div x-show="test.testReportFile" class="d-flex flex-column gap-2">
                                            <div class="d-flex align-items-center justify-content-between p-2 rounded-3 bg-light border border-success border-opacity-25">
                                                <div class="d-flex align-items-center gap-2 overflow-hidden">
                                                    <i class="bi bi-file-earmark-check-fill text-success fs-5"></i>
                                                    <a :href="getTestFileUrl(test)" target="_blank" class="fw-bold small text-dark text-truncate text-decoration-none" style="max-width: 140px;" x-text="test.testReportFileName || 'Uploaded Report File'"></a>
                                                </div>
                                                <button type="button" x-show="!isReadOnly" @click="removeTestFile(index)" class="btn btn-sm btn-link text-danger p-0 border-0 ms-2 text-decoration-none" title="Remove attachment">
                                                    <i class="bi bi-trash3-fill"></i>
                                                </button>
                                            </div>
                                            <!-- Replace file option in edit mode -->
                                            <div x-show="!isReadOnly" class="position-relative">
                                                <label class="text-muted" style="font-size: 10px; cursor:pointer;">
                                                    <i class="bi bi-arrow-repeat text-primary me-1"></i>
                                                    <span>Replace file: </span>
                                                    <input type="file"
                                                           class="d-none"
                                                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp,.xls,.xlsx"
                                                           :disabled="isReadOnly || test.isUploadingFile"
                                                           @change="uploadTestFile($event, index)">
                                                    <span class="text-primary fw-semibold text-decoration-underline" style="cursor:pointer;">Browse</span>
                                                </label>
                                                <span x-show="test.isUploadingFile" class="spinner-border spinner-border-sm text-primary ms-2" role="status"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Component Testing -->
                                    <div class="col-12">
                                        <div class="form-check">
                                            <input type="checkbox" x-model="test.componentTesting" :disabled="isReadOnly" class="form-check-input" :id="'editCompTest_' + index">
                                            <label class="form-check-label fw-bold text-dark small" :for="'editCompTest_' + index">Uses Component Testing?</label>
                                        </div>
                                        <div class="mt-2" x-show="test.componentTesting" x-transition>
                                            <input type="text" x-model="test.componentDetails" :disabled="isReadOnly" class="form-control bg-light border-light fw-semibold px-3 py-2 rounded-3" placeholder="Describe component testing details...">
                                        </div>
                                    </div>
                                </div>

                                <!-- Citations Section -->
                                <div class="mt-3 pt-3 border-top">
                                    <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Applicable Safety Citations*</label>
                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                        <template x-for="(cit, cIdx) in test.citations" :key="cIdx">
                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-2 d-inline-flex align-items-center gap-2">
                                                <span class="fw-bold" x-text="cit.code"></span>
                                                <button type="button" 
                                                        x-show="!isReadOnly"
                                                        @click.stop="removeCitation(index, cIdx)" 
                                                        class="btn btn-link p-0 text-danger border-0 text-decoration-none d-inline-flex align-items-center" 
                                                        title="Remove citation">
                                                    <i class="bi bi-x-circle-fill" style="font-size: 14px;"></i>
                                                </button>
                                            </span>
                                        </template>
                                        <span x-show="!test.citations || test.citations.length === 0" class="text-muted small fst-italic py-1">No citations added.</span>
                                    </div>

                                    <!-- Searchable Safety Citations Selector (Type & Select) -->
                                    <div class="position-relative" style="max-width: 560px;" x-show="!isReadOnly" x-data="{
                                        open: false,
                                        query: '',
                                        get filteredCitations() {
                                            const q = this.query.toLowerCase().trim();
                                            const list = Object.entries(citationMaster || {}).map(([code, desc]) => ({ code, desc }));
                                            if (!q) return list.slice(0, 35);
                                            return list.filter(item => 
                                                item.code.toLowerCase().includes(q) || (item.desc && item.desc.toLowerCase().includes(q))
                                            );
                                        },
                                        select(code) {
                                            addCitation(index, code);
                                            this.open = false;
                                            this.query = '';
                                        }
                                    }" @click.away="open = false">
                                        <div class="position-relative">
                                            <button type="button" 
                                                    class="form-control bg-light border-light fw-semibold rounded-3 text-start ps-3 pe-4 py-2 d-flex align-items-center justify-content-between shadow-none" 
                                                    @click="open = !open; if(open) $nextTick(() => $refs.editCitSearchInput?.focus())">
                                                <span class="text-muted d-flex align-items-center gap-2">
                                                    <i class="bi bi-search text-primary"></i>
                                                    <span class="small">Type code or regulation to add citation...</span>
                                                </span>
                                                <i class="bi bi-chevron-down text-muted" style="font-size: 11px;"></i>
                                            </button>
                                        </div>

                                        <!-- Searchable Dropdown Popup -->
                                        <div x-show="open" 
                                             x-transition:enter="transition ease-out duration-150"
                                             x-transition:enter-start="opacity-0 transform scale-95"
                                             x-transition:enter-end="opacity-100 transform scale-100"
                                             class="dropdown-menu show shadow-lg border-0 rounded-4 p-2 mt-1 w-100 position-absolute z-3" 
                                             style="max-height: 320px; overflow: hidden; display: flex; flex-direction: column;" 
                                             x-cloak>
                                            <!-- Search Input Inside Dropdown -->
                                            <div class="p-1 mb-2">
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-light border-0"><i class="bi bi-search text-muted"></i></span>
                                                    <input type="text" 
                                                           x-ref="editCitSearchInput" 
                                                           x-model="query" 
                                                           placeholder="Type code (e.g. 1610, 1500) or topic (e.g. lead, flammability)..." 
                                                           class="form-control bg-light border-0 fw-semibold">
                                                    <button type="button" x-show="query" @click="query = ''" class="btn btn-light border-0 text-muted">
                                                        <i class="bi bi-x"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Scrollable List of Citations -->
                                            <div class="overflow-y-auto flex-grow-1" style="max-height: 240px;">
                                                <template x-for="item in filteredCitations" :key="item.code">
                                                    <button type="button" 
                                                            class="dropdown-item rounded-3 py-2 px-3 fw-semibold text-wrap d-flex flex-column align-items-start gap-1 border-bottom border-light mb-1" 
                                                            @click="select(item.code)">
                                                        <div class="d-flex align-items-center justify-content-between w-100">
                                                            <span class="fw-bold text-primary" x-text="item.code"></span>
                                                            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill small">+ Add</span>
                                                        </div>
                                                        <div class="small text-muted" style="font-size: 11px; line-height: 1.3;" x-text="item.desc"></div>
                                                    </button>
                                                </template>
                                                <div x-show="filteredCitations.length === 0" class="text-center py-3 text-muted small fw-semibold">
                                                    No matching safety citations found
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer border-0 px-4 pb-4 pt-2 bg-white d-flex justify-content-between">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold border" data-bs-dismiss="modal">Close</button>
                    <button type="button" 
                            x-show="!isReadOnly"
                            class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm d-inline-flex align-items-center gap-2" 
                            :disabled="isSubmitting" 
                            @click="saveTask()">
                        <span x-show="isSubmitting" class="spinner-border spinner-border-sm" role="status"></span>
                        <i x-show="!isSubmitting" class="bi bi-check2-circle fs-5"></i>
                        <span>Save Changes</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
    function labCompletedTasksApp() {
        return {
            tasks: [],
            filteredRecords: [],
            searchTerm: '',
            syncFilter: 'all',
            statusFilter: 'all',
            isLoading: false,
            isSubmitting: false,
            isReadOnly: false,
            currentTask: {},

            notification: {
                show: false,
                type: 'success',
                title: '',
                message: ''
            },

            citationMaster: @json($citationMaster ?? []),

            form: {
                id: null,
                poNumber: '',
                certNo: '',
                certType: 'GCC',
                status: 'Complete',
                productName: '',
                factoryName: '',
                manufactureDate: '',
                lotNumber: '',
                primaryProductId: '',
                productIdType: 'Model #',
                versionId: '',
                tests: []
            },

            init() {
                this.fetchTasks();
            },

            showToast(type, title, message) {
                this.notification.type = type;
                this.notification.title = title;
                this.notification.message = message;
                this.notification.show = true;
                setTimeout(() => { this.notification.show = false; }, 5000);
            },

            resetFilters() {
                this.searchTerm = '';
                this.syncFilter = 'all';
                this.statusFilter = 'all';
                this.filterRecords();
            },

            fetchTasks() {
                this.isLoading = true;
                fetch('{{ route("lab.tasks.completed") }}', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.json())
                .then(data => {
                    this.tasks = data || [];
                    this.filterRecords();
                    this.isLoading = false;
                })
                .catch(err => {
                    console.error('Failed to fetch completed tasks:', err);
                    this.isLoading = false;
                });
            },

            filterRecords() {
                let filtered = this.tasks.filter(t => t.tests && Array.isArray(t.tests) && t.tests.length > 0);

                // Sync status filter
                if (this.syncFilter !== 'all') {
                    if (this.syncFilter === 'synced') {
                        filtered = filtered.filter(t => t.apiSyncStatus === 'synced');
                    } else if (this.syncFilter === 'pending') {
                        filtered = filtered.filter(t => (t.apiSyncStatus === 'pending' || !t.apiSyncStatus) && t.status !== 'Rejected');
                    } else if (this.syncFilter === 'failed') {
                        filtered = filtered.filter(t => t.apiSyncStatus === 'failed');
                    } else if (this.syncFilter === 'rejected') {
                        filtered = filtered.filter(t => t.status === 'Rejected');
                    }
                }

                // Completion status filter
                if (this.statusFilter !== 'all') {
                    filtered = filtered.filter(t => t.status === this.statusFilter);
                }

                // Search term
                if (this.searchTerm.trim()) {
                    const q = this.searchTerm.toLowerCase().trim();
                    filtered = filtered.filter(t => {
                        return (t.poNumber && t.poNumber.toLowerCase().includes(q)) ||
                               (t.styleNo && t.styleNo.toLowerCase().includes(q)) ||
                               (t.productName && t.productName.toLowerCase().includes(q)) ||
                               (t.factoryName && t.factoryName.toLowerCase().includes(q)) ||
                               (t.certNo && t.certNo.toLowerCase().includes(q));
                    });
                }

                this.filteredRecords = filtered;
            },

            openModal(task, readOnly = false) {
                this.currentTask = task;
                this.isReadOnly = readOnly;

                // Deep copy to form
                const rawTests = JSON.parse(JSON.stringify(task.tests || []));
                rawTests.forEach(t => {
                    t.isUploadingFile = false;
                    // Ensure labName & labType are always present (required by server validation)
                    t.labName = t.labName || 'Testing Laboratory';
                    t.labType = t.labType || 'CPSC-Accepted';
                    t.testReportFile = t.testReportFile || '';
                    t.testReportFileName = t.testReportFileName || (t.testReportFile ? t.testReportFile.split('/').pop() : '');
                    t.testReportFileUrl = t.testReportFileUrl || (t.testReportFile ? ('/storage/' + t.testReportFile) : '');
                    // Ensure citations is always an array
                    if (!Array.isArray(t.citations)) t.citations = [];
                });

                this.form = {
                    id: task.id,
                    poNumber: task.poNumber,
                    poDate: task.poDate || '',
                    buyer: task.buyer || '',
                    styleNo: task.styleNo || '',
                    productStyle: task.productStyle || task.styleNo || '',
                    labId: task.labId || null,
                    certNo: task.certNo,
                    certType: task.certType || 'GCC',
                    intendedUse: task.intendedUse || '',
                    childrenProduct: task.childrenProduct || '',
                    status: task.status || 'Complete',
                    rejectionReason: task.rejectionReason || '',
                    productName: task.productName,
                    factoryName: task.factoryName,
                    factoryAddress: task.factoryAddress || '',
                    manufactureDate: task.manufactureDate ? task.manufactureDate.slice(0, 7) : '',
                    lotNumber: task.lotNumber || '',
                    primaryProductId: task.poNumber,
                    productIdType: 'Model #',
                    versionId: Math.random().toString(16).substring(2, 10).toUpperCase(),
                    tests: rawTests
                };

                if (!this.form.tests || this.form.tests.length === 0) {
                    this.form.tests = [{
                        labType: 'CPSC-Accepted',
                        labName: 'Testing Laboratory',
                        labCpscId: '',
                        testReportId: '',
                        testDate: new Date().toISOString().slice(0, 10),
                        testReportUrl: '',
                        testReportKey: '',
                        testReportFile: '',
                        testReportFileName: '',
                        testReportFileUrl: '',
                        isUploadingFile: false,
                        componentTesting: false,
                        componentDetails: '',
                        citations: [],
                        isExclusion: false,
                        exclusionReason: ''
                    }];
                }

                const modal = new bootstrap.Modal(document.getElementById('taskModal'));
                modal.show();
            },

            addTest() {
                if (this.isReadOnly) return;
                this.form.tests.push({
                    labType: 'CPSC-Accepted',
                    labName: 'Testing Laboratory',
                    labCpscId: '',
                    testReportId: '',
                    testDate: new Date().toISOString().slice(0, 10),
                    testReportUrl: '',
                    testReportKey: '',
                    testReportFile: '',
                    testReportFileName: '',
                    testReportFileUrl: '',
                    isUploadingFile: false,
                    componentTesting: false,
                    componentDetails: '',
                    citations: [],
                    isExclusion: false,
                    exclusionReason: ''
                });
            },

            addExclusion() {
                if (this.isReadOnly) return;
                this.form.tests.push({
                    labType: 'N/A',
                    labName: 'Testing Laboratory',
                    isExclusion: true,
                    exclusionReason: '',
                    citations: []
                });
            },

            removeTest(index) {
                if (this.isReadOnly) return;
                if (this.form.tests.length > 1) {
                    this.form.tests.splice(index, 1);
                } else {
                    alert('You must retain at least one test record or exclusion.');
                }
            },

            addCitation(testIndex, citationCode) {
                if (this.isReadOnly || !citationCode) return;
                const test = this.form.tests[testIndex];
                if (!test.citations) test.citations = [];
                
                const exists = test.citations.some(c => c.code === citationCode);
                if (!exists) {
                    const desc = this.citationMaster[citationCode] || citationCode;
                    test.citations.push({ code: citationCode, desc: desc });
                }
            },

            removeCitation(testIndex, citationIndex) {
                if (this.isReadOnly) return;
                this.form.tests[testIndex].citations.splice(citationIndex, 1);
            },

            uploadTestFile(event, testIndex) {
                if (this.isReadOnly) return;
                const file = event.target.files[0];
                if (!file) return;

                if (file.size > 20 * 1024 * 1024) {
                    alert('File size exceeds the 20MB limit.');
                    event.target.value = '';
                    return;
                }

                const test = this.form.tests[testIndex];
                test.isUploadingFile = true;

                const fd = new FormData();
                fd.append('file', file);

                fetch('{{ route("lab.upload-report-file") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: fd
                })
                .then(r => r.json())
                .then(data => {
                    test.isUploadingFile = false;
                    if (data.success) {
                        test.testReportFile = data.file_path;
                        test.testReportFileName = data.file_name;
                        test.testReportFileUrl = data.file_url;
                    } else {
                        alert(data.message || 'Failed to upload report file.');
                        event.target.value = '';
                    }
                })
                .catch(err => {
                    test.isUploadingFile = false;
                    console.error('Upload failed:', err);
                    alert('File upload failed. Please try again.');
                    event.target.value = '';
                });
            },

            removeTestFile(testIndex) {
                if (this.isReadOnly) return;
                const test = this.form.tests[testIndex];
                test.testReportFile = '';
                test.testReportFileName = '';
                test.testReportFileUrl = '';
            },

            getTestFileUrl(test) {
                if (!test) return '';
                if (test.testReportFile) {
                    const cleanPath = String(test.testReportFile).replace(/^\/+/, '');
                    return '{{ route('stafford-certs.view-report-file') }}?file=' + encodeURIComponent(cleanPath);
                }
                if (test.testReportFileUrl) {
                    const str = String(test.testReportFileUrl);
                    if (str.includes('stafford-certs/report-file')) {
                        return str;
                    }
                    const storageIdx = str.indexOf('storage/');
                    if (storageIdx !== -1) {
                        const relPath = str.substring(storageIdx + 8).replace(/^\/+/, '');
                        return '{{ route('stafford-certs.view-report-file') }}?file=' + encodeURIComponent(relPath);
                    }
                    return str;
                }
                if (test.testReportUrl) {
                    let u = String(test.testReportUrl).trim();
                    if (u && !u.match(/^[a-zA-Z]+:\/\//) && !u.startsWith('/')) {
                        u = 'https://' + u;
                    }
                    return u;
                }
                return '';
            },

            saveTask() {
                if (this.isReadOnly) {
                    alert('This record has already been synced and cannot be edited.');
                    return;
                }

                this.isSubmitting = true;

                // When revising and re-submitting a rejected task, reset status to Complete
                const submitStatus = (this.form.status === 'Rejected' || !this.form.status) ? 'Complete' : this.form.status;
                this.form.status = submitStatus;

                fetch(`{{ url('lab/tasks') }}/${this.form.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        poNumber: this.form.poNumber,
                        poDate: this.form.poDate,
                        buyer: this.form.buyer,
                        styleNo: this.form.styleNo,
                        productStyle: this.form.productStyle || this.form.styleNo,
                        labId: this.form.labId,
                        certType: this.form.certType,
                        intendedUse: this.form.intendedUse,
                        childrenProduct: this.form.childrenProduct,
                        status: submitStatus,
                        rejectionReason: null,
                        manufactureDate: this.form.manufactureDate,
                        lotNumber: this.form.lotNumber,
                        productName: this.form.productName,
                        factoryName: this.form.factoryName,
                        factoryAddress: this.form.factoryAddress,
                        primaryProductId: this.form.primaryProductId || this.form.poNumber,
                        productIdType: this.form.productIdType || 'Model #',
                        versionId: this.form.versionId,
                        tests: this.form.tests
                    })
                })
                .then(r => r.json().then(data => ({ status: r.status, body: data })))
                .then(({ status, body }) => {
                    this.isSubmitting = false;
                    if (status === 403) {
                        alert(body.message || 'Edits are locked because this record is already synced.');
                        return;
                    }

                    if (status >= 200 && status < 300 && (body.success || body.data)) {
                        const modalEl = document.getElementById('taskModal');
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();

                        this.showToast('success', 'Task Saved', 'Qualification test report details updated successfully.');
                        this.fetchTasks();
                    } else {
                        let errMsg = body.message || 'Failed to update task.';
                        if (body.errors) {
                            errMsg += ' ' + Object.values(body.errors).flat().join(' ');
                        }
                        alert(errMsg);
                    }
                })
                .catch(err => {
                    this.isSubmitting = false;
                    console.error('Error saving task:', err);
                    alert('Network error while saving changes.');
                });
            }
        };
    }
</script>
@endpush
@endsection
