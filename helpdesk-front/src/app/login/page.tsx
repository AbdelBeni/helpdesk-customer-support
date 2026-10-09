"use client";

import { FormEvent, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import axios from "axios";
import { login } from "../../lib/auth";
import PublicRoute from "../../components/auth/PublicRoute";

const inputClass =
  "w-full rounded-md border border-zinc-700 bg-zinc-950 [color-scheme:dark] px-3 py-2 text-sm text-zinc-100 shadow-sm outline-none transition placeholder:text-zinc-500 focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 disabled:cursor-not-allowed disabled:bg-zinc-900";

const labelClass = "mb-1.5 block text-sm font-medium text-zinc-300";

export default function LoginPage() {
  const router = useRouter();

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    setError("");
    setLoading(true);

    try {
      await login(email, password);
      router.replace("/dashboard");
    } catch (error) {
      if (axios.isAxiosError(error)) {
        if (error.response?.status === 401) {
          setError("Invalid email or password.");
        } else if (error.response?.status === 403) {
          setError(error.response.data?.message || "Your account is inactive.");
        } else if (error.response?.status === 422) {
          setError("Please check your email and password.");
        } else {
          setError("Something went wrong. Please try again.");
        }
      } else {
        setError("Something went wrong. Please try again.");
      }
    } finally {
      setLoading(false);
    }
  }

  return (
    <PublicRoute>
      <main className="flex min-h-screen items-center justify-center bg-zinc-950 px-4 py-12">
        <div className="w-full max-w-sm">
          <p className="mb-6 text-center text-[15px] font-semibold text-zinc-100" style={{ fontFamily: "'Geist Mono'" }}>
            HelpDesk
          </p>

          <div className="rounded-lg border border-zinc-800 bg-zinc-900 p-6 shadow-sm sm:p-8">
            <h1 className="text-xl font-semibold tracking-tight text-zinc-100">
              Sign in
            </h1>

            <p className="mt-1.5 text-sm text-zinc-400">
              Enter your details to access your tickets.
            </p>

            <form onSubmit={handleSubmit} className="mt-6 space-y-4">
              {error && (
                <div
                  role="alert"
                  className="rounded-md border border-red-500/30 bg-red-500/10 px-3 py-2.5 text-sm text-red-300"
                >
                  {error}
                </div>
              )}

              <div>
                <label htmlFor="email" className={labelClass}>
                  Email
                </label>

                <input
                  id="email"
                  type="email"
                  value={email}
                  onChange={(event) => setEmail(event.target.value)}
                  placeholder="you@example.com"
                  autoComplete="email"
                  required
                  className={inputClass}
                />
              </div>

              <div>
                <label htmlFor="password" className={labelClass}>
                  Password
                </label>

                <input
                  id="password"
                  type="password"
                  value={password}
                  onChange={(event) => setPassword(event.target.value)}
                  autoComplete="current-password"
                  required
                  className={inputClass}
                />
              </div>

              <button
                type="submit"
                disabled={loading}
                className="inline-flex w-full items-center justify-center rounded-md bg-teal-500 px-3.5 py-2 text-sm font-medium text-zinc-950 shadow-sm transition-colors hover:bg-teal-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-400 disabled:cursor-not-allowed disabled:opacity-50"
              >
                {loading ? "Signing in…" : "Sign in"}
              </button>
            </form>
          </div>

          <p className="mt-6 text-center text-sm text-zinc-400">
            Don&apos;t have an account?{" "}
            <Link
              href="/register"
              className="font-medium text-teal-400 hover:text-teal-300"
            >
              Create one
            </Link>
          </p>
        </div>
      </main>
    </PublicRoute>
  );
}