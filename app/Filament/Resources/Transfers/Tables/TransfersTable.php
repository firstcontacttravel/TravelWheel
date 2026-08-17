<?php

namespace App\Filament\Resources\Transfers\Tables;

use App\Mail\DriverAssignedMail;
use App\Models\Driver;
use App\Models\DriverAssignment;
use App\Models\Transfer;
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

class TransfersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('payment_reference')->label('Reference')->searchable()->copyable()->weight('bold'),
                TextColumn::make('full_name')->label('Customer')->searchable()->description(fn (Transfer $record): string => $record->email),
                TextColumn::make('phone_number')->copyable(),
                TextColumn::make('vehicle_name')->description(fn (Transfer $record): string => ucfirst($record->vehicle_type)),
                TextColumn::make('pickup_location')->label('Route')->description(fn (Transfer $record): string => '-> ' . $record->dropoff_location)->wrap(),
                TextColumn::make('pickup_date')->label('Pickup')->description(fn (Transfer $record): string => (string) $record->pickup_time)->sortable(),
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
            ->modalHeading(fn (Transfer $record): string => 'Assign driver — ' . $record->payment_reference)
            ->modalSubmitActionLabel('Assign driver')
            ->modalWidth('lg')
            ->form(fn (Transfer $record): array => [
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
                TextInput::make('car_model')->default($record->vehicle_name)->required()->maxLength(100),
                TextInput::make('car_colour')->required()->maxLength(60),
                TextInput::make('plate_number')->required()->maxLength(20),
                FileUpload::make('car_images')->multiple()->image()->disk('fleet_assets')->directory('fleet/assigned'),
                Toggle::make('send_email')->label('Email the customer now')->default(true),
            ])
            ->action(function (Transfer $record, array $data): void {
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

                DriverAssignment::where('transfer_id', $record->id)->delete();

                $assignment = DriverAssignment::create([
                    'driver_id' => $driver->id,
                    'transfer_id' => $record->id,
                    'booking_type' => 'transfer',
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
            ->form(fn (Transfer $record): array => [
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
            ->action(function (Transfer $record, array $data): void {
                $record->update(['payment_status' => $data['status']]);

                Notification::make()->title('Status updated')->success()->send();
            });
    }
}
