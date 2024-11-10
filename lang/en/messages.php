<?php

return [
    'assign_vendor' => [
        'not_found' => ':slug vendor is not available on the configurations.',
        'not_assigned' => 'This order have not been assigned to any vendor.',
        'already_assigned' => 'This order has already assigned by another user.',
        'assigned_user_failed' => 'Unable to assign this order to requested user.',
        'failed' => 'Unable to assign order to requested vendor [:slug].',
        'release_failed' => 'There\'s been an error. while :model with ID::id failed to release.',
        'success' => 'Successfully assigned order to :slug vendor.',
    ],
    'verification' => [
        'wallet_provider_not_found' => 'No wallet verification provider found for (:wallet).',
        'provider_missing_method' => ':provider verification provider doesn\'t implement required interface.',
    ],
];
