"use client";

interface EngagementPoint {
  hour: string;
  opens: number;
  clicks: number;
}

interface EngagementChartProps {
  data: EngagementPoint[];
}

export function EngagementChart({ data }: EngagementChartProps) {
  const maxValue = Math.max(
    1,
    ...data.map((item) => Math.max(item.opens, item.clicks))
  );

  return (
    <div className="rounded-lg border p-4">
      <h3 className="mb-4 text-sm font-semibold">Engagement over time</h3>
      {data.length === 0 ? (
        <p className="text-sm text-muted-foreground">No engagement data yet.</p>
      ) : (
        <div className="space-y-3">
          {data.map((point) => (
            <div key={point.hour} className="space-y-1">
              <p className="text-xs text-muted-foreground">{point.hour}</p>
              <div className="space-y-1">
                <div className="flex items-center gap-2">
                  <span className="w-14 text-xs">Opens</span>
                  <div className="h-2 flex-1 rounded-full bg-muted">
                    <div
                      className="h-2 rounded-full bg-primary"
                      style={{ width: `${(point.opens / maxValue) * 100}%` }}
                    />
                  </div>
                  <span className="text-xs">{point.opens}</span>
                </div>
                <div className="flex items-center gap-2">
                  <span className="w-14 text-xs">Clicks</span>
                  <div className="h-2 flex-1 rounded-full bg-muted">
                    <div
                      className="h-2 rounded-full bg-emerald-500"
                      style={{ width: `${(point.clicks / maxValue) * 100}%` }}
                    />
                  </div>
                  <span className="text-xs">{point.clicks}</span>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
