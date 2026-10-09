import { ReactNode } from "react";
import Logo from "../../app/ui/logo";

const highlights = [
  "Track status and priority across every ticket",
  "Keep the full conversation and attachments in one place",
  "Share internal notes with your team, never with customers",
];

export default function AuthLayout({
  title,
  subtitle,
  footer,
  children,
}: {
  title: string;
  subtitle?: ReactNode;
  footer?: ReactNode;
  children: ReactNode;
}) {
  return (
    <div className="grid min-h-screen lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)]">
      <aside className="hidden flex-col justify-between bg-zinc-900 p-10 lg:flex">
        <div className="flex items-center gap-2.5">
          <Logo className="h-7 w-7" />
          <span className="text-base font-semibold text-white">HelpDesk</span>
        </div>

        <div>
          <p className="max-w-sm text-2xl font-semibold leading-snug tracking-tight text-white">
            Support requests, from first message to resolution.
          </p>

          <ul className="mt-8 space-y-3">
            {highlights.map((item) => (
              <li key={item} className="flex gap-3 text-sm text-zinc-400">
                <svg
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  strokeWidth="2"
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  className="mt-0.5 h-4 w-4 shrink-0 text-teal-400"
                  aria-hidden="true"
                >
                  <path d="M5 12.5l4.5 4.5L19 7.5" />
                </svg>
                {item}
              </li>
            ))}
          </ul>
        </div>

        <span aria-hidden="true" />
      </aside>

      <main className="flex items-center justify-center bg-white px-6 py-12">
        <div className="w-full max-w-sm">
          <div className="mb-8 flex items-center gap-2.5 lg:hidden">
            <Logo className="h-7 w-7" />
            <span className="text-base font-semibold text-zinc-900">HelpDesk</span>
          </div>

          <h1 className="text-2xl font-semibold tracking-tight text-zinc-900">
            {title}
          </h1>

          {subtitle && <p className="mt-1.5 text-sm text-zinc-600">{subtitle}</p>}

          <div className="mt-8">{children}</div>

          {footer && (
            <p className="mt-8 border-t border-zinc-200 pt-6 text-sm text-zinc-600">
              {footer}
            </p>
          )}
        </div>
      </main>
    </div>
  );
}