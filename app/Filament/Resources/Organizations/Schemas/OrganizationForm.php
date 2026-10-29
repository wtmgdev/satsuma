<?php

namespace App\Filament\Resources\Organizations\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class OrganizationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            TextInput::make('credits_allowance')
                ->numeric()
                ->minValue(0)
                ->nullable()
                ->helperText('Monthly allowance — takes effect at the next reset. Leave empty to use the app default.'),
            Toggle::make('credits_unlimited')
                ->label('Unlimited credits')
                ->helperText('Usage is still recorded for spend analytics, but never blocked.'),
        ]);
    }
}
