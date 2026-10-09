"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import api from "../../lib/api";
import { getCurrentUser } from "../../lib/auth";
import Sidebar from "../../components/layout/Sidebar";
import ProtectedRoute from "../../components/auth/ProtectedRoute";
import type { Ticket, User } from "../../types/index";

const statuses = [
  "All",
  "Open",
  "In Progress",
  "Waiting for Customer",
  "Resolved",
  "Closed",
];

const priorities = ["All", "Low", "Medium", "High", "Urgent"];

const columns = "md:grid-cols-[110px_minmax(0,1fr)_170px_110px_100px]";

const fieldClass =
  "w-full rounded-md border border-zinc-700 bg-zinc-950 [color-scheme:dark] px-3 py-2 text-sm text-zinc-100 shadow-sm outline-none transition placeholder:text-zinc-500 focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20";

export default function TicketsPage() {
  const [user, setUser] = useState<User | null>(null);
  const [tickets, setTickets] = useState<Ticket[]>([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState("");
  const [status, setStatus] = useState("All");
  const [priority, setPriority] = useState("All");

  useEffect(() => {
    async function loadTickets() {
      try {
        setLoading(true);

        const [userResponse, ticketsResponse] = await Promise.all([
          getCurrentUser(),
          api.get<{ data: Ticket[] }>("/tickets"),
        ]);

        setUser(userResponse);
        setTickets(ticketsResponse.data.data);
      } catch {
        setTickets([]);
      } finally {
        setLoading(false);
      }
    }

    loadTickets();
  }, []);

  const filteredTickets = tickets.filter((ticket) => {
    const matchesSearch =
      ticket.subject.toLowerCase().includes(search.toLowerCase()) ||
      ticket.ticket_number.toLowerCase().includes(search.toLowerCase());

    const matchesStatus = status === "All" || ticket.status.name === status;

    const matchesPriority =
      priority === "All" || ticket.priority.name === priority;

    return matchesSearch && matchesStatus && matchesPriority;
  });

  return (
    <ProtectedRoute>
      {!user ? (
        <main className="flex min-h-screen items-center justify-center bg-zinc-950">
          <p className="text-sm text-zinc-400">Loading…</p>
        </main>
      ) : (
        <main className="min-h-screen bg-zinc-950 text-zinc-100">
          <div className="flex min-h-screen">
            <Sidebar user={user} />

            <section className="min-w-0 flex-1 pt-14 lg:ml-64 lg:pt-0">
              <header className="flex min-h-16 items-center justify-between gap-4 border-b border-zinc-800 bg-zinc-950 px-4 py-3 sm:px-6">
                <div>
                  <h1 className="text-base font-semibold text-zinc-100">
                    Tickets
                  </h1>

                  <p className="text-xs text-zinc-400">
                    Search, filter and follow every support request.
                  </p>
                </div>

                <Link
                  href="/tickets/create"
                  className="inline-flex shrink-0 items-center justify-center rounded-md bg-teal-500 px-3.5 py-2 text-sm font-medium text-zinc-950 shadow-sm transition-colors hover:bg-teal-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-400"
                >
                  New Ticket
                </Link>
              </header>

              <div className="mx-auto max-w-6xl px-4 py-6 sm:px-6">
                <div className="mb-4 grid gap-3 sm:grid-cols-[minmax(0,1fr)_190px_160px]">
                  <div className="relative">
                    <svg
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      strokeWidth="1.75"
                      strokeLinecap="round"
                      className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400"
                      aria-hidden="true"
                    >
                      <circle cx="11" cy="11" r="7" />
                      <path d="M20 20l-3.5-3.5" />
                    </svg>

                    <input
                      type="search"
                      value={search}
                      onChange={(event) => setSearch(event.target.value)}
                      placeholder="Search by number or subject"
                      aria-label="Search tickets"
                      className={`${fieldClass} pl-9`}
                    />
                  </div>

                  <select
                    value={status}
                    onChange={(event) => setStatus(event.target.value)}
                    aria-label="Filter by status"
                    className={fieldClass}
                  >
                    {statuses.map((item) => (
                      <option key={item} value={item}>
                        {item === "All" ? "All statuses" : item}
                      </option>
                    ))}
                  </select>

                  <select
                    value={priority}
                    onChange={(event) => setPriority(event.target.value)}
                    aria-label="Filter by priority"
                    className={fieldClass}
                  >
                    {priorities.map((item) => (
                      <option key={item} value={item}>
                        {item === "All" ? "All priorities" : item}
                      </option>
                    ))}
                  </select>
                </div>

                <div className="overflow-hidden rounded-lg border border-zinc-800 bg-zinc-900">
                  {loading ? (
                    <div className="px-6 py-12 text-center text-sm text-zinc-400">
                      Loading tickets…
                    </div>
                  ) : filteredTickets.length === 0 ? (
                    <div className="px-6 py-12 text-center">
                      <p className="text-sm font-medium text-zinc-100">
                        No tickets found
                      </p>

                      <Link
                        href="/tickets/create"
                        className="mt-2 inline-block text-sm font-medium text-teal-400 hover:text-teal-300"
                      >
                        Create a new ticket
                      </Link>
                    </div>
                  ) : (
                    <>
                      <div
                        className={`hidden border-b border-zinc-800 bg-zinc-950/60 px-5 py-2.5 text-xs font-medium text-zinc-400 md:grid md:gap-4 ${columns}`}
                      >
                        <span>Ticket</span>
                        <span>Subject</span>
                        <span>Status</span>
                        <span>Priority</span>
                        <span>Created</span>
                      </div>

                      <ul className="divide-y divide-zinc-800">
                        {filteredTickets.map((ticket) => (
                          <li key={ticket.id}>
                            <Link
                              href={`/tickets/${ticket.id}`}
                              className="block px-5 py-3.5 transition-colors hover:bg-zinc-800/50 focus-visible:bg-zinc-800/50 focus-visible:outline-none"
                            >
                              <div
                                className={`grid gap-x-4 gap-y-2 md:items-center ${columns}`}
                              >
                                <span className="text-xs font-medium tabular-nums text-zinc-400 md:text-sm">
                                  {ticket.ticket_number}
                                </span>

                                <div className="min-w-0">
                                  <p className="truncate text-sm font-medium text-zinc-100">
                                    {ticket.subject}
                                  </p>

                                  <p className="mt-0.5 truncate text-xs text-zinc-400">
                                    {ticket.category.name}
                                  </p>
                                </div>

                                <div className="flex flex-wrap items-center gap-x-4 gap-y-1 md:contents">
                                  <StatusBadge status={ticket.status.name} />

                                  <PriorityBadge
                                    priority={ticket.priority.name}
                                  />

                                  <span className="text-xs text-zinc-400">
                                    {new Date(
                                      ticket.created_at
                                    ).toLocaleDateString()}
                                  </span>
                                </div>
                              </div>
                            </Link>
                          </li>
                        ))}
                      </ul>
                    </>
                  )}
                </div>

                {!loading && filteredTickets.length > 0 && (
                  <p className="mt-3 text-sm text-zinc-400">
                    Showing {filteredTickets.length} of {tickets.length} ticket
                    {tickets.length !== 1 ? "s" : ""}
                  </p>
                )}
              </div>
            </section>
          </div>
        </main>
      )}
    </ProtectedRoute>
  );
}

function StatusBadge({ status }: { status: string }) {
  const dots: Record<string, string> = {
    Open: "bg-sky-500",
    "In Progress": "bg-amber-500",
    "Waiting for Customer": "bg-violet-500",
    Resolved: "bg-emerald-500",
    Closed: "bg-zinc-500",
  };

  return (
    <span className="inline-flex items-center gap-1.5 whitespace-nowrap text-xs font-medium text-zinc-300">
      <span
        className={`h-2 w-2 rounded-full ${dots[status] ?? "bg-zinc-500"}`}
        aria-hidden="true"
      />
      {status}
    </span>
  );
}

function PriorityBadge({ priority }: { priority: string }) {
  const levels: Record<string, { bars: number; bar: string; text: string }> = {
    Low: { bars: 1, bar: "bg-zinc-500", text: "text-zinc-400" },
    Medium: { bars: 2, bar: "bg-zinc-300", text: "text-zinc-300" },
    High: { bars: 3, bar: "bg-orange-500", text: "text-orange-400" },
    Urgent: { bars: 3, bar: "bg-red-500", text: "text-red-400" },
  };

  const level = levels[priority] ?? levels.Low;

  return (
    <span
      className={`inline-flex items-center gap-1.5 whitespace-nowrap text-xs font-medium ${level.text}`}
    >
      <span className="flex h-3 items-end gap-0.5" aria-hidden="true">
        {[1, 2, 3].map((n) => (
          <span
            key={n}
            className={`w-1 rounded-sm ${
              n === 1 ? "h-1" : n === 2 ? "h-2" : "h-3"
            } ${n <= level.bars ? level.bar : "bg-zinc-700"}`}
          />
        ))}
      </span>
      {priority}
    </span>
  );
}