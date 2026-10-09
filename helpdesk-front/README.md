HelpDesk Frontend

<p align="center"> <strong>The frontend application for the HelpDesk Customer Support Platform, built with Next.js, React, TypeScript, and Tailwind CSS.</strong> </p>

<p align="center"> <img src="https://img.shields.io/badge/Next.js-000000?style=flat-square&logo=nextdotjs&logoColor=white" alt="Next.js" /> <img src="https://img.shields.io/badge/React-61DAFB?style=flat-square&logo=react&logoColor=black" alt="React" /> <img src="https://img.shields.io/badge/TypeScript-3178C6?style=flat-square&logo=typescript&logoColor=white" alt="TypeScript" /> <img src="https://img.shields.io/badge/Tailwind_CSS-06B6D4?style=flat-square&logo=tailwindcss&logoColor=white" alt="Tailwind CSS" /> <img src="https://img.shields.io/badge/Axios-5A29E4?style=flat-square" alt="Axios" /> </p>

It provides the user interface for customers, support agents, and administrators to manage support requests, communicate through tickets, receive notifications, and monitor support activity.

## Features
Responsive SaaS dashboard
Login and registration
Email verification with six-digit OTP
Protected and public route handling
API integration using Axios
Ticket listing and ticket creation
Ticket details and conversation interface
Notification center
Role-aware navigation
Loading, success, and error states
TypeScript interfaces for application data
Tailwind CSS styling
Technology Stack
Next.js App Router
React
TypeScript
Tailwind CSS
Axios
## Requirements
Node.js compatible with the project's Next.js version
npm
The HelpDesk Laravel API running locally or at a configured server URL

## Installation

### 1. Install dependencies

From the frontend directory:

```bash
npm install
```

### 2. Configure the API URL

Create a `.env.local` file in the frontend root:

```env
NEXT_PUBLIC_API_URL=http://localhost:8000/api
```

Replace the URL with your deployed API URL when running outside local development.

This variable is exposed to the browser because it uses the `NEXT_PUBLIC_` prefix. It must contain only the API base URL, never secrets or private credentials.

### 3. Start the development server

```bash
npm run dev
```

Open:

```text
http://localhost:3000
```

### 4. Build for production

```bash
npm run build
```

Start the production server after building:

```bash
npm run start
```

## Application Routes

The application includes the following primary routes:

| Route | Purpose |
|---|---|
| `/login` | User login |
| `/register` | Account registration |
| `/verify-email` | Email verification |
| `/dashboard` | Dashboard and statistics |
| `/tickets` | Ticket listing |
| `/tickets/create` | Create a support ticket |
| `/notifications` | Notification center |

Ticket detail routes are also part of the application. Check `src/app` for the exact dynamic route structure.

## Authentication Flow

1. The user registers through the registration page.
2. The backend sends a verification code to the user's email.
3. The user enters the code on the verification page.
4. The frontend submits the code to the Laravel API.
5. On successful verification, the authentication token and user data are stored in local storage.
6. Protected pages can then retrieve the authenticated user through the API.

The frontend also provides public and protected route components to manage access and redirects.

## API Integration

The API client is located in:

```text
src/lib/api.ts
```

Authentication functions are located in:

```text
src/lib/auth.ts
```

The frontend communicates with the Laravel API using Axios. Authentication tokens are attached to API requests by the shared API client.

The shared TypeScript definitions are maintained in:

```text
src/types/index.ts
```

Centralized types help maintain consistency between components, authentication functions, tickets, and dashboard data.

## Project Structure

```text
helpdesk-front/
├── public/
├── src/
│   ├── app/
│   │   ├── dashboard/
│   │   ├── login/
│   │   ├── register/
│   │   ├── verify-email/
│   │   ├── tickets/
│   │   └── notifications/
│   ├── components/
│   │   ├── auth/
│   │   └── layout/
│   ├── lib/
│   │   ├── api.ts
│   │   └── auth.ts
│   └── types/
│       └── index.ts
├── .env.local
├── package.json
└── README.md
```

This is a high-level overview; consult the actual source tree for additional components and routes.

## Development Guidelines

- Use TypeScript for application logic and shared data contracts.
- Reuse existing components for consistent UI.
- Keep API communication centralized.
- Keep authentication and authorization enforcement on the backend.
- Provide clear loading and error states.
- Ensure responsive layouts and accessible interactions.
- Never hardcode secrets or replace live API responses with mock data in production code.

## Troubleshooting

### API connection errors

Verify that:

- The Laravel API is running.
- `NEXT_PUBLIC_API_URL` points to the correct API base URL.
- The backend permits requests from the frontend origin through CORS.
- The frontend environment configuration is correct.

Restart the development server after changing `.env.local`.

### Authentication problems

Verify that the API token is valid and that the backend authentication endpoints are reachable. Check the browser console and network panel for failed requests.

### Production build errors

Run:

```bash
npm run build
```

Resolve TypeScript and build errors before deployment.

## Related Project

The Laravel backend is available in the [`helpdesk-api`](../helpdesk-api/) directory.

For the complete project overview, backend test results, architecture, and screenshots, see the [root README](../README.md).
