"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import api from "../../lib/api";
import { getCurrentUser } from "../../lib/auth";
import Sidebar from "../../components/layout/Sidebar";
import ProtectedRoute from "../../components/auth/ProtectedRoute";
import type { User } from "../../types/index";

interface Notification {
  id: number;
  type: string;
  title: string;
  message: string;
  data?: Record<string, unknown> | null;
  read_at: string | null;
  created_at: string;
}

export default function NotificationsPage() {
  const [user, setUser] = useState<User | null>(null);
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    async function loadNotifications() {
      try {
        setLoading(true);
        setError("");

        const [userResponse, notificationsResponse] = await Promise.all([
          getCurrentUser(),
          api.get<{ data: Notification[] }>("/notifications"),
        ]);

        setUser(userResponse);
        setNotifications(notificationsResponse.data.data);
      } catch {
        setError("Unable to load notifications.");
      } finally {
        setLoading(false);
      }
    }

    loadNotifications();
  }, []);

  async function markAsRead(notificationId: number) {
    try {
      await api.patch(`/notifications/${notificationId}/read`);

      setNotifications((current) =>
        current.map((notification) =>
          notification.id === notificationId
            ? {
                ...notification,
                read_at: new Date().toISOString(),
              }
            : notification
        )
      );
    } catch {
      setError("Unable to update notification.");
    }
  }

  async function markAllAsRead() {
    const unreadNotifications = notifications.filter(
      (notification) => !notification.read_at
    );

    try {
      await Promise.all(
        unreadNotifications.map((notification) =>
          api.patch(`/notifications/${notification.id}/read`)
        )
      );

      const now = new Date().toISOString();

      setNotifications((current) =>
        current.map((notification) => ({
          ...notification,
          read_at: notification.read_at ?? now,
        }))
      );
    } catch {
      setError("Unable to mark all notifications as read.");
    }
  }

  const unreadCount = notifications.filter(
    (notification) => !notification.read_at
  ).length;

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
                    Notifications
                  </h1>

                  <p className="text-xs text-zinc-400">
                    {unreadCount > 0
                      ? `${unreadCount} unread`
                      : "Updates about your tickets and support activity."}
                  </p>
                </div>

                {unreadCount > 0 && (
                  <button
                    type="button"
                    onClick={markAllAsRead}
                    className="inline-flex shrink-0 items-center justify-center rounded-md border border-zinc-700 bg-zinc-950 [color-scheme:dark] px-3.5 py-2 text-sm font-medium text-zinc-300 shadow-sm transition-colors hover:bg-zinc-800/50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-400"
                  >
                    Mark all as read
                  </button>
                )}
              </header>

              <div className="mx-auto max-w-4xl px-4 py-6 sm:px-6">
                {error && (
                  <div
                    role="alert"
                    className="mb-4 rounded-md border border-red-500/30 bg-red-500/10 px-3 py-2.5 text-sm text-red-300"
                  >
                    {error}
                  </div>
                )}

                <div className="overflow-hidden rounded-lg border border-zinc-800 bg-zinc-900">
                  {loading ? (
                    <div className="px-6 py-12 text-center text-sm text-zinc-400">
                      Loading notifications…
                    </div>
                  ) : notifications.length === 0 ? (
                    <div className="px-6 py-14 text-center">
                      <p className="text-sm font-medium text-zinc-100">
                        You&apos;re all caught up
                      </p>

                      <p className="mt-1 text-sm text-zinc-400">
                        You don&apos;t have any notifications yet.
                      </p>

                      <Link
                        href="/tickets"
                        className="mt-3 inline-block text-sm font-medium text-teal-400 hover:text-teal-300"
                      >
                        View your tickets
                      </Link>
                    </div>
                  ) : (
                    <ul className="divide-y divide-zinc-800">
                      {notifications.map((notification) => {
                        const unread = !notification.read_at;

                        return (
                          <li
                            key={notification.id}
                            className={`border-l-2 px-5 py-4 ${
                              unread
                                ? "border-teal-500 bg-teal-500/5"
                                : "border-transparent border-b border-zinc-800"
                            }`}
                          >
                            <div className="flex items-start justify-between gap-4">
                              <h2
                                className={`text-sm text-zinc-100 ${
                                  unread ? "font-semibold" : "font-medium"
                                }`}
                              >
                                {unread && (
                                  <span className="sr-only">Unread: </span>
                                )}
                                {notification.title}
                              </h2>

                              <time className="shrink-0 text-xs text-zinc-400">
                                {new Date(
                                  notification.created_at
                                ).toLocaleString()}
                              </time>
                            </div>

                            <p className="mt-1 text-sm leading-6 text-zinc-400">
                              {notification.message}
                            </p>

                            {unread && (
                              <button
                                type="button"
                                onClick={() => markAsRead(notification.id)}
                                className="mt-2 text-xs font-medium text-teal-400 transition-colors hover:text-teal-300"
                              >
                                Mark as read
                              </button>
                            )}
                          </li>
                        );
                      })}
                    </ul>
                  )}
                </div>
              </div>
            </section>
          </div>
        </main>
      )}
    </ProtectedRoute>
  );
}