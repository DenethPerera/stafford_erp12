@extends('layouts.app')

@section('header', auth()->user()->hasRole('lab') ? 'Testing Tasks & Reports' : 'Stafford CPSC/UL Product Qualifications')

@section('content')
<div x-data="certsDashboard()" x-init="initData()" @search.window="searchQuery = $event.detail" class="d-flex flex-column gap-4">
    @role('super_admin')
    <!-- Top summary cards for Super Admin -->

    <div x-data="{ showCards: true }" class="mb-3">

    <div class="d-flex justify-content-end mb-2">
        <button 
            type="button" 
            class="btn btn-sm btn-light border rounded-pill shadow-sm"
            @click="showCards = !showCards"
        >
            <!-- Toggles arrow up/down depending on showCards state -->
            <i class="bi" :class="showCards ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
        </button>
    </div>
    <div class="row g-3 mb-2" x-show="showCards" x-transition>
    <!-- Card 1 -->
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-3 d-flex flex-column align-items-center justify-content-center text-center">
                <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center mb-2" style="width: 36px; height: 36px;">
                    <i class="bi bi-folder-fill fs-5"></i>
                </div>
                <p class="text-uppercase fw-bolder text-muted mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Total Records</p>
                <h3 class="fs-2 fw-bolder text-dark mb-0" x-text="records.length">0</h3>
            </div>
        </div>
    </div>

    <!-- Card 2 -->
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-3 d-flex flex-column align-items-center justify-content-center text-center">
                <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center mb-2" style="width: 36px; height: 36px;">
                    <i class="bi bi-shield-check fs-5"></i>
                </div>
                <p class="text-uppercase fw-bolder text-muted mb-1" style="font-size: 11px; letter-spacing: 0.5px;">GCC Certificates</p>
                <h3 class="fs-2 fw-bolder text-dark mb-0" x-text="records.filter(r => r.certType && r.certType.includes('GCC')).length">0</h3>
            </div>
        </div>
    </div>

    <!-- Card 3 -->
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-3 d-flex flex-column align-items-center justify-content-center text-center">
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center mb-2" style="width: 36px; height: 36px;">
                    <i class="bi bi-building fs-5"></i>
                </div>
                <p class="text-uppercase fw-bolder text-muted mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Active Factories</p>
                <h3 class="fs-2 fw-bolder text-dark mb-0" x-text="uniqueFactoriesCount()">0</h3>
            </div>
        </div>
    </div>

    <!-- Card 4 -->
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-3 d-flex flex-column align-items-center justify-content-center text-center">
                <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center mb-2" style="width: 36px; height: 36px;">
                    <i class="bi bi-check-circle-fill fs-5"></i>
                </div>
                <p class="text-uppercase fw-bolder text-muted mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Completed</p>
                <h3 class="fs-2 fw-bolder text-dark mb-0" x-text="records.filter(r => r.status === 'Complete').length">0</h3>
            </div>
        </div>
    </div>
</div>
    @endrole

    <!-- Bulk Action Toolbar -->
    <div x-show="selectedRecords.length > 0" 
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 translate-y-4 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 scale-95"
         class="fixed-bottom mb-4 z-3" 
         x-cloak 
         style="pointer-events: none;">
        <div class="d-flex justify-content-center">
            <div class="d-flex align-items-center px-4 py-2 gap-3" 
                 style="pointer-events: auto; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border: 1.5px solid rgba(0, 163, 154, 0.25); border-radius: 9999px; box-shadow: 0 16px 36px -4px rgba(18, 78, 120, 0.18), 0 4px 14px rgba(0, 163, 154, 0.12);">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge rounded-pill px-2.5 py-1 text-white shadow-xs fw-bold" 
                          style="background: linear-gradient(135deg, #124E78, #00A39A); font-size: 0.8rem;" 
                          x-text="selectedRecords.length"></span>
                    <span class="small fw-bold text-secondary" style="font-size: 0.85rem;">records selected</span>
                </div>

                <div class="vr opacity-25" style="height: 22px; background-color: #00A39A;"></div>

                <div class="d-flex gap-2 align-items-center">
                    <!-- Sync Button -->
                    <button type="button" 
                            @click="performBulkAction('sync-cpsc')" 
                            class="btn btn-sm rounded-pill fw-bold px-3 py-1.5 text-white shadow-sm d-flex align-items-center gap-2"
                            style="background: linear-gradient(135deg, #124E78, #00A39A); border: none; font-size: 0.85rem;">
                        <i class="bi bi-cloud-arrow-up-fill"></i>
                        <span>Sync</span>
                    </button>

                    <!-- PDF Button -->
                    <button type="button" 
                            @click="exportBulkPdf()" 
                            class="btn btn-sm rounded-pill fw-bold px-3 py-1.5 shadow-sm d-flex align-items-center gap-2"
                            style="background: rgba(0, 163, 154, 0.1); color: #00A39A; border: 1.5px solid rgba(0, 163, 154, 0.35); font-size: 0.85rem;">
                        <i class="bi bi-file-earmark-pdf-fill"></i>
                        <span>PDF</span>
                    </button>

                    <!-- Reject Button -->
                    @role('super_admin')
                    <button type="button" 
                            @click="performBulkAction('reject')" 
                            class="btn btn-sm rounded-pill fw-bold px-3 py-1.5 shadow-sm d-flex align-items-center gap-2"
                            style="background: rgba(220, 53, 69, 0.1); color: #dc3545; border: 1.5px solid rgba(220, 53, 69, 0.35); font-size: 0.85rem;">
                        <i class="bi bi-x-circle-fill"></i>
                        <span>Reject</span>
                    </button>
                    @endrole
                </div>

                <div class="vr opacity-25" style="height: 22px; background-color: #00A39A;"></div>

                <!-- Deselect/Close Button -->
                <button type="button" 
                        @click="selectedRecords = []" 
                        class="btn btn-sm btn-link text-muted p-1 text-decoration-none rounded-circle d-flex align-items-center justify-content-center" 
                        style="width: 28px; height: 28px; transition: all 0.2s ease;"
                        title="Clear selection"
                        onmouseover="this.style.backgroundColor='rgba(0, 163, 154, 0.1)'; this.style.color='#00A39A';"
                        onmouseout="this.style.backgroundColor='transparent'; this.style.color='#6c757d';">
                    <i class="bi bi-x-lg" style="font-size: 0.85rem;"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex flex-wrap align-items-center gap-3 flex-grow-1">
                    <!-- Searchable Style Selector (Type & Select) -->
                    <div class="position-relative" @click.away="styleDropdownOpen = false" style="min-width: 230px; max-width: 290px;">
                        <div class="position-relative">
                            <button type="button" 
                                    class="form-control bg-light border fw-semibold rounded-pill text-start ps-3 pe-5 py-2 d-flex align-items-center justify-content-between shadow-sm" 
                                    :class="styleFilter ? 'border-primary bg-primary bg-opacity-10 text-primary' : 'text-secondary'"
                                    @click="styleDropdownOpen = !styleDropdownOpen; if(styleDropdownOpen) $nextTick(() => $refs.styleSearchInput?.focus())">
                                <div class="d-flex align-items-center gap-2 text-truncate pe-2">
                                    <i class="bi bi-tag-fill" :class="styleFilter ? 'text-primary' : 'text-muted'"></i>
                                    <span class="text-truncate fw-bold" x-text="styleFilter || ('All Styles (' + allStyles.length + ')')"></span>
                                </div>
                                <i class="bi bi-chevron-down text-muted small"></i>
                            </button>
                            <button type="button" 
                                    x-show="styleFilter" 
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
                             class="dropdown-menu show shadow-lg border-0 rounded-4 p-2 mt-1 position-absolute z-3 w-100" 
                             style="min-width: 270px; max-height: 320px; overflow: hidden; display: flex; flex-direction: column;" 
                             x-cloak>
                            <!-- Search Box Inside Dropdown -->
                            <div class="p-1 mb-2">
                                <div class="input-group input-group-sm bg-light rounded-pill border overflow-hidden">
                                    <span class="input-group-text bg-transparent border-0 text-muted ps-3 pe-2"><i class="bi bi-search"></i></span>
                                    <input type="text" 
                                           x-ref="styleSearchInput" 
                                           x-model="styleSearchQuery" 
                                           placeholder="Type to search styles..." 
                                           class="form-control bg-transparent border-0 shadow-none fw-semibold">
                                    <button type="button" x-show="styleSearchQuery" @click="styleSearchQuery = ''" class="btn btn-sm btn-transparent border-0 text-muted pe-3">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Scrollable List of Styles -->
                            <div class="overflow-y-auto flex-grow-1" style="max-height: 230px;">
                                <button type="button" 
                                        class="dropdown-item rounded-3 py-2 fw-semibold d-flex align-items-center justify-content-between"
                                        :class="!styleFilter ? 'bg-primary text-white' : ''"
                                        @click="selectStyle('')">
                                    <span>All Styles</span>
                                    <span class="badge rounded-pill" :class="!styleFilter ? 'bg-white text-primary' : 'bg-light text-muted'" x-text="allStyles.length"></span>
                                </button>
                                <template x-for="s in filteredStyles" :key="s">
                                    <button type="button" 
                                            class="dropdown-item rounded-3 py-2 fw-semibold text-truncate d-flex align-items-center justify-content-between" 
                                            :class="styleFilter === s ? 'bg-primary text-white' : ''"
                                            @click="selectStyle(s)"
                                            :title="s">
                                        <span class="text-truncate" x-text="s"></span>
                                        <i x-show="styleFilter === s" class="bi bi-check-lg ms-2"></i>
                                    </button>
                                </template>
                                <div x-show="filteredStyles.length === 0" class="text-center py-3 text-muted small fw-semibold">
                                    No matching styles found
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Searchable Factory Selector (Type & Select) -->
                    <div class="position-relative" @click.away="factoryDropdownOpen = false" style="min-width: 250px; max-width: 330px;">
                        <div class="position-relative">
                            <button type="button" 
                                    class="form-control bg-light border fw-semibold rounded-pill text-start ps-3 pe-5 py-2 d-flex align-items-center justify-content-between shadow-sm" 
                                    :class="factoryFilter ? 'border-primary bg-primary bg-opacity-10 text-primary' : 'text-secondary'"
                                    @click="factoryDropdownOpen = !factoryDropdownOpen; if(factoryDropdownOpen) $nextTick(() => $refs.factorySearchInput?.focus())">
                                <div class="d-flex align-items-center gap-2 text-truncate pe-2">
                                    <i class="bi bi-building" :class="factoryFilter ? 'text-primary' : 'text-muted'"></i>
                                    <span class="text-truncate fw-bold" x-text="factoryFilter || ('All Factories (' + allFactories.length + ')')"></span>
                                </div>
                                <i class="bi bi-chevron-down text-muted small"></i>
                            </button>
                            <button type="button" 
                                    x-show="factoryFilter" 
                                    @click.stop="clearFactory()" 
                                    class="btn btn-sm btn-link text-muted position-absolute end-0 top-50 translate-middle-y me-4 text-decoration-none p-0" 
                                    title="Clear factory filter">
                                <i class="bi bi-x-circle-fill"></i>
                            </button>
                        </div>

                        <!-- Searchable Dropdown Popup -->
                        <div x-show="factoryDropdownOpen" 
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 transform scale-95"
                             x-transition:enter-end="opacity-100 transform scale-100"
                             class="dropdown-menu show shadow-lg border-0 rounded-4 p-2 mt-1 position-absolute z-3 w-100" 
                             style="min-width: 290px; max-height: 320px; overflow: hidden; display: flex; flex-direction: column;" 
                             x-cloak>
                            <!-- Search Box Inside Dropdown -->
                            <div class="p-1 mb-2">
                                <div class="input-group input-group-sm bg-light rounded-pill border overflow-hidden">
                                    <span class="input-group-text bg-transparent border-0 text-muted ps-3 pe-2"><i class="bi bi-search"></i></span>
                                    <input type="text" 
                                           x-ref="factorySearchInput" 
                                           x-model="factorySearchQuery" 
                                           placeholder="Type to search factories..." 
                                           class="form-control bg-transparent border-0 shadow-none fw-semibold">
                                    <button type="button" x-show="factorySearchQuery" @click="factorySearchQuery = ''" class="btn btn-sm btn-transparent border-0 text-muted pe-3">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Scrollable List of Factories -->
                            <div class="overflow-y-auto flex-grow-1" style="max-height: 230px;">
                                <button type="button" 
                                        class="dropdown-item rounded-3 py-2 fw-semibold d-flex align-items-center justify-content-between"
                                        :class="!factoryFilter ? 'bg-primary text-white' : ''"
                                        @click="selectFactory('')">
                                    <span>All Factories</span>
                                    <span class="badge rounded-pill" :class="!factoryFilter ? 'bg-white text-primary' : 'bg-light text-muted'" x-text="allFactories.length"></span>
                                </button>
                                <template x-for="f in filteredFactories" :key="f.name">
                                    <button type="button" 
                                            class="dropdown-item rounded-3 py-2 fw-semibold text-truncate d-flex align-items-center justify-content-between" 
                                            :class="factoryFilter === f.name ? 'bg-primary text-white' : ''"
                                            @click="selectFactory(f.name)"
                                            :title="f.name">
                                        <div class="d-flex flex-column text-truncate">
                                            <span class="text-truncate" x-text="f.name"></span>
                                            <span x-show="f.location" class="small opacity-75 text-truncate" style="font-size: 10px;" x-text="f.location"></span>
                                        </div>
                                        <i x-show="factoryFilter === f.name" class="bi bi-check-lg ms-2"></i>
                                    </button>
                                </template>
                                <div x-show="filteredFactories.length === 0" class="text-center py-3 text-muted small fw-semibold">
                                    No matching factories found
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reset Filters Button -->
                    <button type="button" 
                            x-show="hasActiveFilters" 
                            @click="clearAllFilters()" 
                            class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-2 fw-bold d-flex align-items-center gap-1 shadow-sm"
                            title="Reset all filters">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </button>
                </div>

                <div class="d-flex gap-2">
                    @role('super_admin')
                    <button @click="openWizard()" class="btn btn-primary fw-bold rounded-pill px-4 d-flex align-items-center gap-2 shadow-sm">
                        <i class="bi bi-plus-circle"></i> Add Record
                    </button>
                    @endrole
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-end mb-4 pb-3 border-bottom">
                <div>
                    <h5 class="fw-bolder text-dark mb-1 d-flex align-items-center gap-2">
                        <i class="bi bi-list-ul text-primary"></i> Product Collections
                    </h5>
                    <p class="small text-muted fw-semibold mb-0">Manage product certifications and test reports.</p>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0" style="font-size: 12px;">
                                        <thead class="table-light">
                        <tr>
                            <th class="py-1.5 px-2 text-center" style="width: 40px;">
                                <div class="form-check d-flex justify-content-center m-0">
                                    <input type="checkbox" @change="toggleAll($event.target.checked)" :checked="filteredRecords().length > 0 && selectedRecords.length === filteredRecords().length" class="form-check-input shadow-none cursor-pointer">
                                </div>
                            </th>
                            <th class="py-1.5 px-2 text-muted text-uppercase fw-bolder text-nowrap" style="width: 140px; font-size: 10.5px; letter-spacing: 0.5px;">Product ID</th>
                            <th class="py-1.5 px-2 text-muted text-uppercase fw-bolder text-nowrap" style="width: 100px; font-size: 10.5px; letter-spacing: 0.5px;">Version ID</th>
                            <th class="py-1.5 px-2 text-muted text-uppercase fw-bolder text-nowrap" style="width: 115px; font-size: 10.5px; letter-spacing: 0.5px;">Trade Brand Name</th>
                            <th class="py-1.5 px-2 text-muted text-uppercase fw-bolder" style="font-size: 10.5px; letter-spacing: 0.5px;">Product Name</th>
                            <th class="py-1.5 px-2 text-muted text-uppercase fw-bolder text-nowrap" style="width: 100px; font-size: 10.5px; letter-spacing: 0.5px;">Last Test Date</th>
                            <th class="py-1.5 px-2 text-muted text-uppercase fw-bolder" style="font-size: 10.5px; letter-spacing: 0.5px;">Manufacturer Name</th>
                            <th class="py-1.5 px-2 text-muted text-uppercase fw-bolder text-nowrap" style="width: 130px; font-size: 10.5px; letter-spacing: 0.5px;">Test Report</th>
                            <th class="py-1.5 px-2 text-muted text-uppercase fw-bolder text-nowrap" style="width: 105px; font-size: 10.5px; letter-spacing: 0.5px;">Status</th>
                            <th class="py-1.5 px-2 text-muted text-uppercase fw-bolder text-nowrap" style="width: 105px; font-size: 10.5px; letter-spacing: 0.5px;">API Sync</th>
                            <th class="py-1.5 px-2 text-muted text-uppercase fw-bolder text-center text-nowrap" style="width: 130px; font-size: 10.5px; letter-spacing: 0.5px;">Action</th>
                        </tr>
                    </thead>

                    <template x-for="(record, index) in filteredRecords()" :key="record.id">
                        <tbody>
                            <tr style="cursor: pointer;" 
                                :class="{
                                    'bg-light': expandedRow === record.id,
                                    'row-synced': record.apiSyncStatus === 'synced'
                                }" 
                                @click="toggleRow(record.id)">

                                <td class="py-1.5 px-2 text-center" @click.stop>
                                    <div class="form-check d-flex justify-content-center m-0">
                                        <input type="checkbox" :value="record.id" x-model="selectedRecords" class="form-check-input shadow-none cursor-pointer">
                                    </div>
                                </td>                        
                                <td class="py-1.5 px-2">
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="fw-bold text-dark" x-text="record.styleNo || record.primaryProductId || record.poNumber || '-'"></span>
                                        
                                        <!-- Show eye button and badge if group has 2+ POs -->
                                        <template x-if="record.otherPosCount > 0">
                                            <div class="d-inline-flex align-items-center gap-1" @click.stop>
                                                <button type="button" 
                                                        class="btn btn-sm btn-link text-primary p-0 text-decoration-none ms-1"
                                                        @click="openViewPosModal(record)"
                                                        :title="'View all ' + record.allPoNumbers.length + ' POs in this group'">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <span class="badge bg-light text-muted border" 
                                                      style="font-size: 10px; cursor: pointer;" 
                                                      @click="openViewPosModal(record)"
                                                      x-text="'+' + record.otherPosCount + ' other PO' + (record.otherPosCount > 1 ? 's' : '')">
                                                </span>
                                            </div>
                                        </template>
                                    </div>
                                </td>
                <td class="py-1.5 px-2 fw-bold text-primary" x-text="record.versionId || '-'"></td>
                <td class="py-1.5 px-2 fw-semibold text-dark" x-text="record.brandName || '-'"></td>
                <td class="py-1.5 px-2 fw-semibold text-dark" x-text="record.productName || '-'"></td>
                <td class="py-1.5 px-2 fw-semibold text-dark" x-text="formatDate(record.lastTestDate) || '-'"></td>

                <td class="py-1.5 px-2 fw-semibold text-dark" x-text="record.factoryName || '-'"></td>
                <td class="py-1.5 px-2" @click.stop>
                    <template x-if="record.tests && record.tests.length > 0">
                        <div class="d-flex flex-column gap-1">
                            <template x-for="(test, tIdx) in record.tests" :key="tIdx">
                                <div class="d-inline-flex align-items-center gap-1.5">
                                    <span class="fw-bold font-monospace cursor-pointer" 
                                          :class="(test.testReportFile || test.testReportFileUrl || test.testReportUrl) ? 'text-primary text-decoration-underline' : 'text-dark'"
                                          style="font-size: 11px;" 
                                          @click="viewAttachment(test, record)"
                                          :title="(test.testReportFile || test.testReportFileUrl || test.testReportUrl) ? 'Click to view test report' : 'No attachment uploaded'"
                                          x-text="test.testReportId || '-'"></span>
                                    <button type="button"
                                            @click="viewAttachment(test, record)"
                                            class="btn btn-sm btn-light border rounded px-1.5 py-0 shadow-sm d-inline-flex align-items-center"
                                            :class="(test.testReportFile || test.testReportFileUrl || test.testReportUrl) ? 'text-primary' : 'text-muted'"
                                            style="font-size: 11px; height: 21px;"
                                            :title="(test.testReportFile || test.testReportFileUrl || test.testReportUrl) ? 'View Attachment' : 'No Attachment Uploaded'">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </template>
                    <template x-if="!record.tests || record.tests.length === 0">
                        <span class="text-muted fw-semibold">-</span>
                    </template>
                </td>
                <td class="py-1.5 px-2">
                    <span class="badge rounded-pill fw-bold" style="font-size: 10.5px;" :class="record.status === 'Complete' ? 'bg-success bg-opacity-10 text-success' : 'bg-secondary bg-opacity-10 text-secondary'">
                        <i class="bi me-1" :class="record.status === 'Complete' ? 'bi-check-circle-fill' : 'bi-clock-fill'"></i>
                        <span x-text="record.status || 'Incomplete'"></span>
                    </span>
                </td>
                <td class="py-3 px-3">
                    <div class="d-flex flex-column align-items-start">
                        <span class="badge rounded-pill fw-bold" style="font-size: 10.5px;" 
                              :class="{
                                'bg-success bg-opacity-10 text-success': record.apiSyncStatus === 'synced',
                                'bg-warning bg-opacity-10 text-warning': record.apiSyncStatus === 'syncing',
                                'bg-danger bg-opacity-10 text-danger': record.apiSyncStatus === 'failed',
                                'bg-secondary bg-opacity-10 text-secondary': !record.apiSyncStatus || record.apiSyncStatus === 'pending'
                              }">
                            <i class="bi me-1" 
                               :class="{
                                 'bi-check-circle-fill': record.apiSyncStatus === 'synced',
                                 'bi-arrow-repeat spinner-border spinner-border-sm': record.apiSyncStatus === 'syncing',
                                 'bi-exclamation-triangle-fill': record.apiSyncStatus === 'failed',
                                 'bi-clock-fill': !record.apiSyncStatus || record.apiSyncStatus === 'pending'
                               }" style="border-width: 1px;"></i>
                            <span x-text="(record.apiSyncStatus || 'pending').charAt(0).toUpperCase() + (record.apiSyncStatus || 'pending').slice(1)"></span>
                        </span>
                        <span x-show="record.apiSyncStatus === 'failed'" class="text-danger small mt-1 fw-bold" style="font-size: 10px;" x-text="record.apiSyncMessage"></span>
                    </div>
                </td>
                <td class="py-3 px-3">
                    <div class="d-flex align-items-center justify-content-center gap-1.5">
                        <!-- @role('super_admin')
                        <button type="button" 
                                @click.stop="openRejectModal(record)" 
                                class="btn btn-sm btn-outline-danger rounded-pill px-2 py-0.5 fw-bold d-inline-flex align-items-center gap-1 shadow-xs" 
                                style="font-size: 11px; height: 24px;"
                                title="Reject this report and send back to laboratory">
                            <i class="bi bi-x-circle"></i> Reject
                        </button>
                        @endrole -->

                        <div class="position-relative" @click.stop>
                            <button @click="toggleMenu(record.id)" class="btn btn-sm btn-light border rounded py-0 px-1.5 text-secondary shadow-sm" style="font-size: 11px;">
                                <i class="bi bi-three-dots"></i>
                            </button>
                            
                            <div x-show="activeMenu === record.id" @click.away="activeMenu = null" class="dropdown-menu show position-absolute end-0 mt-1 shadow-sm border-0 rounded-3 py-2 z-3" x-cloak style="min-width: 180px;">
                                @role('super_admin')
                                <button @click="editRecord(record); activeMenu = null" class="dropdown-item fw-bold text-secondary d-flex align-items-center justify-content-between py-2">
                                    <span><i class="bi bi-pencil me-2"></i>Edit</span>
                                    <span x-show="record.apiSyncStatus === 'synced'" class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" style="font-size: 9px;">Synced</span>
                                </button>
                                <button @click="openRejectModal(record); activeMenu = null" class="dropdown-item fw-bold text-danger d-flex align-items-center gap-2 py-2">
                                    <i class="bi bi-x-circle"></i> Reject Report
                                </button>
                                @else
                                <template x-if="record.apiSyncStatus !== 'synced'">
                                    <button @click="editRecord(record); activeMenu = null" class="dropdown-item fw-bold text-secondary d-flex align-items-center gap-2 py-2">
                                        <i class="bi bi-flask"></i> Enter Test Results
                                    </button>
                                </template>
                                <template x-if="record.apiSyncStatus === 'synced'">
                                    <span class="dropdown-item text-muted small py-2 d-flex align-items-center gap-2 opacity-75" title="Synced records can only be edited by an Administrator">
                                        <i class="bi bi-lock-fill text-secondary"></i> Locked (Synced)
                                    </span>
                                </template>
                                @endrole
                                @role('super_admin')
                                <button class="dropdown-item fw-bold text-secondary d-flex align-items-center gap-2 py-2">
                                    <i class="bi bi-copy"></i> New Version
                                </button>
                                @endrole
                                <a :href="`{{ url('stafford-certs') }}/${record.id}/export-pdf`" class="dropdown-item fw-bold text-secondary d-flex align-items-center gap-2 py-2">
                                    <i class="bi bi-file-earmark-pdf"></i> Export to PDF
                                </a>
                                @role('super_admin')
                                <div class="dropdown-divider"></div>
                                <button @click="deleteRecord(record); activeMenu = null" class="dropdown-item fw-bold text-danger d-flex align-items-center gap-2 py-2">
                                <i class="bi bi-trash"></i> Archive
                                </button>
                                @endrole
                            </div>
                        </div>
                        
                        <button class="btn btn-sm btn-link text-secondary p-0 text-decoration-none" style="transition: transform 0.2s;" :style="expandedRow === record.id ? 'transform: rotate(180deg);' : ''">
                            <i class="bi bi-chevron-down fw-bold"></i>
                        </button>
                    </div>
                                </td>
                            </tr>
                            
                            <!-- Expanded Row Details -->
                            <tr x-show="expandedRow === record.id" class="bg-light border-bottom" x-transition x-cloak>
                <td colspan="11" class="p-4 p-md-5">
                    <div class="row g-4 mb-4">
                        <!-- Product Details -->
                        <div class="col-md-3">
                            <h6 class="fw-bolder text-primary text-uppercase mb-3" style="font-size: 11px; letter-spacing: 1px;"><span class="bg-primary bg-opacity-10 px-2 py-1 rounded">Product Details</span></h6>
                            <div class="d-flex flex-column gap-2 small">
                                <div class="d-flex"><span class="fw-bold text-muted me-2">Product Id:</span> <span class="fw-bolder text-dark" x-text="record.primaryProductId"></span></div>
                                <div class="d-flex"><span class="fw-bold text-muted me-2">Primary Product ID Type:</span> <span class="fw-bolder text-dark" x-text="record.productIdType"></span></div>
                                <div class="d-flex"><span class="fw-bold text-muted me-2">Description:</span> <span class="fw-bolder text-dark" x-text="record.description"></span></div>
                                <div class="d-flex"><span class="fw-bold text-muted me-2">Color:</span> <span class="fw-bolder text-dark" x-text="record.color"></span></div>
                                <div class="d-flex"><span class="fw-bold text-muted me-2">Style:</span> <span class="fw-bolder text-dark" x-text="record.styleNo"></span></div>
                            </div>
                        </div>
                        
                        <!-- Additional IDs -->
                        <div class="col-md-3">
                            <h6 class="fw-bolder text-primary text-uppercase mb-3" style="font-size: 11px; letter-spacing: 1px;"><span class="bg-primary bg-opacity-10 px-2 py-1 rounded">Additional IDs</span></h6>
                            <table class="table table-sm table-bordered bg-white small mb-0">
                                <thead class="table-light">
                                    <tr><th class="text-muted fw-bold">Type</th><th class="text-muted fw-bold">Number</th></tr>
                                </thead>
                                <tbody>
                                    <tr><td class="fw-bold text-dark text-center">UPC</td><td class="fw-bold text-dark text-center" x-text="record.upc || '-'"></td></tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Manufacturer Details -->
                        <div class="col-md-3">
                            <h6 class="fw-bolder text-primary text-uppercase mb-3" style="font-size: 11px; letter-spacing: 1px;"><span class="bg-primary bg-opacity-10 px-2 py-1 rounded">Manufacturer Details</span></h6>
                            <div class="d-flex flex-column gap-2 small">
                                <div class="d-flex"><span class="fw-bold text-muted me-2">Name:</span> <span class="fw-bolder text-dark" x-text="record.factoryName"></span></div>
                                <div class="d-flex"><span class="fw-bold text-muted me-2">GLN:</span> <span class="fw-bolder text-dark" x-text="record.factoryGLN"></span></div>
                                <div class="d-flex"><span class="fw-bold text-muted me-2">Manufacture Date:</span> <span class="fw-bolder text-dark" x-text="formatDateMonthYear(record.manufactureDate)"></span></div>
                                <div class="d-flex"><span class="fw-bold text-muted me-2">Prod Start:</span> <span class="fw-bolder text-dark" x-text="formatDate(record.manufactureStartDate)"></span></div>
                                <div class="d-flex"><span class="fw-bold text-muted me-2">Prod End:</span> <span class="fw-bolder text-dark" x-text="formatDate(record.manufactureEndDate)"></span></div>
                                <div class="d-flex"><span class="fw-bold text-muted me-2">Lot Number:</span> <span class="fw-bolder text-dark" x-text="record.lotNumber"></span></div>
                            </div>
                        </div>

                        <!-- Certificate Details -->
                        <div class="col-md-3">
                            <h6 class="fw-bolder text-primary text-uppercase mb-3" style="font-size: 11px; letter-spacing: 1px;"><span class="bg-primary bg-opacity-10 px-2 py-1 rounded">Certificate Details</span></h6>
                            <div class="d-flex flex-column gap-2 small">
                                <div class="d-flex"><span class="fw-bold text-muted me-2">Type:</span> <span class="fw-bolder text-dark" x-text="record.certType || 'GCC'"></span></div>
                                <div class="d-flex"><span class="fw-bold text-muted me-2">Completed Versions:</span> <span class="fw-bolder text-dark">1</span></div>
                                <div class="d-flex"><span class="fw-bold text-muted me-2">Contact:</span> <span class="fw-bolder text-dark" x-text="record.pointOfContact"></span></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Testing Details Full Width -->
                    <div class="pt-3 border-top">
                        <h6 class="fw-bolder text-primary text-uppercase mb-3" style="font-size: 11px; letter-spacing: 1px;"><span class="bg-primary bg-opacity-10 px-2 py-1 rounded">Testing Details</span></h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm bg-white mb-0 small">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-muted fw-bold">Laboratory</th>
                                        <th class="text-muted fw-bold text-center">CPSC ID</th>
                                        <th class="text-muted fw-bold">Citation Codes</th>
                                        <th class="text-muted fw-bold text-center">Component?</th>
                                        <th class="text-muted fw-bold">Test Report Information</th>
                                    </tr>
                                </thead>
                                <template x-for="test in record.tests || []">
                                    <tbody>
                                        <tr x-show="test.isExclusion" class="bg-light">
                                            <td colspan="5" class="p-3">
                                                <div class="fw-bold text-dark mb-1">Testing Exclusion</div>
                                                <div class="text-secondary mb-2"><span class="fw-bold">Reason:</span> <span x-text="test.exclusionReason"></span></div>
                                                <template x-for="cit in test.citations || []">
                                                    <div class="fw-semibold text-primary" style="font-size: 11px;" x-text="cit.code + ': ' + cit.desc"></div>
                                                </template>
                                            </td>
                                        </tr>
                                        <tr x-show="!test.isExclusion">
                                            <td class="fw-bold text-dark align-middle" x-text="test.labName"></td>
                                            <td class="fw-bold text-dark align-middle text-center" x-text="test.labCpscId"></td>
                                            <td class="fw-bold text-dark align-middle">
                                                <template x-for="cit in test.citations || []">
                                                    <div x-text="cit.code + ': ' + cit.desc"></div>
                                                </template>
                                            </td>
                                            <td class="fw-bold text-dark align-middle text-center" x-text="test.componentTesting ? 'Yes' : 'No'"></td>
                                            <td class="text-secondary align-middle">
                                                <div><span class="fw-bold">ID:</span> <span x-text="test.testReportId"></span></div>
                                                <div x-show="test.testReportUrl"><span class="fw-bold">URL:</span> <a :href="test.testReportUrl" class="text-primary text-decoration-none" target="_blank">Link</a></div>
                                                <div x-show="test.testReportFile" class="mt-1">
                                                    <span class="fw-bold">Attachment:</span> 
                                                    <a :href="getTestFileUrl(test)" target="_blank" class="badge bg-light text-primary border border-primary border-opacity-25 text-decoration-none d-inline-flex align-items-center gap-1">
                                                        <i class="bi bi-paperclip"></i>
                                                        <span x-text="test.testReportFileName || 'View File'"></span>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </template>
                                <tbody x-show="!record.tests || record.tests.length === 0">
                                    <tr>
                                        <td colspan="5" class="p-4 text-center text-muted fw-semibold">No testing data available.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </template>
                    <tbody x-show="filteredRecords().length === 0">
                        <tr>
                            <td colspan="11" class="text-center py-5">
                                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 64px; height: 64px;">
                                    <i class="bi bi-folder2-open fs-2 text-muted"></i>
                                </div>
                                <h5 class="fw-bolder text-dark mb-1">No Collections Found</h5>
                                <p class="small text-muted fw-semibold">Try modifying your search or add a new product.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ADD PRODUCT WIZARD MODAL -->
    <div :class="wizardOpen ? 'd-block' : 'd-none'" class="modal fade show" tabindex="-1" style="background: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden h-100" @click.stop>
                <!-- Wizard Header / Stepper -->
                <div class="modal-header bg-dark text-white border-bottom-0 py-3 px-4 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3 w-100 pe-4">
                        <!-- Step 1 -->
                        <div class="d-flex align-items-center gap-2" :class="wizardStep === 1 ? 'opacity-100' : 'opacity-50'">
                            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 28px; height: 28px; font-size: 12px;" :class="wizardStep >= 1 ? (wizardStep > 1 ? 'bg-primary text-white' : 'bg-white text-dark') : 'border border-light text-white'">
                                <i x-show="wizardStep > 1" class="bi bi-check-lg"></i>
                                <i x-show="wizardStep === 1" class="bi bi-pencil-fill"></i>
                                <span x-show="wizardStep < 1">1</span>
                            </div>
                            <span class="fw-bold small">Product</span>
                        </div>
                        <div class="flex-grow-1 bg-light opacity-25" style="height: 1px;"></div>
                        <!-- Step 2 -->
                        <div class="d-flex align-items-center gap-2" :class="wizardStep === 2 ? 'opacity-100' : 'opacity-50'">
                            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 28px; height: 28px; font-size: 12px;" :class="wizardStep >= 2 ? (wizardStep > 2 ? 'bg-primary text-white' : 'bg-white text-dark') : 'border border-light text-white'">
                                <i x-show="wizardStep > 2" class="bi bi-check-lg"></i>
                                <i x-show="wizardStep === 2" class="bi bi-funnel-fill"></i>
                                <span x-show="wizardStep < 2">2</span>
                            </div>
                            <span class="fw-bold small">Testing</span>
                        </div>
                        <div class="flex-grow-1 bg-light opacity-25" style="height: 1px;"></div>
                        <!-- Step 3 -->
                        <div class="d-flex align-items-center gap-2" :class="wizardStep === 3 ? 'opacity-100' : 'opacity-50'">
                            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 28px; height: 28px; font-size: 12px;" :class="wizardStep >= 3 ? 'bg-white text-dark' : 'border border-light text-white'">
                                <i x-show="wizardStep === 3" class="bi bi-award-fill"></i>
                                <span x-show="wizardStep < 3">3</span>
                            </div>
                            <span class="fw-bold small">Certificate</span>
                        </div>
                    </div>
                    @role('super_admin')
                    <template x-if="formData.apiSyncStatus === 'synced'">
                        <span class="badge bg-warning bg-opacity-25 text-warning border border-warning border-opacity-50 px-2.5 py-1.5 rounded-pill fw-bold text-nowrap me-3 d-inline-flex align-items-center gap-1.5" style="font-size: 11px;">
                            <i class="bi bi-pencil-square"></i> Editing Synced Record
                        </span>
                    </template>
                    @endrole
                    <button type="button" class="btn-close btn-close-white shadow-none" @click="closeWizard()" aria-label="Close"></button>
                </div>

                <!-- Wizard Body -->
@php
    $isLabUser = auth()->user()->hasRole('lab');
    $userLabName = $isLabUser ? auth()->user()->lab?->name : '';
@endphp
                <div class="modal-body bg-light p-4">
                    <!-- STEP 1: PRODUCT -->
                    <div x-show="wizardStep === 1" x-transition.opacity>

                        <!-- Product Details -->
                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                            <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center" style="cursor: pointer;" @click="activeSections.productDetails = !activeSections.productDetails">
                                <h6 class="fw-bolder text-dark mb-0">Product Details</h6>
                                <i class="bi text-muted" :class="activeSections.productDetails ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                            </div>
                            <div class="card-body p-4" x-show="activeSections.productDetails">
                                <div class="row g-4">
                                    <div class="col-md-6 position-relative" @click.outside="showPoSuggestions = false">
                                        <label class="form-label text-muted text-uppercase fw-bold d-flex justify-content-between align-items-center" style="font-size: 11px; letter-spacing: 1px;">
                                            <span>PO Number*</span>
                                            <span x-show="isFetchingErpPo" class="text-primary fw-bold" style="text-transform: none;"><i class="spinner-border spinner-border-sm me-1"></i> Fetching ERP...</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="text" 
                                                   x-model="formData.poNumber" 
                                                   @focus="if(!editId) { showPoSuggestions = true; if(poSuggestions.length === 0) searchPoSuggestions(); }"
                                                   @input.debounce.250ms="searchPoSuggestions()"
                                                   @keydown.enter.prevent="if(showPoSuggestions && poSuggestions.length > 0) { selectPo(poSuggestions[0]) } else { fetchErpPoDetails() }"
                                                   @keydown.escape="showPoSuggestions = false"
                                                   class="form-control bg-light border-light fw-semibold px-3 py-2" 
                                                   placeholder="Search or select PO (e.g. 136308, 66105715)..."
                                                   autocomplete="off">
                                            <button type="button" @click="fetchErpPoDetails()" class="btn btn-primary fw-bold px-3 d-flex align-items-center gap-1 shadow-none" :disabled="!formData.poNumber || isFetchingErpPo" title="Auto-fill details from ERP database">
                                                <i class="bi bi-cloud-arrow-down-fill"></i> Fetch ERP
                                            </button>
                                        </div>


                                        <!-- PO Suggestions Dropdown -->
                                        <div x-show="showPoSuggestions" 
                                             x-transition.opacity.duration.150ms 
                                             class="position-absolute start-0 end-0 bg-white border rounded-3 shadow-lg overflow-hidden mt-1 z-3" 
                                             style="max-height: 280px; overflow-y: auto; display: none;"
                                             :style="showPoSuggestions ? 'display: block;' : 'display: none;'">
                                            
                                            <div class="p-2 px-3 border-bottom bg-light d-flex justify-content-between align-items-center">
                                                <span class="small fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">
                                                    <i class="bi bi-database me-1 text-primary"></i> Available ERP Purchase Orders
                                                </span>
                                                <span x-show="isSearchingPo" class="spinner-border spinner-border-sm text-primary"></span>
                                            </div>

                                            <template x-for="po in poSuggestions" :key="po.id">
                                                <div @mousedown.prevent="selectPo(po)" 
                                                     @click="selectPo(po)"
                                                     class="p-2 px-3 border-bottom d-flex flex-column gap-1 cursor-pointer erp-po-item" 
                                                     style="cursor: pointer; transition: background 0.15s;"
                                                     onmouseover="this.style.backgroundColor='#f0f7ff'" 
                                                     onmouseout="this.style.backgroundColor='transparent'">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <span class="fw-bold text-dark font-monospace">
                                                                <i class="bi bi-file-earmark-text text-primary me-1"></i>
                                                                PO #<span x-text="po.poNumber" class="text-primary"></span>
                                                            </span>
                                                            <template x-if="po.hasLabTests">
                                                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 fw-bold d-inline-flex align-items-center gap-1" style="font-size: 10px;">
                                                                    <i class="bi bi-flask-fill"></i> Lab Tested
                                                                </span>
                                                            </template>
                                                        </div>
                                                        <span class="badge bg-light text-muted border fw-bold" style="font-size: 10px;" x-text="po.poDate"></span>
                                                    </div>
                                                    <div class="d-flex justify-content-between align-items-center small">
                                                        <span class="text-secondary text-truncate" style="max-width: 250px;" x-text="po.product || po.style || 'Product'"></span>
                                                        <span class="text-muted small" style="font-size: 11px;">
                                                            <template x-if="po.hasLabTests && po.labName">
                                                                <span class="text-success fw-bold me-1" x-text="po.labName"></span>
                                                            </template>
                                                            <span x-text="po.buyer"></span>
                                                        </span>
                                                    </div>
                                                </div>
                                            </template>

                                            <div x-show="!isSearchingPo && poSuggestions.length === 0" class="p-3 text-center text-muted small">
                                                No matching PO found in ERP. You can still type manually.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">PO Date</label>
                                        <input type="date" x-model="formData.poDate" class="form-control bg-light border-light fw-semibold px-3 py-2">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Product Name*</label>
                                        <input type="text" x-model="formData.productName" class="form-control bg-light border-light fw-semibold px-3 py-2">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Trade/Brand Name</label>
                                        <input type="text" x-model="formData.brandName" class="form-control bg-light border-light fw-semibold px-3 py-2">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Product ID Type*</label>
                                        <select x-model="formData.productIdType" class="form-select bg-light border-light fw-semibold px-3 py-2">
                                            <option value="">Select...</option>
                                            <option value="Model #">Model #</option>
                                            <option value="SKU">SKU</option>
                                            <option value="Item #">Item #</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Product ID*</label>
                                        <input type="text" x-model="formData.primaryProductId" class="form-control bg-light border-light fw-semibold px-3 py-2">
                                    </div>
                                    
                                    <!-- Additional Identifiers -->
                                    <div class="col-12 mt-4">
                                        <label class="form-label text-muted text-uppercase fw-bold mb-2" style="font-size: 11px; letter-spacing: 1px;">Additional Product ID and Type</label>
                                        <div class="row g-3 align-items-start">
                                            <div class="col-md-8">
                                                <div class="bg-white border rounded-3 p-3">
                                                    <template x-for="(idObj, index) in formData.additionalIdentifiers" :key="index">
                                                        <div class="d-flex gap-2 align-items-center mb-2">
                                                            <input type="text" x-model="idObj.type" class="form-control form-control-sm" placeholder="Type" style="width: 120px;">
                                                            <input type="text" x-model="idObj.id" class="form-control form-control-sm" placeholder="ID">
                                                            <button type="button" @click="formData.additionalIdentifiers.splice(index, 1)" class="btn btn-sm btn-link text-danger p-0 text-decoration-none">
                                                                <i class="bi bi-x-circle-fill"></i>
                                                            </button>
                                                        </div>
                                                    </template>
                                                    <div x-show="!formData.additionalIdentifiers || formData.additionalIdentifiers.length === 0" class="text-muted small fst-italic">
                                                        No additional identifiers added.
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <button type="button" @click="if(!formData.additionalIdentifiers) formData.additionalIdentifiers = []; formData.additionalIdentifiers.push({type: '', id: ''})" class="btn btn-light border fw-bold text-primary w-100 shadow-sm">
                                                    <i class="bi bi-plus me-1"></i> ADD ID
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Product Description -->
                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                            <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center" style="cursor: pointer;" @click="activeSections.productDesc = !activeSections.productDesc">
                                <h6 class="fw-bolder text-dark mb-0">Product Description</h6>
                                <i class="bi text-muted" :class="activeSections.productDesc ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                            </div>
                            <div class="card-body p-4" x-show="activeSections.productDesc">
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Color</label>
                                        <input type="text" x-model="formData.color" class="form-control bg-light border-light fw-semibold px-3 py-2">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Style</label>
                                        <input type="text" x-model="formData.styleNo" class="form-control bg-light border-light fw-semibold px-3 py-2">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Description</label>
                                        <input type="text" x-model="formData.description" class="form-control bg-light border-light fw-semibold px-3 py-2">
                                    </div>
                                    
                                    <div class="col-12">
                                        <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Manufacturer name*</label>
                                        <div class="position-relative">
                                            <select x-model="formData.factoryName" @change="fetchFactoryDetails()" class="form-select bg-light border-light fw-semibold px-3 py-2">
                                                <option value="">Select or Create New Manufacturer...</option>
                                                <!-- If ERP returned a factory not in local dbFactories, display it cleanly -->
                                                <template x-if="formData.factoryName && !dbFactories.some(f => f.name === formData.factoryName) && formData.factoryName !== 'Other'">
                                                    <option :value="formData.factoryName" x-text="formData.factoryName + ' (ERP Factory)'" selected></option>
                                                </template>
                                                <template x-for="factory in dbFactories" :key="factory.id">
                                                    <option :value="factory.name" x-text="factory.name + ' - ' + factory.location"></option>
                                                </template>
                                                <option value="Other">Create New Manufacturer...</option>
                                            </select>
                                            <div x-show="isLoadingFactory" class="position-absolute end-0 top-50 translate-middle-y pe-5">
                                                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Factory Auto-filled Details -->
                                    <div class="col-12" x-show="formData.factoryName" x-transition>
                                        <div class="bg-white p-3 rounded-3 border">
                                            <div class="row g-3">
                                                <div class="col-12"><label class="form-label text-muted text-uppercase fw-bold mb-1" style="font-size: 10px;">Address</label><input type="text" x-model="formData.factoryAddress" class="form-control form-control-sm"></div>
                                                <div class="col-6"><label class="form-label text-muted text-uppercase fw-bold mb-1" style="font-size: 10px;">City</label><input type="text" x-model="formData.factoryCity" class="form-control form-control-sm"></div>
                                                <div class="col-6"><label class="form-label text-muted text-uppercase fw-bold mb-1" style="font-size: 10px;">State/Province</label><input type="text" x-model="formData.factoryState" class="form-control form-control-sm"></div>
                                                <div class="col-6"><label class="form-label text-muted text-uppercase fw-bold mb-1" style="font-size: 10px;">Country</label><input type="text" x-model="formData.factoryCountry" class="form-control form-control-sm"></div>
                                                <div class="col-6"><label class="form-label text-muted text-uppercase fw-bold mb-1" style="font-size: 10px;">Postal Code</label><input type="text" x-model="formData.factoryPostal" class="form-control form-control-sm"></div>
                                                <div class="col-12"><label class="form-label text-muted text-uppercase fw-bold mb-1" style="font-size: 10px;">Contact Person</label><input type="text" x-model="formData.factoryContact" class="form-control form-control-sm"></div>
                                                <div class="col-6"><label class="form-label text-muted text-uppercase fw-bold mb-1" style="font-size: 10px;">Email</label><input type="email" x-model="formData.factoryEmail" class="form-control form-control-sm"></div>
                                                <div class="col-6"><label class="form-label text-muted text-uppercase fw-bold mb-1" style="font-size: 10px;">Phone</label><input type="text" x-model="formData.factoryPhone" class="form-control form-control-sm"></div>
                                                <div class="col-6"><label class="form-label text-muted text-uppercase fw-bold mb-1" style="font-size: 10px;">GLN</label><input type="text" x-model="formData.factoryGLN" class="form-control form-control-sm"></div>
                                                <div class="col-6"><label class="form-label text-muted text-uppercase fw-bold mb-1" style="font-size: 10px;">Alt ID</label><input type="text" x-model="formData.factoryAltId" class="form-control form-control-sm"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <hr class="my-2 border-light">
                                    </div>
                                    
                                    <div class="col-12">
                                        <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Manufacture Date*</label>
                                        <input type="month" x-model="formData.manufactureDate" class="form-control bg-light border-light fw-semibold px-3 py-2 w-50">
                                        <div class="form-text mt-1 text-muted">Select Month and Year</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Production Start Date</label>
                                        <input type="date" x-model="formData.manufactureStartDate" class="form-control border-0 border-bottom border-b-2 rounded-0 shadow-none px-2 py-1 bg-transparent">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Production End Date</label>
                                        <input type="date" x-model="formData.manufactureEndDate" class="form-control border-0 border-bottom border-b-2 rounded-0 shadow-none px-2 py-1 bg-transparent">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Lot Number</label>
                                        <input type="text" x-model="formData.lotNumber" class="form-control bg-light border-light fw-semibold px-3 py-2">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Lot Number Assigned By*</label>
                                        <select x-model="formData.lotAssignedBy" class="form-select bg-light border-light fw-semibold px-3 py-2">
                                            <option value="Manufacturer">Manufacturer</option>
                                            <option value="Testing Lab">Testing Lab</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 2: TESTING & LAB ASSIGNMENT -->
                    <div x-show="wizardStep === 2" x-transition.opacity>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h5 class="fw-bolder text-dark mb-0">
                                    <span x-show="isLabUser">Enter Test Results & Citations</span>
                                    <span x-show="!isLabUser">Testing & Lab Assignment</span>
                                </h5>
                                <div class="text-muted small">
                                    <span x-show="isLabUser">Verify the product against safety standards and submit test details.</span>
                                    <span x-show="!isLabUser">Specify the certificate type and assign the testing laboratory for this PO.</span>
                                </div>
                            </div>
                            <div class="d-flex gap-2" x-show="isLabUser || formData.tests.length > 0">
                                <button type="button" @click="addLab()" class="btn btn-outline-primary btn-sm fw-bold rounded-pill px-3"><i class="bi bi-plus"></i> Add Lab Test</button>
                                <button type="button" @click="addExclusion()" class="btn btn-outline-secondary btn-sm fw-bold rounded-pill px-3"><i class="bi bi-plus"></i> Add Exclusion</button>
                            </div>
                        </div>


                        <!-- Certificate Type and Lab Assignment Card -->
                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                            <div class="card-body p-4">
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Certificate Type*</label>
                                        <select x-model="formData.certType" class="form-select bg-light border-light fw-semibold px-3 py-2">
                                            <option value="GCC">GCC (General Conformity Certificate)</option>
                                            <option value="CPC">CPC (Children's Product Certificate)</option>
                                        </select>
                                    </div>

                                    <!-- Assigned Lab (For Super Admin) -->
                                    <div class="col-md-6" x-show="!isLabUser">
                                        <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Assigned Testing Laboratory*</label>
                                        <select x-model="formData.labId" class="form-select bg-light border-light fw-semibold px-3 py-2">
                                            <option value="">Select Lab to Assign...</option>
                                            <template x-for="lab in dbLabs" :key="lab.id">
                                                <option :value="lab.id" x-text="lab.name"></option>
                                            </template>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6" x-show="formData.certType === 'CPC'" x-transition>
                                        <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Target Age Group (Intended Use)</label>
                                        <input type="text" x-model="formData.intendedUse" class="form-control bg-light border-light fw-semibold px-3 py-2" placeholder="e.g. Ages 3+">
                                    </div>
                                    <div class="col-md-6" x-show="formData.certType === 'CPC'" x-transition>
                                        <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Children's Product Category</label>
                                        <input type="text" x-model="formData.childrenProduct" class="form-control bg-light border-light fw-semibold px-3 py-2" placeholder="e.g. Toys">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Workflow Notice for Admin when no manual test report is added -->
                        <div x-show="!isLabUser && (!formData.tests || formData.tests.length === 0)" class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                            <div class="card-body p-4 text-center py-5">
                                <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center p-3 mb-3">
                                    <i class="bi bi-shield-check text-primary fs-2"></i>
                                </div>
                                <h6 class="fw-bolder text-dark mb-2">Testing Delegated to Laboratory</h6>
                                <p class="text-muted small mx-auto mb-4" style="max-width: 550px;">
                                    When you create this PO, it will automatically appear on the selected Laboratory's portal. The lab will conduct the required safety testing and submit their Test Report ID, URL, and Citations directly.
                                </p>
                                <button type="button" @click="addLab()" class="btn btn-outline-secondary btn-sm fw-bold rounded-pill px-4 shadow-sm">
                                    <i class="bi bi-pencil-square me-1"></i> Enter Test Report Manually (Optional)
                                </button>
                            </div>
                        </div>
                        
                        
                        <template x-for="(test, index) in formData.tests" :key="index">
                            <div class="card border-0 shadow-sm rounded-4 mb-4 position-relative">
                                <button type="button" @click="removeLab(index)" class="btn btn-sm btn-link text-danger position-absolute top-0 end-0 m-3 text-decoration-none">
                                    <i class="bi bi-trash fs-5"></i>
                                </button>
                                <div class="card-body p-4">
                                    <!-- Exclusion Case -->
                                    <div x-show="test.isExclusion" class="row g-4 mt-2">
                                        <div class="col-12">
                                            <h6 class="fw-bolder text-dark mb-3">Testing Exclusion</h6>
                                            <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Exclusion Reason*</label>
                                            <input type="text" x-model="test.exclusionReason" class="form-control bg-light border-light fw-semibold px-3 py-2">
                                        </div>
                                    </div>
                                    
                                    <!-- Standard Lab Case -->
                                    <div x-show="!test.isExclusion" class="row g-4 mt-2">
                                        <div class="col-12 mt-4 pt-3 border-top">
                                            <h6 class="fw-bolder text-dark mb-3" style="font-size: 13px;">Test Report Details</h6>
                                        </div>
                                        
                                        @if(!$isLabUser)
                                        <div class="col-md-6">
                                            <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Lab Type*</label>
                                            <select x-model="test.labType" class="form-select bg-light border-light fw-semibold px-3 py-2">
                                                <option value="CPSC-Accepted">CPSC-Accepted</option>
                                                <option value="In-House">In-House</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>

                                        @else
                                        <div class="col-md-12">
                                            <div class="alert alert-info py-2 px-3 mb-0 d-flex align-items-center gap-2 border-0 shadow-sm" style="background-color: #f0fdf4; color: #166534;">
                                                <i class="bi bi-shield-check fs-5"></i>
                                                <div>
                                                    <strong>Submitting testing for:</strong> {{ $userLabName }}
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                        
                                        <div class="col-md-6">
                                            <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Lab CPSC ID</label>
                                            <input type="text" x-model="test.labCpscId" class="form-control bg-light border-light fw-semibold px-3 py-2">
                                        </div>
                                        <div class="col-12 mt-4 pt-3 border-top">
                                            <h6 class="fw-bolder text-dark mb-3" style="font-size: 13px;">Test Report Details</h6>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Test Report ID</label>
                                            <input type="text" x-model="test.testReportId" class="form-control bg-light border-light fw-semibold px-3 py-2">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Test Report URL</label>
                                            <input type="text" x-model="test.testReportUrl" class="form-control bg-light border-light fw-semibold px-3 py-2">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">
                                                <i class="bi bi-paperclip text-primary me-1"></i> Attach Report File
                                            </label>

                                            {{-- Show existing attachment with option to replace --}}
                                            <div x-show="test.testReportFile">
                                                <a :href="getTestFileUrl(test)"
                                                   target="_blank"
                                                   class="badge bg-light text-primary border border-primary border-opacity-25 text-decoration-none d-inline-flex align-items-center gap-1 p-2 w-100 text-truncate mb-2"
                                                   :title="test.testReportFileName">
                                                    <i class="bi bi-file-earmark-check-fill text-success"></i>
                                                    <span class="text-truncate" x-text="test.testReportFileName || 'View Document'"></span>
                                                </a>
                                                <button type="button"
                                                        @click="test.testReportFile = ''; test.testReportFileName = ''; test.testReportFileUrl = ''"
                                                        class="btn btn-sm btn-outline-danger rounded-pill px-2 py-0 fw-semibold w-100" style="font-size: 11px;">
                                                    <i class="bi bi-x-circle me-1"></i> Remove & Replace
                                                </button>
                                            </div>

                                            {{-- Upload UI shown when no file is attached yet --}}
                                            <div x-show="!test.testReportFile">
                                                {{-- Uploading spinner --}}
                                                <div x-show="test.isUploadingFile" class="d-flex align-items-center gap-2 py-2 text-primary small fw-semibold">
                                                    <span class="spinner-border spinner-border-sm" role="status"></span>
                                                    Uploading...
                                                </div>

                                                {{-- Drop-zone / Upload trigger --}}
                                                <div x-show="!test.isUploadingFile"
                                                     class="border border-dashed rounded-3 text-center p-3 bg-light position-relative"
                                                     style="border-color: #ced4da; cursor: pointer;"
                                                     @dragover.prevent
                                                     @drop.prevent="adminUploadFile($event.dataTransfer.files[0], index)"
                                                     @click="$refs['adminFileInput_' + index] && $refs['adminFileInput_' + index].click()">
                                                    <i class="bi bi-cloud-arrow-up-fill text-primary fs-5 d-block mb-1"></i>
                                                    <span class="small fw-semibold text-muted">Click or drag file here</span>
                                                    <div class="small text-muted mt-1" style="font-size: 10px;">PDF, DOC, JPG, PNG, XLS — max 20 MB</div>
                                                    <input type="file"
                                                           :x-ref="'adminFileInput_' + index"
                                                           class="position-absolute top-0 start-0 w-100 h-100 opacity-0"
                                                           style="cursor: pointer;"
                                                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp,.xls,.xlsx"
                                                           @change="adminUploadFile($event.target.files[0], index); $event.target.value = ''">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-check mt-2">
                                                <input type="checkbox" x-model="test.componentTesting" class="form-check-input" :id="'compTest_'+index">
                                                <label class="form-check-label fw-bold text-dark small" :for="'compTest_'+index">Uses Component Testing?</label>
                                            </div>
                                            <div class="mt-2" x-show="test.componentTesting" x-transition>
                                                <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Component Details</label>
                                                <input type="text" x-model="test.componentDetails" class="form-control bg-light border-light fw-semibold px-3 py-2" placeholder="Describe the component...">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Citations (Both standard and exclusion) -->
                                    <div class="mt-4 pt-3 border-top">
                                        <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Applicable Citations*</label>
                                        <div class="d-flex flex-wrap gap-2 mb-3">
                                            <template x-for="(cit, cIdx) in test.citations" :key="cIdx">
                                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-2 d-inline-flex align-items-center gap-2">
                                                    <span class="fw-bold" x-text="cit.code"></span>
                                                    <button type="button" 
                                                            @click.stop="removeCitation(index, cIdx)" 
                                                            class="btn btn-link p-0 text-danger text-opacity-75 hover-opacity-100 d-inline-flex align-items-center border-0 shadow-none text-decoration-none" 
                                                            title="Remove citation" 
                                                            style="cursor: pointer; line-height: 1;">
                                                        <i class="bi bi-x-circle-fill" style="font-size: 14px;"></i>
                                                    </button>
                                                </span>
                                            </template>
                                            <span x-show="!test.citations || test.citations.length === 0" class="text-muted small fst-italic py-1">No citations added.</span>
                                        </div>
                                        
                                        <!-- Searchable Safety Citations Selector (Type & Select) -->
                                        <div class="position-relative" style="max-width: 540px;" x-data="{
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
                                                        @click="open = !open; if(open) $nextTick(() => $refs.citAdminSearchInput?.focus())">
                                                    <span class="text-muted d-flex align-items-center gap-2">
                                                        <i class="bi bi-search text-primary"></i>
                                                        <span class="small">Type code or regulation to add citation...</span>
                                                    </span>
                                                    <i class="bi bi-chevron-down text-muted" style="font-size: 11px;"></i>
                                                </button>
                                            </div>

                                            <div x-show="open" 
                                                 x-transition:enter="transition ease-out duration-150"
                                                 x-transition:enter-start="opacity-0 transform scale-95"
                                                 x-transition:enter-end="opacity-100 transform scale-100"
                                                 class="dropdown-menu show shadow-lg border-0 rounded-4 p-2 mt-1 w-100 position-absolute z-3" 
                                                 style="max-height: 320px; overflow: hidden; display: flex; flex-direction: column;" 
                                                 x-cloak>
                                                <div class="p-1 mb-2">
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text bg-light border-0"><i class="bi bi-search text-muted"></i></span>
                                                        <input type="text" 
                                                               x-ref="citAdminSearchInput" 
                                                               x-model="query" 
                                                               placeholder="Type code (e.g. 1610, 1500) or topic..." 
                                                               class="form-control bg-light border-0 fw-semibold">
                                                        <button type="button" x-show="query" @click="query = ''" class="btn btn-light border-0 text-muted">
                                                            <i class="bi bi-x"></i>
                                                        </button>
                                                    </div>
                                                </div>

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
                        
                        <div x-show="!formData.tests || formData.tests.length === 0" class="text-center py-5 bg-white rounded-4 border shadow-sm">
                            <p class="text-muted fw-semibold mb-0">No laboratories or exclusions added yet.</p>
                        </div>
                    </div>

                    <!-- STEP 3: CERTIFICATE -->
                    <div x-show="wizardStep === 3" x-transition.opacity>
                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                            <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
                                <h6 class="fw-bolder text-dark mb-0">Certificate Configuration</h6>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Point of Contact*</label>
                                        <select x-model="formData.pointOfContact" class="form-select bg-light border-light fw-semibold px-3 py-2">
                                            <option value="Importer">Importer</option>
                                            <option value="Domestic Manufacturer">Domestic Manufacturer</option>
                                            <option value="Private Labeler">Private Labeler</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Last Test Date</label>
                                        <input type="date" x-model="formData.lastTestDate" class="form-control border-0 border-bottom border-b-2 rounded-0 shadow-none px-2 py-1 bg-transparent">
                                        <div class="form-text mt-1 text-muted">MM/DD/YYYY</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 1px;">Version ID</label>
                                        <div class="input-group">
                                            <input type="text" x-model="formData.versionId" class="form-control bg-light border-light fw-semibold px-3 py-2" readonly placeholder="Auto-generated">
                                            <button type="button" @click="generateVersion()" class="btn btn-secondary fw-bold px-3">Generate</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Wizard Footer -->
                <div class="modal-footer bg-white border-top-0 px-4 py-3 d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-light fw-bold rounded-pill px-4" x-show="wizardStep > 1" @click="wizardStep = wizardStep - 1">
                        <i class="bi bi-arrow-left me-1"></i> Back
                    </button>
                    <div x-show="wizardStep === 1"></div>
                    
                    <div>
                        <button type="button" class="btn btn-light text-secondary fw-bold rounded-pill border px-4 me-2" @click="closeWizard()">Cancel</button>
                        
                        <!-- Lab User Direct Submit on Step 2 -->
                        <button type="button" class="btn btn-success fw-bold rounded-pill px-4" x-show="isLabUser && wizardStep === 2" @click="submitForm()" :disabled="isSubmitting">
                            <span x-show="!isSubmitting"><i class="bi bi-check-circle me-1"></i> Submit Test Results</span>
                            <span x-show="isSubmitting"><span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Saving...</span>
                        </button>

                        <button type="button" class="btn btn-primary fw-bold rounded-pill px-4" x-show="(!isLabUser && wizardStep < 3) || (isLabUser && wizardStep < 2)" @click="wizardStep = wizardStep + 1">
                            Next <i class="bi bi-arrow-right ms-1"></i>
                        </button>
                        <button type="button" class="btn btn-success fw-bold rounded-pill px-4" x-show="!isLabUser && wizardStep === 3" @click="submitForm()" :disabled="isSubmitting">
                            <span x-show="!isSubmitting"><i class="bi bi-check-circle me-1"></i> Submit Certificate</span>
                            <span x-show="isSubmitting"><span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Saving...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- LAB REPORT ATTACHMENT PREVIEW MODAL -->
    <div :class="attachmentModalOpen ? 'd-block' : 'd-none'" 
         x-show="attachmentModalOpen"
         @keydown.escape.window="closeAttachmentModal()"
         class="modal fade show" 
         tabindex="-1" 
         style="background: rgba(0,0,0,0.65); z-index: 1060;" 
         aria-modal="true" 
         role="dialog" 
         @click.self="closeAttachmentModal()"
         x-cloak>
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="max-width: 950px;">
            <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
                <!-- Modal Header -->
                <div class="modal-header border-bottom py-3 px-4 bg-white d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px;">
                            <i class="bi bi-file-earmark-medical fs-5"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h6 class="fw-bolder text-dark mb-0">Lab Test Report Document</h6>
                                <span class="badge bg-primary bg-opacity-10 text-primary fw-bold" x-text="'ID: ' + attachmentData.reportId"></span>
                            </div>
                            <p class="text-muted small mb-0" x-text="attachmentData.title"></p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <template x-if="attachmentData.hasAttachment">
                            <a :href="attachmentData.url" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold d-inline-flex align-items-center gap-1 shadow-none">
                                <i class="bi bi-box-arrow-up-right"></i> Open in New Tab
                            </a>
                        </template>
                        <button type="button" class="btn-close shadow-none" @click="closeAttachmentModal()"></button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="modal-body p-0 bg-light" style="min-height: 520px; max-height: 80vh; overflow-y: auto;">
                    <!-- When attachment exists -->
                    <template x-if="attachmentData.hasAttachment">
                        <div class="h-100 d-flex flex-column">
                            <!-- PDF or document view -->
                            <template x-if="attachmentData.isPdf || (!attachmentData.isImage)">
                                <iframe :src="attachmentData.url" class="w-100 border-0 flex-grow-1" style="min-height: 70vh;"></iframe>
                            </template>

                            <!-- Image view -->
                            <template x-if="attachmentData.isImage">
                                <div class="text-center p-4">
                                    <img :src="attachmentData.url" class="img-fluid rounded-3 shadow-sm border" style="max-height: 75vh;" :alt="attachmentData.fileName">
                                </div>
                            </template>
                        </div>
                    </template>

                    <!-- When NO attachment was uploaded -->
                    <template x-if="!attachmentData.hasAttachment">
                        <div class="text-center py-5 px-4 my-auto">
                            <div class="bg-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3 shadow-sm" style="width: 72px; height: 72px;">
                                <i class="bi bi-file-earmark-x fs-1 text-muted"></i>
                            </div>
                            <h5 class="fw-bolder text-dark mb-1">No Attachment Uploaded</h5>
                            <p class="text-muted small mx-auto mb-3" style="max-width: 440px;">
                                No document file or external report URL was attached to this test report record (<span class="fw-bold font-monospace" x-text="attachmentData.reportId"></span>).
                            </p>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-1.5 rounded-pill mb-3 d-inline-block" x-show="attachmentData.labName" x-text="'Lab: ' + attachmentData.labName"></span>
                            <div x-show="selectedRecordForAttachment">
                                <button type="button" class="btn btn-sm btn-primary rounded-pill px-4 fw-bold shadow-sm" @click="closeAttachmentModal(); editRecord(selectedRecordForAttachment)">
                                    <i class="bi bi-upload me-1"></i> Attach Test Report File
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer bg-white border-top py-2.5 px-4 d-flex justify-content-between align-items-center">
                    <span class="small text-muted text-truncate" style="max-width: 400px;" x-text="attachmentData.fileName || ''"></span>
                    <div class="d-flex align-items-center gap-2">
                        <template x-if="attachmentData.hasAttachment">
                            <a :href="attachmentData.url" download class="btn btn-sm btn-outline-secondary fw-bold rounded-pill px-3">
                                <i class="bi bi-download me-1"></i> Download
                            </a>
                        </template>
                        <button type="button" class="btn btn-light fw-bold rounded-pill px-4 border" @click="closeAttachmentModal()">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Toast -->
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 9999;" x-show="notification.show" x-transition x-cloak>
        <div class="toast show align-items-center border-0 shadow-lg" :class="{
                'bg-success text-white': notification.type === 'success',
                'bg-danger text-white': notification.type === 'error',
                'bg-warning text-dark': notification.type === 'warning',
                'bg-info text-white': notification.type === 'info'
             }" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body d-flex gap-3 align-items-start">
                    <i class="bi fs-4 mt-1" :class="{
                        'bi-check-circle-fill': notification.type === 'success',
                        'bi-x-circle-fill': notification.type === 'error',
                        'bi-exclamation-triangle-fill': notification.type === 'warning',
                        'bi-info-circle-fill': notification.type === 'info'
                    }"></i>
                    <div>
                        <h6 class="fw-bold mb-1" x-text="notification.title"></h6>
                        <div class="small" x-html="notification.message"></div>
                    </div>
                </div>
                <button type="button" class="btn-close me-2 m-auto" :class="(notification.type === 'success' || notification.type === 'error' || notification.type === 'info') ? 'btn-close-white' : ''" @click="closeNotification()" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <!-- VIEW GROUPED POS MODAL -->
    <div class="modal fade" id="viewPosModal" tabindex="-1" aria-hidden="true" x-ref="viewPosModal">
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
                            <span class="fw-bold text-dark text-truncate ms-2" x-text="viewingPosModal.styleNo || '-'"></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Season:</span>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary border fw-semibold ms-2" x-text="viewingPosModal.season || '-'"></span>
                        </div>
                    </div>

                    <label class="form-label text-muted text-uppercase fw-bold mb-2" style="font-size: 11px;">
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
                    <button type="button" class="btn btn-sm btn-light border rounded-pill px-4 w-100 fw-bold" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Confirmation Modal -->
    <div class="modal fade" id="rejectConfirmModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-danger bg-opacity-10 text-danger rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="bi bi-x-circle-fill fs-5"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-dark mb-0">Reject Qualification Report</h5>
                            <span class="text-muted small">Send back to testing laboratory for revision</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3">
                    <div class="alert alert-warning border-0 rounded-3 py-2 px-3 small d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-info-circle-fill text-warning flex-shrink-0"></i>
                        <span>This record will be removed from this Collections table and sent back to the testing laboratory (<span class="fw-bold">Submitted Tasks</span>) where they can review your notes, edit, and re-submit.</span>
                    </div>

                    <template x-if="recordToReject">
                        <div class="bg-light p-3 rounded-3 mb-3 small">
                            <div class="d-flex justify-content-between mb-1.5">
                                <span class="text-muted fw-semibold">Style / Product:</span>
                                <span class="fw-bold text-dark" x-text="recordToReject.styleNo || recordToReject.primaryProductId || '-'"></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1.5">
                                <span class="text-muted fw-semibold">PO Number:</span>
                                <span class="fw-bold text-dark" x-text="recordToReject.poNumber || '-'"></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1.5" x-show="recordToReject.otherPosCount > 0">
                                <span class="text-muted fw-semibold">Grouped POs:</span>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25" x-text="(recordToReject.otherPosCount + 1) + ' POs in this collection'"></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1.5">
                                <span class="text-muted fw-semibold">Factory:</span>
                                <span class="fw-bold text-dark" x-text="recordToReject.factoryName || '-'"></span>
                            </div>
                            <div class="d-flex justify-content-between" x-show="recordToReject.assignedLabName">
                                <span class="text-muted fw-semibold">Testing Lab:</span>
                                <span class="fw-bold text-primary" x-text="recordToReject.assignedLabName || '-'"></span>
                            </div>
                        </div>
                    </template>

                    <div>
                        <label class="form-label text-muted fw-bold small mb-1">
                            Rejection Reason / Notes for Laboratory <span class="fw-normal text-muted">(Optional)</span>:
                        </label>
                        <textarea class="form-control rounded-3" 
                                  rows="3" 
                                  x-model="rejectReason" 
                                  placeholder="e.g. Test report attachment is incomplete, missing flammability standard citation, or needs revised last test date..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold border" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" 
                            class="btn btn-danger rounded-pill px-4 fw-bold shadow-sm d-inline-flex align-items-center gap-2"
                            :disabled="isRejecting"
                            @click="confirmReject()">
                        <span x-show="isRejecting" class="spinner-border spinner-border-sm" role="status"></span>
                        <i x-show="!isRejecting" class="bi bi-x-circle-fill"></i>
                        <span x-text="isRejecting ? 'Rejecting...' : 'Confirm Reject'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<style>
    /* Prevent body scroll when modal open */
    body.modal-open { overflow: hidden; }

    /* Distinct theme-matched highlight for synced records */
    tr.row-synced > td {
        background-color: rgba(0, 163, 154, 0.40) !important;
        border-bottom: 1px solid rgba(0, 163, 154, 0.40) !important;
    }
    tr.row-synced:hover > td {
        background-color: rgba(0, 163, 154, 0.40) !important;
    }
    tr.row-synced > td:first-child {
        border-left: 6px solid #00A39A !important;
    }
</style>
<script>
    function certsDashboard() {
        return {
            notification: {
                show: false,
                type: 'success',
                title: '',
                message: '',
                redirectUrl: ''
            },
            showNotification(type, title, message, redirectUrl = '') {
                this.notification.type = type;
                this.notification.title = title;
                this.notification.message = message;
                this.notification.redirectUrl = redirectUrl;
                this.notification.show = true;
                if(type === 'success') {
                    setTimeout(() => { this.closeNotification(); }, 5000);
                }
            },
            closeNotification() {
                this.notification.show = false;
                if (this.notification.redirectUrl) {
                    window.location.href = this.notification.redirectUrl;
                }
            },
            records: [],
            selectedRecords: [],
            searchQuery: '',
            searchField: 'All',
            
            // Searchable Filter States
            styleFilter: '',
            styleDropdownOpen: false,
            styleSearchQuery: '',
            
            factoryFilter: '',
            factoryDropdownOpen: false,
            factorySearchQuery: '',

            serverStyles: @json($styles ?? []),
            statusFilter: '{{ $initialStatus ?? "All" }}',
            expandedRow: null,
            activeMenu: null,
            
            // Rejection State
            recordToReject: null,
            rejectReason: '',
            isRejecting: false,
            
            // Wizard State
            wizardOpen: false,
            wizardStep: 1,
            isSubmitting: false,
            isLoadingFactory: false,
            editId: null,

            // Attachment Preview Modal State
            attachmentModalOpen: false,
            selectedRecordForAttachment: null,
            attachmentData: {
                title: '',
                reportId: '',
                url: '',
                fileName: '',
                isPdf: false,
                isImage: false,
                hasAttachment: false,
                labName: ''
            },

            viewingPosModal: {
                allPoNumbers: [],
                styleNo: '',
                season: ''
            },

            openViewPosModal(record) {
                this.viewingPosModal = {
                    allPoNumbers: (record.allPoNumbers && record.allPoNumbers.length > 0) ? record.allPoNumbers : [record.poNumber],
                    styleNo: record.styleNo,
                    season: record.season
                };
                const modalEl = document.getElementById('viewPosModal');
                if (modalEl) {
                    new bootstrap.Modal(modalEl).show();
                }
            },
            
            activeSections: {
                productDetails: true,
                productDesc: true
            },

            isLabUser: {{ $isLabUser ? 'true' : 'false' }},
            userLabName: '{{ addslashes($userLabName ?? "") }}',
            dbLabs: @json($labs ?? []),
            dbFactories: [],
            
            citationMaster: @json(
                collect($testReportTypes ?? [])->mapWithKeys(function($t) {
                    $code = $t->standard_citation ?: $t->code;
                    return [$code => ($t->standard_citation ? $t->standard_citation . ': ' : '') . $t->name . ' (' . $t->default_validity_days . 'd)'];
                })->union([
                    '1610' => '16 CFR 1610: Standard for the Flammability of Clothing Textiles (365d)',
                    '1615' => '16 CFR 1615: Standard for the Flammability of Children\'s Sleepwear (365d)',
                    '1303' => '16 CFR 1303: Lead in Paint (730d)'
                ])
            ),

            formData: {
                poNumber: '', poDate: '',
                productName: '', brandName: '', productIdType: 'Model #', primaryProductId: '', additionalIdentifiers: [],
                color: '', styleNo: '', description: '',
                factoryName: '', factoryAddress: '', factoryCity: '', factoryState: '',
                factoryCountry: '', factoryPostal: '', factoryContact: '',
                factoryEmail: '', factoryPhone: '', factoryGLN: '', factoryAltId: '',
                manufactureDate: '',manufactureFullDate: '', manufactureStartDate: '', manufactureEndDate: '',
                lotNumber: '', lotAssignedBy: 'Manufacturer',
                certType: 'GCC', intendedUse: '', childrenProduct: '', lastTestDate: '',
                labId: '',
                versionId: '', pointOfContact: 'Importer', status: 'Incomplete',
                tests: []
            },

            getEmptyForm() {
                return {
                    poNumber: '', poDate: '',
                    productName: '', brandName: '', productIdType: 'Model #', primaryProductId: '', additionalIdentifiers: [],
                    color: '', styleNo: '', description: '', upc: '',
                    factoryName: '', factoryAddress: '', factoryCity: '', factoryState: '',
                    factoryCountry: '', factoryPostal: '', factoryContact: '',
                    factoryEmail: '', factoryPhone: '', factoryGLN: '', factoryAltId: '',
                    importerName: '', importerAddress: '', importerCity: '', importerState: '', importerPostal: '', importerCountry: '',
                    manufactureDate: '', manufactureFullDate: '', manufactureStartDate: '', manufactureEndDate: '',
                    lotNumber: '', lotAssignedBy: 'Manufacturer',
                    certType: 'GCC', intendedUse: '', childrenProduct: '', lastTestDate: '',
                    labId: '',
                    versionId: Math.random().toString(16).substring(2, 10).toUpperCase(),
                    pointOfContact: 'Importer', status: 'Incomplete',
                    tests: []
                };
            },

            initData() {
                this.loadRecords();
                this.loadFactories();
            },
            
            loadFactories() {
                fetch('{{ route('factories.index') }}', {
                    headers: { 'Accept': 'application/json' }
                })
                .then(res => res.json())
                .then(data => {
                    this.dbFactories = data;
                })
                .catch(err => console.error("Failed to load factories", err));
            },

            loadRecords() {
                fetch('{{ route('stafford-certs.index') }}', {
                    headers: { 'Accept': 'application/json' }
                })
                .then(res => res.json())
                .then(data => {
                    this.records = data;
                })
                .catch(err => console.error("Failed to load records", err));
            },

            get allStyles() {
                const set = new Set();
                if (Array.isArray(this.serverStyles)) {
                    this.serverStyles.forEach(s => { if (s && String(s).trim()) set.add(String(s).trim()); });
                }
                if (Array.isArray(this.records)) {
                    this.records.forEach(r => { if (r.styleNo && String(r.styleNo).trim()) set.add(String(r.styleNo).trim()); });
                }
                return Array.from(set).sort((a, b) => a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' }));
            },

            get filteredStyles() {
                if (!this.styleSearchQuery.trim()) return this.allStyles;
                const q = this.styleSearchQuery.toLowerCase().trim();
                return this.allStyles.filter(s => s.toLowerCase().includes(q));
            },

            selectStyle(s) {
                this.styleFilter = s;
                this.styleDropdownOpen = false;
                this.styleSearchQuery = '';
            },

            clearStyle() {
                this.styleFilter = '';
                this.styleSearchQuery = '';
            },

            get allFactories() {
                const map = new Map();
                if (Array.isArray(this.dbFactories)) {
                    this.dbFactories.forEach(f => {
                        if (f && f.name && f.name.trim()) {
                            map.set(f.name.trim(), { name: f.name.trim(), location: f.location || f.city || f.country || '' });
                        }
                    });
                }
                if (Array.isArray(this.records)) {
                    this.records.forEach(r => {
                        if (r.factoryName && r.factoryName.trim() && !map.has(r.factoryName.trim())) {
                            map.set(r.factoryName.trim(), { name: r.factoryName.trim(), location: r.factoryCity || r.factoryCountry || '' });
                        }
                    });
                }
                return Array.from(map.values()).sort((a, b) => a.name.localeCompare(b.name));
            },

            get filteredFactories() {
                if (!this.factorySearchQuery.trim()) return this.allFactories;
                const q = this.factorySearchQuery.toLowerCase().trim();
                return this.allFactories.filter(f => f.name.toLowerCase().includes(q) || (f.location && f.location.toLowerCase().includes(q)));
            },

            selectFactory(fName) {
                this.factoryFilter = fName;
                this.factoryDropdownOpen = false;
                this.factorySearchQuery = '';
            },

            clearFactory() {
                this.factoryFilter = '';
                this.factorySearchQuery = '';
            },

            get hasActiveFilters() {
                return Boolean(this.styleFilter || this.factoryFilter);
            },

            clearAllFilters() {
                this.searchQuery = '';
                this.styleFilter = '';
                this.styleSearchQuery = '';
                this.factoryFilter = '';
                this.factorySearchQuery = '';
            },

            filteredRecords() {
                let filtered = this.records;

                if (this.statusFilter !== 'All') {
                    filtered = filtered.filter(r => r.status === this.statusFilter);
                }

                if (this.styleFilter) {
                    const styleQuery = this.styleFilter.toLowerCase().trim();
                    filtered = filtered.filter(r => (r.styleNo || '').toLowerCase().trim() === styleQuery);
                }

                if (this.factoryFilter) {
                    const factoryQuery = this.factoryFilter.toLowerCase().trim();
                    filtered = filtered.filter(r => (r.factoryName || '').toLowerCase().trim() === factoryQuery);
                }

                if (this.searchQuery) {
                    const query = this.searchQuery.toLowerCase().trim();
                    filtered = filtered.filter(r => {
                        if (this.searchField === 'All') {
                            return (r.primaryProductId || '').toLowerCase().includes(query) ||
                                (r.productName || '').toLowerCase().includes(query) ||
                                (r.brandName || '').toLowerCase().includes(query) ||
                                (r.factoryName || '').toLowerCase().includes(query) ||
                                (r.styleNo || '').toLowerCase().includes(query) ||
                                (r.poNumber || '').toLowerCase().includes(query) ||
                                (r.versionId || '').toLowerCase().includes(query);
                        } else if (this.searchField === 'ID') {
                            return (r.primaryProductId || '').toLowerCase().includes(query);
                        } else if (this.searchField === 'Product') {
                            return (r.productName || '').toLowerCase().includes(query);
                        }
                        return true;
                    });
                }

                return filtered;
            },

            uniqueFactoriesCount() {
                const factories = this.records.filter(r => r.factoryName).map(r => r.factoryName);
                return new Set(factories).size;
            },

            formatDate(dStr) {
                if (!dStr) return '';
                const parts = dStr.split('-');
                if (parts.length === 3) return `${parts[1]}/${parts[2]}/${parts[0]}`;
                return dStr;
            },
            
            formatDateMonthYear(dStr) {
                if (!dStr) return '';
                const parts = dStr.split('-');
                if (parts.length === 3) return `${parts[1]}/${parts[0]}`;
                return dStr;
            },

            toggleRow(id) {
                if (this.expandedRow === id) {
                    this.expandedRow = null;
                } else {
                    this.expandedRow = id;
                }
            },

            toggleAll(checked) {
                if (checked) {
                    this.selectedRecords = this.filteredRecords().map(r => String(r.id));
                } else {
                    this.selectedRecords = [];
                }
            },

            performBulkAction(action) {
                const actionLabels = {
                    'archive': 'archive',
                    'complete': 'mark complete',
                    'incomplete': 'mark incomplete',
                    'sync-cpsc': 'sync',
                    'reject': 'reject'
                };
                let actionText = actionLabels[action] || action;
                let reason = '';
                if (action === 'reject') {
                    const promptVal = prompt(`Are you sure you want to reject ${this.selectedRecords.length} records and send them back to the laboratory?\n\nEnter optional rejection reason for the laboratory:`, '');
                    if (promptVal === null) return; // User cancelled
                    reason = promptVal;
                } else {
                    if (!confirm(`Are you sure you want to ${actionText} ${this.selectedRecords.length} records?`)) return;
                }

                fetch(`{{ url('stafford-certs/bulk-action') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        action: action,
                        ids: this.selectedRecords,
                        reason: reason
                    })
                })
                .then(res => {
                    if(res.ok) {
                        this.selectedRecords = [];
                        this.loadRecords();
                    }
                })
                .catch(err => console.error("Bulk action failed", err));
            },

            async exportBulkPdf() {
                if (this.selectedRecords.length === 0) return;
                
                const confirmMsg = `Are you sure you want to export ${this.selectedRecords.length} records to PDF?\n\n(Important: If your browser asks for permission to download multiple files, click "Allow").`;
                if (!confirm(confirmMsg)) return;

                for (let i = 0; i < this.selectedRecords.length; i++) {
                    const id = this.selectedRecords[i];
                    try {
                        const response = await fetch(`{{ url('stafford-certs') }}/${id}/export-pdf`);
                        if (!response.ok) throw new Error('Network response was not ok');
                        
                        const blob = await response.blob();
                        const url = window.URL.createObjectURL(blob);
                        
                        const link = document.createElement('a');
                        link.href = url;
                        
                        // Try to get filename from headers
                        let filename = `Certificate-${id}.pdf`;
                        const disposition = response.headers.get('Content-Disposition');
                        if (disposition && disposition.indexOf('filename=') !== -1) {
                            const matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(disposition);
                            if (matches != null && matches[1]) { 
                                filename = matches[1].replace(/['"]/g, '');
                            }
                        }
                        link.download = filename;
                        
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        window.URL.revokeObjectURL(url);
                        
                        // Wait 1.5 seconds between downloads to ensure the browser processes it
                        await new Promise(r => setTimeout(r, 1500));
                    } catch (err) {
                        console.error('Download failed for record ' + id, err);
                    }
                }
            },

            toggleMenu(id) {
                this.activeMenu = this.activeMenu === id ? null : id;
            },

            // --- WIZARD LOGIC ---
            showPoSuggestions: false,
            isSearchingPo: false,
            poSuggestions: [],

            async searchPoSuggestions() {
                this.isSearchingPo = true;
                const q = (this.formData.poNumber || '').trim();
                try {
                    const res = await fetch(`{{ route('stafford-certs.erp-po-search') }}?q=${encodeURIComponent(q)}`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    if (res.ok) {
                        this.poSuggestions = await res.json();
                        this.showPoSuggestions = true;
                    }
                } catch (e) {
                    console.error("Error loading PO suggestions", e);
                } finally {
                    this.isSearchingPo = false;
                }
            },

            selectPo(po) {
                this.formData.poNumber = po.poNumber;
                this.showPoSuggestions = false;
                this.fetchErpPoDetails();
            },

            erpSuccessMsg: '',
            isFetchingErpPo: false,

            async fetchErpPoDetails() {
                const po = (this.formData.poNumber || '').trim();
                if (!po) return;
                this.isFetchingErpPo = true;
                this.erpSuccessMsg = '';
                try {
                    const res = await fetch(`{{ route('stafford-certs.erp-po-lookup') }}?po=${encodeURIComponent(po)}`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const data = await res.json();
                    if (!res.ok) {
                        this.erpSuccessMsg = '';
                        return;
                    }
                    if (data.found) {
                        if (data.poDate) this.formData.poDate = data.poDate;
                        if (data.productName) this.formData.productName = data.productName;
                        if (data.brandName) this.formData.brandName = data.brandName;
                        if (data.styleNo) this.formData.styleNo = data.styleNo;
                        if (data.color) this.formData.color = data.color;
                        if (data.description || data.erpStyleDesc || data.productName) {
                            this.formData.description = data.description || data.erpStyleDesc || data.productName;
                        }
                        if (data.primaryProductId) this.formData.primaryProductId = data.primaryProductId;
                        if (data.productIdType) this.formData.productIdType = data.productIdType;
                        if (data.upc) this.formData.upc = data.upc;
                        if (data.buyer) this.formData.buyer = data.buyer;
                        if (data.importerName) this.formData.importerName = data.importerName;
                        if (data.importerAddress) this.formData.importerAddress = data.importerAddress;
                        if (data.importerCity) this.formData.importerCity = data.importerCity;
                        if (data.importerState) this.formData.importerState = data.importerState;
                        if (data.importerPostal) this.formData.importerPostal = data.importerPostal;
                        if (data.importerCountry) this.formData.importerCountry = data.importerCountry;
                        
                        // Manufacturing Dates & Lot Number
                        if (data.manufactureDate) this.formData.manufactureDate = data.manufactureDate;
                        if (data.manufactureFullDate) this.formData.manufactureFullDate = data.manufactureFullDate;
                        if (data.manufactureStartDate) this.formData.manufactureStartDate = data.manufactureStartDate;
                        if (data.manufactureEndDate) this.formData.manufactureEndDate = data.manufactureEndDate;
                        if (data.lotNumber) this.formData.lotNumber = data.lotNumber;
                        if (data.lotAssignedBy) this.formData.lotAssignedBy = data.lotAssignedBy;

                        // Factory & Manufacturer Details
                        if (data.factoryName) {
                            // Ensure the ERP factory exists in dbFactories so the <select> displays it immediately
                            const exists = this.dbFactories.some(f => f.name === data.factoryName);
                            if (!exists) {
                                this.dbFactories.unshift({
                                    id: 'erp_' + (data.factoryGLN || Date.now()),
                                    name: data.factoryName,
                                    location: (data.factoryCity ? data.factoryCity + ', ' : '') + (data.factoryCountry || 'ERP Factory'),
                                    address: data.factoryAddress || '',
                                    city: data.factoryCity || '',
                                    state: data.factoryState || '',
                                    country: data.factoryCountry || '',
                                    postal_code: data.factoryPostal || '',
                                    contact_person: data.factoryContact || '',
                                    email: data.factoryEmail || '',
                                    phone: data.factoryPhone || '',
                                    gln: data.factoryGLN || '',
                                    alt_id: data.factoryAltId || '',
                                });
                            }
                            this.formData.factoryName = data.factoryName;
                            this.formData.factoryAddress = data.factoryAddress || '';
                            this.formData.factoryCity = data.factoryCity || '';
                            this.formData.factoryState = data.factoryState || '';
                            this.formData.factoryCountry = data.factoryCountry || '';
                            this.formData.factoryPostal = data.factoryPostal || '';
                            this.formData.factoryContact = data.factoryContact || '';
                            this.formData.factoryEmail = data.factoryEmail || '';
                            this.formData.factoryPhone = data.factoryPhone || '';
                            this.formData.factoryGLN = data.factoryGLN || '';
                            this.formData.factoryAltId = data.factoryAltId || '';
                        }

                        // Lab Test Reports & Link to Existing Qualification
                        if (data.hasLabTests && data.tests && data.tests.length > 0) {
                            this.formData.tests = data.tests;
                            if (data.labId) this.formData.labId = data.labId;
                            if (data.assignedLabName) this.formData.assignedLabName = data.assignedLabName;
                            if (data.qualificationId) {
                                this.formData.id = data.qualificationId;
                                this.editId = data.qualificationId;
                            }
                            if (data.certNo) this.formData.certNo = data.certNo;
                            if (data.certType) this.formData.certType = data.certType;
                            if (data.lastTestDate) {
    this.formData.lastTestDate = data.lastTestDate;
} else if (data.tests && data.tests.length > 0) {
    const dates = data.tests.map(t => t.testDate).filter(Boolean).sort().reverse();
    if (dates.length > 0) this.formData.lastTestDate = dates[0];
}
                        }

                        this.erpSuccessMsg = `Pre-filled from ERP: PO #${data.poNumber} (${data.productName || data.styleNo})`;
                    }
                } catch (err) {
                    console.error('Error fetching ERP PO:', err);
                } finally {
                    this.isFetchingErpPo = false;
                }
            },
            
            async fetchFactoryDetails() {
                if (!this.formData.factoryName || this.formData.factoryName === 'Other') {
                    if (this.formData.factoryName === 'Other') {
                        this.formData.factoryAddress = '';
                        this.formData.factoryCity = '';
                        this.formData.factoryState = '';
                        this.formData.factoryCountry = '';
                        this.formData.factoryPostal = '';
                        this.formData.factoryContact = '';
                        this.formData.factoryEmail = '';
                        this.formData.factoryPhone = '';
                        this.formData.factoryGLN = '';
                        this.formData.factoryAltId = '';
                    }
                    return;
                }
                
                this.isLoadingFactory = true;
                try {
                    const factory = this.dbFactories.find(f => f.name === this.formData.factoryName);
                    if (factory) {
                        this.formData.factoryAddress = factory.address || factory.location || '';
                        this.formData.factoryCity = factory.city || '';
                        this.formData.factoryState = factory.state || ''; 
                        this.formData.factoryCountry = factory.country || ''; 
                        this.formData.factoryPostal = factory.postal_code || ''; 
                        this.formData.factoryContact = factory.contact_person || '';
                        this.formData.factoryEmail = factory.contact_email || factory.email || '';
                        this.formData.factoryPhone = factory.contact_phone || factory.phone || '';
                        this.formData.factoryGLN = factory.registration_no || factory.gln || '';
                        this.formData.factoryAltId = factory.alt_id || '';
                    }
                } catch (error) {
                    console.error("Error fetching factory details", error);
                } finally {
                    this.isLoadingFactory = false;
                }
            },

            openWizard() {
                this.formData = this.getEmptyForm();
                this.editId = null;
                this.erpSuccessMsg = '';
                this.showPoSuggestions = false;
                this.wizardStep = 1;
                this.wizardOpen = true;
                document.body.classList.add('modal-open');
                this.formData.tests = [];
            },
            
            closeWizard() {
                this.wizardOpen = false;
                document.body.classList.remove('modal-open');
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

            viewAttachment(test, record) {
                this.selectedRecordForAttachment = record;
                const url = this.getTestFileUrl(test);
                const fileName = test.testReportFileName 
                    || (test.testReportFile ? test.testReportFile.split('/').pop() : '') 
                    || (url && url.includes('file=') ? decodeURIComponent(url.split('file=').pop().split('&')[0]).split('/').pop() : '')
                    || (url ? url.split('/').pop() : '');

                const cleanName = (fileName || test.testReportFile || url || '').toLowerCase().split('?')[0];
                const isPdf = cleanName.endsWith('.pdf') || (url || '').toLowerCase().includes('.pdf');
                const isImage = /\.(jpg|jpeg|png|webp|gif|svg)$/i.test(cleanName);

                this.attachmentData = {
                    title: (record.styleNo ? record.styleNo + ' - ' : '') + (record.productName || 'Test Report'),
                    reportId: test.testReportId || 'N/A',
                    url: url,
                    fileName: fileName,
                    isPdf: isPdf,
                    isImage: isImage,
                    hasAttachment: Boolean(url && url.trim()),
                    labName: test.labName || ''
                };
                this.attachmentModalOpen = true;
                document.body.classList.add('modal-open');
            },

            closeAttachmentModal() {
                this.attachmentModalOpen = false;
                this.selectedRecordForAttachment = null;
                document.body.classList.remove('modal-open');
            },
            
            editRecord(record) {
                this.editId = record.id;
                this.formData = JSON.parse(JSON.stringify(record)); // Clone
                if(!this.formData.tests) this.formData.tests = [];
                if (!this.formData.lastTestDate && this.formData.tests.length > 0) {
                    const dates = this.formData.tests.map(t => t.testDate).filter(Boolean).sort().reverse();
                    if (dates.length > 0) this.formData.lastTestDate = dates[0];
                }
                this.showPoSuggestions = false;
                this.poSuggestions = [];
                this.erpSuccessMsg = '';
                this.wizardOpen = true;
                document.body.classList.add('modal-open');
                if (this.isLabUser) {
                    this.wizardStep = 2;
                    if (this.formData.tests.length === 0) {
                        this.addLab();
                    }
                } else {
                    this.wizardStep = 1;
                }
            },

            addLab() {
                if (!this.formData.tests) this.formData.tests = [];
                const assignedLab = this.dbLabs.find(l => l.id == this.formData.labId);
                const defaultLabName = this.isLabUser ? '{{ addslashes($userLabName) }}' : (assignedLab?.name || '');
                const defaultCpscId = assignedLab?.cpsc_lab_id || assignedLab?.code || '';
                this.formData.tests.push({
                    labType: 'CPSC-Accepted',
                    labName: defaultLabName,
                    labNameOther: '',
                    labCpscId: defaultCpscId,
                    testReportUrl: '',
                    testReportKey: '',
                    testReportId: '',
                    testReportFile: '',
                    testReportFileName: '',
                    testReportFileUrl: '',
                    isUploadingFile: false,
                    componentTesting: false,
                    componentDetails: '',
                    citations: []
                });
            },
            
            addExclusion() {
                if (!this.formData.tests) this.formData.tests = [];
                this.formData.tests.push({
                    isExclusion: true,
                    exclusionReason: '',
                    citations: [],
                    labName: 'Testing Exclusion',
                    isUploadingFile: false
                });
            },

            async adminUploadFile(file, testIndex) {
                if (!file) return;
                const test = this.formData.tests[testIndex];
                if (!test) return;

                test.isUploadingFile = true;
                const formData = new FormData();
                formData.append('file', file);
                formData.append('_token', '{{ csrf_token() }}');

                try {
                    const res = await fetch('{{ route('stafford-certs.upload-report-file') }}', {
                        method: 'POST',
                        headers: { 'Accept': 'application/json' },
                        body: formData
                    });
                    const data = await res.json();
                    if (!res.ok || !data.success) {
                        this.showNotification('error', 'Upload Failed', data.message || 'File upload failed. Please try again.');
                        return;
                    }
                    test.testReportFile    = data.file_path;
                    test.testReportFileName = data.file_name;
                    test.testReportFileUrl  = data.file_url;
                    this.showNotification('success', 'File Uploaded', `"${data.file_name}" attached successfully.`);
                } catch (err) {
                    console.error('Upload error:', err);
                    this.showNotification('error', 'Upload Error', 'A network error occurred during upload.');
                } finally {
                    test.isUploadingFile = false;
                }
            },
            
            removeLab(index) {
                this.formData.tests.splice(index, 1);
            },
            
            addCitation(labIndex, code) {
                if(!code) return;
                const desc = this.citationMaster[code];
                const lab = this.formData.tests[labIndex];
                if(!lab.citations.find(c => c.code === code)) {
                    lab.citations.push({ code, desc });
                }
            },
            
            removeCitation(labIndex, citationIndex) {
                this.formData.tests[labIndex].citations.splice(citationIndex, 1);
            },
            
            generateVersion() {
                this.formData.versionId = Math.random().toString(16).substring(2, 10).toUpperCase();
            },

            submitForm() {
                if(this.isSubmitting) return;
                this.isSubmitting = true;
                
                // Set status: Complete if lab user or test reports present; Incomplete if newly created by admin
                if (this.isLabUser || (this.formData.tests && this.formData.tests.length > 0 && this.formData.tests[0].testReportId)) {
                    this.formData.status = 'Complete';
                } else if (!this.editId) {
                    this.formData.status = 'Incomplete';
                }

                // Ensure labName is set for all test entries from assigned lab
                if (this.formData.tests) {
                    const assignedLab = this.dbLabs.find(l => l.id == this.formData.labId);
                    const defaultLabName = this.isLabUser ? this.userLabName : (assignedLab?.name || 'Assigned Testing Laboratory');
                    this.formData.tests.forEach(t => {
                        if (!t.labName || t.labName === 'Other') {
                            t.labName = t.labNameOther || defaultLabName;
                        }
                    });
                }

                const url = this.editId ? `{{ url('stafford-certs') }}/${this.editId}` : '{{ route('stafford-certs.store') }}';
                const method = this.editId ? 'PUT' : 'POST';

                const payload = JSON.parse(JSON.stringify(this.formData));
                if (payload.tests) {
                    payload.tests = payload.tests.map(t => {
                        const { isUploadingFile, ...rest } = t;
                        return rest;
                    });
                }

                fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                })
                .then(async res => {
                    const data = await res.json();
                    if (!res.ok) {
                        throw data;
                    }
                    return data;
                })
                .then(data => {
                    this.isSubmitting = false;
                    if (data.success) {
                        this.closeWizard();
                        this.loadRecords();
                        this.showNotification('success', 'Success', data.message || 'Record saved successfully!');
                    } else {
                        this.showNotification('error', 'Error', data.message || 'Failed to save record.');
                    }
                })
                .catch(err => {
                    this.isSubmitting = false;
                    if (err && err.errors) {
                        let errorList = '<ul class="mb-0 ps-3">';
                        for (let field in err.errors) {
                            errorList += `<li>${err.errors[field][0]}</li>`;
                        }
                        errorList += '</ul>';
                        this.showNotification('error', 'Validation Error', errorList);
                    } else {
                        this.showNotification('error', 'Error', err.message || 'Error saving record.');
                    }
                });
            },

            deleteRecord(record) {
            const id = typeof record === 'object' ? record.id : record;
            const count = (record.allPoNumbers && record.allPoNumbers.length > 0) ? record.allPoNumbers.length : 1;
            const confirmMsg = count > 1 
            ? `Are you sure you want to archive all ${count} POs in this group?`
            : 'Are you sure you want to archive this collection?';
            if (confirm(confirmMsg)) {
            fetch(`{{ url('stafford-certs') }}/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
           })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                this.loadRecords();
                this.showNotification('success', 'Archived', data.message || 'Collection archived successfully!');
            }
        })
        .catch(err => {
            console.error('Delete error:', err);
            this.showNotification('error', 'Error', 'Failed to archive collection.');
           });
        }
       },

       openRejectModal(record) {
           this.recordToReject = record;
           this.rejectReason = '';
           this.isRejecting = false;
           const modalEl = document.getElementById('rejectConfirmModal');
           if (modalEl) {
               const modal = new bootstrap.Modal(modalEl);
               modal.show();
           }
       },

       confirmReject() {
           if (!this.recordToReject) return;
           this.isRejecting = true;
           const id = this.recordToReject.id;

           fetch(`{{ url('stafford-certs') }}/${id}/reject`, {
               method: 'POST',
               headers: {
                   'Content-Type': 'application/json',
                   'X-CSRF-TOKEN': '{{ csrf_token() }}',
                   'Accept': 'application/json'
               },
               body: JSON.stringify({
                   reason: this.rejectReason
               })
           })
           .then(res => res.json())
           .then(data => {
               this.isRejecting = false;
               const modalEl = document.getElementById('rejectConfirmModal');
               if (modalEl) {
                   const modal = bootstrap.Modal.getInstance(modalEl);
                   if (modal) modal.hide();
               }
               if (data.success) {
                   this.loadRecords();
                   this.showNotification('success', 'Rejected', data.message || 'Record rejected and returned to laboratory successfully.');
               } else {
                   this.showNotification('error', 'Error', data.message || 'Failed to reject record.');
               }
           })
           .catch(err => {
               this.isRejecting = false;
               console.error('Reject error:', err);
               this.showNotification('error', 'Error', 'Failed to reject record.');
           });
       }
        };
    }
</script>
@endsection
