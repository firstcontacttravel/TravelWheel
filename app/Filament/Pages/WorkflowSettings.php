<?php

namespace App\Filament\Pages;

use App\Models\ActivityLog;
use App\Models\WorkItem;
use App\Workflow\Deadlines;
use App\Workflow\WorkflowRegistry;
use App\Workflow\WorkItemService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Throwable;

/**
 * Deadlines, set by the CEO: how many hours each step may take before the
 * owner is warned, when it counts as missed, and how long after missing it
 * the CEO hears. Saved without a deploy; open work picks up a change at
 * once.
 */
class WorkflowSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|\UnitEnum|null $navigationGroup = 'Team';

    protected static ?int $navigationSort = 40;

    protected static ?string $navigationLabel = 'Deadlines';

    protected static ?string $title = 'Deadlines';

    protected static ?string $slug = 'workflow-settings';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $settings = app(Deadlines::class)->settings();

        $this->form->fill([
            'warn_percent' => $settings['warn_percent'],
            'ceo_after_hours' => round($settings['ceo_after_minutes'] / 60, 2),
            'steps' => collect($settings['steps'])
                ->map(fn (array $stages) => collect($stages)->map(fn (?int $minutes) => $minutes ? round($minutes / 60, 2) : null)->all())
                ->all(),
        ]);
    }

    public function getSubheading(): ?string
    {
        return 'How long each step may take. Leave a step empty for no deadline. Where a booking has its own hard deadline (an airline\'s ticketing limit, a pickup time) the earlier of the two applies.';
    }

    public function form(Schema $schema): Schema
    {
        $sections = [
            Section::make('Alerts')
                ->columns(2)
                ->schema([
                    TextInput::make('warn_percent')
                        ->label('Warn the owner when this much of the time is gone')
                        ->numeric()->integer()->minValue(50)->maxValue(95)->suffix('%')->required(),
                    TextInput::make('ceo_after_hours')
                        ->label('Tell the CEO when still overdue after')
                        ->numeric()->minValue(0.5)->maxValue(168)->step(0.5)->suffix('hours')->required(),
                ]),
        ];

        foreach (app(WorkflowRegistry::class)->all() as $service => $workflow) {
            $fields = collect($workflow->stages())
                ->filter(fn (array $stage) => $stage['state'] === WorkItem::STATE_OPEN)
                ->map(fn (array $stage, string $key) => TextInput::make("steps.{$service}.{$key}")
                    ->label($stage['label'])
                    ->numeric()->minValue(0)->maxValue(720)->step(0.5)
                    ->suffix('hours')
                    ->placeholder('No deadline'))
                ->values()
                ->all();

            if ($fields !== []) {
                $sections[] = Section::make($workflow->label())->columns(3)->collapsible()->schema($fields);
            }
        }

        return $schema->components($sections)->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Save deadlines')->submit('save'),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $data = $this->form->getState();
        $deadlines = app(Deadlines::class);
        $before = $deadlines->settings();

        $settings = [
            'warn_percent' => (int) $data['warn_percent'],
            'ceo_after_minutes' => (int) round(((float) $data['ceo_after_hours']) * 60),
            'steps' => collect($data['steps'] ?? [])
                ->map(fn (array $stages) => collect($stages)->map(fn ($hours) => filled($hours) ? (int) round(((float) $hours) * 60) : null)->all())
                ->all(),
        ];

        $deadlines->save($settings);
        ActivityLog::record('workflow.deadlines_updated', 'Updated deadlines', properties: ['before' => $before, 'after' => $deadlines->settings()]);

        $updated = $this->applyToOpenWork();

        Notification::make()
            ->title('Deadlines saved')
            ->body("Due times recalculated for {$updated} open items.")
            ->success()
            ->send();
    }

    /** Open work takes the new allowances now, counted from when it entered its step. */
    private function applyToOpenWork(): int
    {
        $items = app(WorkItemService::class);
        $count = 0;

        WorkItem::query()->active()->with('subject')->chunkById(200, function ($chunk) use ($items, &$count): void {
            foreach ($chunk as $item) {
                if (! $item->subject) {
                    continue;
                }

                try {
                    $items->sync($item->subject);
                    $count++;
                } catch (Throwable $e) {
                    report($e);
                }
            }
        });

        return $count;
    }
}
