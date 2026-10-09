import { Suspense } from "react";
import TicketDetails from "../../../components/tickets/TicketDetails";
import ProtectedRoute from "../../../components/auth/ProtectedRoute";

export default function TicketDetailsPage() {
  return (
    <ProtectedRoute>
      <Suspense
        fallback={
          <main className="flex min-h-screen items-center justify-center bg-slate-950 text-white">
            <p className="text-sm text-slate-500">
              Loading ticket...
            </p>
          </main>
        }
      >
        <TicketDetails />
      </Suspense>
    </ProtectedRoute>
  );
}