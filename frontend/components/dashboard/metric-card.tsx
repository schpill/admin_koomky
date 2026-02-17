import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { cn } from "@/lib/utils";
import { Skeleton } from "@/components/ui/skeleton";

interface MetricCardProps {
  title: string;
  value?: React.ReactNode;
  icon: React.ReactNode;
  description?: string;
  className?: string;
  isLoading?: boolean;
}

export function MetricCard({
  title,
  value,
  icon,
  description,
  className,
  isLoading,
}: MetricCardProps) {
  return (
    <Card className={cn("", className)}>
      <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
        <CardTitle className="text-sm font-medium">{title}</CardTitle>
        <div className="text-muted-foreground">{icon}</div>
      </CardHeader>
      <CardContent>
        {isLoading ? (
          <div className="space-y-2">
            <Skeleton className="h-8 w-[100px]" />
            {description && <Skeleton className="h-4 w-[140px]" />}
          </div>
        ) : (
          <>
            <div className="text-2xl font-bold">{value}</div>
            {description && (
              <p className="text-xs text-muted-foreground pt-1">
                {description}
              </p>
            )}
          </>
        )}
      </CardContent>
    </Card>
  );
}
