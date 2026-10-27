<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddMemberRequest;
use App\Http\Requests\StoreOrganizationRequest;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function store(StoreOrganizationRequest $request): RedirectResponse
    {
        $organization = Organization::createWithOwner(
            $request->user(),
            $request->validated('name'),
        );

        $request->session()->put('current_org_id', $organization->id);

        return redirect()->route('dashboard')
            ->with('success', "Organization {$organization->name} created.");
    }

    public function switch(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'integer'],
        ]);

        $organization = $request->user()->organizations()
            ->where('organizations.id', $validated['organization_id'])
            ->firstOrFail();

        $request->session()->put('current_org_id', $organization->id);

        return back();
    }

    public function edit(Request $request): Response|RedirectResponse
    {
        $organization = $request->attributes->get('currentOrganization');

        if (! $organization instanceof Organization) {
            return redirect()->route('dashboard')
                ->with('success', 'Create an organization first.');
        }

        $this->authorize('view', $organization);

        $organization->load('members:id,name,email');

        return Inertia::render('settings/organization', [
            'organization' => $organization->only(['id', 'name', 'slug']),
            'members' => $organization->members->map(fn (User $member) => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => $member->pivot->role,
            ]),
            'canManageMembers' => $request->user()->can('manageMembers', $organization),
            'canUpdate' => $request->user()->can('update', $organization),
        ]);
    }

    public function update(UpdateOrganizationRequest $request): RedirectResponse
    {
        $organization = $request->attributes->get('currentOrganization');
        $organization->update($request->validated());

        return back()->with('success', 'Organization updated.');
    }

    public function addMember(AddMemberRequest $request): RedirectResponse
    {
        $organization = $request->attributes->get('currentOrganization');

        $member = User::where('email', $request->validated('email'))->first();

        if (! $member) {
            return back()->withErrors([
                'email' => 'No user with that email exists yet.',
            ]);
        }

        if ($organization->roleFor($member) === 'owner') {
            return back()->withErrors([
                'email' => 'That user is already the owner of this organization.',
            ]);
        }

        $organization->members()->syncWithoutDetaching([
            $member->id => ['role' => $request->validated('role')],
        ]);

        return back()->with('success', 'Member added.');
    }

    public function updateMember(Request $request, User $member): RedirectResponse
    {
        $organization = $request->attributes->get('currentOrganization');

        $this->authorize('manageMembers', $organization);

        $validated = $request->validate([
            'role' => ['required', Rule::in(['admin', 'member'])],
        ]);

        if ($organization->roleFor($member) === 'owner') {
            abort(403, 'The organization owner role cannot be changed.');
        }

        $organization->members()->updateExistingPivot($member->id, [
            'role' => $validated['role'],
        ]);

        return back()->with('success', 'Member role updated.');
    }

    public function removeMember(Request $request, User $member): RedirectResponse
    {
        $organization = $request->attributes->get('currentOrganization');

        $this->authorize('manageMembers', $organization);

        if ($organization->roleFor($member) === 'owner') {
            abort(403, 'The organization owner cannot be removed.');
        }

        $organization->members()->detach($member->id);

        return back()->with('success', 'Member removed.');
    }
}
