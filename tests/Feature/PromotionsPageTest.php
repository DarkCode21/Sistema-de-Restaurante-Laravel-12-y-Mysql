<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;

it('mounts the promotions Livewire component', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('productos.ver'));

    $this->actingAs($user)
        ->get(route('promotions.index'))
        ->assertOk()
        ->assertSeeLivewire('promotion-component');
});
