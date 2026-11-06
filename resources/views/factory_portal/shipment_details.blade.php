@extends('layouts.app')

@section('header', 'Shipment Details')

@section('content')
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
        <div class="card-body p-4">
            <h2 class="h4 fw-bold mb-0">Shipment Details</h2>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4 text-uppercase text-muted small">
                            Shipment ID
                        </th>
                        <th class="text-uppercase text-muted small">
                            Factory Name
                        </th>
                        <th class="text-uppercase text-muted small">
                            Purchase Order (PO) Number
                        </th>
                        <th class="text-end pe-4 text-uppercase text-muted small">
                            Action
                        </th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($shipmentRows as $row)
                        <tr>
                            <td class="ps-4 fw-bold">
                                {{ $row->shipment_number }}
                            </td>
                            <td>
                                {{ $row->factory_name }}
                            </td>
                            <td>
                                {{ $row->po_number }}
                            </td>
                            <td class="text-end pe-4">
                                <a
                                    href="{{ route('create-shipment.index') }}"
                                    class="btn btn-sm text-white rounded-pill px-3"
                                    style="background-color: #00A39A;"
                                >
                                    Create Shipment
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-5">
                                No shipments found for your assigned factory.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($shipmentRows->hasPages())
            <div class="p-3 border-top">
                {{ $shipmentRows->links() }}
            </div>
        @endif
    </div>
@endsection
