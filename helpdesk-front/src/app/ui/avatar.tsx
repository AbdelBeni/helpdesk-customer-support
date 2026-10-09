import type { User } from "../../types/index";

const tones = [
  "bg-teal-100 text-teal-800",
  "bg-sky-100 text-sky-800",
  "bg-amber-100 text-amber-800",
  "bg-violet-100 text-violet-800",
  "bg-rose-100 text-rose-800",
];

const sizes = {
  sm: "h-7 w-7 text-[11px]",
  md: "h-8 w-8 text-xs",
  lg: "h-9 w-9 text-xs",
};

export default function Avatar({
  user,
  size = "md",
}: {
  user: Pick<User, "id" | "first_name" | "last_name">;
  size?: keyof typeof sizes;
}) {
  const initials = `${user.first_name?.charAt(0) ?? ""}${
    user.last_name?.charAt(0) ?? ""
  }`.toUpperCase();

  return (
    <span
      className={`inline-flex shrink-0 items-center justify-center rounded-full font-semibold ${
        sizes[size]
      } ${tones[Number(user.id) % tones.length]}`}
      aria-hidden="true"
    >
      {initials}
    </span>
  );
}