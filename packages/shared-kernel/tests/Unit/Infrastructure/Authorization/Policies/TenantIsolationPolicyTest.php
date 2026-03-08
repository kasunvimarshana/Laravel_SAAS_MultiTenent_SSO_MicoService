<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Tests\Unit\Infrastructure\Authorization\Policies;

use PHPUnit\Framework\TestCase;
use Saas\SharedKernel\Infrastructure\Authorization\Policies\TenantIsolationPolicy;

final class TenantIsolationPolicyTest extends TestCase
{
    private TenantIsolationPolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new TenantIsolationPolicy();
    }

    public function test_grants_access_when_tenant_ids_match(): void
    {
        $subject  = ['id' => 'user-1', 'tenant_id' => 'tenant-a', 'roles' => ['member']];
        $resource = ['tenant_id' => 'tenant-a', 'type' => 'product'];

        $this->assertTrue($this->policy->evaluate($subject, $resource, []));
    }

    public function test_denies_access_when_tenant_ids_differ(): void
    {
        $subject  = ['id' => 'user-1', 'tenant_id' => 'tenant-a', 'roles' => ['admin']];
        $resource = ['tenant_id' => 'tenant-b', 'type' => 'product'];

        $this->assertFalse($this->policy->evaluate($subject, $resource, []));
    }

    public function test_super_admin_bypasses_tenant_isolation(): void
    {
        $subject  = ['id' => 'user-1', 'tenant_id' => 'tenant-a', 'roles' => ['super-admin']];
        $resource = ['tenant_id' => 'tenant-b', 'type' => 'product'];

        $this->assertTrue($this->policy->evaluate($subject, $resource, []));
    }

    public function test_denies_when_subject_tenant_id_missing(): void
    {
        $subject  = ['id' => 'user-1', 'roles' => ['admin']];
        $resource = ['tenant_id' => 'tenant-a'];

        $this->assertFalse($this->policy->evaluate($subject, $resource, []));
    }

    public function test_policy_name(): void
    {
        $this->assertSame('tenant.isolation', $this->policy->getName());
    }
}
