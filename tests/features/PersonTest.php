<?php

use Tests\TestCase;
use LaravelEnso\Core\App\Models\User;
use LaravelEnso\People\App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LaravelEnso\Forms\App\TestTraits\EditForm;
use LaravelEnso\Forms\App\TestTraits\CreateForm;
use LaravelEnso\Forms\App\TestTraits\DestroyForm;
use LaravelEnso\Tables\App\Traits\Tests\Datatable;

class PersonTest extends TestCase
{
    use Datatable, DestroyForm, CreateForm, EditForm, RefreshDatabase;

    private $permissionGroup = 'administration.people';
    private $testModel;

    protected function setUp(): void
    {
        parent::setUp();

        // $this->withoutExceptionHandling();

        $this->seed()
            ->actingAs(User::first());

        $this->testModel = factory(Person::class)
            ->make();
    }

    /** @test */
    public function can_view_create_form()
    {
        $this->get(route($this->permissionGroup.'.create', false))
            ->assertStatus(200)
            ->assertJsonStructure(['form']);
    }

    /** @test */
    public function can_store_person()
    {
        $response = $this->post(
            route('administration.people.store', [], false),
            $this->testModel->toArray() +
            ['companies' => []]
        );

        $person = Person::whereEmail($this->testModel->email)
            ->first();

        $response->assertStatus(200)
            ->assertJsonStructure(['message'])
            ->assertJsonFragment([
                'redirect' => 'administration.people.edit',
                'param' => ['person' => $person->id],
            ]);
    }

    /** @test */
    public function can_update_person()
    {
        $this->testModel->save();

        $this->testModel->name = 'updated';

        $this->patch(
            route('administration.people.update', $this->testModel->id, false),
            $this->testModel->toArray() +
            ['companies' => []]
        )->assertStatus(200)
        ->assertJsonStructure(['message']);

        $this->assertEquals('updated', $this->testModel->fresh()->name);
    }

    /** @test */
    public function get_option_list()
    {
        $this->testModel->save();

        $this->get(route('administration.people.options', [
            'query' => $this->testModel->name,
            'limit' => 10,
        ], false))
        ->assertStatus(200)
        ->assertJsonFragment(['name' => $this->testModel->name]);
    }
}
