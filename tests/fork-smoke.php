<?php
require dirname(__DIR__) . '/vendor/autoload.php';
$app = require dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
Carbon\Carbon::setTestNow(Carbon\Carbon::parse('2026-10-26 12:00:00'));
Illuminate\Support\Carbon::setTestNow(Carbon\Carbon::parse('2026-10-26 12:00:00'));
$checks = 0;
function checkResult(bool $condition, string $name): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException($name);
    }
    $checks++;
    echo "PASS: $name\n";
}
class SyncVerificationAccount extends App\Models\Account
{
    public int $quietSaves = 0;
    public function saveQuietly(array $options = [])
    {
        $this->quietSaves++;
        return true;
    }
}
checkResult(PHP_VERSION === '8.2.35', 'Pinned PHP runtime is 8.2.35');
checkResult(trim(file_get_contents(dirname(__DIR__) . '/VERSION.txt')) === '5.13.50', 'Candidate version is 5.13.50');
checkResult(config('ninja.app_version') === '5.13.50', 'Laravel version is 5.13.50');
Illuminate\Support\Facades\Http::preventStrayRequests();
config(['ninja.environment' => 'selfhost', 'ninja.production' => false]);
$account = new SyncVerificationAccount();
$account->plan = null;
$account->plan_expires = '2020-01-01';
checkResult($account->getPlan() === App\Models\Account::PLAN_WHITE_LABEL, 'Self-host expired account retains white-label plan');
checkResult($account->hasFeature(App\Models\Account::FEATURE_WHITE_LABEL), 'Self-host account has white-label feature');
checkResult($account->isPaid(), 'Self-host account is paid');
$renewal = new App\Services\License\WhiteLabelRenewalService();
foreach ([null, 'invalid-test-key'] as $key) {
    config(['ninja.license_key' => $key]);
    checkResult($renewal->checkAndRenew($account) === true, 'Renewal succeeds with dummy or missing key');
    checkResult($account->plan === App\Models\Account::PLAN_WHITE_LABEL, 'Renewal retains white-label plan');
    checkResult($account->plan_expires === '2076-10-26', 'Renewal grants existing fifty-year expiry');
    checkResult($account->plan_term === App\Models\Account::PLAN_TERM_YEARLY, 'Renewal retains yearly term');
}
checkResult($account->quietSaves === 2, 'Renewal saves quietly once per invocation');
config(['ninja.environment' => 'hosted', 'ninja.production' => true]);
$account->plan = 'pro';
$account->plan_expires = '2020-01-01';
checkResult($account->getPlan() === '', 'Hosted expired plan stays expired');
checkResult(!$account->isPaid(), 'Hosted expired account stays unpaid');
$account->plan_expires = '2027-10-26';
checkResult($account->getPlan() === 'pro', 'Hosted valid pro plan retained');
checkResult($account->isPaid(), 'Hosted valid pro account remains paid');
echo "Focused behavioral checks passed: $checks\n";
