#!/usr/bin/env bash
set -euo pipefail

cd "$(git rev-parse --show-toplevel)"
image='invoiceninja/invoiceninja@sha256:6923d749ea56a07b992cdee11063789c2fd98cf2aee2538e26d69537a0cdf73e'
git ls-files -z app bootstrap config routes resources lang database Modules VERSION.txt artisan composer.json composer.lock tests/fork-smoke.php |
  tar --null -T - -cf - |
  docker run --rm -i --network none --entrypoint sh \
    -e APP_ENV=testing \
    -e APP_DEBUG=false \
    -e APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= \
    -e APP_URL=http://localhost \
    -e DB_CONNECTION=sqlite \
    -e DB_DATABASE=:memory: \
    -e CACHE_DRIVER=array \
    -e CACHE_STORE=array \
    -e QUEUE_CONNECTION=sync \
    -e SESSION_DRIVER=array \
    -e MAIL_MAILER=array \
    -e LOG_CHANNEL=stderr \
    -e NINJA_ENVIRONMENT=selfhost \
    "$image" -ec '
      tar xf -
      rm -f bootstrap/cache/*.php
      for path in app/Models/Account.php app/Http/Controllers/LicenseController.php app/Jobs/Util/VersionCheck.php app/Services/License/WhiteLabelRenewalService.php tests/fork-smoke.php; do
        php -l "$path"
      done
      composer check-platform-reqs --no-dev
      php tests/fork-smoke.php
    '
