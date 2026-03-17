<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseParticipantSplitResource\Pages;
use App\Models\ExpenseParticipantSplit;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ExpenseParticipantSplitResource extends Resource
{
    protected static ?string $model = ExpenseParticipantSplit::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('expense.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('creditor.name')
                    ->description('Person who paid'),
                Tables\Columns\TextColumn::make('debtor.name')
                    ->description('Person who owes'),
                Tables\Columns\TextColumn::make('amount')
                    ->money('INR')
                    ->weight('bold')
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray'),

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
            'index' => Pages\ListExpenseParticipantSplits::route('/'),
            'create' => Pages\CreateExpenseParticipantSplit::route('/create'),
            'edit' => Pages\EditExpenseParticipantSplit::route('/{record}/edit'),
        ];
    }
}
