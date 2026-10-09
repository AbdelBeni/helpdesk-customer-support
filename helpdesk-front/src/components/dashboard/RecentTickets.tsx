"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import api from "../../lib/api";
import type { Ticket } from "../../types/index";

export default function RecentTickets() {
  const [tickets, setTickets] = useState<Ticket[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    api
      .get<{ data: Ticket[] }>("/tickets", {
        params: {
          per_page: 5,
          page: 1,
        },
      })
      .then((response) => {
        setTickets(response.data.data);
      })
      .catch(() => {
        setTickets([]);
      })
      .finally(() => {
        setLoading(false);
      });
  }, []);

  return (
    <section className="mt-6 rounded-lg border border-zinc-800 bg-zinc-900">
      <div className="flex items-center justify-between border-b border-zinc-800 px-5 py-3.5">
        <h2 className="text-sm font-semibold text-zinc-100">Recent tickets</h2>

        <Link
          href="/tickets"
          className="text-sm font-medium text-teal-400 transition-colors hover:text-teal-300"
        >
          View all
        </Link>
      </div>

      {loading ? (
        <div className="px-5 py-10 text-center text-sm text-zinc-400">
          Loading tickets…
        </div>
      ) : tickets.length === 0 ? (
        <div className="px-5 py-10 text-center">
          <p className="text-sm font-medium text-zinc-100">No tickets found</p>

          <Link
            href="/tickets"
            className="mt-2 inline-block text-sm font-medium text-teal-400 hover:text-teal-300"
          >
            Go to tickets
          </Link>
        </div>
      ) : (
        <ul className="divide-y divide-zinc-800">
          {tickets.map((ticket) => (
            <li key={ticket.id}>
              <Link
                href={`/tickets/${ticket.id}`}
                className="block px-5 py-3.5 transition-colors hover:bg-zinc-800/50 focus-visible:bg-zinc-800/50 focus-visible:outline-none"
              >
                <div className="flex items-center justify-between gap-4">
                  <p className="min-w-0 truncate text-sm font-medium text-zinc-100">
                    {ticket.subject}
                  </p>

                  <StatusBadge status={ticket.status.name} />
                </div>

                <div className="mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-zinc-400">
                  <span className="font-medium tabular-nums text-zinc-400">
                    {ticket.ticket_number}
                  </span>

                  <span>{ticket.category.name}</span>

                  <PriorityBadge priority={ticket.priority.name} />

                  <span>{new Date(ticket.created_at).toLocaleDateString()}</span>
                </div>
              </Link>
            </li>
          ))}
        </ul>
      )}
    </section>
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
    <span className="inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap text-xs font-medium text-zinc-300">
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
      className={`inline-flex items-center gap-1.5 whitespace-nowrap font-medium ${level.text}`}
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