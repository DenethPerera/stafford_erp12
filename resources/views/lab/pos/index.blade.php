@extends('layouts.app')

@section('header', 'All Details')

@section('content')
<div x-data="labPosApp()" x-init="init()" class="d-flex flex-column gap-4">

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

    <!-- Top Bar: Filters & Search -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <div class="row g-3 align-items-end">
                <!-- Searchable Style Selector (Type & Select) -->
                <div class="col-md-5 position-relative" @click.away="styleDropdownOpen = false">
                    <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">
                        <i class="bi bi-tag-fill text-primary me-1"></i> Filter by Style
                    </label>
                    <div class="position-relative">
                        <button type="button" 
                                class="form-control bg-light border-light fw-semibold rounded-pill text-start ps-3 pe-5 py-2 d-flex align-items-center justify-content-between shadow-none" 
                                @click="styleDropdownOpen = !styleDropdownOpen; if(styleDropdownOpen) $nextTick(() => $refs.styleSearchInput?.focus())">
                            <span class="text-truncate" style="max-width: 320px;" x-text="selectedStyle || ('All Styles (' + styles.length + ')')"></span>
                            <i class="bi bi-chevron-down text-muted" style="font-size: 11px;"></i>
                        </button>
                        <button type="button" 
                                x-show="selectedStyle" 
                                @click.stop="clearStyle()" 
                                class="btn btn-sm btn-link text-muted position-absolute end-0 top-50 translate-middle-y me-4 text-decoration-none p-0" 
                                title="Clear style filter">
                            <i class="bi bi-x-circle-fill"></i>
                        </button>
                    </div>

                    <!-- Searchable Dropdown Popup -->
                    <div x-show="styleDropdownOpen" 
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 transform scale-95"
                         x-transition:enter-end="opacity-100 transform scale-100"
                         class="dropdown-menu show shadow-lg border-0 rounded-4 p-2 mt-1 w-100 position-absolute z-3" 
                         style="max-height: 320px; overflow: hidden; display: flex; flex-direction: column;" 
                         x-cloak>
                        <!-- Search Box Inside Dropdown -->
                        <div class="p-1 mb-2">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light border-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" 
                                       x-ref="styleSearchInput" 
                                       x-model="styleSearchQuery" 
                                       placeholder="Type to search styles..." 
                                       class="form-control bg-light border-0 fw-semibold">
                                <button type="button" x-show="styleSearchQuery" @click="styleSearchQuery = ''" class="btn btn-light border-0 text-muted">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Scrollable List of Styles -->
                        <div class="overflow-y-auto flex-grow-1" style="max-height: 230px;">
                            <button type="button" 
                                    class="dropdown-item rounded-3 py-2 fw-semibold d-flex align-items-center justify-content-between"
                                    :class="!selectedStyle ? 'bg-primary text-white' : ''"
                                    @click="selectStyle('')">
                                <span>All Styles</span>
                                <span class="badge rounded-pill" :class="!selectedStyle ? 'bg-white text-primary' : 'bg-light text-muted'" x-text="styles.length"></span>
                            </button>
                            <template x-for="s in filteredStyles" :key="s">
                                <button type="button" 
                                        class="dropdown-item rounded-3 py-2 fw-semibold text-truncate d-flex align-items-center justify-content-between" 
                                        :class="selectedStyle === s ? 'bg-primary text-white' : ''"
                                        @click="selectStyle(s)"
                                        :title="s">
                                    <span x-text="s"></span>
                                    <i x-show="selectedStyle === s" class="bi bi-check-lg ms-2"></i>
                                </button>
                            </template>
                            <div x-show="filteredStyles.length === 0" class="text-center py-3 text-muted small fw-semibold">
                                No matching styles found
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter & Search by Purchase Order Ref No -->
                <div class="col-md-5 position-relative" @click.away="poDropdownOpen = false">
                    <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">
                        <i class="bi bi-file-earmark-text-fill text-primary me-1"></i> Filter by PO Reference #
                    </label>
                    <div class="position-relative">
                        <input type="text" 
                               x-model="selectedPo" 
                               @focus="poDropdownOpen = true"
                               @input.debounce.300ms="poDropdownOpen = true; fetchPos(1)"
                               @keydown.enter.prevent="poDropdownOpen = false; fetchPos(1)"
                               placeholder="Search or select PO # (e.g. 66185433)..." 
                               class="form-control bg-light border-light fw-semibold rounded-pill ps-3 pe-5 py-2 shadow-none">
                        <button type="button" 
                                x-show="selectedPo" 
                                @click="clearPo()" 
                                class="btn btn-sm btn-link text-muted position-absolute end-0 top-50 translate-middle-y me-3 text-decoration-none p-0" 
                                title="Clear PO filter">
                            <i class="bi bi-x-circle-fill"></i>
                        </button>
                        <span x-show="!selectedPo" class="position-absolute end-0 top-50 translate-middle-y me-3 text-muted pointer-events-none">
                            <i class="bi bi-search text-muted small"></i>
                        </span>
                    </div>

                    <!-- Dropdown suggestions -->
                    <div x-show="poDropdownOpen && filteredPoList.length > 0" 
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 transform scale-95"
                         x-transition:enter-end="opacity-100 transform scale-100"
                         class="dropdown-menu show shadow-lg border-0 rounded-4 p-2 mt-1 w-100 position-absolute z-3" 
                         style="max-height: 250px; overflow-y: auto;" 
                         x-cloak>
                        <div class="px-2 py-1 text-muted text-uppercase fw-bold d-flex justify-content-between align-items-center" style="font-size: 10px; letter-spacing: 0.5px;">
                            <span>Suggested PO Numbers</span>
                            <span class="badge bg-light text-muted" x-text="filteredPoList.length"></span>
                        </div>
                        <template x-for="p in filteredPoList" :key="p">
                            <button type="button" 
                                    class="dropdown-item rounded-3 py-2 fw-semibold text-truncate d-flex align-items-center justify-content-between" 
                                    :class="selectedPo === p ? 'bg-primary text-white' : ''"
                                    @click="selectPo(p)"
                                    :title="p">
                                <span x-text="p"></span>
                                <i x-show="selectedPo === p" class="bi bi-check-lg ms-2"></i>
                            </button>
                        </template>
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

    <!-- POs Table Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bolder text-dark mb-0 d-flex align-items-center gap-2">
                    <span>Direct ERP Purchase Orders</span>
                    <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-1 fw-bold" style="font-size: 12px;" x-text="pagination.total + ' POs'"></span>
                </h5>
                <p class="text-muted small mb-0">Select a Purchase Order to enter test report details and safety certifications.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small fw-semibold" x-show="isLoading">
                    <span class="spinner-border spinner-border-sm text-primary me-1" role="status"></span> Loading...
                </span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-uppercase text-muted" style="font-size: 11px; letter-spacing: 0.5px;">
                        <th class="py-3 px-4">PO Reference</th>
                        <th class="py-3 px-3">Date</th>
                        <th class="py-3 px-3">Buyer</th>
                        <th class="py-3 px-3">Factory / Branch</th>
                        <th class="py-3 px-3">Style ID</th>
                        <th class="py-3 px-3">Season</th>
                        <th class="py-3 px-3">Product Description</th>
                        <th class="py-3 px-3">Variations</th>
                        <th class="py-3 px-3 text-center">Status</th>
                        <th class="py-3 px-4 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="isLoading && pos.length === 0">
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <div class="spinner-border text-primary mb-2" role="status"></div>
                                <div class="fw-semibold small">Fetching Purchase Orders from ERP Database...</div>
                            </td>
                        </tr>
                    </template>

                    <template x-if="!isLoading && pos.length === 0">
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 text-muted opacity-50 d-block mb-2"></i>
                                <div class="fw-bold">No Purchase Orders Found</div>
                                <div class="small">Try adjusting your style filter or search criteria.</div>
                            </td>
                        </tr>
                    </template>

                    <template x-for="po in pos" :key="po.id">
                        <tr>
                            <!-- PO Number -->
                            <td class="py-3 px-4">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center text-primary" style="width: 32px; height: 32px;">
                                        <i class="bi bi-file-earmark-text"></i>
                                    </div>
                                    <div>
                                        <div class="d-flex align-items-center gap-1">
                                            <span class="fw-bolder text-dark" x-text="po.poNumber"></span>

                                            <!-- Show Eye icon and +N badge ONLY if there are 2 or more POs in this group -->
                                            <template x-if="po.otherPosCount > 0">
                                                <div class="d-inline-flex align-items-center gap-1">
                                                    <button type="button" 
                                                            class="btn btn-sm btn-link text-primary p-0 text-decoration-none ms-1 d-inline-flex align-items-center" 
                                                            @click="openViewPosModal(po)" 
                                                            :title="'View all ' + (po.allPoNumbers ? po.allPoNumbers.length : 1) + ' POs in this group'">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <span class="badge bg-light text-muted border" 
                                                          style="font-size: 10px; cursor: pointer;" 
                                                          @click="openViewPosModal(po)"
                                                          :title="'Click to view all ' + (po.allPoNumbers ? po.allPoNumbers.length : 1) + ' POs'" 
                                                          x-text="'+' + po.otherPosCount + ' other PO' + (po.otherPosCount > 1 ? 's' : '')">
                                                    </span>
                                                </div>
                                            </template>
                                        </div>
                                        <div class="text-muted small" style="font-size: 10px;" x-text="(po.allPoNumbers ? po.allPoNumbers.length : 1) + ' PO(s) • ' + (po.totalQty ? Number(po.totalQty).toLocaleString() : 0) + ' pcs'"></div>
                                    </div>
                                </div>
                            </td>

                            <!-- Date -->
                            <td class="py-3 px-3 text-nowrap">
                                <span class="fw-semibold text-secondary small" x-text="po.poDate || '-'"></span>
                            </td>

                            <!-- Buyer -->
                            <td class="py-3 px-3">
                                <span class="fw-bold text-dark small text-truncate d-inline-block" style="max-width: 170px;" x-text="po.buyer" :title="po.buyer"></span>
                            </td>

                            <!-- Factory -->
                            <td class="py-3 px-3">
                                <span class="fw-semibold text-secondary small text-truncate d-inline-block" style="max-width: 150px;" x-text="po.factoryName || 'Branch N/A'" :title="po.factoryName"></span>
                            </td>

                            <!-- Style No -->
                            <td class="py-3 px-3">
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 fw-bold px-2.5 py-1.5" style="font-size: 12px;" x-text="po.styleNo || '-'"></span>
                            </td>

                            <!-- Season -->
                            <td class="py-3 px-3">
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border fw-semibold px-2 py-1" style="font-size: 11px;" x-text="po.season || '-'"></span>
                            </td>

                            <!-- Product Description -->
                            <td class="py-3 px-3">
                                <span class="text-secondary small text-truncate d-inline-block" style="max-width: 200px;" x-text="po.productName || '-'" :title="po.productName"></span>
                            </td>

                            <!-- Consolidated Variations (Collapsed Colors/Sizes) -->
                            <td class="py-3 px-3">
                                <div class="d-flex align-items-center gap-1">
                                    <span class="badge bg-light text-dark border fw-semibold px-2 py-1" style="font-size: 11px;" x-text="(po.variationsCount || 1) + ' variation' + ((po.variationsCount || 1) > 1 ? 's' : '')"></span>
                                    <template x-if="po.variations && po.variations.length > 0">
                                        <button type="button" 
                                                class="btn btn-sm btn-link text-primary p-0 text-decoration-none ms-1" 
                                                @click="openVariationsModal(po)" 
                                                title="View collapsed color and size variations">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </template>
                                </div>
                                <div class="text-muted" style="font-size: 10px;" x-show="po.colors && po.colors.length">
                                    <span x-text="po.colors.length + ' color' + (po.colors.length > 1 ? 's' : '')"></span>
                                    <span x-show="po.sizes && po.sizes.length" x-text="' • ' + po.sizes.length + ' size' + (po.sizes.length > 1 ? 's' : '')"></span>
                                </div>
                            </td>

                            <!-- Report Status Badge -->
                            <td class="py-3 px-3 text-center">
                                <template x-if="po.hasReport && po.isMyLab">
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-1 fw-bold" style="font-size: 11px;">
                                        <i class="bi bi-check-circle-fill me-1"></i> Report Added (Your Lab)
                                    </span>
                                </template>
                                <template x-if="po.hasReport && !po.isMyLab">
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-1 fw-bold" 
                                          style="font-size: 11px; cursor: default;" 
                                          :title="'Submitted by ' + (po.submittingLab || 'another laboratory')">
                                        <i class="bi bi-check-circle-fill me-1 text-primary"></i> Report Added
                                        <span class="fw-normal text-muted ms-1" style="font-size: 10px;" x-text="'(' + (po.submittingLab || 'Other Lab') + ')'"></span>
                                    </span>
                                </template>
                                <template x-if="!po.hasReport">
                                    <span class="badge bg-warning bg-opacity-20 text-dark rounded-pill px-3 py-1 fw-semibold" style="font-size: 11px;">
                                        <i class="bi bi-clock me-1"></i> Needs Report
                                    </span>
                                </template>
                            </td>

                            <!-- Action Column: Upload exclusively on consolidated row -->
                            <td class="py-3 px-4 text-end">
                                <template x-if="!po.hasReport">
                                    <button type="button" 
                                            class="btn btn-sm btn-primary rounded-pill px-3 fw-bold shadow-sm d-inline-flex align-items-center gap-1 text-nowrap"
                                            @click="openAddTestModal(po)"
                                            title="Attach and upload Test Report for this consolidated Style">
                                        <i class="bi bi-cloud-arrow-up-fill me-1"></i> Upload Report
                                    </button>
                                </template>
                                <template x-if="po.hasReport">
                                    <button type="button" 
                                            class="btn btn-sm btn-light text-muted rounded-pill px-3 fw-semibold border d-inline-flex align-items-center gap-1"
                                            disabled
                                            :title="po.isMyLab ? 'Test report has already been created by your lab' : ('Test report has already been submitted by ' + (po.submittingLab || 'another laboratory'))">
                                        <i class="bi bi-check2-all text-success"></i> Report Added
                                    </button>
                                </template>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Pagination Footer -->
        <div class="card-footer bg-white border-0 py-3 px-4 d-flex flex-column flex-md-row align-items-center justify-content-between gap-2" x-show="pagination.total > 0">
            <div class="text-muted small fw-semibold">
                Showing <span class="fw-bold text-dark" x-text="pagination.from || 0"></span> to <span class="fw-bold text-dark" x-text="pagination.to || 0"></span> of <span class="fw-bold text-dark" x-text="pagination.total"></span> POs
            </div>

            <div class="d-flex align-items-center gap-1">
                <button type="button" class="btn btn-sm btn-light border rounded-pill px-3 fw-bold" 
                        :disabled="pagination.currentPage <= 1 || isLoading" 
                        @click="fetchPos(pagination.currentPage - 1)">
                    <i class="bi bi-chevron-left"></i> Prev
                </button>
                <span class="px-2 small fw-bold text-muted" x-text="'Page ' + pagination.currentPage + ' of ' + pagination.lastPage"></span>
                <button type="button" class="btn btn-sm btn-light border rounded-pill px-3 fw-bold" 
                        :disabled="pagination.currentPage >= pagination.lastPage || isLoading" 
                        @click="fetchPos(pagination.currentPage + 1)">
                    Next <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- VIEW GROUPED POs MODAL (Popup to see PO numbers only) -->

    <div class="modal fade" id="viewGroupedPosModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
                <div class="modal-header border-0 pb-2 px-4 pt-4 bg-white d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                            <i class="bi bi-collection-fill fs-6"></i>
                        </div>
                        <div>
                            <h6 class="fw-bolder text-dark mb-0">Purchase Orders</h6>
                            <span class="text-muted small" x-text="(viewingPosModal.allPoNumbers ? viewingPosModal.allPoNumbers.length : 0) + ' PO(s) in this group'"></span>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <div class="mb-3 p-2 bg-light rounded-3 small">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Style No:</span>
                            <span class="fw-bold text-dark text-truncate ms-2" style="max-width: 160px;" x-text="viewingPosModal.styleNo || '-'"></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Season:</span>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary border fw-semibold text-truncate ms-2" style="max-width: 160px;" x-text="viewingPosModal.season || '-'"></span>
                        </div>
                    </div>

                    <label class="form-label text-muted text-uppercase fw-bold mb-2" style="font-size: 11px; letter-spacing: 0.5px;">
                        PO Numbers
                    </label>
                    <div class="d-flex flex-column gap-2" style="max-height: 240px; overflow-y: auto;">
                        <template x-for="(num, idx) in viewingPosModal.allPoNumbers" :key="idx">
                            <div class="d-flex align-items-center justify-content-between p-2 rounded-3 border bg-white shadow-sm">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-file-earmark-text text-primary"></i>
                                    <span class="fw-bold text-dark" x-text="num"></span>
                                </div>
                                <span class="badge bg-light text-muted border" x-text="'#' + (idx + 1)"></span>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-1 bg-white">
                    <button type="button" class="btn btn-sm btn-light border rounded-pill px-4 w-100 fw-bold shadow-sm" data-bs-dismiss="modal">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- VIEW COLLAPSED VARIATIONS MODAL (Color & Size Breakdown) -->
    <div class="modal fade" id="viewVariationsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
                <div class="modal-header border-0 pb-2 px-4 pt-4 bg-white d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                            <i class="bi bi-layers-fill fs-6"></i>
                        </div>
                        <div>
                            <h6 class="fw-bolder text-dark mb-0">Color & Size Variations</h6>
                            <span class="text-muted small" x-text="'Style: ' + (viewingVariationsModal.styleNo || '-') + ' • ' + (viewingVariationsModal.variations ? viewingVariationsModal.variations.length : 0) + ' variation(s)'"></span>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <div class="p-2 mb-3 bg-light rounded-3 small">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Style ID:</span>
                            <span class="fw-bold text-dark text-truncate ms-2" x-text="viewingVariationsModal.styleNo || '-'"></span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Season:</span>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary border fw-semibold ms-2" x-text="viewingVariationsModal.season || '-'"></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Consolidation:</span>
                            <span class="text-muted fst-italic ms-2">1 Test Report applies to this entire Style.</span>
                        </div>
                    </div>

                    <label class="form-label text-muted text-uppercase fw-bold mb-2" style="font-size: 11px; letter-spacing: 0.5px;">
                        Collapsed Variations Breakdown
                    </label>
                    <div class="table-responsive rounded-3 border" style="max-height: 260px; overflow-y: auto;">
                        <table class="table table-sm table-hover mb-0 align-middle" style="font-size: 12px;">
                            <thead class="table-light">
                                <tr>
                                    <th class="py-2 px-3">Color</th>
                                    <th class="py-2 px-3">Size</th>
                                    <th class="py-2 px-3 text-end">Order Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(v, vIdx) in viewingVariationsModal.variations" :key="vIdx">
                                    <tr>
                                        <td class="py-2 px-3 fw-semibold text-dark" x-text="v.color || '-'"></td>
                                        <td class="py-2 px-3"><span class="badge bg-light text-dark border" x-text="v.size || '-'"></span></td>
                                        <td class="py-2 px-3 text-end fw-bold" x-text="v.qty ? Number(v.qty).toLocaleString() : '-'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-1 bg-white">
                    <button type="button" class="btn btn-sm btn-light border rounded-pill px-4 w-100 fw-bold shadow-sm" data-bs-dismiss="modal">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ENTER TEST RESULTS MODAL -->

    <div class="modal fade" id="addTestModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
                <!-- Modal Header -->
                <div class="modal-header border-0 pb-0 px-4 pt-4 bg-white d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white flex-shrink-0" style="width: 44px; height: 44px; background: linear-gradient(135deg, #124E78, #00A39A);">
                            <i class="bi bi-flask-fill fs-5"></i>
                        </div>
                        <div>
                            <h5 class="fw-bolder text-dark mb-0">Enter Test Results & Citations</h5>
                            <p class="text-muted small mb-0">Input lab testing details, protocol citations, and report ID for this PO.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body px-4 py-3">
                    <!-- Loading PO Details State -->
                    <div x-show="isLoadingPoDetails" class="text-center py-5">
                        <div class="spinner-border text-primary mb-2" role="status"></div>
                        <div class="fw-semibold text-muted">Retrieving PO & Product specifications from ERP...</div>
                    </div>

                    <div x-show="!isLoadingPoDetails">
                        <!-- PO Summary Card -->
                        <div class="card border-0 rounded-4 mb-4" style="background: linear-gradient(135deg, #f8fafc, #f1f5f9); border: 1px solid #e2e8f0 !important;">
                            <div class="card-body p-3">
                                <div class="row g-3">
                                    <div class="col-md-3">
    <span class="text-uppercase fw-bold text-muted" style="font-size: 10px; letter-spacing: 0.5px;">PO Number</span>
    <div class="fw-bolder text-dark d-flex align-items-center gap-1">
        <span x-text="formData.poNumber || 'N/A'"></span>
        <template x-if="formData.allPoNumbers && formData.allPoNumbers.length > 1">
            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25" 
                  style="font-size: 10px;" 
                  x-text="'+' + (formData.allPoNumbers.length - 1) + ' in group'">
            </span>
        </template>
    </div>
</div>
                                    <div class="col-md-3">
                                        <span class="text-uppercase fw-bold text-muted" style="font-size: 10px; letter-spacing: 0.5px;">Style No</span>
                                        <div class="fw-bold text-primary" x-text="formData.styleNo || 'N/A'"></div>
                                    </div>
                                    <div class="col-md-3">
                                        <span class="text-uppercase fw-bold text-muted" style="font-size: 10px; letter-spacing: 0.5px;">Product Name</span>
                                        <div class="fw-semibold text-dark text-truncate" x-text="formData.productName || 'N/A'" :title="formData.productName"></div>
                                    </div>
                                    <div class="col-md-3">
                                        <span class="text-uppercase fw-bold text-muted" style="font-size: 10px; letter-spacing: 0.5px;">Factory</span>
                                        <div class="fw-semibold text-secondary text-truncate" x-text="formData.factoryName || 'N/A'" :title="formData.factoryName"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Certificate & Testing Form -->
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
                                    formData.certType = val;
                                    this.open = false;
                                    this.query = '';
                                }
                            }" @click.away="open = false">
                                <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Certificate Type*</label>
                                <div class="position-relative">
                                    <button type="button" 
                                            class="form-control bg-light border-light fw-semibold rounded-3 text-start ps-3 pe-4 py-2 d-flex align-items-center justify-content-between shadow-none" 
                                            @click="open = !open; if(open) $nextTick(() => $refs.certTypeSearchInput?.focus())">
                                        <span class="text-truncate" x-text="options.find(o => o.value === formData.certType)?.label || 'Select Certificate Type'"></span>
                                        <i class="bi bi-chevron-down text-muted" style="font-size: 11px;"></i>
                                    </button>
                                </div>

                                <div x-show="open" 
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
                                                   placeholder="Type to search type..." 
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
                                                    :class="formData.certType === opt.value ? 'bg-primary text-white' : ''"
                                                    @click="select(opt.value)">
                                                <div>
                                                    <div class="fw-bold" x-text="opt.label"></div>
                                                    <div class="small opacity-75" style="font-size: 11px;" x-text="opt.desc"></div>
                                                </div>
                                                <i x-show="formData.certType === opt.value" class="bi bi-check-lg ms-2"></i>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4" x-show="formData.certType === 'CPC'">
                                <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Target Age Group</label>
                                <input type="text" x-model="formData.intendedUse" class="form-control bg-light border-light fw-semibold px-3 py-2 rounded-3" placeholder="e.g. Ages 3+">
                            </div>

                            <div class="col-md-4" x-show="formData.certType === 'CPC'">
                                <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Children's Product Category</label>
                                <input type="text" x-model="formData.childrenProduct" class="form-control bg-light border-light fw-semibold px-3 py-2 rounded-3" placeholder="e.g. Toys, Sleepwear">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Manufacture Date</label>
                                <input type="month" x-model="formData.manufactureDate" class="form-control bg-light border-light fw-semibold px-3 py-2 rounded-3">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Lot / Production Order #</label>
                                <input type="text" x-model="formData.lotNumber" class="form-control bg-light border-light fw-semibold px-3 py-2 rounded-3">
                            </div>
                        </div>

                        <!-- Lab Tests Header & Actions -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="fw-bolder text-dark mb-0">Safety Standard Tests & Citations</h6>
                                <span class="text-muted small">Add the testing protocols conducted by your laboratory.</span>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" @click="addLabTest()" class="btn btn-outline-primary btn-sm fw-bold rounded-pill px-3">
                                    <i class="bi bi-plus-lg me-1"></i> Add Lab Test
                                </button>
                                <button type="button" @click="addExclusion()" class="btn btn-outline-secondary btn-sm fw-bold rounded-pill px-3">
                                    <i class="bi bi-plus-lg me-1"></i> Add Exclusion
                                </button>
                            </div>
                        </div>

                        <!-- Test Cards Loop -->
                        <template x-for="(test, index) in formData.tests" :key="index">
                            <div class="card border-0 shadow-sm rounded-4 mb-3 position-relative" style="border: 1px solid #e2e8f0 !important;">
                                <button type="button" @click="removeLabTest(index)" class="btn btn-sm btn-link text-danger position-absolute top-0 end-0 m-3 text-decoration-none" title="Remove Test">
                                    <i class="bi bi-trash fs-5"></i>
                                </button>
                                <div class="card-body p-4">
                                    <!-- Exclusion Form -->
                                    <div x-show="test.isExclusion">
                                        <div class="d-flex align-items-center gap-2 mb-2 text-warning fw-bold small">
                                            <i class="bi bi-shield-exclamation fs-5"></i> Safety Standard Exclusion
                                        </div>
                                        <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Exclusion Reason*</label>
                                        <input type="text" x-model="test.exclusionReason" class="form-control bg-light border-light fw-semibold px-3 py-2 rounded-3" placeholder="State reason for test exclusion...">
                                    </div>

                                    <!-- Standard Lab Test Form -->
                                    <div x-show="!test.isExclusion" class="row g-3">
                                        <!-- Lab Identity Banner -->
                                        <div class="col-12">
                                            <div class="alert alert-success py-2 px-3 mb-0 d-flex align-items-center gap-2 border-0 rounded-3" style="background-color: #f0fdf4; color: #166534;">
                                                <i class="bi bi-shield-check fs-5"></i>
                                                <div>
                                                    <strong>Testing Laboratory:</strong> <span x-text="test.labName"></span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Test Report ID / Ref #*</label>
                                            <input type="text" x-model="test.testReportId" class="form-control bg-light border-light fw-semibold px-3 py-2 rounded-3" placeholder="e.g. TR-2026-9812">
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Test Date*</label>
                                            <input type="date" x-model="test.testDate" class="form-control bg-light border-light fw-semibold px-3 py-2 rounded-3">
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Lab CPSC ID</label>
                                            <input type="text" x-model="test.labCpscId" class="form-control bg-light border-light fw-semibold px-3 py-2 rounded-3" placeholder="e.g. 1045">
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Test Report URL (Optional)</label>
                                            <input type="url" x-model="test.testReportUrl" class="form-control bg-light border-light fw-semibold px-3 py-2 rounded-3" placeholder="https://...">
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
                                                           :disabled="test.isUploadingFile"
                                                           @change="uploadTestFile($event, index)">
                                                    <span x-show="test.isUploadingFile" class="spinner-border spinner-border-sm text-primary position-absolute end-0 top-50 translate-middle-y me-3" role="status"></span>
                                                </div>
                                                <div class="text-muted small mt-1" style="font-size: 10px;">PDF, DOC, images (max 20MB)</div>
                                            </div>

                                            <!-- When file is uploaded -->
                                            <div x-show="test.testReportFile" class="d-flex align-items-center justify-content-between p-2 rounded-3 bg-light border border-success border-opacity-25">
                                                <div class="d-flex align-items-center gap-2 overflow-hidden">
                                                    <i class="bi bi-file-earmark-check-fill text-success fs-5"></i>
                                                    <a :href="test.testReportFileUrl || ('/storage/' + test.testReportFile)" target="_blank" class="fw-bold small text-dark text-truncate text-decoration-none" style="max-width: 150px;" x-text="test.testReportFileName || 'Uploaded Report File'"></a>
                                                </div>
                                                <button type="button" @click="removeTestFile(index)" class="btn btn-sm btn-link text-danger p-0 border-0 ms-2 text-decoration-none" title="Remove attachment">
                                                    <i class="bi bi-trash3-fill"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Component Testing -->
                                        <div class="col-12">
                                            <div class="form-check">
                                                <input type="checkbox" x-model="test.componentTesting" class="form-check-input" :id="'compTest_' + index">
                                                <label class="form-check-label fw-bold text-dark small" :for="'compTest_' + index">Uses Component Testing?</label>
                                            </div>
                                            <div class="mt-2" x-show="test.componentTesting" x-transition>
                                                <input type="text" x-model="test.componentDetails" class="form-control bg-light border-light fw-semibold px-3 py-2 rounded-3" placeholder="Describe component testing details...">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Applicable Citations -->
                                    <div class="mt-3 pt-3 border-top">
                                        <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Applicable Safety Standard Citations*</label>
                                        <div class="d-flex flex-wrap gap-2 mb-3">
                                            <template x-for="(cit, cIdx) in test.citations" :key="cIdx">
                                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-2 d-inline-flex align-items-center gap-2">
                                                    <span class="fw-bold" x-text="cit.code"></span>
                                                    <button type="button" 
                                                            @click.stop="removeCitation(index, cIdx)" 
                                                            class="btn btn-link p-0 text-danger border-0 text-decoration-none d-inline-flex align-items-center" 
                                                            title="Remove citation">
                                                        <i class="bi bi-x-circle-fill" style="font-size: 14px;"></i>
                                                    </button>
                                                </span>
                                            </template>
                                            <span x-show="!test.citations || test.citations.length === 0" class="text-muted small fst-italic py-1">No citations added yet.</span>
                                        </div>

                                        <!-- Searchable Safety Citations Selector (Type & Select) -->
                                        <div class="position-relative" style="max-width: 560px;" x-data="{
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
                                                        @click="open = !open; if(open) $nextTick(() => $refs.citSearchInput?.focus())">
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
                                                               x-ref="citSearchInput" 
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
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer border-0 px-4 pb-4 pt-2 bg-white d-flex justify-content-between">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold border" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" 
                            class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm d-inline-flex align-items-center gap-2" 
                            :disabled="isSubmitting || isLoadingPoDetails" 
                            @click="submitTestReport()">
                        <span x-show="isSubmitting" class="spinner-border spinner-border-sm" role="status"></span>
                        <i x-show="!isSubmitting" class="bi bi-check2-circle fs-5"></i>
                        <span>Submit & Save Test Report</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
    function labPosApp() {
        return {
            pos: [],
            styles: @json($styles ?? []),
            selectedStyle: '',
            styleDropdownOpen: false,
            styleSearchQuery: '',
            poNumbers: @json($poNumbers ?? []),
            selectedPo: '',
            poDropdownOpen: false,
            searchTerm: '',
            isLoading: false,
            isLoadingPoDetails: false,
            isSubmitting: false,
            userLabName: '{{ addslashes($userLabName ?? "Testing Laboratory") }}',

            get filteredStyles() {
                if (!this.styleSearchQuery.trim()) return this.styles;
                const q = this.styleSearchQuery.toLowerCase().trim();
                return this.styles.filter(s => s.toLowerCase().includes(q));
            },

            get filteredPoList() {
                if (!this.selectedPo || !this.selectedPo.trim()) {
                    return this.poNumbers.slice(0, 50);
                }
                const q = this.selectedPo.toLowerCase().trim();
                return this.poNumbers.filter(p => p.toLowerCase().includes(q)).slice(0, 50);
            },

            selectStyle(s) {
                this.selectedStyle = s;
                this.styleDropdownOpen = false;
                this.styleSearchQuery = '';
                this.fetchPos(1);
            },

            clearStyle() {
                this.selectedStyle = '';
                this.styleSearchQuery = '';
                this.fetchPos(1);
            },

            selectPo(p) {
                this.selectedPo = p;
                this.poDropdownOpen = false;
                this.fetchPos(1);
            },

            clearPo() {
                this.selectedPo = '';
                this.poDropdownOpen = false;
                this.fetchPos(1);
            },

            pagination: {
                currentPage: 1,
                lastPage: 1,
                total: 0,
                from: 0,
                to: 0,
            },

            notification: {
                show: false,
                type: 'success',
                title: '',
                message: ''
            },

            viewingPosModal: {
                styleNo: '',
                season: '',
                allPoNumbers: []
            },

            viewingVariationsModal: {
                styleNo: '',
                season: '',
                variations: []
            },

            citationMaster: @json($citationMaster ?? []),

            formData: {
                poNumber: '',
                season: '',
                poDate: '',
                buyer: '',
                styleNo: '',
                productName: '',
                factoryName: '',
                factoryAddress: '',
                certType: 'GCC',
                intendedUse: '',
                childrenProduct: '',
                manufactureDate: '',
                lotNumber: '',
                primaryProductId: '',
                productIdType: 'Model #',
                versionId: '',
                status: 'Complete',
                tests: []
            },

            init() {
                this.fetchPos(1);
            },

            showToast(type, title, message) {
                this.notification.type = type;
                this.notification.title = title;
                this.notification.message = message;
                this.notification.show = true;
                setTimeout(() => { this.notification.show = false; }, 5000);
            },

            resetFilters() {
                this.selectedStyle = '';
                this.styleSearchQuery = '';
                this.styleDropdownOpen = false;
                this.selectedPo = '';
                this.poDropdownOpen = false;
                this.searchTerm = '';
                this.fetchPos(1);
            },

            fetchPos(page = 1) {
                this.isLoading = true;
                const params = new URLSearchParams({
                    page: page,
                    style: this.selectedStyle,
                    po_number: this.selectedPo,
                    search: this.searchTerm
                });

                fetch(`{{ route('lab.pos.data') }}?${params.toString()}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.json())
                .then(data => {
                    this.pos = data.data || [];
                    this.pagination.currentPage = data.current_page || 1;
                    this.pagination.lastPage = data.last_page || 1;
                    this.pagination.total = data.total || 0;
                    this.pagination.from = data.from || 0;
                    this.pagination.to = data.to || 0;
                    this.isLoading = false;
                })
                .catch(err => {
                    console.error('Failed to fetch POs:', err);
                    this.isLoading = false;
                });
            },

            openViewPosModal(po) {
                this.viewingPosModal.styleNo = po.styleNo || '';
                this.viewingPosModal.season = po.season || '';
                this.viewingPosModal.allPoNumbers = (po.allPoNumbers && po.allPoNumbers.length > 0) ? po.allPoNumbers : [po.poNumber];
                const modal = new bootstrap.Modal(document.getElementById('viewGroupedPosModal'));
                modal.show();
            },

            openVariationsModal(po) {
                this.viewingVariationsModal.styleNo = po.styleNo || '';
                this.viewingVariationsModal.season = po.season || '';
                this.viewingVariationsModal.variations = po.variations || [];
                const modal = new bootstrap.Modal(document.getElementById('viewVariationsModal'));
                modal.show();
            },

            openAddTestModal(po) {
                this.isLoadingPoDetails = true;
                
                const poNumber = typeof po === 'object' ? po.poNumber : po;
                const styleNo = typeof po === 'object' ? (po.styleNo || '') : '';
                const allPos = (typeof po === 'object' && po.allPoNumbers && po.allPoNumbers.length > 0)
                    ? po.allPoNumbers
                    : [poNumber];

                // Initialize default form with consolidated style data
                this.formData = {
                    poNumber: poNumber,
                    season: (typeof po === 'object' && po.season) ? po.season : '',
                    allPoNumbers: allPos, // Preserves the entire group list for submission
                    poDate: (typeof po === 'object' && po.poDate) ? po.poDate : '',
                    buyer: (typeof po === 'object' && po.buyer) ? po.buyer : '',
                    styleNo: styleNo,
                    productName: (typeof po === 'object' && po.productName) ? po.productName : '',
                    factoryName: (typeof po === 'object' && po.factoryName) ? po.factoryName : '',
                    factoryAddress: '',
                    certType: 'GCC',
                    intendedUse: '',
                    childrenProduct: '',
                    manufactureDate: new Date().toISOString().slice(0, 7),
                    lotNumber: poNumber,
                    primaryProductId: styleNo || poNumber,
                    productIdType: 'Model #',
                    versionId: Math.random().toString(16).substring(2, 10).toUpperCase(),
                    status: 'Complete',
                    tests: [{
                        labType: 'CPSC-Accepted',
                        labName: this.userLabName,
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
                    }]
                };

                const modal = new bootstrap.Modal(document.getElementById('addTestModal'));
                modal.show();

                // Fetch real ERP PO details targeting the specific selected style
                const url = `{{ url('stafford-certs/erp-po-lookup') }}?po=${encodeURIComponent(poNumber)}&style=${encodeURIComponent(styleNo)}`;
                fetch(url, {
                    headers: { 'Accept': 'application/json' }
                })
                .then(r => r.json())
                .then(data => {
                    if (data && data.found) {
                        if (data.season) this.formData.season = data.season;
                        this.formData.poDate = data.poDate || this.formData.poDate;
                        this.formData.buyer = data.buyer || data.importerName || this.formData.buyer;
                        this.formData.styleNo = styleNo || data.styleNo || '';
                        this.formData.productName = (typeof po === 'object' && po.productName) ? po.productName : (data.productName || data.description || '');
                        this.formData.factoryName = (typeof po === 'object' && po.factoryName) ? po.factoryName : (data.factoryName || '');
                        this.formData.factoryAddress = data.factoryAddress || '';
                        this.formData.factoryCity = data.factoryCity || '';
                        this.formData.factoryState = data.factoryState || '';
                        this.formData.factoryCountry = data.factoryCountry || '';
                        this.formData.factoryPostal = data.factoryPostal || '';
                        this.formData.factoryContact = data.factoryContact || '';
                        this.formData.factoryPhone = data.factoryPhone || '';
                        this.formData.importerName = data.importerName || data.buyer || '';
                        this.formData.importerAddress = data.importerAddress || '';
                        this.formData.importerCity = data.importerCity || '';
                        this.formData.importerState = data.importerState || '';
                        this.formData.importerCountry = data.importerCountry || '';
                        this.formData.importerPostal = data.importerPostal || '';
                        this.formData.primaryProductId = styleNo || data.primaryProductId || data.upc || poNumber;
                        this.formData.productIdType = data.productIdType || 'Model #';
                        this.formData.lotNumber = data.lotNumber || poNumber;
                        if (data.manufactureDate) {
                            this.formData.manufactureDate = data.manufactureDate;
                        }
                        this.formData.brandName    = data.brandName    || '';
                        this.formData.color        = data.color        || '';
                        this.formData.description  = data.description  || '';
                        this.formData.factoryGLN   = data.factoryGLN   || '';
                        this.formData.factoryAltId = data.factoryAltId || '';
                    }
                    this.isLoadingPoDetails = false;
                })
                .catch(err => {
                    console.error('Failed to lookup PO details:', err);
                    this.isLoadingPoDetails = false;
                });
            },

            addLabTest() {
                this.formData.tests.push({
                    labType: 'CPSC-Accepted',
                    labName: this.userLabName,
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
                this.formData.tests.push({
                    labType: 'N/A',
                    labName: this.userLabName,
                    isExclusion: true,
                    exclusionReason: '',
                    citations: []
                });
            },

            removeLabTest(index) {
                if (this.formData.tests.length > 1) {
                    this.formData.tests.splice(index, 1);
                } else {
                    alert('You must include at least one test record or exclusion.');
                }
            },

            uploadTestFile(event, testIndex) {
                const file = event.target.files[0];
                if (!file) return;

                if (file.size > 20 * 1024 * 1024) {
                    alert('File size exceeds 20MB limit.');
                    event.target.value = '';
                    return;
                }

                const test = this.formData.tests[testIndex];
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
                const test = this.formData.tests[testIndex];
                test.testReportFile = '';
                test.testReportFileName = '';
                test.testReportFileUrl = '';
            },

            addCitation(testIndex, citationCode) {
                if (!citationCode) return;
                const test = this.formData.tests[testIndex];
                if (!test.citations) test.citations = [];
                
                const exists = test.citations.some(c => c.code === citationCode);
                if (!exists) {
                    const desc = this.citationMaster[citationCode] || citationCode;
                    test.citations.push({ code: citationCode, desc: desc });
                }
            },

            removeCitation(testIndex, citationIndex) {
                this.formData.tests[testIndex].citations.splice(citationIndex, 1);
            },

            submitTestReport() {
                // Validation
                if (!this.formData.poNumber) {
                    alert('PO Number is missing.');
                    return;
                }

                // Check that standard lab tests have report ID and citations
                for (let i = 0; i < this.formData.tests.length; i++) {
                    const t = this.formData.tests[i];
                    if (!t.isExclusion) {
                        if (!t.testReportId) {
                            alert(`Please enter Test Report ID for Test #${i + 1}.`);
                            return;
                        }
                        if (!t.citations || t.citations.length === 0) {
                            alert(`Please add at least one applicable Safety Citation for Test #${i + 1}.`);
                            return;
                        }
                    } else {
                        if (!t.exclusionReason) {
                            alert(`Please provide Exclusion Reason for Exclusion #${i + 1}.`);
                            return;
                        }
                    }
                }

                this.isSubmitting = true;

                // Ensure primary product fields are valid
                if (!this.formData.primaryProductId) {
                    this.formData.primaryProductId = this.formData.styleNo || this.formData.poNumber;
                }
                if (!this.formData.productName) {
                    this.formData.productName = 'Product for PO ' + this.formData.poNumber;
                }
                if (!this.formData.factoryName) {
                    this.formData.factoryName = 'Factory Branch';
                }

                fetch('{{ route("stafford-certs.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.formData)
                })
                .then(r => r.json().then(data => ({ status: r.status, body: data })))
                .then(({ status, body }) => {
                    this.isSubmitting = false;
                    if (status >= 200 && status < 300 && (body.success || body.data)) {
                        // Success!
                        const modalEl = document.getElementById('addTestModal');
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();

                        this.showToast('success', 'Report Created', `Test report for PO #${this.formData.poNumber} has been successfully submitted!`);
                        
                        // Update local row state (checks primary PO or any grouped PO for this style)
                        const found = this.pos.find(p => (p.poNumber === this.formData.poNumber || (p.allPoNumbers && p.allPoNumbers.includes(this.formData.poNumber))) && (!p.styleNo || !this.formData.styleNo || p.styleNo === this.formData.styleNo));
                        if (found) {
                            found.hasReport = true;
                            found.isMyLab = true;
                            found.submittingLab = this.userLabName;
                        }
                    } else {
                        let errMsg = body.message || 'Failed to submit report.';
                        if (body.errors) {
                            errMsg += ' ' + Object.values(body.errors).flat().join(' ');
                        }
                        alert(errMsg);
                    }
                })
                .catch(err => {
                    this.isSubmitting = false;
                    console.error('Error submitting test report:', err);
                    alert('Network error while saving test report. Please check your connection.');
                });
            }
        };
    }
</script>
@endpush
@endsection
