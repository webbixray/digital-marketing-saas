<?php

namespace App\Http\Controllers;

use App\Mail\TeamInvitationMail;
use App\Models\Agency;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Notifications\TeamInvitationNotification;
use App\Services\RBAC\EnterpriseRBACService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function __construct(
        private readonly EnterpriseRBACService $rbacService,
    ) {
        $this->middleware(['auth', 'agency']);
    }

    /**
     * Display a listing of teams for the current agency.
     */
    public function index(Request $request): View
    {
        $agencyId = $request->user()->agency_id;
        $teams = Team::byAgency($agencyId)
            ->withCount('users')
            ->with('owner')
            ->orderBy('name')
            ->paginate(12);

        return view('teams.index', compact('teams'));
    }

    /**
     * Show the form for creating a new team.
     */
    public function create(Request $request): View
    {
        $agencyId = $request->user()->agency_id;
        $members = User::where('agency_id', $agencyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('teams.create', compact('members'));
    }

    /**
     * Store a newly created team.
     */
    public function store(Request $request): RedirectResponse
    {
        $agencyId = $request->user()->agency_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'sometimes|boolean',
        ]);

        $team = Team::create([
            'agency_id' => $agencyId,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'owner_id' => $request->user()->id,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('teams.show', $team)
            ->with('success', "Team '{$team->name}' created successfully.");
    }

    /**
     * Display the specified team.
     */
    public function show(Request $request, Team $team): View
    {
        $agencyId = $request->user()->agency_id;

        if ($team->agency_id !== $agencyId) {
            abort(403, 'Unauthorized access to team.');
        }

        $team->load(['owner', 'users' => function ($query) use ($agencyId) {
            $query->where('agency_id', $agencyId);
        }]);

        $invitations = TeamInvitation::where('team_id', $team->id)
            ->with('invitedBy')
            ->orderBy('created_at', 'desc')
            ->get();

        $availableMembers = User::where('agency_id', $agencyId)
            ->whereNotIn('id', $team->users->pluck('id'))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('teams.show', compact('team', 'invitations', 'availableMembers'));
    }

    /**
     * Show the form for editing the specified team.
     */
    public function edit(Request $request, Team $team): View
    {
        $agencyId = $request->user()->agency_id;

        if ($team->agency_id !== $agencyId) {
            abort(403, 'Unauthorized access to team.');
        }

        $team->load('owner');

        return view('teams.edit', compact('team'));
    }

    /**
     * Update the specified team.
     */
    public function update(Request $request, Team $team): RedirectResponse
    {
        $agencyId = $request->user()->agency_id;

        if ($team->agency_id !== $agencyId) {
            abort(403, 'Unauthorized access to team.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'sometimes|boolean',
        ]);

        $team->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? $team->is_active,
        ]);

        return redirect()->route('teams.show', $team)
            ->with('success', "Team '{$team->name}' updated successfully.");
    }

    /**
     * Remove the specified team.
     */
    public function destroy(Request $request, Team $team): RedirectResponse
    {
        $agencyId = $request->user()->agency_id;

        if ($team->agency_id !== $agencyId) {
            abort(403, 'Unauthorized access to team.');
        }

        $name = $team->name;
        $team->delete();

        return redirect()->route('teams.index')
            ->with('success', "Team '{$name}' deleted successfully.");
    }

    /**
     * Send an invitation to join a team.
     */
    public function invite(Request $request, Team $team): RedirectResponse
    {
        $agencyId = $request->user()->agency_id;

        if ($team->agency_id !== $agencyId) {
            abort(403, 'Unauthorized access to team.');
        }

        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'role' => 'required|string|in:admin,member,viewer',
        ]);

        $token = bin2hex(random_bytes(32));

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => $validated['email'],
            'role' => $validated['role'],
            'token' => $token,
            'invited_by' => $request->user()->id,
            'expires_at' => now()->addDays(7),
        ]);

        // Send invitation email
        Mail::to($invitation->email)->send(
            new TeamInvitationMail($team, $request->user(), $token)
        );

        // Notify user if they already exist
        $existingUser = User::where('email', $validated['email'])->first();
        if ($existingUser) {
            $existingUser->notify(new TeamInvitationNotification($team, $request->user(), $token));
        }

        return redirect()->route('teams.show', $team)
            ->with('success', "Invitation sent to {$validated['email']}.");
    }

    /**
     * Accept a team invitation.
     */
    public function acceptInvite(Request $request, string $token): RedirectResponse
    {
        $invitation = TeamInvitation::where('token', $token)
            ->pending()
            ->first();

        if (! $invitation) {
            abort(404, 'Invalid or expired invitation.');
        }

        if ($invitation->isExpired()) {
            abort(410, 'This invitation has expired.');
        }

        $user = $request->user();

        // Ensure user email matches invitation
        if ($user->email !== $invitation->email) {
            abort(403, 'This invitation is not for your email address.');
        }

        // Add user to team
        $invitation->team->users()->save($user);

        // Mark invitation as accepted
        $invitation->update(['accepted_at' => now()]);

        // Assign Spatie role by name
        $role = \Spatie\Permission\Models\Role::where('name', $invitation->role)
            ->where('agency_id', $invitation->team->agency_id)
            ->first();
        if (! $role) {
            // Fallback: create the role for this agency
            $role = $this->rbacService->createCustomRole($invitation->team->agency_id, $invitation->role, []);
        }
        $user->assignRole($role);

        return redirect()->route('teams.show', $invitation->team)
            ->with('success', "You've joined the team '{$invitation->team->name}'.");
    }

    /**
     * Cancel a team invitation.
     */
    public function cancelInvite(Request $request, string $token): RedirectResponse
    {
        $agencyId = $request->user()->agency_id;

        $invitation = TeamInvitation::where('token', $token)
            ->with('team')
            ->first();

        if (! $invitation) {
            abort(404, 'Invitation not found.');
        }

        if ($invitation->team->agency_id !== $agencyId) {
            abort(403, 'Unauthorized access to invitation.');
        }

        $email = $invitation->email;
        $invitation->delete();

        return redirect()->route('teams.show', $invitation->team)
            ->with('success', "Invitation for {$email} has been cancelled.");
    }
}
