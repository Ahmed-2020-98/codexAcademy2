# Google Forms → CRM

Each Google Forms submission lands in the CRM as a **Pending** lead and starts the 24-hour SLA timer.

## 1. Server

Set the shared secret in `.env`:

```
GOOGLE_FORMS_SECRET=a-long-random-string
```

Endpoint: `POST https://<your-domain>/api/webhooks/google-forms`. The secret goes in the `X-Codex-Secret` header.

| Field | Required | Notes |
|---|---|---|
| `name` | yes | |
| `phone` | yes | WhatsApp number, international format |
| `email` | no | |
| `course` | no | Course title or slug as written in the platform |
| `department` | no | Department name / English name / slug |
| `study_mode` | no | `recorded`, `live` or `offline` |
| `notes` | no | |
| `response_id` | no | Makes retries idempotent (recommended) |

## 2. Apps Script (Form → Extensions → Apps Script)

```javascript
const ENDPOINT = 'https://<your-domain>/api/webhooks/google-forms';
const SECRET = 'a-long-random-string';

// Map your form's question titles to the API fields.
const MAP = {
  'الاسم بالكامل': 'name',
  'رقم الهاتف / واتساب': 'phone',
  'البريد الإلكتروني': 'email',
  'الكورس': 'course',
  'القسم': 'department',
  'ملاحظات': 'notes',
};

function onFormSubmit(e) {
  const payload = { response_id: e.response.getId() };
  e.response.getItemResponses().forEach((r) => {
    const key = MAP[r.getItem().getTitle()];
    if (key) payload[key] = r.getResponse();
  });

  UrlFetchApp.fetch(ENDPOINT, {
    method: 'post',
    contentType: 'application/json',
    headers: { 'X-Codex-Secret': SECRET },
    payload: JSON.stringify(payload),
    muteHttpExceptions: true,
  });
}
```

Then add a trigger: **Triggers → Add trigger → onFormSubmit → From form → On form submit**.

## 3. What happens next

1. Lead is created as `Pending` with `sla_due_at = now + 24h` (adjust in *Settings → SLA*).
2. A WhatsApp welcome message is sent to the student.
3. `crm:check-sla` (every 5 minutes) moves uncontacted leads to the **Overdue** list and notifies Super Admins and the assigned sales rep (in-app and WhatsApp).
4. Any status change by the sales team stops the SLA clock.
