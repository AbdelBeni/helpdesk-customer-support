"use client";

import { FormEvent, useEffect, useState } from "react";
import api from "../../lib/api";
import type { User } from "../../types/index";

interface TicketMessage {
  id: number;
  message: string;
  user: User;
  created_at: string;
  updated_at: string;
}

interface TicketMessagesProps {
  ticketId: number;
  currentUser: User | null;
}

export default function TicketMessages({
  ticketId,
  currentUser,
}: TicketMessagesProps) {
  const [messages, setMessages] = useState<TicketMessage[]>([]);
  const [message, setMessage] = useState("");
  const [loading, setLoading] = useState(true);
  const [sending, setSending] = useState(false);
  const [error, setError] = useState("");

  useEffect(() => {
    async function loadMessages() {
      try {
        setLoading(true);
        setError("");

        const response = await api.get<{
          data: TicketMessage[];
        }>(`/tickets/${ticketId}/messages`, {
          params: {
            per_page: 50,
          },
        });

        setMessages(response.data.data.reverse());
      } catch {
        setError("Unable to load messages.");
      } finally {
        setLoading(false);
      }
    }

    loadMessages();
  }, [ticketId]);

  async function handleSubmit(
    event: FormEvent<HTMLFormElement>
  ) {
    event.preventDefault();

    const trimmedMessage = message.trim();

    if (!trimmedMessage || sending) {
      return;
    }

    try {
      setSending(true);
      setError("");

      const response = await api.post<{
        data: TicketMessage;
      }>(`/tickets/${ticketId}/messages`, {
        message: trimmedMessage,
      });

      setMessages((current) => [
        ...current,
        response.data.data,
      ]);

      setMessage("");
    } catch {
      setError("Unable to send your message.");
    } finally {
      setSending(false);
    }
  }

  return (
    <div className="rounded-2xl border border-slate-800 bg-slate-900">
      <div className="border-b border-slate-800 px-6 py-5">
        <div className="flex items-center justify-between">
          <div>
            <h2 className="font-semibold">Conversation</h2>

            <p className="mt-1 text-xs text-slate-500">
              Communicate with the support team.
            </p>
          </div>

          {!loading && (
            <span className="text-xs text-slate-600">
              {messages.length}{" "}
              {messages.length === 1
                ? "message"
                : "messages"}
            </span>
          )}
        </div>
      </div>

      <div className="custom-scrollbar max-h-[560px] min-h-48 overflow-y-auto px-6 py-6">
        {loading ? (
          <div className="flex min-h-40 items-center justify-center">
            <p className="text-sm text-slate-500">
              Loading conversation...
            </p>
          </div>
        ) : error && messages.length === 0 ? (
          <div className="flex min-h-40 items-center justify-center">
            <p className="text-sm text-red-400">{error}</p>
          </div>
        ) : messages.length === 0 ? (
          <div className="flex min-h-40 items-center justify-center">
            <div className="text-center">
              <p className="text-sm text-slate-400">
                No messages yet.
              </p>

              <p className="mt-1 text-xs text-slate-600">
                Start the conversation below.
              </p>
            </div>
          </div>
        ) : (
          <div className="space-y-5">
            {messages.map((item) => {
              const isCurrentUser =
                currentUser?.id === item.user.id;

              return (
                <div
                  key={item.id}
                  className={`flex gap-3 ${
                    isCurrentUser
                      ? "justify-end"
                      : "justify-start"
                  }`}
                >
                  {!isCurrentUser && (
                    <Avatar user={item.user} />
                  )}

                  <div
                    className={`max-w-[80%] ${
                      isCurrentUser
                        ? "items-end"
                        : "items-start"
                    }`}
                  >
                    <div className="mb-1 flex items-center gap-2">
                      {!isCurrentUser && (
                        <span className="text-xs font-medium text-slate-300">
                          {item.user.first_name}{" "}
                          {item.user.last_name}
                        </span>
                      )}

                      <span className="text-[11px] text-slate-600">
                        {new Date(
                          item.created_at
                        ).toLocaleString()}
                      </span>
                    </div>

                    <div
                      className={`rounded-2xl px-4 py-3 text-sm leading-6 ${
                        isCurrentUser
                          ? "rounded-br-md bg-white text-slate-950"
                          : "rounded-bl-md bg-slate-800 text-slate-300"
                      }`}
                    >
                      <p className="whitespace-pre-wrap">
                        {item.message}
                      </p>
                    </div>
                  </div>

                  {isCurrentUser && (
                    <Avatar user={item.user} />
                  )}
                </div>
              );
            })}
          </div>
        )}
      </div>

      <div className="border-t border-slate-800 p-5">
        {error && messages.length > 0 && (
          <p className="mb-3 text-xs text-red-400">
            {error}
          </p>
        )}

        <form
          onSubmit={handleSubmit}
          className="flex items-end gap-3"
        >
          <textarea
            value={message}
            onChange={(event) =>
              setMessage(event.target.value)
            }
            placeholder="Write a message..."
            rows={3}
            disabled={sending}
            className="min-h-20 flex-1 resize-none rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-white outline-none placeholder:text-slate-600 focus:border-slate-400 disabled:cursor-not-allowed disabled:opacity-60"
          />

          <button
            type="submit"
            disabled={!message.trim() || sending}
            className="rounded-xl bg-white px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-slate-200 disabled:cursor-not-allowed disabled:opacity-50"
          >
            {sending ? "Sending..." : "Send"}
          </button>
        </form>
      </div>
    </div>
  );
}

function Avatar({ user }: { user: User }) {
  return (
    <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-800 text-xs font-medium text-slate-300">
      {user.first_name.charAt(0).toUpperCase()}
    </div>
  );
}