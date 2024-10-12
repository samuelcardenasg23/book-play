<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Book;
use Filament\Tables;
use App\Enums\Status;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Log;
use App\Services\GoogleBooksService;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Columns\Summarizers\Sum;
use App\Filament\Resources\BookResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\BookResource\RelationManagers;

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
                        Forms\Components\Group::make([
                            Select::make('google_books_search')
                                ->label('Search Google Books')
                                ->searchable()
                                ->getSearchResultsUsing(function (string $search) {
                                    $googleBooksService = new GoogleBooksService();
                                    $results = $googleBooksService->searchBooks($search);

                                    return collect($results['items'] ?? [])
                                        ->take(10)
                                        ->mapWithKeys(function ($item) {
                                            return [$item['id'] => $item['volumeInfo']['title'] . ' - ' . implode(', ', $item['volumeInfo']['authors'] ?? []) . ' - ' . ($item['volumeInfo']['publisher'] ?? 'N/A')];
                                        })
                                        ->toArray();
                                })
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state && $state !== 'label') {
                                        $googleBooksService = new GoogleBooksService();
                                        $book = $googleBooksService->getBookById($state);

                                        if ($book && isset($book['volumeInfo'])) {
                                            $volumeInfo = $book['volumeInfo'];

                                            $set('title', $volumeInfo['title'] ?? '');
                                            $set('authors', implode(', ', $volumeInfo['authors'] ?? []));
                                            $set('description', strip_tags($volumeInfo['description'] ?? ''));
                                            $set('cover_image_url', $volumeInfo['imageLinks']['thumbnail'] ?? '');
                                            $set('page_count', $volumeInfo['pageCount'] ?? null);
                                            $set('published_date', $volumeInfo['publishedDate'] ?? null);
                                            $set('main_category', $volumeInfo['categories'][0] ?? '');
                                            $set('average_rating', $volumeInfo['averageRating'] ?? null);
                                            $set('google_books_id', $state);
                                        }
                                    }
                                })
                                ->reactive(),
                        ])->visible(fn ($livewire) => $livewire instanceof Pages\CreateBook),
                        Placeholder::make('image')
                            ->label('Cover Image')
                            ->content(function ($get): HtmlString {
                                $coverImage = $get('cover_image_url');
                                return $coverImage
                                    ? new HtmlString("<img src='{$coverImage}' style='max-width: 200px; max-height: 300px;'>")
                                    : new HtmlString("<span>No cover image available</span>");
                            }),
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->disabled()
                            ->dehydrated(),
                        TextInput::make('authors')
                            ->disabled()
                            ->dehydrated(),
                        TextInput::make('cover_image_url')
                            ->url()
                            ->disabled()
                            ->dehydrated()
                            ->hidden(),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull()
                            ->disabled()
                            ->dehydrated()
                            ->autosize(),
                        TextInput::make('page_count')
                            ->numeric()
                            ->minValue(1)
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\DatePicker::make('published_date')
                            ->format('Y-m-d')
                            ->native(false)
                            ->disabled()
                            ->dehydrated(),
                        TextInput::make('main_category')
                            ->maxLength(255)
                            ->disabled()
                            ->dehydrated(),
                        TextInput::make('average_rating')
                            ->numeric()
                            ->step(0.1)
                            ->minValue(0)
                            ->maxValue(5)
                            ->suffix('/5')
                            ->disabled()
                            ->dehydrated(),
                        TextInput::make('google_books_id')
                            ->maxLength(255)
                            ->placeholder('Enter Google Books ID')
                            ->helperText('Optional: ID from Google Books API')
                            ->disabled()
                            ->dehydrated()
                            ->hidden(),
                    ])->columns(2),
                Section::make('Book Status')
                    ->icon('heroicon-o-calculator')
                    ->schema([
                        Forms\Components\ToggleButtons::make('status')
                            ->options([
                                'For Purchase' => 'For Purchase',
                                'Owned' => 'Owned',
                                'Reading' => 'Reading',
                                'Read' => 'Read',
                            ])
                            ->colors([
                                'For Purchase' => 'danger',
                                'Owned' => 'info',
                                'Reading' => 'warning',
                                'Read' => 'success',
                            ])
                            ->default('For Purchase')
                            ->reactive()
                            ->inline(),
                        Forms\Components\DatePicker::make('purchase_date')
                            ->label('Purchase Date')
                            ->native(false)
                            ->format('Y-m-d')
                            ->nullable()
                            ->hidden(fn (callable $get) => $get('status') === 'For Purchase'),
                        Forms\Components\TextInput::make('price')
                            ->label('Price')
                            ->prefix('$')
                            ->required()
                            ->default(0),
                        Forms\Components\DatePicker::make('start_reading_date')
                            ->label('Start Reading Date')
                            ->native(false)
                            ->format('Y-m-d')
                            ->nullable()
                            ->hidden(fn (callable $get) => in_array($get('status'), ['For Purchase', 'Owned'])),
                        Forms\Components\DatePicker::make('finish_reading_date')
                            ->label('Finish Reading Date')
                            ->native(false)
                            ->format('Y-m-d')
                            ->nullable()
                            ->hidden(fn (callable $get) => $get('status') !== 'Read'),
                        Forms\Components\TextInput::make('reading_progress')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->hidden(fn (callable $get) => in_array($get('status'), ['For Purchase', 'Owned'])),
                        Forms\Components\TextInput::make('personal_rating')
                            ->numeric()
                            ->step(0.1)
                            ->minValue(0)
                            ->maxValue(5)
                            ->suffix('/5')
                            ->visible(fn (callable $get) => in_array($get('status'), ['Read', 'Reading'])),
                        Forms\Components\RichEditor::make('personal_notes')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])->columns(2),
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
                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->money('COP')
                    ->sortable()
                    ->toggleable()
                    ->summarize([
                        Sum::make()->money(currency: 'COP', divideBy: 100),
                    ]),
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
                Tables\Actions\ViewAction::make(),
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
