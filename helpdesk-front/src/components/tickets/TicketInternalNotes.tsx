"use client";

import { FormEvent, useEffect, useState } from "react";
import api from "../../lib/api";
import type { User } from "../../types/index";

interface InternalNote {
  id: number;
  content: string;
  user: {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
  };
  created_at: string;
}

interface TicketInternalNotesProps {
  ticketId: number;
  currentUser: User | null;
}

export default function TicketInternalNotes({
  ticketId,
  currentUser,
}: TicketInternalNotesProps) {
  const [notes, setNotes] = useState<InternalNote[]>([]);
  const [content, setContent] = useState("");
  const [loading, setLoading] = useState(true);
  const [sending, setSending] = useState(false);
  const [error, setError] = useState("");

  useEffect(() => {
    async function loadNotes() {
      try {
        setLoading(true);
        setError("");

        const response = await api.get<{
          data: InternalNote[];
        }>(`/tickets/${ticketId}/internal-notes`, {
          params: {
            per_page: 50,
          },
        });

        setNotes(response.data.data);
      } catch {
        setError("Unable to load internal notes.");
      } finally {
        setLoading(false);
      }
    }

    loadNotes();
  }, [ticketId]);

  async function handleSubmit(
    event: FormEvent<HTMLFormElement>
  ) {
    event.preventDefault();

    const trimmedContent = content.trim();

    if (!trimmedContent || sending) {
      return;
    }

    try {
      setSending(true);
      setError("");

      const response = await api.post<{
        data: InternalNote;
      }>(`/tickets/${ticketId}/internal-notes`, {
        content: trimmedContent,
      });

      setNotes((current) => [
        response.data.data,
        ...current,
      ]);

      setContent("");
    } catch {
      setError("Unable to add internal note.");
    } finally {
      setSending(false);
    }
  }

  const isStaff =
    currentUser?.role.name === "Admin" ||
    currentUser?.role.name === "Agent";

  if (!isStaff) {
    return null;
  }

  return (
    <div className="rounded-2xl border border-amber-900/40 bg-slate-900">
      <div className="border-b border-amber-900/30 px-6 py-5">
        <h2 className="font-semibold text-amber-400">
          Internal Notes
        </h2>

        <p className="mt-1 text-xs text-slate-500">
          Only support staff can see these notes.
        </p>
      </div>

      <div className="max-h-[500px] overflow-y-auto px-6 py-5">
        {loading ? (
          <div className="py-8 text-center">
            <p className="text-sm text-slate-500">
              Loading notes...
            </p>
          </div>
        ) : error && notes.length === 0 ? (
          <div className="py-8 text-center">
            <p className="text-sm text-red-400">{error}</p>
          </div>
        ) : notes.length === 0 ? (
          <div className="py-8 text-center">
            <p className="text-sm text-slate-400">
              No internal notes yet.
            </p>
          </div>
        ) : (
          <div className="space-y-4">
            {notes.map((note) => (
              <div
                key={note.id}
                className="rounded-xl border border-slate-800 bg-slate-950 p-4"
              >
                <div className="flex items-center justify-between gap-3">
                  <p className="text-sm font-medium text-slate-300">
                    {note.user.first_name}{" "}
                    {note.user.last_name}
                  </p>

                  <span className="text-[11px] text-slate-600">
                    {new Date(
                      note.created_at
                    ).toLocaleString()}
                  </span>
                </div>

                <p className="mt-3 whitespace-pre-wrap text-sm leading-6 text-slate-400">
                  {note.content}
                </p>
              </div>
            ))}
          </div>
        )}
      </div>

      <div className="border-t border-slate-800 p-5">
        {error && notes.length > 0 && (
          <p className="mb-3 text-xs text-red-400">
            {error}
          </p>
        )}

        <form
          onSubmit={handleSubmit}
          className="space-y-3"
        >
          <textarea
            value={content}
            onChange={(event) =>
              setContent(event.target.value)
            }
            placeholder="Write an internal note..."
            rows={3}
            disabled={sending}
            className="w-full resize-none rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-white outline-none placeholder:text-slate-600 focus:border-amber-700 disabled:cursor-not-allowed disabled:opacity-60"
          />

          <div className="flex justify-end">
            <button
              type="submit"
              disabled={!content.trim() || sending}
              className="rounded-xl bg-amber-400 px-4 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-amber-300 disabled:cursor-not-allowed disabled:opacity-50"
            >
              {sending ? "Adding..." : "Add note"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}