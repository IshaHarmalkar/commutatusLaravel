<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Expense;
use App\Models\User;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')->required(),
                TextInput::make('amount')->numeric()->required(),
                TextInput::make('tax')->numeric()->default(0),

                // participants
                Select::make('participant_ids')->label('Participants')->multiple()
                    ->options(User::pluck('name', 'id'))
                    ->required(),

                Repeater::make('items')
                    ->schema([
                        TextInput::make('name')
                            ->required(),
                        TextInput::make('amount')->numeric()->required(),
                        Select::make('type')->options([
                            'equal' => 'Equal',
                            'assigned' => 'Assigned',
                        ])->required()->reactive(),

                        Select::make('assigned_to_id')
                            ->label('Assigned Too')
                            ->options(User::pluck('name', 'id'))
                            ->visible(fn ($get) => $get('type') === 'assigned'),
                    ])->required()->minItems(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('paidBy.name')
                    ->label('Payer')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money('INR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}
