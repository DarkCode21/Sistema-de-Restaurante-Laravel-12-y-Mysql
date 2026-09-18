<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;

test('a user can select only an assigned active branch', function () {
    $company = Company::firstOrFail();
    $firstBranch = $company->branches()->firstOrFail();
    $secondBranch = Branch::create([
        'company_id' => $company->id,
        'name' => 'Sede Centro',
        'code' => 'CENTRO',
        'is_active' => true,
    ]);
    $otherBranch = Branch::create([
        'company_id' => $company->id,
        'name' => 'Sede Norte',
        'code' => 'NORTE',
        'is_active' => true,
    ]);
    $user = User::factory()->create();
    $user->branches()->attach([$firstBranch->id, $secondBranch->id]);

    $this->actingAs($user)
        ->put(route('branches.current', $secondBranch))
        ->assertRedirect();

    expect(session('branch_id'))->toBe($secondBranch->id);
    expect(session('company_id'))->toBe($company->id);

    $this->put(route('branches.current', $otherBranch))
        ->assertForbidden();
});
