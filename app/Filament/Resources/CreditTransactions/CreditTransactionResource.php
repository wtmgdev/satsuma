<?php

namespace App\Filament\Resources\CreditTransactions;

use App\Filament\Resources\CreditTransactions\Pages\ListCreditTransactions;
use App\Filament\Resources\CreditTransactions\Tables\CreditTransactionsTable;
use App\Models\CreditTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CreditTransactionResource extends Resource
{
    protected static ?string $model = CreditTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static ?string $navigationLabel = 'Credit Usage';

    protected static ?string $modelLabel = 'Credit usage';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            //
        ]);
    }

    public static function table(Table $table): Table
    {
        return CreditTransactionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCreditTransactions::route('/'),
        ];
    }
}
