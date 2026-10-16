<?php

namespace App\Services;

class EFilingApiService
{
    protected CpscService $cpscService;

    public function __construct(CpscService $cpscService)
    {
        $this->cpscService = $cpscService;
    }

    public function getCollections(): array
    {
        return $this->cpscService->getCollections();
    }

    public function import(array $productLists, ?bool $doCertify = null): array
    {
        $doCertifyBool = $doCertify ?? config('services.cpsc.do_certify', true);
        return $this->cpscService->importProducts($productLists, $doCertifyBool);
    }

    public function getImportStatus(string $importId): array
    {
        return $this->cpscService->getImportStatus($importId);
    }

    public function getImportLog(string $importId, bool $errorsOnly = false): array
    {
        return $this->cpscService->getImportLog($importId, $errorsOnly);
    }

    public function export(array $filters = []): array
    {
        return $this->cpscService->export($filters);
    }

    public function exportAsync(array $filters = []): array
    {
        return $this->cpscService->exportAsync($filters);
    }

    public function exportAsyncStatus(string $exportId): array
    {
        return $this->cpscService->exportAsyncStatus($exportId);
    }

    public function exportAsyncData(string $exportId): array
    {
        return $this->cpscService->exportAsyncData($exportId);
    }

    public function getCertificates(array $productTuples): array
    {
        return $this->cpscService->getCertificates($productTuples);
    }

    public function getTradeParties(?string $tradePartyType = null): array
    {
        return $this->cpscService->getTradeParties($tradePartyType);
    }

    public function getTokenExpiration(): array
    {
        return $this->cpscService->getTokenExpiration();
    }
}
