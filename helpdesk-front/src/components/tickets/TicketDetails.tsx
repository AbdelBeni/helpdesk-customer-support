"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { useParams } from "next/navigation";
import api from "../../lib/api";
import { getCurrentUser } from "../../lib/auth";
import Sidebar from "../layout/Sidebar";
import TicketMessages from "./TicketMessages";
import TicketAttachments from "./TicketAttachments";
import TicketActivityLog from "./TicketActivityLog";
import TicketInternalNotes from "./TicketInternalNotes";
import TicketActions from "./TicketActions";
import type { Ticket, User } from "../../types/index";

export default function TicketDetails() {
  const params = useParams();
  const id = params.id;

  const [ticket, setTicket] = useState<Ticket | null>(null);
  const [currentUser, setCurrentUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    if (!id) {
      return;
    }

    async function loadTicket() {
      try {
        setLoading(true);
        setError("");

        const response = await api.get<{ data: Ticket }>(
          `/tickets/${id}`
        );

        setTicket(response.data.data);
      } catch {
        setError("Unable to load this ticket.");
      } finally {
        setLoading(false);
      }
    }

    loadTicket();
  }, [id]);

  useEffect(() => {
    getCurrentUser()
      .then((user) => {
        setCurrentUser(user);
      })
      .catch(() => {
        setCurrentUser(null);
      });
  }, []);

  if (loading) {
    return (
      <main className="flex min-h-screen items-center justify-center bg-slate-950 text-white">
        <p className="text-sm text-slate-500">
          Loading ticket...
        </p>
      </main>
    );
  }

  if (error || !ticket) {
    return (
      <main className="flex min-h-screen items-center justify-center bg-slate-950 text-white">
        <div className="text-center">
          <p className="text-sm text-red-400">
            {error || "Ticket not found."}
          </p>

          <Link
            href="/tickets"
            className="mt-4 inline-block text-sm text-white hover:underline"
          >
            Back to tickets
          </Link>
        </div>
      </main>
    );
  }

  if (!currentUser) {
    return null;
  }

  return (
    <main className="min-h-screen bg-slate-950 text-white">
      <div className="flex min-h-screen">
        <Sidebar user={currentUser} />

        <section className="ml-0 min-w-0 flex-1 lg:ml-64">
          <header className="border-b border-slate-800 bg-slate-900">
            <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-6">
              <div className="flex items-center gap-4">
                <Link
                  href="/tickets"
                  className="text-sm text-slate-500 transition hover:text-white"
                >
                  Tickets
                </Link>

                <span className="text-slate-700">/</span>

                <span className="text-sm text-slate-300">
                  {ticket.ticket_number}
                </span>
              </div>

              <Link
                href="/dashboard"
                className="text-sm text-slate-400 transition hover:text-white"
              >
                Dashboard
              </Link>
            </div>
          </header>

          <div className="mx-auto max-w-7xl px-6 py-8">
            <div className="mb-8">
              <div className="flex flex-wrap items-center gap-3">
                <span className="text-sm font-medium text-slate-500">
                  {ticket.ticket_number}
                </span>

                <StatusBadge status={ticket.status.name} />

                <PriorityBadge priority={ticket.priority.name} />
              </div>

              <h1 className="mt-4 text-2xl font-semibold tracking-tight text-white">
                {ticket.subject}
              </h1>

              <p className="mt-2 text-sm text-slate-500">
                Created on{" "}
                {new Date(ticket.created_at).toLocaleString()}
              </p>
            </div>

            <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
              <section className="space-y-6">
                <div className="rounded-2xl border border-slate-800 bg-slate-900">
                  <div className="border-b border-slate-800 px-6 py-5">
                    <h2 className="font-semibold">
                      Description
                    </h2>
                  </div>

                  <div className="px-6 py-6">
                    <p className="whitespace-pre-wrap text-sm leading-7 text-slate-300">
                      {ticket.description}
                    </p>
                  </div>
                </div>

                <TicketMessages
                  ticketId={ticket.id}
                  currentUser={currentUser}
                />

                <TicketAttachments
                  ticketId={ticket.id}
                  currentUser={currentUser}
                />

                {currentUser.role.name !== "Customer" && (
                  <>
                    <TicketActivityLog ticketId={ticket.id} />

                    <TicketInternalNotes
                      ticketId={ticket.id}
                      currentUser={currentUser}
                    />
                  </>
                )}
              </section>

              <aside className="space-y-6">
                {currentUser.role.name !== "Customer" && (
                  <TicketActions
                    ticket={ticket}
                    currentUser={currentUser}
                    onTicketUpdated={setTicket}
                  />
                )}

                <div className="rounded-2xl border border-slate-800 bg-slate-900">
                  <div className="border-b border-slate-800 px-5 py-4">
                    <h2 className="font-semibold">
                      Ticket Details
                    </h2>
                  </div>

                  <div className="divide-y divide-slate-800">
                    <DetailRow
                      label="Status"
                      value={ticket.status.name}
                    />

                    <DetailRow
                      label="Priority"
                      value={ticket.priority.name}
                    />

                    <DetailRow
                      label="Category"
                      value={ticket.category.name}
                    />

                    <DetailRow
                      label="Created"
                      value={new Date(
                        ticket.created_at
                      ).toLocaleDateString()}
                    />

                    <DetailRow
                      label="First response"
                      value={
                        ticket.first_response_at
                          ? new Date(
                              ticket.first_response_at
                            ).toLocaleString()
                          : "Not yet"
                      }
                    />

                    <DetailRow
                      label="Resolved"
                      value={
                        ticket.resolved_at
                          ? new Date(
                              ticket.resolved_at
                            ).toLocaleString()
                          : "Not yet"
                      }
                    />

                    <DetailRow
                      label="Closed"
                      value={
                        ticket.closed_at
                          ? new Date(
                              ticket.closed_at
                            ).toLocaleString()
                          : "Not yet"
                      }
                    />
                  </div>
                </div>

                <div className="rounded-2xl border border-slate-800 bg-slate-900">
                  <div className="border-b border-slate-800 px-5 py-4">
                    <h2 className="font-semibold">
                      Customer
                    </h2>
                  </div>

                  <div className="flex items-center gap-3 px-5 py-5">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-800 text-sm font-medium">
                      {ticket.customer.first_name
                        .charAt(0)
                        .toUpperCase()}
                    </div>

                    <div className="min-w-0">
                      <p className="truncate text-sm font-medium text-white">
                        {ticket.customer.first_name}{" "}
                        {ticket.customer.last_name}
                      </p>

                      <p className="truncate text-xs text-slate-500">
                        {ticket.customer.email}
                      </p>
                    </div>
                  </div>
                </div>
              </aside>
            </div>
          </div>
        </section>
      </div>
    </main>
  );
}

function DetailRow({
  label,
  value,
}: {
  label: string;
  value: string;
}) {
  return (
    <div className="flex items-center justify-between gap-4 px-5 py-4">
      <span className="text-xs text-slate-500">
        {label}
      </span>

      <span className="text-right text-sm text-slate-300">
        {value}
      </span>
    </div>
  );
}

function StatusBadge({ status }: { status: string }) {
  const styles: Record<string, string> = {
    Open: "bg-blue-500/10 text-blue-400",
    "In Progress": "bg-amber-500/10 text-amber-400",
    "Waiting for Customer":
      "bg-purple-500/10 text-purple-400",
    Resolved: "bg-emerald-500/10 text-emerald-400",
    Closed: "bg-slate-500/10 text-slate-400",
  };

  return (
    <span
      className={`rounded-full px-2.5 py-1 text-xs font-medium ${
        styles[status] ?? "bg-slate-800 text-slate-400"
      }`}
    >
      {status}
    </span>
  );
}

function PriorityBadge({ priority }: { priority: string }) {
  const styles: Record<string, string> = {
    Low: "text-slate-400",
    Medium: "text-blue-400",
    High: "text-amber-400",
    Urgent: "text-red-400",
  };

  return (
    <span
      className={`text-xs font-medium ${
        styles[priority] ?? "text-slate-400"
      }`}
    >
      {priority}
    </span>
  );
}