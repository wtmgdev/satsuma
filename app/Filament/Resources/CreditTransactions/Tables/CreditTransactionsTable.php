<?php

namespace App\Filament\Resources\CreditTransactions\Tables;

use App\Models\CreditTransaction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CreditTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('organization.name')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Deleted organization'),
                TextColumn::make('site.name')
                    ->placeholder('—')
                    ->label('Site'),
                TextColumn::make('user.email')
                    ->placeholder('—')
                    ->label('User'),
                TextColumn::make('action')
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        in_array($state, ['topup', 'grant', 'monthly_reset']) => 'success',
                        default => 'info',
                    }),
                TextColumn::make('quantity')
                    ->numeric()
                    ->label('Units'),
                TextColumn::make('credits')
                    ->numeric()
                    ->color(fn (CreditTransaction $record) => $record->credits >= 0 ? 'success' : 'danger'),
                TextColumn::make('cost_estimate')
                    ->money('USD')
                    ->label('Est. cost'),
                IconColumn::make('metered')
                    ->boolean()
                    ->label('Metered'),
                TextColumn::make('description')
                    ->placeholder('—')
                    ->limit(40),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->options([
                        'pagespeed_analysis' => 'PageSpeed analysis',
                        'ai_summary' => 'AI summary',
                        'crawl_page' => 'Crawl page',
                        'topup' => 'Top-up',
                        'grant' => 'Bonus grant',
                        'monthly_reset' => 'Monthly reset',
                        'monthly_expiry' => 'Monthly expiry',
                    ]),
                TernaryFilter::make('metered')
                    ->label('Metered')
                    ->trueLabel('Customer')
                    ->falseLabel('Internal'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
