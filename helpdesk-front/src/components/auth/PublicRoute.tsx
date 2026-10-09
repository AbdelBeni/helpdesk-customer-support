"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { getCurrentUser } from "../../lib/auth";

export default function PublicRoute({
  children,
}: {
  children: React.ReactNode;
}) {
  const router = useRouter();

  const [status, setStatus] = useState<
    "checking" | "allowed" | "redirecting"
  >("checking");

  useEffect(() => {
    let active = true;

    async function checkAuth() {
      const token = localStorage.getItem("token");

      if (!token) {
        if (active) {
          setStatus("allowed");
        }

        return;
      }

      try {
        await getCurrentUser();

        if (active) {
          setStatus("redirecting");
          router.replace("/dashboard");
        }
      } catch {
        localStorage.removeItem("token");
        localStorage.removeItem("user");

        if (active) {
          setStatus("allowed");
        }
      }
    }

    checkAuth();

    return () => {
      active = false;
    };
  }, [router]);

  if (status === "checking" || status === "redirecting") {
    return (
      <main className="flex min-h-screen items-center justify-center bg-slate-950 text-white">
        <p className="text-sm text-slate-500">
          Loading...
        </p>
      </main>
    );
  }

  return <>{children}</>;
}