"use client";

import {
  ClipboardEvent,
  FormEvent,
  KeyboardEvent,
  Suspense,
  useEffect,
  useRef,
  useState,
} from "react";
import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { resendVerification, verifyEmail } from "../../lib/auth";
import PublicRoute from "../../components/auth/PublicRoute";

function VerifyEmailContent() {
  const router = useRouter();
  const searchParams = useSearchParams();

  const email = searchParams.get("email") ?? "";

  const [code, setCode] = useState(["", "", "", "", "", ""]);

  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");
  const [loading, setLoading] = useState(false);
  const [resending, setResending] = useState(false);
  const [cooldown, setCooldown] = useState(60);

  const inputRefs = useRef<Array<HTMLInputElement | null>>([]);

  useEffect(() => {
    if (cooldown <= 0) {
      return;
    }

    const timer = window.setInterval(() => {
      setCooldown((current) => {
        if (current <= 1) {
          window.clearInterval(timer);
          return 0;
        }

        return current - 1;
      });
    }, 1000);

    return () => window.clearInterval(timer);
  }, [cooldown]);

  useEffect(() => {
    inputRefs.current[0]?.focus();
  }, []);

  function handleChange(index: number, value: string) {
    const digit = value.replace(/\D/g, "").slice(-1);

    const nextCode = [...code];

    nextCode[index] = digit;

    setCode(nextCode);
    setError("");
    setSuccess("");

    if (digit && index < code.length - 1) {
      inputRefs.current[index + 1]?.focus();
    }
  }

  function handleKeyDown(index: number, event: KeyboardEvent<HTMLInputElement>) {
    if (event.key === "Backspace" && !code[index] && index > 0) {
      inputRefs.current[index - 1]?.focus();
    }

    if (event.key === "ArrowLeft" && index > 0) {
      inputRefs.current[index - 1]?.focus();
    }

    if (event.key === "ArrowRight" && index < code.length - 1) {
      inputRefs.current[index + 1]?.focus();
    }
  }

  function handlePaste(event: ClipboardEvent<HTMLInputElement>) {
    event.preventDefault();

    const pastedCode = event.clipboardData
      .getData("text")
      .replace(/\D/g, "")
      .slice(0, 6);

    if (!pastedCode) {
      return;
    }

    const nextCode = ["", "", "", "", "", ""];

    pastedCode.split("").forEach((digit, index) => {
      nextCode[index] = digit;
    });

    setCode(nextCode);
    setError("");
    setSuccess("");

    const focusIndex = Math.min(pastedCode.length, code.length - 1);

    inputRefs.current[focusIndex]?.focus();
  }

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    setError("");
    setSuccess("");

    if (!email) {
      setError("Verification email is missing. Please register again.");
      return;
    }

    const verificationCode = code.join("");

    if (verificationCode.length !== 6) {
      setError("Please enter the 6-digit verification code.");
      return;
    }

    try {
      setLoading(true);

      await verifyEmail(email, verificationCode);

      router.replace("/dashboard");
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
              "Invalid verification code."
          );
        } else {
          setError(
            response?.data?.message || "Invalid or expired verification code."
          );
        }
      } else {
        setError("Unable to verify your email.");
      }
    } finally {
      setLoading(false);
    }
  }

  async function handleResend() {
    if (!email || cooldown > 0 || resending) {
      return;
    }

    setError("");
    setSuccess("");

    try {
      setResending(true);

      await resendVerification(email);

      setCode(["", "", "", "", "", ""]);

      setCooldown(60);

      setSuccess("A new verification code has been sent to your email.");

      inputRefs.current[0]?.focus();
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

        setError(
          response?.data?.message || "Unable to resend the verification code."
        );
      } else {
        setError("Unable to resend the verification code.");
      }
    } finally {
      setResending(false);
    }
  }

  return (
    <main className="flex min-h-screen items-center justify-center bg-zinc-950 px-4 py-12">
      <div className="w-full max-w-sm">
        <p className="mb-6 text-center text-[15px] font-semibold text-zinc-100">
          HelpDesk
        </p>

        <div className="rounded-lg border border-zinc-800 bg-zinc-900 p-6 shadow-sm sm:p-8">
          <h1 className="text-xl font-semibold tracking-tight text-zinc-100">
            Check your email
          </h1>

          <p className="mt-1.5 text-sm leading-6 text-zinc-400">
            We sent a 6-digit code to{" "}
            <span className="break-all font-medium text-zinc-100">
              {email || "your email address"}
            </span>
            .
          </p>

          <form onSubmit={handleSubmit} className="mt-6 space-y-5">
            {error && (
              <div
                role="alert"
                className="rounded-md border border-red-500/30 bg-red-500/10 px-3 py-2.5 text-sm text-red-300"
              >
                {error}
              </div>
            )}

            {success && (
              <div
                role="status"
                className="rounded-md border border-emerald-500/30 bg-emerald-500/10 px-3 py-2.5 text-sm text-emerald-300"
              >
                {success}
              </div>
            )}

            <div className="flex gap-2">
              {code.map((digit, index) => (
                <input
                  key={index}
                  ref={(element) => {
                    inputRefs.current[index] = element;
                  }}
                  type="text"
                  inputMode="numeric"
                  pattern="[0-9]*"
                  maxLength={1}
                  value={digit}
                  onChange={(event) => handleChange(index, event.target.value)}
                  onKeyDown={(event) => handleKeyDown(index, event)}
                  onPaste={handlePaste}
                  disabled={loading}
                  aria-label={`Verification digit ${index + 1}`}
                  className="h-12 w-full min-w-0 rounded-md border border-zinc-700 bg-zinc-950 [color-scheme:dark] text-center text-lg font-semibold tabular-nums text-zinc-100 shadow-sm outline-none transition focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 disabled:cursor-not-allowed disabled:bg-zinc-900"
                />
              ))}
            </div>

            <button
              type="submit"
              disabled={loading || code.join("").length !== 6}
              className="inline-flex w-full items-center justify-center rounded-md bg-teal-500 px-3.5 py-2 text-sm font-medium text-zinc-950 shadow-sm transition-colors hover:bg-teal-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-400 disabled:cursor-not-allowed disabled:opacity-50"
            >
              {loading ? "Verifying…" : "Verify email"}
            </button>
          </form>

          <p className="mt-6 text-sm text-zinc-400">
            Didn&apos;t receive the code?{" "}
            <button
              type="button"
              onClick={handleResend}
              disabled={cooldown > 0 || resending || !email}
              className="font-medium text-teal-400 transition-colors hover:text-teal-300 disabled:cursor-not-allowed disabled:text-zinc-600"
            >
              {resending
                ? "Sending…"
                : cooldown > 0
                  ? `Resend in ${cooldown}s`
                  : "Resend code"}
            </button>
          </p>
        </div>

        <p className="mt-6 text-center text-sm text-zinc-400">
          <Link
            href="/register"
            className="font-medium text-teal-400 hover:text-teal-300"
          >
            Use a different email
          </Link>
        </p>
      </div>
    </main>
  );
}

function VerifyEmailFallback() {
  return (
    <main className="flex min-h-screen items-center justify-center bg-zinc-950">
      <p className="text-sm text-zinc-400">Loading…</p>
    </main>
  );
}

export default function VerifyEmailPage() {
  return (
    <PublicRoute>
      <Suspense fallback={<VerifyEmailFallback />}>
        <VerifyEmailContent />
      </Suspense>
    </PublicRoute>
  );
}