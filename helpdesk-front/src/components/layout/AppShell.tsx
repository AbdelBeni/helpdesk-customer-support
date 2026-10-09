"use client";

import { ReactNode, useState } from "react";
import Sidebar from "./Sidebar";
import Logo from "../../app/ui/logo";
import type { User } from "../../types/index";

interface AppShellProps {
  user: User;
  title: ReactNode;
  description?: ReactNode;
  breadcrumb?: ReactNode;
  actions?: ReactNode;
  width?: string;
  children: ReactNode;
}

export default function AppShell({
  user,
  title,
  description,
  breadcrumb,
  actions,
  width = "max-w-6xl",
  children,
}: AppShellProps) {
  const [menuOpen, setMenuOpen] = useState(false);

  return (
    <div className="min-h-screen bg-zinc-50 text-zinc-900">
      <Sidebar user={user} open={menuOpen} onClose={() => setMenuOpen(false)} />

      <div className="lg:pl-60">
        <div className="sticky top-0 z-20 flex h-14 items-center gap-3 border-b border-zinc-200 bg-white px-4 lg:hidden">
          <button
            type="button"
            onClick={() => setMenuOpen(true)}
            aria-label="Open navigation"
            className="-ml-1.5 rounded-md p-2 text-zinc-600 transition-colors hover:bg-zinc-100"
          >
            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              strokeWidth="1.75"
              strokeLinecap="round"
              className="h-5 w-5"
              aria-hidden="true"
            >
              <path d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>
          <Logo className="h-6 w-6" />
          <span className="text-[15px] font-semibold">HelpDesk</span>
        </div>

        <main className="px-4 py-6 sm:px-8 sm:py-8">
          <div className={`mx-auto ${width}`}>
            {breadcrumb && (
              <nav aria-label="Breadcrumb" className="mb-3">
                {breadcrumb}
              </nav>
            )}

            <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
              <div className="min-w-0">
                <h1 className="text-xl font-semibold tracking-tight text-zinc-900 sm:text-2xl">
                  {title}
                </h1>

                {description && (
                  <div className="mt-1 text-sm text-zinc-600">{description}</div>
                )}
              </div>

              {actions && (
                <div className="flex shrink-0 items-center gap-2">{actions}</div>
              )}
            </div>

            {children}
          </div>
        </main>
      </div>
    </div>
  );
}