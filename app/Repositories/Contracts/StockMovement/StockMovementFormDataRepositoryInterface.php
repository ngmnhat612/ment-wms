<?php

namespace App\Repositories\Contracts\StockMovement;

use Illuminate\Support\Collection;

interface StockMovementFormDataRepositoryInterface
{
    public function activeProducts(): Collection;
    public function receivingLocations(): Collection;
    public function issuingLocations(): Collection;
    public function activeEmployees(): Collection;
    public function suppliers(): Collection;
    public function brands(): Collection;
    public function uoms(): Collection;
    public function warehouses(): Collection;
    public function sns(): Collection;
    public function stockInRequests(): Collection;
    public function stockOutRequests(): Collection;
    public function lotsInStockGroupedByProduct(): Collection;
}