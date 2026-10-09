HelpDesk API

<p align="center"> <strong>The backend REST API for the HelpDesk Customer Support Platform, built with Laravel.</strong> </p>

<p align="center"> <img src="https://img.shields.io/badge/Laravel-FF2D20?style=flat-square&logo=laravel&logoColor=white" alt="Laravel" /> <img src="https://img.shields.io/badge/PHP-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP" /> <img src="https://img.shields.io/badge/Sanctum-Authentication-64748B?style=flat-square" alt="Laravel Sanctum" /> <img src="https://img.shields.io/badge/REST-API-2563EB?style=flat-square" alt="REST API" /> </p>

<p align="center"> Authentication · Ticket Management · Notifications · Analytics </p>

It handles authentication, authorization, ticket workflows, customer-agent communication, notifications, file attachments, and support analytics.

Features
RESTful API architecture
Laravel Sanctum token authentication
Role-based authorization: Customer, Agent, Admin
Registration, login, logout, and current-user endpoints
Email verification using six-digit OTP codes
OTP expiration, attempt limits, and resend throttling
Ticket creation, retrieval, updating, assignment, and claiming
Ticket status transitions and reopening
Customer-agent messages
Internal notes and activity logs
Attachment management and upload validation
Notifications and unread counts
Dashboard statistics and performance analytics
Request validation, authorization checks, and rate limiting
Technology Stack
Laravel
PHP
Composer
SQL database
Laravel Sanctum
SMTP email delivery
Requirements

Install the PHP version and extensions required by the project's composer.json, along with:

Composer
A supported SQL database
Node.js is not required to run the API itself
An SMTP account for email verification in a configured environment
Installation
1. Install dependencies
```composer install```

2. Configure environment variables

```Copy .env.example to .env.```

Configure the database connection in .env:
```
APP_NAME=HelpDesk
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=helpdesk
DB_USERNAME=your_database_username
DB_PASSWORD=your_database_password
```

Use values appropriate for your local environment. Do not commit real credentials.

3. Generate the application key
php artisan key:generate

4. Run database migrations

Create the database first, then execute:

```php artisan migrate```


If the project uses database seeders, inspect the available seeders and run the appropriate ones when needed:
```
php artisan db:seed
```

Only run seeders that are part of your project and appropriate for your environment.

5. Configure email delivery

The application uses SMTP to deliver email verification codes. Configure your mail settings using your own SMTP provider:
```
MAIL_MAILER=smtp
MAIL_HOST=your_smtp_host
MAIL_PORT=587
MAIL_USERNAME=your_smtp_username
MAIL_PASSWORD=your_smtp_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your_verified_sender@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

Use the values required by your provider. Never commit SMTP credentials.

6. Start the API
```php artisan serve```


The default local API base URL is:

```http://localhost:8000/api```

Authentication

Protected endpoints use Laravel Sanctum bearer tokens.

After successful login or email verification, the API returns an authentication token. Send it with protected requests:

Authorization: ```Bearer YOUR_TOKEN```
Accept: application/json

Email Verification Flow
Register a customer account.
Generate and email a six-digit verification code.
Submit the email address and code.
Validate the code, expiration, and attempt limit.
Mark the email as verified and issue an authentication token.

The verification flow also supports resending codes subject to rate limiting.

API Endpoints

All paths below are relative to /api.

Authentication
<table>
  <thead>
    <tr>
      <th>Method</th>
      <th>Endpoint</th>
      <th>Purpose</th>
    </tr>
  </thead>
  <tbody>
    <tr><td>POST</td><td><code>/auth/register</code></td><td>Register an account</td></tr>
    <tr><td>POST</td><td><code>/auth/login</code></td><td>Authenticate a user</td></tr>
    <tr><td>GET</td><td><code>/auth/me</code></td><td>Retrieve the authenticated user</td></tr>
    <tr><td>POST</td><td><code>/auth/logout</code></td><td>Log out</td></tr>
    <tr><td>POST</td><td><code>/auth/verify-email</code></td><td>Verify an email code</td></tr>
    <tr><td>POST</td><td><code>/auth/resend-verification</code></td><td>Request another verification code</td></tr>
    <tr><td>GET</td><td><code>/auth/email-verification-status</code></td><td>Check verification status</td></tr>
  </tbody>
</table>
Tickets
<table>
  <thead>
    <tr>
      <th>Method</th>
      <th>Endpoint</th>
      <th>Purpose</th>
    </tr>
  </thead>
  <tbody>
    <tr><td>GET</td><td><code>/tickets</code></td><td>List accessible tickets</td></tr>
    <tr><td>POST</td><td><code>/tickets</code></td><td>Create a ticket</td></tr>
    <tr><td>GET</td><td><code>/tickets/{ticket}</code></td><td>Retrieve ticket details</td></tr>
    <tr><td>PUT/PATCH</td><td><code>/tickets/{ticket}</code></td><td>Update a ticket</td></tr>
    <tr><td>PATCH</td><td><code>/tickets/{ticket}/status</code></td><td>Change ticket status</td></tr>
    <tr><td>GET</td><td><code>/tickets/unassigned</code></td><td>List unassigned tickets</td></tr>
    <tr><td>POST</td><td><code>/tickets/{ticket}/assign</code></td><td>Assign a ticket</td></tr>
    <tr><td>POST</td><td><code>/tickets/{ticket}/claim</code></td><td>Claim an available ticket</td></tr>
  </tbody>
</table>
Messages and Attachments
<table>
  <thead>
    <tr>
      <th>Method</th>
      <th>Endpoint</th>
      <th>Purpose</th>
    </tr>
  </thead>
  <tbody>
    <tr><td>GET</td><td><code>/tickets/{ticket}/messages</code></td><td>List ticket messages</td></tr>
    <tr><td>POST</td><td><code>/tickets/{ticket}/messages</code></td><td>Send a message</td></tr>
    <tr><td>GET</td><td><code>/tickets/{ticket}/attachments</code></td><td>List attachments</td></tr>
    <tr><td>POST</td><td><code>/tickets/{ticket}/attachments</code></td><td>Upload an attachment</td></tr>
    <tr><td>DELETE</td><td><code>/tickets/{ticket}/attachments/{attachment}</code></td><td>Delete an attachment</td></tr>
  </tbody>
</table>
Internal Notes and Activity
<table>
  <thead>
    <tr>
      <th>Method</th>
      <th>Endpoint</th>
      <th>Purpose</th>
    </tr>
  </thead>
  <tbody>
    <tr><td>GET</td><td><code>/tickets/{ticket}/activity-logs</code></td><td>Retrieve ticket activity</td></tr>
    <tr><td>GET</td><td><code>/tickets/{ticket}/internal-notes</code></td><td>List internal notes</td></tr>
    <tr><td>POST</td><td><code>/tickets/{ticket}/internal-notes</code></td><td>Add an internal note</td></tr>
  </tbody>
</table>
Notifications
<table>
  <thead>
    <tr>
      <th>Method</th>
      <th>Endpoint</th>
      <th>Purpose</th>
    </tr>
  </thead>
  <tbody>
    <tr><td>GET</td><td><code>/notifications</code></td><td>List notifications</td></tr>
    <tr><td>GET</td><td><code>/notifications/unread-count</code></td><td>Retrieve unread count</td></tr>
    <tr><td>PATCH</td><td><code>/notifications/{notification}/read</code></td><td>Mark a notification as read</td></tr>
  </tbody>
</table>
Dashboard and Reference Data
<table>
  <thead>
    <tr>
      <th>Method</th>
      <th>Endpoint</th>
      <th>Purpose</th>
    </tr>
  </thead>
  <tbody>
    <tr><td>GET</td><td><code>/dashboard/stats</code></td><td>Retrieve dashboard statistics</td></tr>
    <tr><td>GET</td><td><code>/dashboard/ticket-trends</code></td><td>Retrieve ticket trends</td></tr>
    <tr><td>GET</td><td><code>/dashboard/agent-performance</code></td><td>Retrieve agent metrics</td></tr>
    <tr><td>GET</td><td><code>/dashboard/response-performance</code></td><td>Retrieve response metrics</td></tr>
    <tr><td>GET</td><td><code>/dashboard/customer-stats</code></td><td>Retrieve customer statistics</td></tr>
    <tr><td>GET</td><td><code>/agents</code></td><td>List agents (admin access)</td></tr>
    <tr><td>GET</td><td><code>/categories</code></td><td>List ticket categories</td></tr>
    <tr><td>GET</td><td><code>/priorities</code></td><td>List ticket priorities</td></tr>
  </tbody>
</table>

Access requirements depend on the configured route middleware and authorization policies. Consult routes/api.php for the authoritative route definitions.

Testing

Run the automated backend test suite:

php artisan test


Recorded test results:

Metric	Recorded result
Tests	74
Assertions	302
Failures	0

The suite covers authentication, permissions, ticket workflows, assignment, messages, attachments, activity logs, notifications, request security, dashboards, and performance metrics.

Security
Authentication through Sanctum
Server-side authorization and role checks
Request validation
Rate limiting on sensitive endpoints
OTP hashing and expiration
Upload validation and access control
Protection against unauthorized ticket access and mass assignment
Environment and Deployment

For production deployments:

Set APP_ENV=production and APP_DEBUG=false.
Use HTTPS.
Configure production database and SMTP credentials securely.
Restrict CORS to trusted origins.
Run migrations through your deployment process.
Never expose private tokens or secrets in source control.
Related Project

The Next.js frontend is available in the helpdesk-front directory.

For the complete application overview, setup instructions, and screenshots, see the root README.
