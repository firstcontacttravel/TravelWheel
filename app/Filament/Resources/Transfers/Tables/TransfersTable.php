<?php

namespace App\Filament\Resources\Transfers\Tables;

use App\Filament\Workflow\FulfilmentActions;
use App\Filament\Workflow\WorkItemTable;
use App\Models\Driver;
use App\Models\DriverAssignment;
use App\Models\FleetCar;
use App\Models\Transfer;
use App\Services\DriverAssignmentNotifier;
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
use Illuminate\Database\Eloquent\Builder;

class TransfersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('workItem.owner'))
            ->columns([
                WorkItemTable::ownerColumn(),
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
                WorkItemTable::filter(),
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
                FulfilmentActions::rowGroup(),
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
                TextInput::make('driver_email')
                    ->label('New driver email')
                    ->email()
                    ->helperText('So the driver receives the trip details by email.')
                    ->visible(fn ($get) => blank($get('driver_id')))
                    ->maxLength(255),
                // Older bookings name the car the customer picked; newer ones only
                // name the category (e.g. "Standard Mini Van"), so start empty then
                TextInput::make('car_model')
                    ->default(FleetCar::where('car_name', $record->vehicle_name)->exists() ? $record->vehicle_name : null)
                    ->required()->maxLength(100),
                TextInput::make('car_colour')->required()->maxLength(60),
                TextInput::make('plate_number')->required()->maxLength(20),
                FileUpload::make('car_images')->multiple()->image()->disk('fleet_assets')->directory('fleet/assigned'),
                Toggle::make('send_email')->label('Email the customer, the driver and reservations now')->default(true),
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
                            'email' => filled($data['driver_email'] ?? null) ? trim($data['driver_email']) : null,
                            'is_active' => true,
                        ]);

                    if (blank($driver->email) && filled($data['driver_email'] ?? null)) {
                        $driver->update(['email' => trim($data['driver_email'])]);
                    }
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

                if (! ($data['send_email'] ?? false)) {
                    Notification::make()->title('Driver assigned (no emails sent)')->success()->send();

                    return;
                }

                $result = app(DriverAssignmentNotifier::class)->notify($assignment);

                Notification::make()
                    ->title('Driver assigned')
                    ->body(collect([
                        $result['sent'] ? 'Emailed: '.implode(', ', $result['sent']).'.' : null,
                        $result['problems'] ? 'Not sent: '.implode('; ', $result['problems']).'.' : null,
                    ])->filter()->implode(' '))
                    ->{$result['problems'] ? 'warning' : 'success'}()
                    ->persistent(filled($result['problems']))
                    ->send();
            });
    }
}
