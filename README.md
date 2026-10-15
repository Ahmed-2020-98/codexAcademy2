# Codex Academy — LMS & Mini-CRM

Arabic-first (RTL) learning platform and back office built from the requirements document
*«متطلبات تطوير منصة الأكاديمية التعليمية - LMS & Mini-CRM»*, in the Codex visual identity
(pixel-square `< >` mark, navy `#0A5A9E` and sky `#33B8F6`).

**Stack:** Laravel 12 · Filament 4 (admin at `/admin`) · Livewire 3 · Tailwind 4 · MySQL · spatie/laravel-permission

## Setup

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
# configure DB_* (MySQL), then:
php artisan migrate --seed
npm run build
php artisan serve            # or link the folder with Herd
php artisan schedule:work    # SLA checks + WhatsApp reminders
php artisan queue:work       # bulk task-template assignment
```

## Demo accounts (password: `password`)

| Role | Email | Lands on |
|---|---|---|
| Super Admin | admin@codex.academy | `/admin` |
| Sales / Care | sales@codex.academy, care@codex.academy | `/admin` (CRM) |
| Instructor | instructor1…6@codex.academy | `/admin` (own groups & profile) |
| Student | student@codex.academy | `/learn` |

## Requirements coverage

| # | Requirement | Where |
|---|---|---|
| 1 | Recorded courses: purchase, Bunny Stream, dynamic watermark, quizzes, auto certificates | `/courses`, `/learn/checkout/{course}`, `LessonPlayer`, `TakeQuiz`, `ProgressService`, `/certificates/{serial}` |
| 1 | Live / offline: Zoom/Meet links, WhatsApp groups, halls, attendance & grades | Admin → Groups (sessions, attendance, grades), `/learn/courses/{course}` |
| 2 | Google Forms → Pending lead | `POST /api/webhooks/google-forms` — see `docs/google-forms.md` |
| 2 | 24H SLA timer + overdue list + admin alerts | `crm:check-sla`, Leads → «المتأخرون» tab, dashboard widget |
| 2 | Pipeline (6 stages) | Admin → «لوحة مراحل الطلاب» (drag & drop) and Leads |
| 2 | Drop-out reasons + filtered reports | Required on drop-out, admin approval, «تحليل أسباب الانسحاب» + CSV export |
| 3 | Personalized task board + templates for 500+ students | `/learn/tasks`, Admin → Task templates («إسناد لدفعة»), Group → tasks |
| 4 | Revenue (online + manual bookings), expenses log, instructor payables, P&L | Admin → Finance; P&L page with print/PDF and CSV |
| 5 | Instructor KPIs, attendance log, up-skilling tasks | Admin → Instructors → profile view |
| 6 | Instructor profile, intro video, demo lesson | `/instructors/{slug}`, editable in admin |
| 7 | Multi-department / languages, unlimited courses, WhatsApp triggers, referral program | Settings → Departments; `WhatsApp` service + scheduler; referral codes at checkout |
| 8 | RBAC matrix | `app/Policies`, resource query scoping, `tests/Feature/RbacMatrixTest.php` |

## Integrations (all optional; the platform runs locally without keys)

| Service | Env | Default |
|---|---|---|
| Bunny Stream (signed embeds) | `BUNNY_STREAM_LIBRARY_ID`, `BUNNY_STREAM_TOKEN_KEY` | branded placeholder player |
| WhatsApp | `WHATSAPP_DRIVER=log\|meta\|ultramsg` + credentials, `WHATSAPP_ADMIN_NUMBERS` | `log` (written to the log + «سجل رسائل واتساب») |
| Payments | `PAYMENT_DRIVER` — implement `App\Contracts\PaymentGateway` for Paymob / Moyasar | `fake` (approves charges) |
| Google Forms | `GOOGLE_FORMS_SECRET` | — |

## Scheduler

| Command | Frequency | Purpose |
|---|---|---|
| `crm:check-sla` | every 5 min | flag overdue leads, notify admins |
| `academy:session-reminders` | every 10 min | WhatsApp 24h / 1h before sessions |
| `academy:weekly-task-reminders` | Sunday 09:00 | weekly «مهامي» summary |

## Tests

```bash
php artisan test
```

They cover the webhook and SLA, the pipeline rules, the P&L formula, payables, certificates,
referral checkout, assigning templates to 500 students, the RBAC matrix, and a smoke render of every page.

## Vercel preview deployment

The repository root is the Laravel app. The `preview` branch deploys to the Vercel
project `codex-academy` in team `ahmedgamal1`; automatic deployment of `main` is
disabled. This is a demo environment with seeded accounts and simulated payments.

For a manual preview, use `vercel deploy --target preview --yes --no-wait --scope ahmedgamal1`.
An explicit target is required because recent CLI versions can otherwise assign
a project's first deployment to production.

- Runtime: Node.js 22, `vercel-php@0.8.0` (PHP 8.4), Frankfurt (`fra1`).
- Build: `npm ci` then `npm run build`. `dist/` contains only public static assets;
  other requests, including Livewire, are handled by `api/index.php`.
- Filament's published assets are tracked because the PHP runtime's Composer
  build hook cannot publish browser assets. After upgrading Filament, run
  `php artisan filament:assets` and commit the updated assets with `composer.lock`.
- Supabase PostgreSQL uses the private `academy` schema through a dedicated
  `academy_app` database role. Laravel retains authentication and authorization;
  no browser access to the Supabase Data API is needed.
- Runtime connections use the shared transaction pooler on port `6543`, with
  `DB_SSLMODE=require` and `DB_PGSQL_DISABLE_PREPARES=true`. Use session pooling
  on port `5432` for migrations and one-time seeding.
- Set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` to the preview branch
  alias, `SESSION_DRIVER=database`, `SESSION_SECURE_COOKIE=true`,
  `CACHE_STORE=database`, `QUEUE_CONNECTION=sync`, and `LOG_CHANNEL=stderr`.
  Keep `PAYMENT_DRIVER=fake`, `WHATSAPP_DRIVER=log`, and `MAIL_MAILER=log`.
- Store `APP_KEY`, database credentials, `CRON_SECRET`, and `GOOGLE_FORMS_SECRET`
  in Vercel's Preview environment. The local `.env` is never uploaded.
- Supabase Cron invokes `POST /api/internal/scheduler` every minute with
  `Authorization: Bearer <CRON_SECRET>`. The secret belongs in Supabase Vault.
  A distributed database lock prevents overlapping scheduler invocations.
  Responses are `204` on success, `401` for missing/invalid credentials, `409`
  when another invocation holds the lock, and `500` if scheduling fails.

Run `php artisan migrate --force` when deploying schema changes. Seed a new,
empty demo database once with `php artisan db:seed --force`; do not reseed it
on subsequent deploys. Runtime cache and compiled view files are stored in
`/tmp/codex-academy`, while durable application state lives in PostgreSQL.
