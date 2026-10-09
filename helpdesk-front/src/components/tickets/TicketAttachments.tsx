"use client";

import { useEffect, useRef, useState } from "react";
import api from "../../lib/api";
import type { User } from "../../types/index";

interface TicketAttachment {
  id: number;
  original_name: string;
  mime_type: string;
  file_size: number;
  url: string;
  uploaded_by: User;
  created_at: string;
}

interface TicketAttachmentsProps {
  ticketId: number;
  currentUser: User | null;
}

export default function TicketAttachments({
  ticketId,
  currentUser,
}: TicketAttachmentsProps) {
  const [attachments, setAttachments] = useState<TicketAttachment[]>([]);
  const [loading, setLoading] = useState(true);
  const [uploading, setUploading] = useState(false);
  const [deletingId, setDeletingId] = useState<number | null>(null);
  const [error, setError] = useState("");

  const fileInputRef = useRef<HTMLInputElement>(null);

    useEffect(() => {
    async function loadAttachments() {
        try {
        setLoading(true);
        setError("");

        const response = await api.get<{
            data: TicketAttachment[];
        }>(`/tickets/${ticketId}/attachments`, {
            params: {
            per_page: 50,
            },
        });

        setAttachments(response.data.data);
        } catch {
        setError("Unable to load attachments.");
        } finally {
        setLoading(false);
        }
    }

    loadAttachments();
    }, [ticketId]);

  async function handleUpload(
    event: React.ChangeEvent<HTMLInputElement>
  ) {
    const file = event.target.files?.[0];

    if (!file || uploading) {
      return;
    }

    try {
      setUploading(true);
      setError("");

      const formData = new FormData();
      formData.append("file", file);

      const response = await api.post<{
        data: TicketAttachment;
      }>(`/tickets/${ticketId}/attachments`, formData);

      setAttachments((current) => [
        response.data.data,
        ...current,
      ]);
    } catch {
      setError("Unable to upload this file.");
    } finally {
      setUploading(false);

      if (fileInputRef.current) {
        fileInputRef.current.value = "";
      }
    }
  }

  async function handleDelete(id: number) {
    if (deletingId !== null) {
      return;
    }

    try {
      setDeletingId(id);
      setError("");

      await api.delete(
        `/tickets/${ticketId}/attachments/${id}`
      );

      setAttachments((current) =>
        current.filter((attachment) => attachment.id !== id)
      );
    } catch {
      setError("Unable to delete this file.");
    } finally {
      setDeletingId(null);
    }
  }

  function formatFileSize(size: number) {
    if (size < 1024) {
      return `${size} B`;
    }

    if (size < 1024 * 1024) {
      return `${(size / 1024).toFixed(1)} KB`;
    }

    return `${(size / (1024 * 1024)).toFixed(1)} MB`;
  }

  function getInitials(user: User) {
    return (
      `${user.first_name.charAt(0)}${user.last_name.charAt(0)}`
    ).toUpperCase();
  }

  const canDelete = currentUser?.role.name === "Admin";

  return (
    <div className="rounded-2xl border border-slate-800 bg-slate-900">
      <div className="flex items-center justify-between border-b border-slate-800 px-6 py-5">
        <div>
          <h2 className="font-semibold">Attachments</h2>

          <p className="mt-1 text-xs text-slate-500">
            Files attached to this ticket.
          </p>
        </div>

        <label
          className={`cursor-pointer rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-slate-200 ${
            uploading
              ? "pointer-events-none opacity-50"
              : ""
          }`}
        >
          {uploading ? "Uploading..." : "Upload file"}

          <input
            ref={fileInputRef}
            type="file"
            onChange={handleUpload}
            disabled={uploading}
            className="hidden"
          />
        </label>
      </div>

      <div className="custom-scrollbar px-6 py-5 max-h-[400px] overflow-y-scroll overflow-x-hidden">
        {error && (
          <div className="mb-4 rounded-xl border border-red-900/50 bg-red-950/20 px-4 py-3 text-sm text-red-400">
            {error}
          </div>
        )}

        {loading ? (
          <div className="py-8 text-center">
            <p className="text-sm text-slate-500">
              Loading attachments...
            </p>
          </div>
        ) : attachments.length === 0 ? (
          <div className="py-8 text-center">
            <p className="text-sm text-slate-400">
              No attachments yet.
            </p>

            <p className="mt-1 text-xs text-slate-600">
              Upload a file to attach it to this ticket.
            </p>
          </div>
        ) : (
          <div className="divide-y divide-slate-800">
            {attachments.map((attachment) => (
              <div
                key={attachment.id}
                className="flex items-center gap-4 py-4 first:pt-0 last:pb-0"
              >
                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-800 text-xs font-medium text-slate-400">
                  FILE
                </div>

                <div className="min-w-0 flex-1">
                  <a
                    href={attachment.url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="block truncate text-sm font-medium text-white transition hover:text-slate-300"
                  >
                    {attachment.original_name}
                  </a>

                  <div className="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-600">
                    <span>
                      {formatFileSize(attachment.file_size)}
                    </span>

                    <span>•</span>

                    <span>
                      {attachment.uploaded_by
                        ? `${attachment.uploaded_by.first_name} ${attachment.uploaded_by.last_name}`
                        : "Unknown user"}
                    </span>

                    <span>•</span>

                    <span>
                      {new Date(
                        attachment.created_at
                      ).toLocaleDateString()}
                    </span>
                  </div>
                </div>

                {attachment.uploaded_by?.id === currentUser?.id && (
                  <div className="text-xs text-slate-600">
                    {getInitials(attachment.uploaded_by)}
                  </div>
                )}

                {canDelete && (
                  <button
                    type="button"
                    onClick={() =>
                      handleDelete(attachment.id)
                    }
                    disabled={deletingId === attachment.id}
                    className="text-xs text-slate-600 transition hover:text-red-400 disabled:cursor-not-allowed disabled:opacity-50"
                  >
                    {deletingId === attachment.id
                      ? "Deleting..."
                      : "Delete"}
                  </button>
                )}
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}