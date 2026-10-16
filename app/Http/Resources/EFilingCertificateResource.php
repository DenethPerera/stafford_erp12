<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EFilingCertificateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $cleanStr = function ($val) {
            if (!is_string($val)) return $val;
            $val = preg_replace('/[\r\n\t]+/', ' ', $val);
            $val = preg_replace('/\s+/', ' ', $val);
            $trimmed = trim($val);
            return $trimmed !== '' ? $trimmed : null;
        };

        $cleanCountry = function ($val) use ($cleanStr) {
            $cleaned = $cleanStr($val);
            if (!$cleaned) return null;
            $map = [
                'India' => 'India',
                'Egypt' => 'Egypt',
                'Vietnam' => 'Vietnam',
                'Bangladesh' => 'Bangladesh',
                'Cambodia' => 'Cambodia',
                'Pakistan' => 'Pakistan',
                'Sri Lanka' => 'Sri Lanka',
                'United States' => 'United States',
                'USA' => 'United States',
                'US' => 'United States',
                'Canada' => 'Canada',
                'China' => 'China',
                'Ghana' => 'Ghana',
                'Myanmar' => 'Myanmar',
            ];
            foreach ($map as $needle => $official) {
                if (stripos($cleaned, $needle) !== false) {
                    return $official;
                }
            }
            return rtrim(trim($cleaned), '.,');
        };

        $cleanUrl = function ($val) use ($cleanStr) {
            $cleaned = $cleanStr($val);
            if (!$cleaned) return null;

            // Extract plain URL from Markdown link pattern [label](https://...) or [https://...](https://...)
            if (preg_match('/\[.*?\]\((https?:\/\/[^\s\)]+)\)/i', $cleaned, $matches)) {
                return trim($matches[1]);
            }

            // Extract URL if prefixed or wrapped in brackets/parens
            if (preg_match('/(https?:\/\/[^\s>\]\)]+)/i', $cleaned, $matches)) {
                return trim($matches[1]);
            }

            return $cleaned;
        };

        $masterLab = $this->lab;
        $tests = $this->tests ?? [];
        $mappedLabs = [];
        $directiveLabs = [];

        foreach ($tests as $test) {
            $uiLabType = $test['labType'] ?? '';
            $apiLabType = ($uiLabType === 'CPSC-Accepted' || $uiLabType === 'ITL') ? 'ITL' : ($uiLabType === 'LAB' ? 'LAB' : ($uiLabType ?: 'ITL'));

            $labName = $cleanStr($masterLab?->name) ?? $cleanStr($test['labName'] ?? null);

            $cpscId = null;
            if ($apiLabType === 'ITL') {
                $rawCpscId = isset($test['labCpscId']) ? preg_replace('/[^\d]/', '', (string)$test['labCpscId']) : null;
                if (!empty($rawCpscId) && strlen($rawCpscId) === 4) {
                    $cpscId = $rawCpscId;
                } else {
                    $cpscId = $cleanStr($test['labCpscId'] ?? null);
                }
            }

            $labGln = $cleanStr($test['gln'] ?? null);
            $labAltId = $cleanStr($test['alternateId'] ?? null);

            $isComponent = $test['isComponent'] ?? $test['componentTesting'] ?? false;
            if (is_string($isComponent)) {
                $isComponent = in_array(strtolower($isComponent), ['true', 'yes', '1', 'y']);
            }

            $rawTestUrl = $test['testURL'] ?? $test['testReportUrl'] ?? null;

            $lab = [
                'type' => $apiLabType,
                'cpscId' => $cpscId,
                'gln' => $labGln,
                'alternateId' => $labAltId,
                'name' => $labName,
                'addressLine1' => $cleanStr($masterLab?->address_line1) ?? $cleanStr($test['addressLine1'] ?? null),
                'addressLine2' => $cleanStr($masterLab?->address_line2) ?? $cleanStr($test['addressLine2'] ?? null),
                'aptNumber' => $cleanStr($test['aptNumber'] ?? null),
                'city' => $cleanStr($test['city'] ?? null),
                'stateProvince' => $cleanStr($test['stateProvince'] ?? null),
                'country' => $cleanCountry($test['country'] ?? null),
                'postalCode' => $cleanStr($test['postalCode'] ?? null),
                'phone' => $cleanStr($masterLab?->phone) ?? $cleanStr($test['phone'] ?? null),
                'email' => $cleanStr($masterLab?->email) ?? $cleanStr($test['email'] ?? null),
                'testReportId' => $cleanStr($test['testReportId'] ?? $test['reportNo'] ?? null),
                'testURL' => $cleanUrl($rawTestUrl),
                'testReportAccessKey' => $cleanStr($test['testReportAccessKey'] ?? null),
                'isComponent' => $isComponent,
                'componentDescription' => $cleanStr($test['componentDescription'] ?? null),
            ];

            if (!empty($test['citations'])) {
                $cleanedCitations = [];
                foreach ($test['citations'] as $citation) {
                    $rawCode = is_array($citation) ? ($citation['code'] ?? null) : $citation;
                    if ($rawCode) {
                        $cleanCode = trim(preg_replace('/^(?:16\s*CFR\s*|CFR\s*)/i', '', $rawCode));
                        if (!empty($cleanCode)) {
                            $cleanedCitations[] = $cleanCode;
                        }
                    }
                }
                if (!empty($cleanedCitations)) {
                    $lab['citationCodes'] = array_values(array_unique($cleanedCitations));
                }
            }

            $mappedLabs[] = array_filter($lab, fn($val) => !is_null($val) && $val !== '');

            // Lab Directive for CPSC
            $isLabNew = (isset($test['isNew']) && $test['isNew'] === 'N' && !empty($labGln)) ? 'N' : 'Y';
            $labDirective = [
                'isNew' => $isLabNew,
            ];
            if ($labGln) $labDirective['gln'] = $labGln;
            if ($labAltId) $labDirective['alternateId'] = $labAltId;
            $directiveLabs[] = array_filter($labDirective, fn($val) => !is_null($val) && $val !== '');
        }

        // Point of Contact (POC) Handling - Preserve database poc_type and adhere to CPSC v4.0 Spec Page 23
        $rawPocType = $cleanStr($this->poc_type ?? $this->point_of_contact);
        $validPocTypes = ['Importer', 'Manufacturer', 'Laboratory', 'Broker', 'Other'];
        
        $pocType = 'Importer';
        if ($rawPocType && in_array($rawPocType, $validPocTypes)) {
            $pocType = $rawPocType;
        }

        $pocGln = $cleanStr($this->poc_gln);
        $pocAltId = $cleanStr($this->poc_alternate_id);

        $poc = [
            'type' => $pocType,
            'gln' => $pocGln,
            'alternateId' => $pocAltId,
            'name' => $cleanStr($this->poc_name),
        ];

        // Per CPSC v4.0 Spec Page 23: Do not provide address/phone/email if POC type is Importer, Manufacturer, Laboratory, or Broker.
        if ($pocType === 'Other') {
            $poc['addressLine1'] = $cleanStr($this->poc_address_line1);
            $poc['addressLine2'] = $cleanStr($this->poc_address_line2);
            $poc['aptNumber'] = $cleanStr($this->poc_apt_number);
            $poc['city'] = $cleanStr($this->poc_city);
            $poc['stateProvince'] = $cleanStr($this->poc_state_province);
            $poc['country'] = $cleanCountry($this->poc_country);
            $poc['postalCode'] = $cleanStr($this->poc_postal_code);
            $poc['phone'] = $cleanStr($this->poc_phone);
            $poc['email'] = $cleanStr($this->poc_email);
        }
        $poc = array_filter($poc, fn($val) => !is_null($val) && $val !== '');

        $factoryGln = $cleanStr($this->factory_gln);
        $factoryAltId = $cleanStr($this->factory_alt_id);

        $manufacturer = [
            'name' => $cleanStr($this->factory_name),
            'addressLine1' => trim(mb_substr($cleanStr($this->factory_address) ?? '', 0, 100)),
            'city' => $cleanStr($this->factory_city),
            'stateProvince' => $cleanStr($this->factory_state),
            'country' => $cleanCountry($this->factory_country),
            'postalCode' => $cleanStr($this->factory_postal),
            'phone' => $cleanStr($this->factory_phone),
            'email' => $cleanStr($this->factory_email),
            'gln' => $factoryGln,
            'alternateId' => $factoryAltId,
            'sbmId' => $cleanStr($this->factory_sbm_id ?? null),
        ];
        $manufacturer = array_filter($manufacturer, fn($val) => !is_null($val) && $val !== '');

        // Standard CPSC primary product ID types: GTIN, UPC, SKU, Model #, Serial #, Registered #, Alternate ID
        $validTypes = ['GTIN', 'UPC', 'SKU', 'Model #', 'Serial #', 'Registered #', 'Alternate ID'];
        $productIdType = $this->product_id_type;
        if (!in_array($productIdType, $validTypes)) {
            $productIdType = 'Alternate ID';
        }

        // CPSC Certificate Types: Only GCC or CPC
        $certType = 'GCC';
        if ($this->cert_type && stripos($this->cert_type, 'CPC') !== false) {
            $certType = 'CPC';
        }

        // versionId must come from actual record version_id (do NOT default to V1)
        $versionId = $cleanStr($this->version_id);

        $rawIdentifiers = $this->additional_identifiers ?? [];
        $mappedIdentifiers = [];
        if (is_array($rawIdentifiers)) {
            foreach ($rawIdentifiers as $item) {
                if (!is_array($item)) continue;
                $val = $cleanStr($item['identifier'] ?? $item['id'] ?? null);
                $type = $cleanStr($item['identType'] ?? $item['type'] ?? null);
                if ($val && $type) {
                    $mappedIdentifiers[] = [
                        'identifier' => mb_substr($val, 0, 50),
                        'identType'  => $type,
                    ];
                }
            }
        }

        $coreProduct = [
            'versionId' => $versionId,
            'primaryProductId' => mb_substr($cleanStr($this->primary_product_id) ?? '', 0, 19),
            'primaryProductIdType' => $productIdType,
            'identifiers' => $mappedIdentifiers,
            'certificateType' => $certType,
            'name' => $cleanStr($this->product_name),
            'tradeBrandName' => $cleanStr($this->brand_name),
            'description' => $cleanStr($this->description),
            'color' => $cleanStr($this->color),
            'style' => $cleanStr($this->style_no),
            'manufactureDate' => $this->manufacture_date ? \Carbon\Carbon::parse($this->manufacture_date)->format('m/Y') : null,
            'productionStartDate' => $this->manufacture_start_date ? \Carbon\Carbon::parse($this->manufacture_start_date)->format('m/d/Y') : null,
            'productionEndDate' => $this->manufacture_end_date ? \Carbon\Carbon::parse($this->manufacture_end_date)->format('m/d/Y') : null,
            'lotNumber' => $cleanStr($this->lot_number),
            'lotNumberAssignedBy' => $this->lot_assigned_by,
            'lastTestDate' => $this->last_test_date ? \Carbon\Carbon::parse($this->last_test_date)->format('m/d/Y') : null,
            'manufacturer' => $manufacturer,
            'labs' => $mappedLabs,
            'poc' => $poc,
            'exemptions' => $this->exemptions ?? [],
        ];
        
        $coreProduct = array_filter($coreProduct, fn($val) => !is_null($val) && $val !== '' && $val !== []);

        // Directives logic strictly following CPSC v4.0 specification rules
        $productUpdate = ($this->api_sync_status === 'synced') ? 'Y' : 'N';

        $isMfgNew = ($this->factory_is_new === 'Y') ? 'Y' : 'N';
        $mfgDirective = [
            'isNew' => $isMfgNew,
        ];
        if ($factoryGln) $mfgDirective['gln'] = $factoryGln;
        if ($factoryAltId) $mfgDirective['alternateId'] = $factoryAltId;

        $isPocNew = $this->poc_is_new === 'Y' ? 'Y' : 'N';
        $pocDirective = [
            'isNew' => $isPocNew,
        ];
        if ($pocGln) $pocDirective['gln'] = $pocGln;
        if ($pocAltId) $pocDirective['alternateId'] = $pocAltId;

        $directives = [
            'productUpdate' => $productUpdate,
            'manufacturer' => $mfgDirective,
            'poc' => $pocDirective,
        ];

        // Per CPSC Spec Page 24: versionIdToUpdate is ONLY included when productUpdate === 'Y'
        if ($productUpdate === 'Y' && !empty($versionId)) {
            $directives['versionIdToUpdate'] = $versionId;
        }

        if (!empty($directiveLabs)) {
            $directives['labs'] = $directiveLabs;
        }

        $directives = array_filter($directives, fn($val) => !is_null($val) && $val !== '' && $val !== []);

        return [
            'coreProduct' => $coreProduct,
            'directives' => $directives,
        ];
    }
}
