<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Organization;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function index(Request $request): Response
    {
        $organization = $request->attributes->get('currentOrganization');

        $clients = $organization
            ? $organization->clients()
                ->withCount('sites')
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'phone'])
            : collect();

        return Inertia::render('clients/index', ['clients' => $clients]);
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        $organization = $request->attributes->get('currentOrganization');

        abort_unless($organization instanceof Organization, 403, 'Create an organization first.');

        $organization->clients()->create($request->validated());

        return redirect()->route('clients.index')
            ->with('success', 'Client created.');
    }

    public function edit(Request $request, Client $client): Response
    {
        $this->authorize('view', $client);

        return Inertia::render('clients/edit', [
            'client' => $client->only(['id', 'name', 'email', 'phone', 'notes']),
        ]);
    }

    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $client->update($request->validated());

        return redirect()->route('clients.index')
            ->with('success', 'Client updated.');
    }

    public function destroy(Request $request, Client $client): RedirectResponse
    {
        $this->authorize('delete', $client);

        $client->delete();

        return redirect()->route('clients.index')
            ->with('success', 'Client deleted.');
    }
}
