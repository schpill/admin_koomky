import Link from "next/link";
import { CalendarClock } from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { CurrencyAmount } from "@/components/shared/currency-amount";

interface RecurringUpcomingItem {
  id: string;
  name: string;
  frequency: string;
  next_due_date: string;
  client_name?: string | null;
}

interface RecurringInvoicesWidgetProps {
  activeCount: number;
  estimatedMonthlyRevenue: number;
  upcomingProfiles: RecurringUpcomingItem[];
  currency?: string;
}

export function RecurringInvoicesWidget({
  activeCount,
  estimatedMonthlyRevenue,
  upcomingProfiles,
  currency = "EUR",
}: RecurringInvoicesWidgetProps) {
  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
        <CardTitle className="text-base">Recurring invoices</CardTitle>
        <CalendarClock className="h-4 w-4 text-muted-foreground" />
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="grid grid-cols-2 gap-2">
          <div className="rounded-md border p-2">
            <p className="text-xs text-muted-foreground">Active profiles</p>
            <p className="text-xl font-semibold">{activeCount}</p>
          </div>
          <div className="rounded-md border p-2">
            <p className="text-xs text-muted-foreground">Est. month revenue</p>
            <p className="text-xl font-semibold">
              <CurrencyAmount
                amount={Number(estimatedMonthlyRevenue || 0)}
                currency={currency}
              />
            </p>
          </div>
        </div>

        <div className="space-y-2">
          {upcomingProfiles.length === 0 ? (
            <p className="text-sm text-muted-foreground">
              No upcoming recurring invoices.
            </p>
          ) : (
            upcomingProfiles.map((profile) => (
              <div
                key={profile.id}
                className="rounded-md border border-border/80 bg-muted/20 px-3 py-2"
              >
                <p className="text-sm font-medium">{profile.name}</p>
                <p className="text-xs text-muted-foreground">
                  {profile.client_name || "Unknown client"} -{" "}
                  {profile.frequency}
                </p>
                <p className="text-xs text-muted-foreground">
                  Next due: {profile.next_due_date}
                </p>
              </div>
            ))
          )}
        </div>

        <Button asChild variant="outline" className="w-full">
          <Link href="/invoices/recurring">Manage recurring invoices</Link>
        </Button>
      </CardContent>
    </Card>
  );
}
