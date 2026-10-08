# SonarQube scan

Static analysis with **SonarQube Community Build 26.9.0.129388**, the rule
set the assessors use as the standard. Everything runs in Docker: you do not
install SonarQube, Java or the scanner on your machine.

| Piece | Where |
|-------|-------|
| SonarQube server (UI at http://localhost:9000) | `compose.sonar.yaml`, separate from the app's `compose.yaml` |
| Scan settings: sources, exclusions, report paths | `sonar-project.properties` |
| One-command scan | `scripts/sonar-scan.ps1` (Windows PowerShell) |
| Your analysis token | `SONAR_TOKEN` in `.env` (gitignored, never commit it) |

SonarQube needs about 2–3 GB of free RAM on top of the app stack.

## First-time setup (once per machine, ~10 minutes)

**Before you start:** Docker Desktop is running, `.env` exists
(`copy .env.example .env`), and the app stack is up:
```powershell
docker compose up -d --build
```

### 1. Start SonarQube
```powershell
docker compose -f compose.sonar.yaml up -d
```
The first start takes 1–2 minutes. It is ready when
http://localhost:9000 shows the login page, or when this prints `"status":"UP"`:
```powershell
curl.exe http://localhost:9000/api/system/status
```
The yellow banner "Embedded database should be used for evaluation purposes
only" is expected. This local setup keeps SonarQube's data in Docker volumes
and needs no separate database.

### 2. Log in and change the password
Log in with `admin` / `admin`. SonarQube then asks for a new password. That
password is only for your local SonarQube, not for the app.

### 3. Create the project
1. **Projects → Create project → Local project**
2. Project display name: `Ordina`
3. Project key: **`ordina`**. It must match `sonar.projectKey` in
   `sonar-project.properties`, otherwise the scan is refused.
4. Main branch name: leave the default, then **Next**.
5. "Set up new code for project": choose **Follows the instance's default**,
   then **Create project**.

### 4. Create a token and put it in `.env`
1. "How do you want to analyze your project?": **Locally**
2. **Generate a project token** (any name, e.g. `ordina-local`), then copy it.
   It is shown only once.
3. Add it to `.env` (not `.env.example`):
   ```
   SONAR_TOKEN=sqp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
   ```
   You can ignore the scanner commands SonarQube shows next; the script
   below replaces them.

## Run a scan (every time)
From the project root, with the app stack and SonarQube both running:
```powershell
powershell -ExecutionPolicy Bypass -File scripts/sonar-scan.ps1
```
The script does three things:
1. Runs the unit tests with coverage in the `web` container
   (`coverage/clover.xml`, `coverage/junit.xml`). If a test fails, it stops here.
2. Writes the PHPStan report (`coverage/phpstan.json`).
3. Runs SonarScanner in Docker and waits for the Quality Gate result.

A successful run ends with:
```
QUALITY GATE STATUS: PASSED - View details on http://sonarqube:9000/dashboard?id=ordina
EXECUTION SUCCESS
Scan done, Quality Gate passed: http://localhost:9000/dashboard?id=ordina
```
The exit code is `0` when the Quality Gate passes and non-zero when it fails
or the scan breaks. A full run takes about 2 minutes.

### Reading the results
Open http://localhost:9000/dashboard?id=ordina.
- **New Code** covers changes since the last version. On the very first scan
  it is empty, which is why the first Quality Gate always passes.
- **Overall Code** covers the whole project. Assessors look here.
- Coverage is measured on the same scope as `phpunit.xml` (business-logic
  layers). Controllers, views and Mysql repositories are excluded on purpose,
  see `docs/testing/test-scenarios.md`.
- Some rules are known false positives in this codebase: `require` → `require_once`
  and "unused" `$content`/`$title` in `app/Core/View.php`. Mark them
  **False positive** with a reason instead of "fixing" them. `require_once`
  would break view rendering and `MenuRegistry`.

## macOS / Linux (no PowerShell)
Run the same three steps by hand from the project root:
```bash
export SONAR_TOKEN=$(grep '^SONAR_TOKEN=' .env | cut -d= -f2-)
docker compose exec -T web php -d pcov.enabled=1 vendor/bin/phpunit --testsuite Unit \
  --coverage-clover coverage/clover.xml --log-junit coverage/junit.xml
docker compose exec -T web sh -c 'vendor/bin/phpstan analyse --memory-limit=1G --no-progress --error-format=json > coverage/phpstan.json || true'
docker run --rm --network ordina-sonar_default \
  -e SONAR_HOST_URL=http://sonarqube:9000 -e SONAR_TOKEN \
  -v "$PWD:/var/www/html" -w /var/www/html \
  sonarsource/sonar-scanner-cli@sha256:a3f4215076706c95a17a68c19322ee916e40a3acd081a8c1a1e839e0194afa57 \
  -Dsonar.projectBaseDir=/var/www/html
```

## Troubleshooting
| Symptom | Cause | Fix |
|---------|-------|-----|
| `SONAR_TOKEN is missing from .env` | Step 4 not done | Add `SONAR_TOKEN=...` to `.env` |
| `network ordina-sonar_default not found` | SonarQube is not running | `docker compose -f compose.sonar.yaml up -d` |
| `service "web" is not running` | App stack is down | `docker compose up -d` |
| Scanner says not authorized / 401 | Wrong or revoked token, or the project key is not `ordina` | Generate a new token (step 4) and check the key |
| SonarQube container stops, log mentions `vm.max_map_count` | Docker Desktop's VM limit is too low | `wsl -d docker-desktop sysctl -w vm.max_map_count=524288`, then start it again |
| Coverage shows 0% | The reports' paths don't match the scanned files | Always use the script (or the exact commands above). The project must be mounted at `/var/www/html`, the same path as in the `web` container |
| `Unrecognized option: .projectBaseDir` | PowerShell splits `-Dsonar.x=y` at the dot when typed by hand | Quote it: `'-Dsonar.projectBaseDir=/var/www/html'` (the script already does) |
| "running scripts is disabled on this system" | PowerShell execution policy | Use the exact command above (`-ExecutionPolicy Bypass`) |
| Log warning about git / `sonar.text.inclusions` | The scanner can't read git blame in the container | Harmless; results are complete, only "who changed this line" is missing |

## Stopping and resetting
```powershell
docker compose -f compose.sonar.yaml down      # stop; keeps projects, results and your password
docker compose -f compose.sonar.yaml down -v   # wipe SonarQube completely; redo first-time setup
```

## Versions
Both versions are pinned so every developer and the assessors get the same
rules and the same results:
- Server: `sonarqube:26.9.0.129388-community` (`compose.sonar.yaml`)
- Scanner: SonarScanner CLI 8.1.0.6389, pinned by image digest in
  `scripts/sonar-scan.ps1`. The image's tags don't follow the CLI version.
