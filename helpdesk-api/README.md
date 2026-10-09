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
composer install

2. Configure environment variables

Copy .env.example to .env.

Configure the database connection in .env:

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


Use values appropriate for your local environment. Do not commit real credentials.

3. Generate the application key
php artisan key:generate

4. Run database migrations

Create the database first, then execute:

php artisan migrate


If the project uses database seeders, inspect the available seeders and run the appropriate ones when needed:

php artisan db:seed


Only run seeders that are part of your project and appropriate for your environment.

5. Configure email delivery

The application uses SMTP to deliver email verification codes. Configure your mail settings using your own SMTP provider:

MAIL_MAILER=smtp
MAIL_HOST=your_smtp_host
MAIL_PORT=587
MAIL_USERNAME=your_smtp_username
MAIL_PASSWORD=your_smtp_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your_verified_sender@example.com
MAIL_FROM_NAME="${APP_NAME}"


Use the values required by your provider. Never commit SMTP credentials.

6. Start the API
php artisan serve


The default local API base URL is:

http://localhost:8000/api

Authentication

Protected endpoints use Laravel Sanctum bearer tokens.

After successful login or email verification, the API returns an authentication token. Send it with protected requests:

Authorization: Bearer YOUR_TOKEN
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

```

Authentication
Method	Endpoint	Purpose
POST	/auth/register	Register an account
POST	/auth/login	Authenticate a user
GET	/auth/me	Retrieve the authenticated user
POST	/auth/logout	Log out
POST	/auth/verify-email	Verify an email code
POST	/auth/resend-verification	Request another verification code
GET	/auth/email-verification-status	Check verification status
Tickets
Method	Endpoint	Purpose
GET	/tickets	List accessible tickets
POST	/tickets	Create a ticket
GET	/tickets/{ticket}	Retrieve ticket details
PUT/PATCH	/tickets/{ticket}	Update a ticket
PATCH	/tickets/{ticket}/status	Change ticket status
GET	/tickets/unassigned	List unassigned tickets
POST	/tickets/{ticket}/assign	Assign a ticket
POST	/tickets/{ticket}/claim	Claim an available ticket
Messages and Attachments
Method	Endpoint	Purpose
GET	/tickets/{ticket}/messages	List ticket messages
POST	/tickets/{ticket}/messages	Send a message
GET	/tickets/{ticket}/attachments	List attachments
POST	/tickets/{ticket}/attachments	Upload an attachment
DELETE	/tickets/{ticket}/attachments/{attachment}	Delete an attachment
Internal Notes and Activity
Method	Endpoint	Purpose
GET	/tickets/{ticket}/activity-logs	Retrieve ticket activity
GET	/tickets/{ticket}/internal-notes	List internal notes
POST	/tickets/{ticket}/internal-notes	Add an internal note
Notifications
Method	Endpoint	Purpose
GET	/notifications	List notifications
GET	/notifications/unread-count	Retrieve unread count
PATCH	/notifications/{notification}/read	Mark a notification as read
Dashboard and Reference Data
Method	Endpoint	Purpose
GET	/dashboard/stats	Retrieve dashboard statistics
GET	/dashboard/ticket-trends	Retrieve ticket trends
GET	/dashboard/agent-performance	Retrieve agent metrics
GET	/dashboard/response-performance	Retrieve response metrics
GET	/dashboard/customer-stats	Retrieve customer statistics
GET	/agents	List agents (admin access)
GET	/categories	List ticket categories
GET	/priorities	List ticket priorities

```

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
