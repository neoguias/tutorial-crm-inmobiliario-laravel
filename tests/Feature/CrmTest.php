<?php

namespace Tests\Feature;

use App\Enums\PropertyStatus;
use App\Enums\VisitOutcome;
use App\Models\Contact;
use App\Models\Demand;
use App\Models\Property;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CrmTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Las páginas usan @vite: sin esto los tests pedirían haber compilado antes los assets.
        $this->withoutVite();
    }

    private function property(array $overrides = []): Property
    {
        return Property::create($overrides + [
            'reference' => 'AT-1', 'title' => 'Piso de prueba', 'operation' => 'sale', 'type' => 'flat',
            'zone' => 'Ruzafa', 'price' => 280000, 'bedrooms' => 3, 'area' => 90,
        ]);
    }

    private function demand(array $overrides = []): Demand
    {
        $contact = Contact::create(['name' => 'Lucía']);

        return $contact->demands()->create($overrides + [
            'operation' => 'sale', 'type' => 'flat', 'zone' => 'Ruzafa', 'max_price' => 300000, 'min_bedrooms' => 2,
        ]);
    }

    public function test_the_crossing_works_both_ways(): void
    {
        $property = $this->property();
        $demand = $this->demand();

        $this->assertTrue(Property::matching($demand)->whereKey($property->id)->exists());
        $this->assertTrue(Demand::matching($property)->whereKey($demand->id)->exists());
    }

    public function test_a_demand_without_type_or_zone_accepts_any(): void
    {
        $property = $this->property(['type' => 'house', 'zone' => 'Patraix']);
        $demand = $this->demand(['type' => null, 'zone' => null]);

        $this->assertTrue(Demand::matching($property)->whereKey($demand->id)->exists());
    }

    public function test_over_budget_short_of_rooms_or_already_sold_does_not_match(): void
    {
        $demand = $this->demand();

        $this->property(['reference' => 'AT-2', 'price' => 310000]);
        $this->property(['reference' => 'AT-3', 'bedrooms' => 1]);
        $this->property(['reference' => 'AT-4', 'status' => PropertyStatus::Closed]);
        $this->property(['reference' => 'AT-5', 'operation' => 'rent', 'price' => 900]);

        $this->assertSame(0, Property::matching($demand)->count());
    }

    public function test_a_new_property_is_validated_and_saved(): void
    {
        Livewire::test('pages::properties')
            ->set('form.reference', 'AT-9')
            ->call('save')
            ->assertHasErrors(['form.title', 'form.zone', 'form.price', 'form.area']);

        Livewire::test('pages::properties')
            ->set('form.reference', 'AT-9')
            ->set('form.title', 'Piso con balcón')
            ->set('form.zone', 'Benimaclet')
            ->set('form.price', 199000)
            ->set('form.area', 70)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('property', Property::where('reference', 'AT-9')->first()));
    }

    public function test_a_visit_cannot_be_scheduled_in_the_past(): void
    {
        $property = $this->property();
        $contact = Contact::create(['name' => 'Marc']);

        Livewire::test('pages::property', ['property' => $property])
            ->set('contact_id', $contact->id)
            ->set('scheduled_at', now()->subDay()->format('Y-m-d\TH:i'))
            ->call('schedule')
            ->assertHasErrors(['scheduled_at']);

        Livewire::test('pages::property', ['property' => $property])
            ->set('contact_id', $contact->id)
            ->set('scheduled_at', now()->addDay()->format('Y-m-d\TH:i'))
            ->call('schedule')
            ->assertHasNoErrors();

        $this->assertSame(1, $property->visits()->count());
    }

    public function test_a_new_client_is_saved_with_what_they_are_looking_for(): void
    {
        Livewire::test('pages::contacts')
            ->set('form.name', 'Ana Ruiz')
            ->set('form.operation', 'rent')
            ->set('form.max_price', 1000)
            ->call('save')
            ->assertHasNoErrors();

        $contact = Contact::where('name', 'Ana Ruiz')->first();
        $this->assertSame(1000, $contact->demands->first()->max_price);
        $this->assertNull($contact->demands->first()->zone);
    }

    public function test_the_agenda_records_how_a_visit_went(): void
    {
        $visit = Visit::create([
            'property_id' => $this->property()->id,
            'contact_id' => Contact::create(['name' => 'Jorge'])->id,
            'scheduled_at' => now()->addHour(),
        ]);

        Livewire::test('pages::visits')
            ->assertSee('Piso de prueba')
            ->call('mark', $visit->id, 'interested');

        $this->assertSame(VisitOutcome::Interested, $visit->fresh()->outcome);
    }

    public function test_the_pages_load(): void
    {
        $this->seed();

        foreach (['/inmuebles', '/clientes', '/visitas', '/inmuebles/'.Property::first()->id] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
