<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BranchContextController extends Controller
{
    public function update(Request $request, Branch $branch): RedirectResponse
    {
        abort_unless(
            $request->user()->branches()->whereKey($branch)->where('is_active', true)->exists(),
            403,
        );

        $request->session()->put([
            'branch_id' => $branch->id,
            'company_id' => $branch->company_id,
        ]);
        setPermissionsTeamId($branch->company_id);
        $request->user()->unsetRelation('roles')->unsetRelation('permissions');

        return back();
    }
}
