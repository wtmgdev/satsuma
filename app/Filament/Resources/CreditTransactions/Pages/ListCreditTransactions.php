<?php

namespace App\Filament\Resources\CreditTransactions\Pages;

use App\Filament\Resources\CreditTransactions\CreditTransactionResource;
use Filament\Resources\Pages\ListRecords;

class ListCreditTransactions extends ListRecords
{
    protected static string $resource = CreditTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
