<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseItemSplitResource\Pages;
use App\Models\ExpenseItemSplit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ExpenseItemSplitResource extends Resource
{
    protected static ?string $model = ExpenseItemSplit::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('expense_item_id')
                    ->relationship('expenseItem', 'name')
                    ->required()
                    ->searchable(),
                Forms\Components\Select::make('creditor_id')
                    ->relationship('creditor', 'name')
                    ->label('Person Owed')
                    ->required(),
                Forms\Components\Select::make('debtor_id')
                    ->relationship('debtor', 'name')
                    ->label('Person Owing')
                    ->required(),
                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->prefix('Rs')
                    ->required(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('expenseItem.expense.name')
                    ->label('Parent Expense')
                    ->searchable(),
                Tables\Columns\TextColumn::make('expenseItem.name')
                    ->label('Item')
                    ->description(fn (ExpenseItemSplit $record): string => "Type: {$record->expenseItem->type}"),
                Tables\Columns\TextColumn::make('debtor.name')
                    ->label('Debtor'),
                Tables\Columns\TextColumn::make('amount')
                    ->money('INR')
                    ->color('danger'),
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
            'index' => Pages\ListExpenseItemSplits::route('/'),
            'create' => Pages\CreateExpenseItemSplit::route('/create'),
            'edit' => Pages\EditExpenseItemSplit::route('/{record}/edit'),
        ];
    }
}
