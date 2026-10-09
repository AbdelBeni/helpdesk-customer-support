"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import api from "../../lib/api";
import StatCard from "../../components/dashboard/StatCard";
import RecentTickets from "../../components/dashboard/RecentTickets";
import ProtectedRoute from "../../components/auth/ProtectedRoute";
import Sidebar from "../../components/layout/Sidebar";
import type {
  CustomerDashboardStats,
  DashboardStats,
  User,
} from "../../types/index";

type Stats = DashboardStats | CustomerDashboardStats;

const metricsGrid =
  "grid gap-px overflow-hidden rounded-lg border border-zinc-800 bg-zinc-800";

export default function DashboardPage() {
  const router = useRouter();

  const [user, setUser] = useState<User | null>(null);
  const [stats, setStats] = useState<Stats | null>(null);
  const [loading, setLoading] = useState(true);
  const [statsLoading, setStatsLoading] = useState(true);

  useEffect(() => {
    async function loadDashboard() {
      try {
        const userResponse = await api.get<{ data: User }>("/auth/me");
        const currentUser = userResponse.data.data;

        setUser(currentUser);

        if (
          currentUser.role.name === "Admin" ||
          currentUser.role.name === "Agent"
        ) {
          const statsResponse =
            await api.get<DashboardStats>("/dashboard/stats");

          setStats(statsResponse.data);
        }

        if (currentUser.role.name === "Customer") {
          const statsResponse = await api.get<CustomerDashboardStats>(
            "/dashboard/customer-stats"
          );

          setStats(statsResponse.data);
        }
      } catch {
        localStorage.removeItem("token");
        localStorage.removeItem("user");
        router.replace("/login");
      } finally {
        setLoading(false);
        setStatsLoading(false);
      }
    }

    loadDashboard();
  }, [router]);

  if (loading) {
    return (
      <main className="flex min-h-screen items-center justify-center bg-zinc-950">
        <div className="text-sm text-zinc-400">Loading dashboard…</div>
      </main>
    );
  }

  if (!user) {
    return null;
  }

  const isStaff = user.role.name === "Admin" || user.role.name === "Agent";

  const showUnassigned = isStaff && !!stats && "unassigned_tickets" in stats;

  return (
    <ProtectedRoute>
      <main className="min-h-screen bg-zinc-950 text-zinc-100">
        <div className="flex min-h-screen">
          <Sidebar user={user} />

          <section className="min-w-0 flex-1 pt-14 lg:ml-64 lg:pt-0">
            <header className="flex min-h-16 items-center border-b border-zinc-800 bg-[#18181b99] px-4 py-3 sm:px-6">
              <div>
                <h1 className="text-base font-semibold text-zinc-100">
                  Dashboard
                </h1>

                <p className="text-xs text-zinc-400">
                  Welcome back, {user.first_name}. Here&apos;s where your
                  tickets stand.
                </p>
              </div>
            </header>

            <div className="mx-auto max-w-6xl px-4 py-6 sm:px-6">
              <div className={`${metricsGrid} sm:grid-cols-2 xl:grid-cols-4`}>
                <StatCard
                  label="Total tickets"
                  value={statsLoading ? "—" : stats?.total_tickets ?? 0}
                />

                <StatCard
                  label="Open"
                  value={statsLoading ? "—" : stats?.open_tickets ?? 0}
                />

                <StatCard
                  label="In progress"
                  value={statsLoading ? "—" : stats?.in_progress_tickets ?? 0}
                />

                <StatCard
                  label="Resolved"
                  value={statsLoading ? "—" : stats?.resolved_tickets ?? 0}
                />
              </div>

              {stats && (
                <div
                  className={`mt-4 ${metricsGrid} ${
                    showUnassigned
                      ? "sm:grid-cols-2 xl:grid-cols-4"
                      : "sm:grid-cols-3"
                  }`}
                >
                  <StatCard
                    label="Waiting for customer"
                    value={stats.waiting_for_customer_tickets}
                  />

                  <StatCard label="Closed" value={stats.closed_tickets} />

                  <StatCard
                    label="Average resolution time"
                    value={
                      stats.average_resolution_time.hours !== null
                        ? `${stats.average_resolution_time.hours}h`
                        : "—"
                    }
                  />

                  {isStaff && "unassigned_tickets" in stats && (
                    <StatCard
                      label="Unassigned"
                      value={stats.unassigned_tickets}
                    />
                  )}
                </div>
              )}

              <RecentTickets />
            </div>
          </section>
        </div>
      </main>
    </ProtectedRoute>
  );
}