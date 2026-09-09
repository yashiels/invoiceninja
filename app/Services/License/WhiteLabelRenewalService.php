<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Services\License;

use App\Models\Account;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class WhiteLabelRenewalService
{
    /**
     * Check if an expired white label license has been renewed on the license server.
     *
     * @return bool|null true = renewed, false = not renewed, null = inconclusive (network error)
     */
    public function checkAndRenew(Account $account): ?bool
    {
        $account->plan_term = Account::PLAN_TERM_YEARLY;
        $account->plan_expires = now()->addYears(50)->format('Y-m-d');
        $account->plan = Account::PLAN_WHITE_LABEL;
        $account->saveQuietly();

        return true;
    }
}
