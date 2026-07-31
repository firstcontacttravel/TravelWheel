<?php

namespace App\Filament\Resources\CarHires\Tables;

use App\Mail\DriverAssignedMail;
use App\Models\CarHire;
use App\Models\Driver;
use App\Models\DriverAssignment;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;
use Throwable;

class CarHiresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('payment_reference')->label('Reference')->searchable()->copyable()->weight('bold'),
                TextColumn::make('full_name')->label('Customer')->searchable()->description(fn (CarHire $record): string => $record->email),
                TextColumn::make('phone_number')->copyable(),
                TextColumn::make('car_type')->badge()->description(fn (CarHire $record): string => $record->category . ' · ' . $record->car_model),
                TextColumn::make('pickup_location')->label('Pick-up')->description(fn (CarHire $record): string => filled($record->dropoff_location) ? '-> ' . $record->dropoff_location : (($record->duration_mins ?? $record->rental_hours * 60) ? round(($record->duration_mins ?? $record->rental_hours * 60) / 60, 1) . 'h rental' : ''))->wrap(),
                TextColumn::make('pickup_date')->label('Pickup')->description(fn (CarHire $record): string => (string) $record->pickup_time)->sortable(),
                TextColumn::make('amount')->money('NGN')->sortable(),
                TextColumn::make('payment_status')->badge()->color(fn (string $state): string => match ($state) {
                    'paid', 'confirmed', 'completed' => 'success',
                    'failed', 'cancelled' => 'danger',
                    default => 'warning',
                })->sortable(),
                IconColumn::make('driver_assigned')->label('Driver')->boolean()->sortable(),
                TextColumn::make('created_at')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('payment_status')->options([
                    'pending' => 'Pending',
                    'paid' => 'Paid',
                    'confirmed' => 'Confirmed',
                    'cancelled' => 'Cancelled',
                    'completed' => 'Completed',
                ]),
            ])
            ->recordActions([
                ViewAction::make(),
                self::assignDriverAction(),
                self::changeStatusAction(),
            ]);
    }

    public static function assignDriverAction(): Action
    {
        return Action::make('assignDriver')
            ->label('Assign driver')
            ->icon('heroicon-o-user-plus')
            ->color('info')
            ->modalHeading(fn (CarHire $record): string => 'Assign driver — ' . $record->payment_reference)
            ->modalSubmitActionLabel('Assign driver')
            ->modalWidth('lg')
            ->form(fn (CarHire $record): array => [
                Select::make('driver_id')
                    ->label('Existing driver')
                    ->options(Driver::query()->active()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->live(),
                TextInput::make('driver_name')
                    ->label('New driver name')
                    ->visible(fn ($get) => blank($get('driver_id')))
                    ->maxLength(100),
                TextInput::make('driver_phone')
                    ->label('New driver phone')
                    ->tel()
                    ->visible(fn ($get) => blank($get('driver_id')))
                    ->maxLength(20),
                TextInput::make('car_model')->default($record->car_model)->required()->maxLength(100),
                TextInput::make('car_colour')->required()->maxLength(60),
                TextInput::make('plate_number')->required()->maxLength(20),
                FileUpload::make('car_images')->multiple()->image()->disk('public')->directory('fleet/assigned'),
                Toggle::make('send_email')->label('Email the customer now')->default(true),
            ])
            ->action(function (CarHire $record, array $data): void {
                if (filled($data['driver_id'] ?? null)) {
                    $driver = Driver::findOrFail($data['driver_id']);
                } else {
                    if (blank($data['driver_name'] ?? null) || blank($data['driver_phone'] ?? null)) {
                        Notification::make()->title('Select a driver or provide a name and phone number.')->danger()->send();

                        return;
                    }

                    $driver = Driver::query()->whereRaw('LOWER(name) = ?', [strtolower(trim($data['driver_name']))])->first()
                        ?? Driver::create([
                            'name' => trim($data['driver_name']),
                            'phone' => trim($data['driver_phone']),
                            'is_active' => true,
                        ]);
                }

                DriverAssignment::where('car_hire_id', $record->id)->delete();

                $assignment = DriverAssignment::create([
                    'driver_id' => $driver->id,
                    'car_hire_id' => $record->id,
                    'booking_type' => 'car_hire',
                    'car_model' => $data['car_model'],
                    'car_colour' => $data['car_colour'],
                    'plate_number' => $data['plate_number'],
                    'car_images' => $data['car_images'] ?? [],
                    'assigned_at' => now(),
                ]);

                $record->update(['driver_assigned' => true, 'payment_status' => 'confirmed']);

                if ($data['send_email'] ?? false) {
                    try {
                        Mail::to($record->email)->send(new DriverAssignedMail($assignment));
                        $assignment->update(['email_sent_at' => now()]);
                    } catch (Throwable $e) {
                        Notification::make()->title('Driver assigned, but the email failed to send.')->body($e->getMessage())->warning()->send();

                        return;
                    }
                }

                Notification::make()->title('Driver assigned')->success()->send();
            });
    }

    public static function changeStatusAction(): Action
    {
        return Action::make('changeStatus')
            ->label('Change status')
            ->icon('heroicon-o-arrow-path')
            ->color('gray')
            ->form(fn (CarHire $record): array => [
                Select::make('status')
                    ->label('Payment status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'confirmed' => 'Confirmed',
                        'cancelled' => 'Cancelled',
                        'completed' => 'Completed',
                    ])
                    ->default($record->payment_status)
                    ->required(),
            ])
            ->action(function (CarHire $record, array $data): void {
                $record->update(['payment_status' => $data['status']]);

                Notification::make()->title('Status updated')->success()->send();
            });
    }
}
