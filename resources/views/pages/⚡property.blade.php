<?php

use App\Enums\PropertyStatus;
use App\Models\Contact;
use App\Models\Demand;
use App\Models\Property;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * La ficha de un inmueble: sus datos, los clientes cuya demanda encaja con él y las visitas,
 * con un formulario para programar la siguiente.
 */
new class extends Component
{
    public Property $property;

    public string $status = '';

    public ?int $contact_id = null;

    public string $scheduled_at = '';

    public function mount(): void
    {
        $this->status = $this->property->status->value;
    }

    public function render()
    {
        return $this->view()->title($this->property->title);
    }

    #[Computed]
    public function interested()
    {
        return Demand::matching($this->property)->with('contact')->get();
    }

    #[Computed]
    public function contacts()
    {
        return Contact::orderBy('name')->get();
    }

    /** Los días que el inmueble ya tiene visitas, para marcarlos en el calendario. */
    #[Computed]
    public function busyDays(): array
    {
        return $this->property->visits()->with('contact')
            ->where('scheduled_at', '>=', today())
            ->orderBy('scheduled_at')
            ->get()
            ->groupBy(fn ($visit) => $visit->scheduled_at->toDateString())
            ->map(fn ($visits) => $visits->map(fn ($visit) => $visit->scheduled_at->format('H:i').' '.$visit->contact->name)->all())
            ->all();
    }

    public function updatedStatus(): void
    {
        $this->validate(['status' => [Rule::enum(PropertyStatus::class)]]);

        $this->property->update(['status' => $this->status]);

        $this->notify('Estado cambiado a '.mb_strtolower($this->property->status->label()).'.', 'info');
    }

    public function propose(int $contactId): void
    {
        $this->contact_id = $contactId;
    }

    public function schedule(): void
    {
        $data = $this->validate([
            'contact_id' => ['required', Rule::exists('contacts', 'id')],
            'scheduled_at' => ['required', 'date', 'after:now'],
        ]);

        $visit = $this->property->visits()->create($data);

        $this->reset('contact_id', 'scheduled_at');
        $this->notify('Visita con '.$visit->contact->name.' el '.$visit->scheduled_at->isoFormat('dddd D [de] MMMM [a las] HH:mm').'.');
    }
};
?>

<div class="grid gap-8 lg:grid-cols-3">
    <section class="lg:col-span-2">
        <a href="{{ route('properties') }}" wire:navigate class="text-sm text-slate-500 hover:text-teal-700">← Inmuebles</a>
        <h1 class="mt-2 text-2xl font-semibold">{{ $property->title }}</h1>
        <p class="mt-1 text-slate-600">
            <span class="font-mono text-xs">{{ $property->reference }}</span> ·
            {{ $property->operation->label() }} · {{ $property->type->label() }} en {{ $property->zone }} ·
            @if ($property->bedrooms) {{ $property->bedrooms }} dorm. · @endif {{ $property->area }} m² ·
            <strong>{{ $property->priceLabel() }}</strong>
        </p>

        <label class="mt-4 inline-flex items-center gap-2 text-sm">Estado
            <select wire:model.live="status" class="rounded border border-slate-300 px-2 py-1.5 text-sm">
                @foreach (PropertyStatus::cases() as $case)
                    <option value="{{ $case->value }}">{{ $case->label() }}</option>
                @endforeach
            </select>
        </label>

        <h2 class="mt-8 mb-3 text-lg font-semibold">Clientes que buscan algo así</h2>
        <ul class="divide-y divide-slate-100 rounded-lg border border-slate-200 bg-white">
            @forelse ($this->interested as $demand)
                <li wire:key="demand-{{ $demand->id }}" class="flex items-center gap-4 px-4 py-3 text-sm">
                    <div class="mr-auto">
                        <div class="font-medium">{{ $demand->contact->name }}</div>
                        <div class="text-xs text-slate-500">
                            Hasta {{ number_format($demand->max_price, 0, ',', '.') }} € · {{ $demand->zone ?? 'cualquier zona' }}
                        </div>
                    </div>
                    <button wire:click="propose({{ $demand->contact_id }})" class="rounded border border-teal-700 px-3 py-1 text-teal-700 hover:bg-teal-50">
                        Programar visita
                    </button>
                </li>
            @empty
                <li class="px-4 py-3 text-sm text-slate-500">
                    {{ $property->status === PropertyStatus::Available ? 'Ningún cliente busca algo así todavía.' : 'Solo se cruzan los inmuebles disponibles.' }}
                </li>
            @endforelse
        </ul>
    </section>

    <aside>
        <form wire:submit="schedule" class="rounded-lg border border-slate-200 bg-white p-5">
            <h2 class="mb-4 text-lg font-semibold">Programar visita</h2>
            <label class="block text-sm">Cliente
                <select wire:model="contact_id" class="mt-1 w-full rounded border border-slate-300 px-2 py-1.5">
                    <option value="">Elige uno</option>
                    @foreach ($this->contacts as $contact)
                        <option value="{{ $contact->id }}">{{ $contact->name }}</option>
                    @endforeach
                </select>
                @error('contact_id') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </label>
            <label for="scheduled-at" class="mt-3 block text-sm">Día y hora</label>
            <x-wirepicker id="scheduled-at" wire:model="scheduled_at" time :step="30" default-time="10:00"
                :min="today()->toDateString()" :marked="$this->busyDays" trigger-class="mt-1 rounded border border-slate-300 px-2 py-1.5 text-sm" />
            @error('scheduled_at') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            <button class="mt-4 w-full rounded bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Guardar visita</button>
        </form>

        <h2 class="mt-8 mb-3 text-lg font-semibold">Visitas</h2>
        <ul class="space-y-2 text-sm">
            @forelse ($property->visits()->with('contact')->orderBy('scheduled_at')->get() as $visit)
                <li wire:key="visit-{{ $visit->id }}" class="rounded border border-slate-200 bg-white px-3 py-2">
                    {{ $visit->scheduled_at->isoFormat('ddd D MMM, HH:mm') }} · {{ $visit->contact->name }}
                    <span class="text-xs text-slate-500">({{ $visit->outcome->label() }})</span>
                </li>
            @empty
                <li class="text-slate-500">Sin visitas todavía.</li>
            @endforelse
        </ul>
    </aside>
</div>
