import Image from "next/image";
import { BrandFooter } from "@/components/layout/brand-footer";
import { LocaleSwitcher } from "@/components/layout/locale-switcher";

export const dynamic = "force-static";

export default function AuthLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <div className="flex min-h-screen flex-col p-4">
      <div className="mx-auto flex w-full max-w-md justify-end pb-4">
        <LocaleSwitcher />
      </div>
      <div className="flex flex-1 items-center justify-center">
        <div className="w-full max-w-md space-y-4">
          <div className="mx-auto flex w-fit items-center gap-2 rounded-full border bg-card/90 px-4 py-2 shadow-sm backdrop-blur">
            <Image
              src="/brand/icon.png"
              alt="Koomky"
              width={24}
              height={24}
              sizes="24px"
              className="h-6 w-6"
              priority
            />
            <span className="text-sm font-semibold tracking-wide text-primary">
              Koomky CRM
            </span>
          </div>
          <div>{children}</div>
        </div>
      </div>
      <BrandFooter className="pb-2 pt-4" compact />
    </div>
  );
}
