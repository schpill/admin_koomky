import type { OlHTMLAttributes } from "react";
import { cn } from "@/lib/utils";

export function DocSteps({
  className,
  ...props
}: OlHTMLAttributes<HTMLOListElement>) {
  return (
    <ol
      className={cn(
        "my-8 list-decimal space-y-4 pl-5 marker:font-semibold marker:text-primary",
        className
      )}
      {...props}
    />
  );
}
