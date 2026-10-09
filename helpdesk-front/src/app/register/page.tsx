"use client";

import { FormEvent, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { register } from "../../lib/auth";
import PublicRoute from "../../components/auth/PublicRoute";

const inputClass =
  "w-full rounded-md border border-zinc-700 bg-zinc-950 [color-scheme:dark] px-3 py-2 text-sm text-zinc-100 shadow-sm outline-none transition placeholder:text-zinc-500 focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 disabled:cursor-not-allowed disabled:bg-zinc-900";

const labelClass = "mb-1.5 block text-sm font-medium text-zinc-300";

export default function RegisterPage() {
  const router = useRouter();

  const [firstName, setFirstName] = useState("");
  const [lastName, setLastName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");

  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    setError("");

    if (
      !firstName.trim() ||
      !lastName.trim() ||
      !email.trim() ||
      !password ||
      !passwordConfirmation
    ) {
      setError("Please complete all fields.");
      return;
    }

    if (password !== passwordConfirmation) {
      setError("Passwords do not match.");
      return;
    }

    if (password.length < 8) {
      setError("Password must be at least 8 characters.");
      return;
    }

    try {
      setLoading(true);

      await register({
        first_name: firstName.trim(),
        last_name: lastName.trim(),
        email: email.trim(),
        password,
        password_confirmation: passwordConfirmation,
      });

      router.replace(`/verify-email?email=${encodeURIComponent(email.trim())}`);
    } catch (err: unknown) {
      if (typeof err === "object" && err !== null && "response" in err) {
        const response = (
          err as {
            response?: {
              data?: {
                message?: string;
                errors?: Record<string, string[]>;
              };
            };
          }
        ).response;

        const validationErrors = response?.data?.errors;

        if (validationErrors) {
          const firstError = Object.values(validationErrors)[0]?.[0];

          setError(
            firstError ||
              response?.data?.message ||
              "Unable to create your account."
          );
        } else {
          setError(response?.data?.message || "Unable to create your account.");
        }
      } else {
        setError("Unable to create your account.");
      }
    } finally {
      setLoading(false);
    }
  }

  return (
    <PublicRoute>
      <main className="flex min-h-screen items-center justify-center bg-zinc-950 px-4 py-12">
        <div className="w-full max-w-sm">
          <p className="mb-6 text-center text-[15px] font-semibold text-zinc-100">
            HelpDesk
          </p>

          <div className="rounded-lg border border-zinc-800 bg-zinc-900 p-6 shadow-sm sm:p-8">
            <h1 className="text-xl font-semibold tracking-tight text-zinc-100">
              Create your account
            </h1>

            <p className="mt-1.5 text-sm text-zinc-400">
              Open and follow support requests in one place.
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

              <div className="grid gap-4 sm:grid-cols-2">
                <div>
                  <label htmlFor="firstName" className={labelClass}>
                    First name
                  </label>

                  <input
                    id="firstName"
                    type="text"
                    value={firstName}
                    onChange={(event) => setFirstName(event.target.value)}
                    disabled={loading}
                    autoComplete="given-name"
                    className={inputClass}
                  />
                </div>

                <div>
                  <label htmlFor="lastName" className={labelClass}>
                    Last name
                  </label>

                  <input
                    id="lastName"
                    type="text"
                    value={lastName}
                    onChange={(event) => setLastName(event.target.value)}
                    disabled={loading}
                    autoComplete="family-name"
                    className={inputClass}
                  />
                </div>
              </div>

              <div>
                <label htmlFor="email" className={labelClass}>
                  Email
                </label>

                <input
                  id="email"
                  type="email"
                  value={email}
                  onChange={(event) => setEmail(event.target.value)}
                  disabled={loading}
                  autoComplete="email"
                  placeholder="you@example.com"
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
                  disabled={loading}
                  autoComplete="new-password"
                  className={inputClass}
                />

                <p className="mt-1.5 text-xs text-zinc-400">
                  Minimum 8 characters.
                </p>
              </div>

              <div>
                <label htmlFor="passwordConfirmation" className={labelClass}>
                  Confirm password
                </label>

                <input
                  id="passwordConfirmation"
                  type="password"
                  value={passwordConfirmation}
                  onChange={(event) =>
                    setPasswordConfirmation(event.target.value)
                  }
                  disabled={loading}
                  autoComplete="new-password"
                  className={inputClass}
                />
              </div>

              <button
                type="submit"
                disabled={loading}
                className="inline-flex w-full items-center justify-center rounded-md bg-teal-500 px-3.5 py-2 text-sm font-medium text-zinc-950 shadow-sm transition-colors hover:bg-teal-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-400 disabled:cursor-not-allowed disabled:opacity-50"
              >
                {loading ? "Creating account…" : "Create account"}
              </button>
            </form>
          </div>

          <p className="mt-6 text-center text-sm text-zinc-400">
            Already have an account?{" "}
            <Link
              href="/login"
              className="font-medium text-teal-400 hover:text-teal-300"
            >
              Sign in
            </Link>
          </p>
        </div>
      </main>
    </PublicRoute>
  );
}