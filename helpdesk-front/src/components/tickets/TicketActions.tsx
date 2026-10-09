"use client";

import { useState } from "react";
import axios from "axios";
import api from "../../lib/api";
import type { Ticket, User } from "../../types/index";

interface TicketActionsProps {
  ticket: Ticket;
  currentUser: User | null;
  onTicketUpdated: (ticket: Ticket) => void;
}

interface Status {
  id: number;
  name: string;
}

interface ApiErrorResponse {
  message?: string;
}

interface AgentsResponse {
  data: User[];
}

const statuses: Status[] = [
  { id: 1, name: "Open" },
  { id: 2, name: "In Progress" },
  { id: 3, name: "Waiting for Customer" },
  { id: 4, name: "Resolved" },
  { id: 5, name: "Closed" },
];

export default function TicketActions({
  ticket,
  currentUser,
  onTicketUpdated,
}: TicketActionsProps) {
  const [selectedStatus, setSelectedStatus] = useState(
    ticket.status.id
  );

  const [updatingStatus, setUpdatingStatus] = useState(false);
  const [claiming, setClaiming] = useState(false);

  const [agents, setAgents] = useState<User[]>([]);
  const [selectedAgent, setSelectedAgent] = useState("");
  const [showAssignment, setShowAssignment] = useState(false);
  const [loadingAgents, setLoadingAgents] = useState(false);
  const [assigning, setAssigning] = useState(false);

  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");

  const isAgent = currentUser?.role.name === "Agent";
  const isAdmin = currentUser?.role.name === "Admin";

  const assignedAgentId =
    ticket.assignment?.agent?.id ?? null;

  const isAssignedToCurrentUser =
    isAgent &&
    currentUser?.id === assignedAgentId;

  const isAssignedToAnotherAgent =
    isAgent &&
    assignedAgentId !== null &&
    currentUser?.id !== assignedAgentId;

  async function handleStatusChange() {
    if (
      selectedStatus === ticket.status.id ||
      updatingStatus
    ) {
      return;
    }

    try {
      setUpdatingStatus(true);
      setError("");
      setSuccess("");

      const response = await api.patch<{
        data: Ticket;
      }>(`/tickets/${ticket.id}/status`, {
        status_id: selectedStatus,
      });

      onTicketUpdated(response.data.data);

      setSuccess("Ticket status updated successfully.");
    } catch (error: unknown) {
      console.error(
        "STATUS UPDATE ERROR:",
        axios.isAxiosError(error)
          ? error.response?.data
          : error
      );

      setSelectedStatus(ticket.status.id);

      const message = axios.isAxiosError<ApiErrorResponse>(
        error
      )
        ? error.response?.data?.message
        : undefined;

      setError(
        message || "Unable to update ticket status."
      );
    } finally {
      setUpdatingStatus(false);
    }
  }

  async function handleClaim() {
    if (claiming || isAssignedToCurrentUser) {
      return;
    }

    try {
      setClaiming(true);
      setError("");
      setSuccess("");

      const response = await api.post<{
        data: {
          id: number;
          assigned_at: string;
        };
      }>(`/tickets/${ticket.id}/claim`);

      if (!currentUser) {
        return;
      }

      onTicketUpdated({
        ...ticket,
        assignment: {
          id: response.data.data.id,
          agent: currentUser,
          assigned_at:
            response.data.data.assigned_at,
        },
      });

      setSuccess("Ticket claimed successfully.");
    } catch (error: unknown) {
      console.error(
        "CLAIM ERROR:",
        axios.isAxiosError(error)
          ? error.response?.data
          : error
      );

      const message = axios.isAxiosError<ApiErrorResponse>(
        error
      )
        ? error.response?.data?.message
        : undefined;

      setError(
        message || "Unable to claim this ticket."
      );
    } finally {
      setClaiming(false);
    }
  }

  async function handleOpenAssignment() {
    if (showAssignment) {
      setShowAssignment(false);
      return;
    }

    try {
      setLoadingAgents(true);
      setError("");
      setSuccess("");

      const response = await api.get<AgentsResponse>(
        "/agents"
      );

      setAgents(response.data.data);

      if (ticket.assignment?.agent?.id) {
        setSelectedAgent(
          String(ticket.assignment.agent.id)
        );
      } else {
        setSelectedAgent("");
      }

      setShowAssignment(true);
    } catch (error: unknown) {
      console.error(
        "AGENTS ERROR:",
        axios.isAxiosError(error)
          ? error.response?.data
          : error
      );

      const message = axios.isAxiosError<ApiErrorResponse>(
        error
      )
        ? error.response?.data?.message
        : undefined;

      setError(
        message || "Unable to load agents."
      );
    } finally {
      setLoadingAgents(false);
    }
  }

  async function handleAssign() {
    if (!selectedAgent || assigning) {
      return;
    }

    const agent = agents.find(
      (item) => item.id === Number(selectedAgent)
    );

    if (!agent) {
      return;
    }

    try {
      setAssigning(true);
      setError("");
      setSuccess("");

      const response = await api.post<{
        data: {
          id: number;
          agent_id: number;
          assigned_at: string;
        };
      }>(`/tickets/${ticket.id}/assign`, {
        agent_id: Number(selectedAgent),
      });

      onTicketUpdated({
        ...ticket,
        assignment: {
          id: response.data.data.id,
          agent,
          assigned_at:
            response.data.data.assigned_at,
        },
      });

      setSuccess(
        `Ticket assigned to ${agent.first_name} ${agent.last_name}.`
      );
    } catch (error: unknown) {
      console.error(
        "ASSIGN ERROR:",
        axios.isAxiosError(error)
          ? error.response?.data
          : error
      );

      const message = axios.isAxiosError<ApiErrorResponse>(
        error
      )
        ? error.response?.data?.message
        : undefined;

      setError(
        message || "Unable to assign ticket."
      );
    } finally {
      setAssigning(false);
    }
  }

  if (!isAgent && !isAdmin) {
    return null;
  }

  return (
    <div className="rounded-2xl border border-slate-800 bg-slate-900">
      <div className="border-b border-slate-800 px-5 py-4">
        <h2 className="font-semibold">Ticket Actions</h2>

        <p className="mt-1 text-xs text-slate-500">
          Manage the status and assignment of this ticket.
        </p>
      </div>

      <div className="space-y-5 px-5 py-5">
        <div>
          <label className="mb-2 block text-xs font-medium text-slate-500">
            Status
          </label>

          <div className="flex gap-2">
            <select
              value={selectedStatus}
              onChange={(event) =>
                setSelectedStatus(
                  Number(event.target.value)
                )
              }
              disabled={updatingStatus}
              className="min-w-0 flex-1 rounded-xl border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm text-white outline-none focus:border-slate-400 disabled:cursor-not-allowed disabled:opacity-50"
            >
              {statuses.map((status) => (
                <option
                  key={status.id}
                  value={status.id}
                >
                  {status.name}
                </option>
              ))}
            </select>

            <button
              type="button"
              onClick={handleStatusChange}
              disabled={
                selectedStatus === ticket.status.id ||
                updatingStatus
              }
              className="rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-slate-200 disabled:cursor-not-allowed disabled:opacity-40"
            >
              {updatingStatus
                ? "Updating..."
                : "Update"}
            </button>
          </div>
        </div>

        {isAdmin && (
          <div className="border-t border-slate-800 pt-5">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-xs font-medium text-slate-500">
                  Assignment
                </p>

                <p className="mt-1 text-sm text-slate-300">
                  {ticket.assignment?.agent
                    ? `${ticket.assignment.agent.first_name} ${ticket.assignment.agent.last_name}`
                    : "Unassigned"}
                </p>
              </div>

              <button
                type="button"
                onClick={handleOpenAssignment}
                disabled={loadingAgents}
                className="rounded-xl border border-slate-700 px-3 py-2 text-xs font-semibold text-white transition hover:border-slate-500 hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
              >
                {loadingAgents
                  ? "Loading..."
                  : showAssignment
                    ? "Cancel"
                    : "Assign Agent"}
              </button>
            </div>

            {showAssignment && (
              <div className="mt-4 space-y-3">
                <select
                  value={selectedAgent}
                  onChange={(event) =>
                    setSelectedAgent(event.target.value)
                  }
                  disabled={assigning}
                  className="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm text-white outline-none focus:border-slate-400 disabled:cursor-not-allowed disabled:opacity-50"
                >
                  <option value="">
                    Select an agent
                  </option>

                  {agents.map((agent) => (
                    <option
                      key={agent.id}
                      value={agent.id}
                    >
                      {agent.first_name} {agent.last_name}
                    </option>
                  ))}
                </select>

                <button
                  type="button"
                  onClick={handleAssign}
                  disabled={!selectedAgent || assigning}
                  className="w-full rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-slate-200 disabled:cursor-not-allowed disabled:opacity-40"
                >
                  {assigning
                    ? "Assigning..."
                    : "Assign Agent"}
                </button>
              </div>
            )}
          </div>
        )}

        {isAgent && !isAssignedToAnotherAgent && (
          <div className="border-t border-slate-800 pt-5">
            {isAssignedToCurrentUser ? (
              <div className="rounded-xl border border-emerald-500/20 bg-emerald-500/5 px-4 py-3">
                <p className="text-sm font-medium text-emerald-400">
                  Claimed
                </p>

                <p className="mt-1 text-xs text-slate-500">
                  This ticket is assigned to you.
                </p>
              </div>
            ) : (
              <button
                type="button"
                onClick={handleClaim}
                disabled={claiming}
                className="w-full rounded-xl border border-slate-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:border-slate-500 hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
              >
                {claiming
                  ? "Claiming..."
                  : "Claim Ticket"}
              </button>
            )}
          </div>
        )}

        {isAgent && isAssignedToAnotherAgent && (
          <div className="border-t border-slate-800 pt-5">
            <div className="rounded-xl border border-slate-800 bg-slate-950 px-4 py-3">
              <p className="text-sm font-medium text-slate-300">
                Assigned to another agent
              </p>

              <p className="mt-1 text-xs text-slate-500">
                This ticket is already being handled by{" "}
                {ticket.assignment?.agent?.first_name}{" "}
                {ticket.assignment?.agent?.last_name}.
              </p>
            </div>
          </div>
        )}

        {error && (
          <p className="text-xs text-red-400">
            {error}
          </p>
        )}

        {success && (
          <p className="text-xs text-emerald-400">
            {success}
          </p>
        )}
      </div>
    </div>
  );
}