<?php

use App\Livewire\OrdersChefComponent;
use Livewire\Livewire;

it('refreshes the kitchen monitor automatically while it is open', function () {
    Livewire::test(OrdersChefComponent::class)
        ->assertSeeHtml('wire:poll.5s.keep-alive="refreshForAlerts"');
});
