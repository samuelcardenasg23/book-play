<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Book;
use Filament\Tables;
use App\Enums\Status;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Section;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\BookResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\BookResource\RelationManagers;
use Illuminate\Support\HtmlString;

class BookResource extends Resource
{
    protected static ?string $model = Book::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'Books';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'authors'];
    }

    public static function getNavigationBadge(): ?string
    {
        return Book::where('user_id', Auth::user()->id)->count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Book Information')
                    ->icon('heroicon-o-book-open')
                    ->schema([
                Forms\Components\Placeholder::make('image')
                    ->label('Cover Image')
                    ->content(function (Book $record): HtmlString {
                        $coverImage = $record->cover_image_url;

                        return new HtmlString(
                            "<img src='{$coverImage}'>",
                        );
                    })
                    ->hidden(fn(?Book $record) => $record === null)
                    ->columnSpan(3),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TagsInput::make('authors')
                    ->separator(',')
                    ->splitKeys(['Tab', ',']),
                Forms\Components\TextInput::make('cover_image_url')
                    ->url()
                    ->maxLength(255),
                Forms\Components\RichEditor::make('description')
                    ->maxLength(65535)
                    ->columnSpan(3),
                Forms\Components\TextInput::make('page_count')
                    ->numeric()
                    ->minValue(1),
                Forms\Components\DatePicker::make('published_date')
                    ->format('Y-m-d')
                    ->native(false),
                Forms\Components\TextInput::make('main_category')
                    ->maxLength(255),
                Forms\Components\TextInput::make('average_rating')
                    ->numeric()
                    ->step(0.1)
                    ->minValue(0)
                    ->maxValue(5)
                    ->suffix('/5'),
                Forms\Components\TextInput::make('google_books_id')
                    ->maxLength(255)
                    ->placeholder('Enter Google Books ID')
                    ->helperText('Optional: ID from Google Books API'),
            ])->columns(3),
            Section::make('Book Status')
                ->icon('heroicon-o-calculator')
                ->schema([
                Forms\Components\ToggleButtons::make('status')
                    ->options(Status::class)
                    ->colors(Status::getColors())
                    ->default(Status::FORPURCHASE)
                    ->inline(),
                Forms\Components\DatePicker::make('purchase_date')
                    ->native(false)
                    ->default(now())
                    ->format('Y-m-d'),
                Forms\Components\DatePicker::make('start_reading_date')
                    ->native(false)
                    ->default(now())
                    ->format('Y-m-d'),
                Forms\Components\DatePicker::make('finish_reading_date')
                    ->native(false)
                    ->default(now())
                    ->format('Y-m-d'),
                Forms\Components\TextInput::make('reading_progress')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->maxValue(100)
                    ->suffix('%'),
                Forms\Components\TextInput::make('personal_rating')
                    ->numeric()
                    ->step(0.1)
                    ->minValue(0)
                    ->maxValue(5)
                    ->suffix('/5'),
                Forms\Components\RichEditor::make('personal_notes')
                    ->maxLength(65535)
                    ->columnSpan(3),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover_image_url')
                    ->label('Cover'),
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('authors')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(
                        fn(string $state): string => match ($state) {
                            'For Purchase' => 'danger',
                            'Owned' => 'info',
                            'Reading' => 'warning',
                            'Read' => 'success',
                        },
                    )
                    ->searchable(),
                Tables\Columns\TextColumn::make('reading_progress')
                    ->label('Progress'),
                Tables\Columns\TextColumn::make('personal_rating')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'For Purchase' => 'For Purchase',
                        'Owned' => 'Owned',
                        'Reading' => 'Reading',
                        'Read' => 'Read',
                    ]),
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
            'index' => Pages\ListBooks::route('/'),
            'create' => Pages\CreateBook::route('/create'),
            'edit' => Pages\EditBook::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', Auth::user()->id);
    }
}
