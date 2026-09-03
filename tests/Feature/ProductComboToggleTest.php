<?php

use App\Livewire\ProductComponent;
use Livewire\Livewire;

it('adds an editable component row when a product becomes a combo', function () {
    Livewire::test(ProductComponent::class)
        ->call('create')
        ->set('is_combo', true)
        ->assertSet('is_combo', true)
        ->assertSet('components.0.quantity', 1)
        ->call('addComponent')
        ->call('addComponent')
        ->assertSet('components.2.quantity', 1)
        ->call('removeComponent', 1)
        ->call('addComponent')
        ->assertSet('components.2.quantity', 1)
        ->assertSee('Componentes del combo');
});
