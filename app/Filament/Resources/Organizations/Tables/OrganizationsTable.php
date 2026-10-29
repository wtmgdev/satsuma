<?php

namespace App\Filament\Resources\Organizations\Tables;

use App\Models\Organization;
use App\Services\CreditService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrganizationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('members_count')
                    ->counts('members')
                    ->label('Members')
                    ->badge(),
                TextColumn::make('credits_balance')
                    ->numeric()
                    ->sortable()
                    ->label('Monthly'),
                TextColumn::make('credits_purchased')
                    ->numeric()
                    ->sortable()
                    ->label('Purchased'),
                TextColumn::make('credits_allowance')
                    ->placeholder('Default')
                    ->label('Allowance'),
                IconColumn::make('credits_unlimited')
                    ->boolean()
                    ->label('Unlimited'),
                TextColumn::make('credits_reset_at')
                    ->since()
                    ->label('Resets'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('grantCredits')
                    ->label('Grant credits')
                    ->icon('heroicon-o-plus-circle')
                    ->schema([
                        TextInput::make('amount')
                            ->numeric()
                            ->required()
                            ->minValue(1),
                        Select::make('action')
                            ->options([
                                'topup' => 'Top-up (purchased)',
                                'grant' => 'Bonus grant',
                            ])
                            ->default('topup')
                            ->required(),
                    ])
                    ->action(function (Organization $record, array $data) {
                        app(CreditService::class)->grant(
                            $record,
                            (int) $data['amount'],
                            $data['action'],
                            auth()->user(),
                            'Granted via admin panel',
                        );
                    })
                    ->successNotificationTitle('Credits granted'),
            ])
            ->defaultSort('name');
    }
}
