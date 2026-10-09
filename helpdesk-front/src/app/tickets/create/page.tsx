"use client";

import { FormEvent, useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import Link from "next/link";
import api from "../../../lib/api";
import { getCurrentUser } from "../../../lib/auth";
import Sidebar from "../../../components/layout/Sidebar";
import ProtectedRoute from "../../../components/auth/ProtectedRoute";
import type { User } from "../../../types/index";

interface Category {
  id: number;
  name: string;
}

interface Priority {
  id: number;
  name: string;
  level: number;
}

const fieldClass =
  "w-full rounded-md border border-[#2a2a2a] bg-zinc-950 [color-scheme:dark] px-3 py-2 text-sm text-zinc-100 shadow-sm outline-none transition placeholder:text-zinc-500 focus:border-[#2a2a2a] focus:ring-2 focus:ring-[color-mix(in_oklab,lab(38_0_0)_20%,transparent)] disabled:cursor-not-allowed disabled:bg-zinc-900 disabled:text-zinc-500 resize-none leading-6";

const labelClass = "mb-1.5 block text-sm font-medium text-zinc-300";

export default function CreateTicketPage() {
  const router = useRouter();

  const [user, setUser] = useState<User | null>(null);
  const [categories, setCategories] = useState<Category[]>([]);
  const [priorities, setPriorities] = useState<Priority[]>([]);

  const [categoryId, setCategoryId] = useState("");
  const [priorityId, setPriorityId] = useState("");
  const [subject, setSubject] = useState("");
  const [description, setDescription] = useState("");

  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState("");

  useEffect(() => {
    async function loadData() {
      try {
        setLoading(true);

        const userResponse = await getCurrentUser();

        const [categoriesResponse, prioritiesResponse] = await Promise.all([
          api.get<{ data: Category[] }>("/categories"),
          api.get<{ data: Priority[] }>("/priorities"),
        ]);

        setUser(userResponse);
        setCategories(categoriesResponse.data.data);
        setPriorities(prioritiesResponse.data.data);
      } catch {
        setError("Unable to load ticket form.");
      } finally {
        setLoading(false);
      }
    }

    loadData();
  }, []);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    if (!categoryId || !priorityId || !subject.trim() || !description.trim()) {
      setError("Please complete all required fields.");
      return;
    }

    try {
      setSubmitting(true);
      setError("");

      const response = await api.post("/tickets", {
        category_id: Number(categoryId),
        priority_id: Number(priorityId),
        subject: subject.trim(),
        description: description.trim(),
      });

      const ticket = response.data.data;

      router.push(`/tickets/${ticket.id}`);
    } catch (err: unknown) {
      if (typeof err === "object" && err !== null && "response" in err) {
        const response = (
          err as {
            response?: {
              data?: {
                message?: string;
              };
            };
          }
        ).response;

        setError(response?.data?.message || "Unable to create the ticket.");
      } else {
        setError("Unable to create the ticket.");
      }
    } finally {
      setSubmitting(false);
    }
  }

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
              <header className="flex min-h-16 items-center border-b border-zinc-800 bg-zinc-950 px-4 py-3 sm:px-6">
                <div>
                  <h1 className="text-base font-semibold text-zinc-100">
                    New ticket
                  </h1>

                  <p className="text-xs text-zinc-400">
                    Describe your issue and our support team will take care of
                    it.
                  </p>
                </div>
              </header>

              <div className="mx-auto max-w-3xl px-4 py-6 sm:px-6">
                <Link
                  href="/tickets"
                  className="mb-4 inline-block text-sm text-zinc-400 transition-colors hover:text-zinc-100"
                >
                  ← Back to tickets
                </Link>

                {error && (
                  <div
                    role="alert"
                    className="mb-4 rounded-md border border-red-500/30 bg-red-500/10 px-3 py-2.5 text-sm text-red-300"
                  >
                    {error}
                  </div>
                )}

                <div className="rounded-lg border border-zinc-800 bg-zinc-900">
                  {loading ? (
                    <div className="py-12 text-center text-sm text-zinc-400">
                      Loading form…
                    </div>
                  ) : (
                    <form onSubmit={handleSubmit}>
                      <div className="space-y-5 p-5 sm:p-6">
                        <div className="grid gap-5 sm:grid-cols-2">
                          <div>
                            <label htmlFor="category" className={labelClass}>
                              Category
                            </label>

                            <select
                              id="category"
                              value={categoryId}
                              onChange={(event) =>
                                setCategoryId(event.target.value)
                              }
                              disabled={submitting}
                              className={fieldClass}
                            >
                              <option value="">Select a category</option>

                              {categories.map((category) => (
                                <option key={category.id} value={category.id}>
                                  {category.name}
                                </option>
                              ))}
                            </select>
                          </div>

                          <div>
                            <label htmlFor="priority" className={labelClass}>
                              Priority
                            </label>

                            <select
                              id="priority"
                              value={priorityId}
                              onChange={(event) =>
                                setPriorityId(event.target.value)
                              }
                              disabled={submitting}
                              className={fieldClass}
                            >
                              <option value="">Select a priority</option>

                              {priorities.map((priority) => (
                                <option key={priority.id} value={priority.id}>
                                  {priority.name}
                                </option>
                              ))}
                            </select>
                          </div>
                        </div>

                        <div>
                          <label htmlFor="subject" className={labelClass}>
                            Subject
                          </label>

                          <input
                            id="subject"
                            type="text"
                            value={subject}
                            onChange={(event) => setSubject(event.target.value)}
                            disabled={submitting}
                            placeholder="Briefly describe your issue"
                            maxLength={255}
                            className={fieldClass}
                          />
                        </div>

                        <div>
                          <label htmlFor="description" className={labelClass}>
                            Description
                          </label>

                          <textarea
                            id="description"
                            value={description}
                            onChange={(event) =>
                              setDescription(event.target.value)
                            }
                            disabled={submitting}
                            placeholder="Explain your issue in detail…"
                            rows={8}
                            className={`${fieldClass} resize-none leading-6`}
                          />
                        </div>
                      </div>

                      <div className="flex items-center justify-end gap-2 border-t border-zinc-800 bg-zinc-950/60/60 px-5 py-3.5 sm:px-6">
                        <Link
                          href="/tickets"
                          className="inline-flex items-center justify-center rounded-md px-3.5 py-2 text-sm font-medium text-zinc-400 transition-colors hover:bg-zinc-800 hover:text-zinc-100"
                        >
                          Cancel
                        </Link>

                        <button
                          type="submit"
                          disabled={submitting}
                          className="inline-flex items-center justify-center rounded-md bg-teal-500 px-3.5 py-2 text-sm font-medium text-zinc-950 shadow-sm transition-colors hover:bg-teal-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-400 disabled:cursor-not-allowed disabled:opacity-50 cursor-pointer"
                        >
                          {submitting ? "Creating…" : "Create Ticket"}
                        </button>
                      </div>
                    </form>
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