<?php

namespace App\Services;

use App\Models\StaffordQualification;
use App\Models\Erav\EravPurchaseOrder;
use App\Models\Erav\EravProductStyle;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ApiIntegrationService
{
    /**
     * Fetch PO details from ERP database and assign it to a Lab.
     * This creates a StaffordQualification record pre-filled with real ERP PO data.
     *
     * @param string $poNumber
     * @param int $labId
     * @return StaffordQualification
     */
    public function fetchPoAndAssignToLab(string $poNumber, int $labId)
    {
        // Try to find the PO in ERP database if provided
        $eravPo = null;
        if (!empty($poNumber)) {
            $eravPo = EravPurchaseOrder::with(['items', 'prodOrders.branch'])
                ->where('pdf_po_refno', $poNumber)
                ->first();
        }

        // If not found (or if simulating a mock PO), grab a real valid PO with items from ERP
        if (!$eravPo) {
            $eravPo = EravPurchaseOrder::with(['items', 'prodOrders.branch'])
                ->whereNotNull('pdf_po_refno')
                ->where('pdf_po_refno', '!=', '')
                ->has('items')
                ->inRandomOrder()
                ->first();
        }

        if ($eravPo) {
            $firstItem = $eravPo->items->first();
            $prodOrder = $eravPo->prodOrders->first(function ($po) {
                return !empty($po->mbranchlist_id) && $po->branch !== null;
            }) ?? $eravPo->prodOrders->first();
            $branch = $prodOrder?->branch;
            $factoryDetails = $this->parseBranchDetails($branch, $prodOrder);

            $style = null;
            if (!empty($firstItem?->item_styleno)) {
                $style = EravProductStyle::where('styleid', $firstItem->item_styleno)->first();
            }

            $brandName = $style?->program_title ?: 'Stafford';
            $fabric = $style?->fabric_content ?: '';
            $desc = $firstItem?->item_description ?: '';
            if ($fabric) {
                $desc = $desc ? ($desc . ' - ' . $fabric) : $fabric;
            }

            $mfgStart = $prodOrder?->prodorder_date ? (string)$prodOrder->prodorder_date : ($eravPo->porder_date ? (string)$eravPo->porder_date : now()->format('Y-m-d'));
            $mfgEnd = $prodOrder?->ex_factory_date ? (string)$prodOrder->ex_factory_date : ($prodOrder?->proddispatch_date ? (string)$prodOrder->proddispatch_date : $mfgStart);
            $lotNumber = $prodOrder?->prodorder_ref ?: ($eravPo->order_conf_refno ?: $eravPo->pdf_po_refno);

            // Extract and clean Primary Product ID directly from item_code
           $rawProdId = $style?->styleid ?: ($firstItem?->item_styleno ?: '');
           
           if (preg_match('/^([^\(]+)/', $rawProdId, $m)) {
           $rawProdId = trim($m[1]);
           }
           
           $cleanProductId = $rawProdId ? substr($rawProdId, 0, 19) : null;

            $data = [
                'po_number'              => $eravPo->pdf_po_refno,
                'season'                 => $season,
                'po_date'                => $eravPo->porder_date ? (string)$eravPo->porder_date : now()->format('Y-m-d'),
                'buyer'                  => $eravPo->customer_name ?: 'Stafford Buyer',
                'importer_name'          => $eravPo->customer_name,
                'importer_address'       => $eravPo->customer_addr,
                'style_no'               => $firstItem?->item_styleno ?: 'STYLE-' . rand(100, 999),
                'erp_style_desc'         => $firstItem?->item_description,
                'product_name'           => $firstItem?->item_description ?: 'ERP Product ' . $eravPo->pdf_po_refno,
                'description'            => $desc,
                'brand_name'             => $brandName,
                'color'                  => $firstItem?->item_colordesc,
                'upc'                    => $firstItem?->upc_refno,
                'primary_product_id'     => $cleanProductId, // <-- Clean item_code (e.g. B116T3QM2)
                'product_id_type'        => !empty($firstItem?->upc_refno) ? 'UPC' : 'Model #',
                'cert_type'              => 'GCC',
                'status'                 => 'Incomplete',
                'lab_id'                 => $labId,
                'factory_name'           => $factoryDetails['factoryName'] ?: 'Stafford Branch',
                'factory_address'        => $factoryDetails['factoryAddress'],
                'factory_city'           => $factoryDetails['factoryCity'],
                'factory_state'          => $factoryDetails['factoryState'],
                'factory_country'        => $factoryDetails['factoryCountry'],
                'factory_postal'         => $factoryDetails['factoryPostal'],
                'factory_contact'        => $factoryDetails['factoryContact'],
                'factory_phone'          => $factoryDetails['factoryPhone'],
                'factory_gln'            => $factoryDetails['factoryGLN'],
                'factory_alt_id'         => $factoryDetails['factoryAltId'],
                'lot_number'             => $lotNumber,
                'lot_assigned_by'        => 'Manufacturer',
                'manufacture_start_date' => $mfgStart,
                'manufacture_end_date'   => $mfgEnd,
                'version_id'             => strtoupper(Str::random(8)),
                'last_test_date'         => now()->format('Y-m-d'),
                'manufacture_date'       => $mfgEnd ?: now()->subMonths(1)->format('Y-m-d'),
                'api_sync_status'        => 'synced',
                'api_import_id'          => (string)$eravPo->id,
                'api_sync_message'       => 'Fetched from ERP database',
            ];

            return StaffordQualification::create($data);
        }

        // Fallback if ERP database is empty
        return StaffordQualification::create([
            'po_number'          => $poNumber,
            'buyer'              => 'Acme Corp',
            'style_no'           => 'STY-' . rand(1000, 9999),
            'product_name'       => 'Sample API Product for PO ' . $poNumber,
            'primary_product_id' => 'SKU-' . rand(10000, 99999),
            'product_id_type'    => 'SKU',
            'cert_type'          => 'CPC',
            'status'             => 'Incomplete',
            'lab_id'             => $labId,
            'version_id'         => Str::random(10),
            'last_test_date'     => now()->format('Y-m-d'),
            'manufacture_date'   => now()->subMonths(1)->format('Y-m-d'),
        ]);
    }

    /**
     * Get real ERP details for a specific PO number for frontend autofill,
     * merged with any submitted lab test reports.
     *
     * @param string $poNumber
     * @return array|null
     */
    public function getPoDetails(string $poNumber, ?string $targetStyle = null): ?array
    {
        $eravPo = EravPurchaseOrder::with(['items', 'prodOrders.branch'])
            ->where('pdf_po_refno', $poNumber)
            ->first();

        // Check if there is an existing StaffordQualification record with lab tests
        $qualQuery = StaffordQualification::withoutGlobalScopes()
            ->with('lab')
            ->where('po_number', $poNumber);

        if (!empty($targetStyle)) {
            $qualQuery->where(function ($q) use ($targetStyle) {
                $q->where('style_no', $targetStyle)
                  ->orWhereNull('style_no')
                  ->orWhere('style_no', '');
            });
        }

        $existingQual = $qualQuery->latest('id')->first();

        if (!$eravPo && !$existingQual) {
            return null;
        }

        $hasLabTests = false;
        $labTests = [];
        $labId = null;
        $assignedLabName = null;
        $qualificationId = null;
        $qualificationStatus = null;
        $qualificationCertNo = null;

        if ($existingQual) {
            $qualificationId = $existingQual->id;
            $qualificationStatus = $existingQual->status;
            $qualificationCertNo = $existingQual->cert_no;
            $labId = $existingQual->lab_id;
            $assignedLabName = $existingQual->lab?->name;

            if (!empty($existingQual->tests) && is_array($existingQual->tests) && count($existingQual->tests) > 0) {
                $hasLabTests = true;
                $labTests = array_map(function ($test) {
                    if (!empty($test['testReportFile']) && empty($test['testReportFileUrl'])) {
                        $test['testReportFileUrl'] = asset('storage/' . $test['testReportFile']);
                    }
                    return $test;
                }, $existingQual->tests);
            }
        }

        $season = $eravPo?->prodOrders?->first(fn($p) => !empty($p->prod_order_season))?->prod_order_season
            ?? ($existingQual?->season ?? '');

        // If not found in ERP tables but exists in qualifications table
        if (!$eravPo) {
            return $this->cleanUtf8([
                'found'                  => true,
                'poNumber'               => $existingQual->po_number,
                'season'                 => $existingQual->season ?? '',
                'poDate'                 => $existingQual->po_date ? (string)$existingQual->po_date : '',
                'buyer'                  => $existingQual->buyer,
                'importerName'           => $existingQual->importer_name ?: $existingQual->buyer,
                'importerAddress'        => $existingQual->importer_address,
                'importerCity'           => $existingQual->importer_city,
                'importerState'          => $existingQual->importer_state,
                'importerPostal'         => $existingQual->importer_postal,
                'importerCountry'        => $existingQual->importer_country,
                'styleNo'                => $targetStyle ?: $existingQual->style_no,
                'erpStyleDesc'           => $existingQual->erp_style_desc,
                'productName'            => $existingQual->product_name,
                'brandName'              => $existingQual->brand_name,
                'color'                  => $existingQual->color,
                'upc'                    => $existingQual->upc,
                'primaryProductId'       => $existingQual->primary_product_id,
                'productIdType'          => $existingQual->product_id_type ?: 'Model #',
                'description'            => $existingQual->description,
                'fabricContent'          => '',
                'manufactureDate'        => $existingQual->manufacture_date ? date('Y-m', strtotime($existingQual->manufacture_date)) : '',
                'manufactureStartDate'   => $existingQual->manufacture_start_date,
                'manufactureEndDate'     => $existingQual->manufacture_end_date,
                'lotNumber'              => $existingQual->lot_number,
                'lotAssignedBy'          => $existingQual->lot_assigned_by ?: 'Manufacturer',
                'factoryName'            => $existingQual->factory_name,
                'factoryAddress'         => $existingQual->factory_address,
                'factoryCity'            => $existingQual->factory_city,
                'factoryState'           => $existingQual->factory_state,
                'factoryCountry'         => $existingQual->factory_country,
                'factoryPostal'          => $existingQual->factory_postal,
                'factoryContact'         => $existingQual->factory_contact,
                'factoryEmail'           => $existingQual->factory_email,
                'factoryPhone'           => $existingQual->factory_phone,
                'factoryGLN'             => $existingQual->factory_gln,
                'factoryAltId'           => $existingQual->factory_alt_id,
                'items'                  => [],
                'hasLabTests'            => $hasLabTests,
                'tests'                  => $labTests,
                'labId'                  => $labId,
                'assignedLabName'        => $assignedLabName,
                'labTestCount'           => count($labTests),
                'qualificationId'        => $qualificationId,
                'qualificationStatus'    => $qualificationStatus,
                'certNo'                 => $qualificationCertNo,
                'certType'               => $existingQual->cert_type ?: 'GCC',
                'lastTestDate' => $existingQual->last_test_date ?: (collect($existingQual->tests)->pluck('testDate')->filter()->sortDesc()->first() ?? null),
            ]);
        }

        // Target the specific style item if requested, otherwise fallback to first item
        $firstItem = null;
        if (!empty($targetStyle)) {
            $firstItem = $eravPo->items->first(function ($i) use ($targetStyle) {
                return strcasecmp(trim($i->item_styleno), trim($targetStyle)) === 0;
            });
        }
        if (!$firstItem) {
            $firstItem = $eravPo->items->first();
        }

        // Pick production order that has a linked factory/branch
        $prodOrder = $eravPo->prodOrders->first(function ($po) {
            return !empty($po->mbranchlist_id) && $po->branch !== null;
        }) ?? $eravPo->prodOrders->first();

        $season = $eravPo->prodOrders->first(fn($p) => !empty($p->prod_order_season))?->prod_order_season
            ?? $prodOrder?->prod_order_season
            ?? ($existingQual?->season ?? '');

        $branch = $prodOrder?->branch;

        // Query product style table for brand name and fabric composition
        $effectiveStyleNo = !empty($targetStyle) ? $targetStyle : ($firstItem?->item_styleno ?: '');
        $style = null;
        if (!empty($effectiveStyleNo)) {
            $style = EravProductStyle::where('styleid', $effectiveStyleNo)->first();
        }

        $brandName = $existingQual?->brand_name ?: ($style?->program_title ?: 'Stafford');
        $fabric = $style?->fabric_content ?: '';
        $itemDesc = $firstItem?->item_description ?: ($style?->product_description ?: '');
        $description = $itemDesc;
        if ($fabric) {
            $description = $description ? ($description . ' - ' . $fabric) : $fabric;
        }

        // Delivery Date & Manufacturing dates (taken ONLY from ex_factory_date, no fallback)
        $deliveryDate = $prodOrder?->ex_factory_date 
            ?? $eravPo->prodOrders->first(fn($p) => !empty($p->ex_factory_date))?->ex_factory_date;

        if (!empty($deliveryDate)) {
            $delivery = Carbon::parse($deliveryDate);
            $manufactureMonth    = $delivery->format('Y-m');         
            $manufactureFullDate = $delivery->format('Y-m-d');      
            $mfgStart            = $delivery->copy()->subMonth()->format('Y-m-d'); 
            $mfgEnd              = $delivery->copy()->subDays(5)->format('Y-m-d');
        } else {
            // Keep completely empty if ex_factory_date is not available
            $manufactureMonth    = '';
            $manufactureFullDate = '';
            $mfgStart            = '';
            $mfgEnd              = '';
        }

        $lotNumber = $prodOrder?->prodorder_ref ?: ($eravPo->order_conf_refno ?: $eravPo->pdf_po_refno);

        // Factory and branch details
        $factoryDetails = $this->parseBranchDetails($branch, $prodOrder);

        // Importer / Customer address details
        $importerAddr = $eravPo->customer_addr ?? '';
        $parsedImporter = $this->parseCustomerAddress($importerAddr);

        // Primary Product ID taken from styleid in prodstyle_list (fallback to item_styleno)
        $primaryProductId = $style?->styleid ?: ($firstItem?->item_styleno ?: '');
        if (preg_match('/^([^\(]+)/', $primaryProductId, $m)) {
        $primaryProductId = trim($m[1]);
       }
       
       $primaryProductId = substr($primaryProductId, 0, 19);

        return $this->cleanUtf8(array_merge([
            'found'                  => true,
            'poNumber'               => $eravPo->pdf_po_refno,
            'season'                 => $season,
            'poDate'                 => $eravPo->porder_date ? (string)$eravPo->porder_date : '',
            'buyer'                  => $eravPo->customer_name,
            'importerName'           => $eravPo->customer_name,
            'importerAddress'        => $importerAddr,
            'importerCity'           => $parsedImporter['city'],
            'importerState'          => $parsedImporter['state'],
            'importerPostal'         => $parsedImporter['postal'],
            'importerCountry'        => $parsedImporter['country'],
            'styleNo'                => $effectiveStyleNo ?: ($firstItem?->item_styleno ?: ''),
            'erpStyleDesc'           => $itemDesc,
            'productName'            => $itemDesc ?: ($style?->product_description ?: 'ERP Product ' . $eravPo->pdf_po_refno),
            'brandName'              => $brandName,
            'color'                  => $firstItem?->item_colordesc ?: '',
            'upc'                    => $firstItem?->upc_refno ?: '',
            'primaryProductId'       => $primaryProductId,
            'productIdType'          => !empty($firstItem?->upc_refno) ? 'UPC' : 'Model #',
            'description'            => $description ?: $itemDesc,
            'fabricContent'          => $fabric,
            'manufactureDate'        => $manufactureMonth,
            'manufactureFullDate'    => $manufactureFullDate,
            'manufactureStartDate'   => $mfgStart,
            'manufactureEndDate'     => $mfgEnd,
            'lotNumber'              => $lotNumber,
            'lotAssignedBy'          => 'Manufacturer',
            'hasLabTests'            => $hasLabTests,
            'tests'                  => $labTests,
            'labId'                  => $labId,
            'assignedLabName'        => $assignedLabName,
            'labTestCount'           => count($labTests),
            'qualificationId'        => $qualificationId,
            'qualificationStatus'    => $qualificationStatus,
            'certNo'                 => $qualificationCertNo,
            'certType'               => $existingQual?->cert_type ?: 'GCC',
            'lastTestDate' => $existingQual?->last_test_date ?: (collect($existingQual?->tests)->pluck('testDate')->filter()->sortDesc()->first() ?? null),
            'items'                  => $eravPo->items->map(function ($item) {
                return [
                    'styleNo'     => $item->item_styleno,
                    'description' => $item->item_description,
                    'color'       => $item->item_colordesc,
                    'size'        => $item->size_code,
                    'upc'         => $item->upc_refno,
                    'orderQty'    => $item->item_order_qty,
                ];
            })->values()->all(),
        ], $factoryDetails));
    }

    /**
     * Parse structured importer/customer address into city, state, postal code, and country.
     *
     * @param string|null $addr
     * @return array
     */
    public function parseCustomerAddress(?string $addr): array
    {
        if (empty($addr)) {
            return ['city' => '', 'state' => '', 'postal' => '', 'country' => ''];
        }

        $lines = array_values(array_filter(array_map('trim', explode("\n", str_replace(["\r\n", "\r"], "\n", $addr)))));
        $country = '';
        $postal = '';
        $state = '';
        $city = '';

        $knownCountries = ['USA', 'United States', 'US', 'Canada', 'Egypt', 'Bangladesh', 'India', 'Sri Lanka', 'Vietnam', 'Cambodia', 'China'];
        foreach ($lines as $line) {
            foreach ($knownCountries as $c) {
                if (stripos($line, $c) !== false) {
                    $country = ($c === 'US') ? 'USA' : $c;
                    break 2;
                }
            }
        }

        foreach ($lines as $line) {
            if (preg_match('/\b([A-Z]{2})\s+(\d{5}(?:-\d{4})?)\b/i', $line, $m)) {
                $state = strtoupper($m[1]);
                $postal = $m[2];
                break;
            } elseif (preg_match('/\b([A-Z]\d[A-Z]\s*\d[A-Z]\d|\d{4,6})\b/', $line, $m)) {
                $postal = $m[1];
            }
        }

        if (preg_match('/([A-Za-z\s]+),\s*([A-Z]{2})\b/', $addr, $m)) {
            $city = trim($m[1]);
            if (!$state) $state = strtoupper($m[2]);
        } else {
            for ($i = count($lines) - 1; $i >= 0; $i--) {
                $line = $lines[$i];
                if ($country && stripos($line, $country) !== false) continue;
                if ($state && stripos($line, $state) !== false) continue;
                if ($postal && stripos($line, $postal) !== false) continue;
                if (preg_match('/^[A-Za-z\s]+$/', $line) && strlen($line) < 35) {
                    $city = $line;
                    break;
                }
            }
        }

        return [
            'city'    => $city,
            'state'   => $state,
            'postal'  => $postal,
            'country' => $country ?: 'USA',
        ];
    }

    /**
     * Recursively convert strings to valid UTF-8.
     *
     * @param mixed $data
     * @return mixed
     */
    public function cleanUtf8($data)
    {
        if (is_string($data)) {
            return mb_convert_encoding($data, 'UTF-8', 'UTF-8');
        }
        if (is_array($data)) {
            return array_map([$this, 'cleanUtf8'], $data);
        }
        return $data;
    }

    /**
     * Parse structured branch address and contact details from ERP mbranchlist.
     *
     * @param \App\Models\Erav\EravBranch|null $branch
     * @param \App\Models\Erav\EravProdOrder|null $prodOrder
     * @return array
     */
    protected function parseBranchDetails($branch, $prodOrder = null): array
    {
        if (!$branch) {
            return [
                'factoryName'    => '',
                'factoryAddress' => '',
                'factoryCity'    => '',
                'factoryState'   => '',
                'factoryCountry' => '',
                'factoryPostal'  => '',
                'factoryContact' => '',
                'factoryEmail'   => '',
                'factoryPhone'   => '',
                'factoryGLN'     => '',
                'factoryAltId'   => '',
            ];
        }

        $rawAddr = $branch->br_addr ?? '';
        $cleanAddr = trim(preg_replace('/[ \t]+/', ' ', str_replace(["\r\n", "\r"], "\n", $rawAddr)));

        // Extract Phone/Tel
        $phone = '';
        if (preg_match('/(?:Tel|Phone|Mobile)\s*[:\-]?\s*([+0-9\s\-()\/]+)/i', $cleanAddr, $m)) {
            $phone = trim($m[1], " \t\n\r\0\x0B,;./-");
        }

        // Extract Country
        $knownCountries = [
            'Egypt', 'Bangladesh', 'India', 'Vietnam', 'Cambodia',
            'Myanmar', 'China', 'Canada', 'Sri Lanka', 'Pakistan', 'USA', 'United States'
        ];
        $country = '';
        foreach ($knownCountries as $c) {
            if (stripos($cleanAddr, $c) !== false) {
                $country = $c;
                break;
            }
        }
        // Check group code prefix for country (e.g. EG, BD, IN, VN, KH)
        if (!$country && !empty($branch->br_grpcode)) {
            $prefix = strtoupper(substr($branch->br_grpcode, 0, 2));
            $countryMap = [
                'EG' => 'Egypt',
                'BD' => 'Bangladesh',
                'IN' => 'India',
                'VN' => 'Vietnam',
                'KH' => 'Cambodia',
                'MM' => 'Myanmar',
                'LK' => 'Sri Lanka',
                'CN' => 'China',
                'CA' => 'Canada',
                'US' => 'United States',
            ];
            if (isset($countryMap[$prefix])) {
                $country = $countryMap[$prefix];
            }
        }
        if (!$country && stripos($cleanAddr, 'S.A.E') !== false) {
            $country = 'Egypt';
        }
        if (!$country && !empty($prodOrder?->loading_port)) {
            $port = $prodOrder->loading_port;
            foreach ($knownCountries as $c) {
                if (stripos($port, $c) !== false) {
                    $country = $c;
                    break;
                }
            }
            if (!$country) {
                $country = trim(preg_replace('/[.,]/', '', $port));
            }
        }

        // Extract Postal Code
        $postal = '';
        if (preg_match('/\b([A-Z]\d[A-Z]\s*\d[A-Z]\d|\d{4,6}|\d{3}\s*\d{3})\b/', $cleanAddr, $m)) {
            $postal = trim($m[1]);
        }

        // Extract City
        $knownCities = [
            'Alexandria', 'Ismailia', 'Cairo', 'Port Said', 'Borg Al Arab', 'Amreya',
            'Dhaka', 'Chittagong', 'Chattogram', 'Gazipur', 'Narayanganj', 'Mymensingh',
            'Tirupur', 'Tiruppur', 'Chennai', 'Mumbai', 'Bangalore', 'Coimbatore',
            'Phnom Penh', 'Dorval', 'Montreal', 'Hanoi', 'Ho Chi Minh', 'Bago'
        ];
        $city = '';
        foreach ($knownCities as $ct) {
            if (stripos($cleanAddr, $ct) !== false) {
                $city = $ct;
                break;
            }
        }

        // Extract State / Province
        $knownStates = [
            'Tamil Nadu', 'Tamilnadu', 'Quebec', 'Ontario', 'Tien Giang', 'Thai Nguyen',
            'Kampong Speu', 'Bono Region', 'Punjab'
        ];
        $state = '';
        foreach ($knownStates as $st) {
            if (stripos($cleanAddr, $st) !== false) {
                $state = $st;
                break;
            }
        }

        // Extract Email
        $email = '';
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $cleanAddr, $m)) {
            $email = trim($m[0]);
        }

        $defaultEmail = env('DEFAULT_COMPLIANCE_EMAIL', 'compliance@stafford.lk');
        $defaultPhone = env('DEFAULT_COMPLIANCE_PHONE', '+1 416-231-1000');

        return [
            'factoryName'    => $branch->br_name ?? '',
            'factoryAddress' => $cleanAddr,
            'factoryCity'    => $city,
            'factoryState'   => $state,
            'factoryCountry' => $country,
            'factoryPostal'  => $postal,
            'factoryContact' => $branch->br_agent ?? '',
            'factoryEmail'   => $email ?: $defaultEmail,
            'factoryPhone'   => $phone ?: $defaultPhone,
            'factoryGLN'     => $branch->br_grpcode ?? '',
            'factoryAltId'   => $branch->br_grpcode ?? '',
        ];
    }
}
