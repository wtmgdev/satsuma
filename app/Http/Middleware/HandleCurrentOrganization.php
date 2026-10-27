<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;

class HandleCurrentOrganization
{
    public function handle(Request $request, Closure $next): mixed
    {
        if ($user = $request->user()) {
            $orgId = $request->session()->get('current_org_id');

            $organization = $orgId
                ? $user->organizations()->where('organizations.id', $orgId)->first()
                : null;

            $organization ??= $user->organizations()->orderBy('organizations.id')->first();

            if ($organization) {
                $request->session()->put('current_org_id', $organization->id);
            }

            $request->attributes->set('currentOrganization', $organization);
        }

        return $next($request);
    }
}
