# SonarQube scan: builds the reports SonarQube imports (unit test coverage,
# test results, PHPStan) inside the running `web` container, then runs
# SonarScanner CLI in Docker against the local server from compose.sonar.yaml.
#
#   docker compose up -d                          # app stack (web container)
#   docker compose -f compose.sonar.yaml up -d    # SonarQube at http://localhost:9000
#   powershell -ExecutionPolicy Bypass -File scripts/sonar-scan.ps1
#
# The token comes from SONAR_TOKEN in .env (gitignored) and reaches the
# scanner container through the environment, never on the command line.

$root = Split-Path $PSScriptRoot -Parent
Set-Location $root

# SonarScanner CLI 8.1.0.6389, pinned by digest (the image tags don't follow the CLI version)
$scanner = 'sonarsource/sonar-scanner-cli@sha256:a3f4215076706c95a17a68c19322ee916e40a3acd081a8c1a1e839e0194afa57'

$tokenLine = Get-Content .env | Where-Object { $_ -match '^SONAR_TOKEN=' } | Select-Object -Last 1
if (-not $tokenLine) {
    Write-Error 'SONAR_TOKEN is missing from .env'
    exit 1
}
$env:SONAR_TOKEN = ($tokenLine -replace '^SONAR_TOKEN=', '').Trim().Trim('"')

Write-Host '1/3 Unit tests + coverage (PCOV)'
docker compose exec -T web php -d pcov.enabled=1 vendor/bin/phpunit --testsuite Unit --coverage-clover coverage/clover.xml --log-junit coverage/junit.xml
if ($LASTEXITCODE -ne 0) {
    Write-Error 'Unit tests failed - fix them before scanning.'
    exit 1
}

Write-Host '2/3 PHPStan report'
docker compose exec -T web sh -c 'vendor/bin/phpstan analyse --memory-limit=1G --no-progress --error-format=json > coverage/phpstan.json || true'

Write-Host '3/3 SonarScanner'
docker run --rm --network ordina-sonar_default `
    -e SONAR_HOST_URL=http://sonarqube:9000 -e SONAR_TOKEN `
    -v "${root}:/var/www/html" -w /var/www/html `
    $scanner '-Dsonar.projectBaseDir=/var/www/html'
$scanExit = $LASTEXITCODE

Write-Host ''
if ($scanExit -eq 0) {
    Write-Host 'Scan done, Quality Gate passed: http://localhost:9000/dashboard?id=ordina'
} else {
    Write-Host "Scan finished with exit code $scanExit (Quality Gate failed or scan error): http://localhost:9000/dashboard?id=ordina"
}
exit $scanExit
