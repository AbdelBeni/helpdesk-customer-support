"use client";

import { useEffect, useState } from "react";
import api from "../../lib/api";
import type { User } from "../../types/index";

interface ActivityLog {
  id: number;
  action: string;
  old_value: unknown;
  new_value: unknown;
  metadata: Record<string, unknown> | null;
  user: User;
  created_at: string;
}

interface TicketActivityLogProps {
  ticketId: number;
}

export default function TicketActivityLog({
  ticketId,
}: TicketActivityLogProps) {
  const [logs, setLogs] = useState<ActivityLog[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    async function loadLogs() {
      try {
        setLoading(true);
        setError("");

        const response = await api.get<{
          data: ActivityLog[];
        }>(`/tickets/${ticketId}/activity-logs`, {
          params: {
            per_page: 50,
          },
        });

        setLogs(response.data.data);
      } catch {
        setError("Unable to load activity.");
      } finally {
        setLoading(false);
      }
    }

    loadLogs();
  }, [ticketId]);

  function formatAction(action: string) {
    const labels: Record<string, string> = {
      ticket_created: "Ticket created",
      ticket_updated: "Ticket updated",
      status_changed: "Status changed",
      ticket_assigned: "Ticket assigned",
      ticket_claimed: "Ticket claimed",
      attachment_uploaded: "Attachment uploaded",
      internal_note_added: "Internal note added",
      message_added: "Message added",
    };

    return labels[action] ?? action.replaceAll("_", " ");
  }

  function getDetails(log: ActivityLog) {
    if (
      log.action === "attachment_uploaded" &&
      log.metadata
    ) {
      const name = log.metadata.original_name;

      if (typeof name === "string") {
        return name;
      }
    }

    if (
      log.action === "internal_note_added" &&
      log.metadata
    ) {
      return "A new internal note was added.";
    }

    if (
      log.action === "status_changed" &&
      log.old_value &&
      log.new_value
    ) {
      return `${formatValue(log.old_value)} → ${formatValue(
        log.new_value
      )}`;
    }

    return null;
  }

  return (
    <div className="rounded-2xl border border-slate-800 bg-slate-900">
      <div className="border-b border-slate-800 px-6 py-5">
        <h2 className="font-semibold">Activity</h2>

        <p className="mt-1 text-xs text-slate-500">
          Internal history of changes made to this ticket.
        </p>
      </div>

      <div className="px-6 py-5 max-h-[400px] custom-scrollbar overflow-y-auto overflow-x-hidden">
        {loading ? (
          <div className="py-8 text-center">
            <p className="text-sm text-slate-500">
              Loading activity...
            </p>
          </div>
        ) : error ? (
          <div className="py-8 text-center">
            <p className="text-sm text-red-400">{error}</p>
          </div>
        ) : logs.length === 0 ? (
          <div className="py-8 text-center">
            <p className="text-sm text-slate-400">
              No activity yet.
            </p>
          </div>
        ) : (
          <div className="relative">
            <div className="absolute bottom-0 left-2.5 top-0 w-px bg-slate-800" />

            <div className="space-y-6">
              {logs.map((log) => {
                const details = getDetails(log);

                return (
                  <div
                    key={log.id}
                    className="relative flex gap-4"
                  >
                    <div className="relative z-10 mt-1 h-5 w-5 shrink-0 rounded-full border-4 border-slate-900 bg-slate-700" />

                    <div className="min-w-0 flex-1">
                      <div className="flex flex-wrap items-center gap-2">
                        <span className="text-sm font-medium text-slate-300">
                          {formatAction(log.action)}
                        </span>

                        <span className="text-xs text-slate-600">
                          {new Date(
                            log.created_at
                          ).toLocaleString()}
                        </span>
                      </div>

                      <p className="mt-1 text-xs text-slate-500">
                        {log.user.first_name}{" "}
                        {log.user.last_name}
                      </p>

                      {details && (
                        <p className="mt-2 text-sm text-slate-400">
                          {details}
                        </p>
                      )}
                    </div>
                  </div>
                );
              })}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

function formatValue(value: unknown): string {
  if (typeof value === "string") {
    return value;
  }

  if (
    typeof value === "object" &&
    value !== null
  ) {
    const object = value as Record<string, unknown>;

    const name = object.name;

    if (typeof name === "string") {
      return name;
    }

    return JSON.stringify(value);
  }

  return String(value);
}