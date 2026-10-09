interface StatCardProps {
  label: string;
  value: number | string;
  description?: string;
}

export default function StatCard({
  label,
  value,
  description,
}: StatCardProps) {
  return (
    <div className="bg-zinc-900 px-5 py-4">
      <p className="text-sm text-zinc-400">{label}</p>

      <p className="mt-1 text-2xl font-semibold tabular-nums tracking-tight text-zinc-100">
        {value}
      </p>

      {description && (
        <p className="mt-1 text-xs text-zinc-400">{description}</p>
      )}
    </div>
  );
}