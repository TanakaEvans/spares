<?php

namespace App\Services;

use App\Exceptions\ApprovalDeniedException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Server-side supervisor overrides (2.1 rule 3): the approver's own
 * credentials are checked, their authority is the permission key for the
 * action (a registered route name, grantable per role), and the approval is
 * written to the audit stream. Nothing a browser sends is trusted on its own.
 */
class SupervisorApprovalService
{
    public function __construct(private readonly AuditLogService $audit)
    {
    }

    /**
     * @throws ApprovalDeniedException
     */
    public function verify(?string $username, ?string $password, string $permission, ?int $requestedBy = null): User
    {
        $username = trim((string) $username);
        if ($username === '' || (string) $password === '') {
            throw new ApprovalDeniedException('A supervisor must enter their username and password to approve this.', $permission);
        }

        $approver = User::where('username', $username)->orWhere('email', $username)->first();

        if (! $approver || ! Hash::check((string) $password, $approver->password)) {
            Log::warning('approval.bad_credentials', ['username' => $username, 'permission' => $permission, 'requested_by' => $requestedBy]);
            throw new ApprovalDeniedException('Supervisor credentials were not recognised.', $permission);
        }

        if ($approver->status !== 'active' || $approver->locked_at) {
            throw new ApprovalDeniedException('That supervisor account is inactive or locked.', $permission);
        }

        if (! $approver->hasRouteAccess($permission)) {
            Log::warning('approval.no_authority', ['approver_id' => $approver->id, 'permission' => $permission]);
            throw new ApprovalDeniedException("{$approver->name} does not have authority to approve this.", $permission);
        }

        return $approver;
    }

    /** Verify and audit in one call. */
    public function approve(?string $username, ?string $password, string $permission, string $action, array $details = [], ?int $requestedBy = null): User
    {
        $approver = $this->verify($username, $password, $permission, $requestedBy);

        $this->audit->record($action, null, null, $details + ['approved_by' => $approver->id, 'approver' => $approver->name],
            "Approved by {$approver->name}", $requestedBy);

        return $approver;
    }
}
