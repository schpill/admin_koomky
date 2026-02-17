"use client";

import { useMemo, useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { cn } from "@/lib/utils";
import { CurrencyAmount } from "@/components/shared/currency-amount";

interface OutstandingItem {
  id: string;
  number: string;
  client_name?: string;
  status: string;
  due_date: string;
  aging_days: number;
  aging_bucket: string;
  currency?: string;
  balance_due: number;
  balance_due_base?: number;
}

interface OutstandingTableProps {
  items: OutstandingItem[];
  baseCurrency?: string;
  showOriginalCurrency?: boolean;
}

const VIRTUALIZATION_THRESHOLD = 100;
const VIRTUAL_ROW_HEIGHT = 52;
const VIRTUAL_VIEWPORT_HEIGHT = 420;
const VIRTUAL_OVERSCAN_ROWS = 8;

function bucketLabel(bucket: string): string {
  switch (bucket) {
    case "0_30":
      return "0-30 days";
    case "31_60":
      return "31-60 days";
    case "61_90":
      return "61-90 days";
    case "90_plus":
      return "90+ days";
    default:
      return bucket;
  }
}

function bucketClass(bucket: string): string {
  switch (bucket) {
    case "0_30":
      return "bg-amber-200 text-amber-800 hover:bg-amber-200 dark:bg-amber-900/40 dark:text-amber-200 dark:hover:bg-amber-900/50";
    case "31_60":
      return "bg-orange-200 text-orange-800 hover:bg-orange-200 dark:bg-orange-900/40 dark:text-orange-200 dark:hover:bg-orange-900/50";
    case "61_90":
      return "bg-rose-200 text-rose-800 hover:bg-rose-200 dark:bg-rose-900/40 dark:text-rose-200 dark:hover:bg-rose-900/50";
    case "90_plus":
      return "bg-red-200 text-red-800 hover:bg-red-200 dark:bg-red-900/40 dark:text-red-200 dark:hover:bg-red-900/50";
    default:
      return "bg-zinc-200 text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-700 dark:text-zinc-100 dark:hover:bg-zinc-700";
  }
}

export function OutstandingTable({
  items,
  baseCurrency = "EUR",
  showOriginalCurrency = false,
}: OutstandingTableProps) {
  const [scrollTop, setScrollTop] = useState(0);
  const isVirtualized = items.length > VIRTUALIZATION_THRESHOLD;

  const { visibleItems, paddingTop, paddingBottom } = useMemo(() => {
    if (!isVirtualized) {
      return {
        visibleItems: items,
        paddingTop: 0,
        paddingBottom: 0,
      };
    }

    const startIndex = Math.max(
      0,
      Math.floor(scrollTop / VIRTUAL_ROW_HEIGHT) - VIRTUAL_OVERSCAN_ROWS
    );
    const visibleRowCount =
      Math.ceil(VIRTUAL_VIEWPORT_HEIGHT / VIRTUAL_ROW_HEIGHT) +
      VIRTUAL_OVERSCAN_ROWS * 2;
    const endIndex = Math.min(items.length, startIndex + visibleRowCount);

    return {
      visibleItems: items.slice(startIndex, endIndex),
      paddingTop: startIndex * VIRTUAL_ROW_HEIGHT,
      paddingBottom: Math.max(
        0,
        (items.length - endIndex) * VIRTUAL_ROW_HEIGHT
      ),
    };
  }, [isVirtualized, items, scrollTop]);

  return (
    <Card>
      <CardHeader>
        <CardTitle>Outstanding invoices</CardTitle>
      </CardHeader>
      <CardContent>
        {items.length === 0 ? (
          <p className="text-sm text-muted-foreground">
            No outstanding invoices.
          </p>
        ) : (
          <div
            data-testid="outstanding-table-scroll"
            data-virtualized={isVirtualized ? "true" : "false"}
            className={cn(
              "overflow-x-auto",
              isVirtualized && "max-h-[420px] overflow-y-auto"
            )}
            onScroll={
              isVirtualized
                ? (event) => setScrollTop(event.currentTarget.scrollTop)
                : undefined
            }
          >
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b text-left">
                  <th className="pb-3 font-medium text-muted-foreground">
                    Invoice
                  </th>
                  <th className="pb-3 font-medium text-muted-foreground">
                    Client
                  </th>
                  <th className="pb-3 font-medium text-muted-foreground">
                    Due date
                  </th>
                  <th className="pb-3 font-medium text-muted-foreground">
                    Aging
                  </th>
                  <th className="pb-3 font-medium text-muted-foreground">
                    Balance due
                  </th>
                </tr>
              </thead>
              <tbody>
                {isVirtualized && paddingTop > 0 && (
                  <tr aria-hidden="true">
                    <td colSpan={5} style={{ height: `${paddingTop}px` }} />
                  </tr>
                )}
                {visibleItems.map((item) => (
                  <tr
                    key={item.id}
                    data-testid="outstanding-table-row"
                    className="border-b last:border-0"
                  >
                    <td className="py-3 font-medium">{item.number}</td>
                    <td className="py-3 text-muted-foreground">
                      {item.client_name || "-"}
                    </td>
                    <td className="py-3 text-muted-foreground">
                      {item.due_date}
                    </td>
                    <td className="py-3">
                      <Badge className={bucketClass(item.aging_bucket)}>
                        {bucketLabel(item.aging_bucket)}
                      </Badge>
                    </td>
                    <td className="py-3">
                      <CurrencyAmount
                        amount={Number(item.balance_due_base ?? item.balance_due)}
                        currency={baseCurrency}
                      />
                      {showOriginalCurrency &&
                        item.currency &&
                        item.currency !== baseCurrency && (
                          <p className="text-xs text-muted-foreground">
                            Original:{" "}
                            <CurrencyAmount
                              amount={Number(item.balance_due)}
                              currency={item.currency}
                            />
                          </p>
                        )}
                    </td>
                  </tr>
                ))}
                {isVirtualized && paddingBottom > 0 && (
                  <tr aria-hidden="true">
                    <td colSpan={5} style={{ height: `${paddingBottom}px` }} />
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        )}
      </CardContent>
    </Card>
  );
}
