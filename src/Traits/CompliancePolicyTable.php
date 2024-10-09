<?php

namespace Fintech\Remit\Traits;

use Fintech\Core\Enums\Auth\RiskProfile;

trait CompliancePolicyTable
{
    public function renderPolicyData(array &$order_data): void
    {
        $policies = [];

        if (! empty($order_data['compliance_data'])) {
            foreach ($order_data['compliance_data'] as $index => $compliance) {
                $policies[$index] = $compliance;
                $policies[$index]['risk'] = RiskProfile::from($compliance['risk']);
                $policies[$index]['priority'] = RiskProfile::from($compliance['priority'] ?? 'green');
            }
        }

        $order_data['compliance_data'] = $policies;
    }
}
