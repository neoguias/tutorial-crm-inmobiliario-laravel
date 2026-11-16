<?php

use App\Enums\Operation;
use App\Enums\PropertyType;
use App\Livewire\Forms\PropertyForm;
use App\Models\Demand;
use App\Models\Property;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * La cartera de la agencia: los inmuebles con sus filtros, el alta de uno nuevo y, en cada
 * fila, cuántos clientes buscan justo algo así.
 */
new #[Title('Inmuebles')] class extends Component
{
    #[Url]
    public string $operation = '';

    #[Url]
    public string $zone = '';

    public bool $creating = false;

    public PropertyForm $form;

    #[Computed]
    public function properties()
    {
        return Property::query()
            ->when($this->operation, fn ($q) => $q->where('operation', $this->operation))
            ->when($this->zone, fn ($q) => $q->where('zone', $this->zone))
            ->orderBy('reference')
            ->get();
    }

    public function interested(Property $property): int
    {
        return Demand::matching($property)->count();
    }

    public function save(): void
    {
        $property = $this->form->store();

        $this->redirectRoute('property', $property, navigate: true);
    }
};
?>

<div>
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <h1 class="mr-auto text-2xl font-semibold">Inmuebles</h1>

        <select wire:model.live="operation" class="rounded border border-slate-300 px-2 py-1.5 text-sm">
            <option value="">Venta y alquiler</option>
            @foreach (Operation::cases() as $case)
                <option value="{{ $case->value }}">{{ $case->label() }}</option>
            @endforeach
        </select>

        <select wire:model.live="zone" class="rounded border border-slate-300 px-2 py-1.5 text-sm">
            <option value="">Todas las zonas</option>
            @foreach (config('crm.zones') as $name)
                <option>{{ $name }}</option>
            @endforeach
        </select>

        <button wire:click="$toggle('creating')" class="rounded bg-teal-700 px-3 py-2 text-sm font-medium text-white hover:bg-teal-800">
            Nuevo inmueble
        </button>
    </div>

    @if ($creating)
        <form wire:submit="save" class="mb-8 grid gap-4 rounded-lg border border-slate-200 bg-white p-5 sm:grid-cols-4">
            <label class="text-sm">Referencia
                <input wire:model="form.reference" class="mt-1 w-full rounded border border-slate-300 px-2 py-1.5">
                @error('form.reference') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </label>
            <label class="text-sm sm:col-span-3">Título
                <input wire:model="form.title" class="mt-1 w-full rounded border border-slate-300 px-2 py-1.5">
                @error('form.title') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </label>
            <label class="text-sm">Operación
                <select wire:model="form.operation" class="mt-1 w-full rounded border border-slate-300 px-2 py-1.5">
                    @foreach (Operation::cases() as $case)
                        <option value="{{ $case->value }}">{{ $case->label() }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm">Tipo
                <select wire:model="form.type" class="mt-1 w-full rounded border border-slate-300 px-2 py-1.5">
                    @foreach (PropertyType::cases() as $case)
                        <option value="{{ $case->value }}">{{ $case->label() }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm">Zona
                <select wire:model="form.zone" class="mt-1 w-full rounded border border-slate-300 px-2 py-1.5">
                    <option value="">Elige una</option>
                    @foreach (config('crm.zones') as $name)
                        <option>{{ $name }}</option>
                    @endforeach
                </select>
                @error('form.zone') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </label>
            <label class="text-sm">Precio (€)
                <input type="number" wire:model="form.price" class="mt-1 w-full rounded border border-slate-300 px-2 py-1.5">
                @error('form.price') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </label>
            <label class="text-sm">Dormitorios
                <input type="number" wire:model="form.bedrooms" class="mt-1 w-full rounded border border-slate-300 px-2 py-1.5">
                @error('form.bedrooms') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </label>
            <label class="text-sm">Superficie (m²)
                <input type="number" wire:model="form.area" class="mt-1 w-full rounded border border-slate-300 px-2 py-1.5">
                @error('form.area') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </label>
            <div class="flex items-end sm:col-span-2">
                <button class="rounded bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Guardar inmueble</button>
            </div>
        </form>
    @endif

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-100 text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Ref.</th>
                    <th class="px-4 py-3">Inmueble</th>
                    <th class="px-4 py-3">Zona</th>
                    <th class="px-4 py-3">Precio</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3">Interesados</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->properties as $property)
                    <tr wire:key="property-{{ $property->id }}" class="border-t border-slate-100">
                        <td class="px-4 py-3 font-mono text-xs">{{ $property->reference }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('property', $property) }}" wire:navigate class="font-medium text-teal-700 hover:underline">{{ $property->title }}</a>
                            <div class="text-xs text-slate-500">{{ $property->type->label() }} · @if ($property->bedrooms) {{ $property->bedrooms }} dorm. · @endif {{ $property->area }} m²</div>
                        </td>
                        <td class="px-4 py-3">{{ $property->zone }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $property->priceLabel() }}</td>
                        <td class="px-4 py-3">{{ $property->status->label() }}</td>
                        <td class="px-4 py-3">{{ $this->interested($property) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">No hay inmuebles con esos filtros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
