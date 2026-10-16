<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificate of Compliance</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; line-height: 1.4; color: #333; margin: 0; padding: 20px; }
        h1 { font-size: 18px; text-align: center; margin-bottom: 5px; }
        h2 { font-size: 14px; text-align: center; margin-top: 0; font-weight: normal; margin-bottom: 30px; }
        h3 { font-size: 12px; border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-top: 20px; margin-bottom: 10px; }
        .grid { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .grid td { vertical-align: top; width: 33.33%; padding: 5px; }
        .info-grid { width: 100%; border-collapse: collapse; }
        .info-grid td { vertical-align: top; width: 33.33%; padding: 2px; }
        .label { font-weight: bold; }
        table.border-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.border-table th, table.border-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        table.border-table th { background-color: #f9f9f9; font-weight: bold; }
        .attestation { font-size: 9px; text-align: justify; margin-top: 10px; }
    </style>
</head>
<body>

    <h1>Certificate of Compliance</h1>
    <h2>General Certificate of Conformity</h2>

    <table class="grid">
        <tr>
            <td>
                <span class="label">Certifier</span><br>
                {{ $record->certifier_name ?? 'Stafford Textile Ltd' }}<br>
                @if(($record->certifier_name ?? 'Stafford Textile Ltd') == 'Stafford Textile Ltd')
                101-1 Eva Rd, Etobicoke, ON, M9C 4Z5<br>
                Toronto, ON M9C 4Z5<br>
                Canada<br>
                @endif
                {{ $record->certifier_phone }}<br>
                {{ $record->certifier_email }}
            </td>
            <td>
                <span class="label">Manufacturer</span><br>
                {{ $record->factory_name }}<br>
                {{ $record->factory_address }}<br>
                {{ $record->factory_city }}, {{ $record->factory_state }} {{ $record->factory_postal }}<br>
                {{ $record->factory_country }}<br>
                {{ $record->factory_phone }}<br>
                {{ $record->factory_email }}
            </td>
            <td>
                <span class="label">Point of Contact for Test Result Records</span><br>
                {{ $record->point_of_contact ?? 'Importer' }}
            </td>
        </tr>
    </table>

    <h3>Product Information</h3>
    <table class="info-grid">
        <tr>
            <td>
                <span class="label">Product Name:</span> {{ $record->product_name }}<br>
                <span class="label">Product ID:</span> {{ $record->primary_product_id }}<br>
                <span class="label">Product ID Type:</span> {{ $record->product_id_type }}<br>
                <span class="label">Product Trade/Brand Name:</span> {{ $record->brand_name }}<br>
                <span class="label">Product/Model Description:</span> {{ $record->description }}<br>
                <span class="label">Product/Model Color:</span> {{ $record->color }}<br>
                <span class="label">Product/Model Style:</span> {{ $record->style_no }}
            </td>
            <td>
                @php
                    $addtl = is_string($record->additional_identifiers) ? json_decode($record->additional_identifiers, true) : $record->additional_identifiers;
                    if (!is_array($addtl)) $addtl = [];
                    $getAddtl = function($type) use ($addtl) {
                        foreach ($addtl as $item) {
                            if (isset($item['type']) && $item['type'] === $type) return $item['id'];
                        }
                        return '';
                    };
                @endphp
                <span class="label">GTIN:</span> {{ $record->gtin }}<br>
                <span class="label">Model #:</span> {{ $record->product_id_type == 'Model #' ? $record->primary_product_id : '' }}<br>
                <span class="label">Serial #:</span> {{ $getAddtl('Serial #') }}<br>
                <span class="label">UPC:</span> {{ $record->upc }}<br>
                <span class="label">SKU:</span> {{ $getAddtl('SKU') }}<br>
                <span class="label">Registered #:</span> {{ $getAddtl('Registered #') }}<br>
                <span class="label">Alternate ID:</span> {{ $getAddtl('Alternate ID') }}
            </td>
            <td>
                <span class="label">Manufacture Date:</span> {{ $record->manufacture_date ? date('m/Y', strtotime($record->manufacture_date)) : '' }}<br>
                <span class="label">Production Start Date:</span> {{ $record->manufacture_start_date ? date('n/j/Y', strtotime($record->manufacture_start_date)) : '' }}<br>
                <span class="label">Production End Date:</span> {{ $record->manufacture_end_date ? date('n/j/Y', strtotime($record->manufacture_end_date)) : '' }}<br>
                <span class="label">Lot #:</span> {{ $record->lot_number }}<br>
                <span class="label">Lot # Assigned By:</span> {{ $record->lot_assigned_by }}
            </td>
        </tr>
    </table>

    <h3>Testing Details</h3>
    <p><span class="label">Last Test Date:</span> {{ $record->last_test_date }}</p>

    <table class="border-table">
        <thead>
            <tr>
                <th>Laboratory</th>
                <th>CPSC ID</th>
                <th>Citation Codes</th>
                <th>Component?</th>
                <th>Test Report Information</th>
            </tr>
        </thead>
        <tbody>
            @if($record->tests && is_array($record->tests))
                @foreach($record->tests as $test)
                @if(isset($test['isExclusion']) && $test['isExclusion'])
                <tr style="background-color: #f2f2f2; font-weight: bold;">
                    <td colspan="5">Exclusion</td>
                </tr>
                <tr>
                    <td>{{ $test['labName'] ?? '' }}</td>
                    <td>{{ $test['labCpscId'] ?? '' }}</td>
                    <td>
                        @if(isset($test['citations']) && is_array($test['citations']))
                            @foreach($test['citations'] as $cit)
                                {{ $cit['code'] ?? '' }}: {{ $cit['desc'] ?? '' }}<br>
                            @endforeach
                        @endif
                    </td>
                    <td>Component?: {{ isset($test['componentTesting']) && $test['componentTesting'] ? 'Yes' : 'No' }}</td>
                    <td>
                        ID: {{ $test['testReportId'] ?? '' }}
                        @if(!empty($test['testReportUrl']))<br><br>URL: {{ $test['testReportUrl'] }}@endif
                        @if(!empty($test['testReportKey']))<br><br>Access Key: {{ $test['testReportKey'] }}@endif
                        @if(!empty($test['testReportFile']))
                            <br><br>File: {{ $test['testReportFileName'] ?? basename($test['testReportFile']) }}
                        @endif
                    </td>
                </tr>
                @else
                <tr>
                    <td>{{ $test['labName'] ?? '' }}</td>
                    <td>{{ $test['labCpscId'] ?? '' }}</td>
                    <td>
                        @if(isset($test['citations']) && is_array($test['citations']))
                            @foreach($test['citations'] as $cit)
                                {{ $cit['code'] ?? '' }}: {{ $cit['desc'] ?? '' }}<br>
                            @endforeach
                        @endif
                    </td>
                    <td>Component?: {{ isset($test['componentTesting']) && $test['componentTesting'] ? 'Yes' : 'No' }}</td>
                    <td>
                        ID: {{ $test['testReportId'] ?? '' }}
                        @if(!empty($test['testReportUrl']))<br><br>URL: {{ $test['testReportUrl'] }}@endif
                        @if(!empty($test['testReportKey']))<br><br>Access Key: {{ $test['testReportKey'] }}@endif
                        @if(!empty($test['testReportFile']))
                            <br><br>File: {{ $test['testReportFileName'] ?? basename($test['testReportFile']) }}
                        @endif
                    </td>
                </tr>
                @endif
                @endforeach
            @else
                <tr>
                    <td colspan="5" style="text-align: center;">No testing details provided.</td>
                </tr>
            @endif
        </tbody>
    </table>

    <h3>Certifier's Attestation</h3>
    <div class="attestation">
        I hereby certify that the finished product(s) covered by this certificate comply with the rules, bans, standards, and regulations stated herein, and that the information in this certificate is true and accurate to the best of my knowledge, information, and belief. I understand and acknowledge that it is a United States federal crime to knowingly and willfully make any materially false, fictitious, or fraudulent statement, representation, or omission on this certificate.
    </div>

</body>
</html>
