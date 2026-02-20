"use client";

import { useParams } from "next/navigation";
import { useEffect, useState } from "react";
import Link from "next/link";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { ArrowLeft, RotateCcw } from "lucide-react";
import { apiClient } from "@/lib/api";
import { toast } from "sonner";

interface WebhookDelivery {
  id: string;
  event: string;
  attempt_count: number;
  response_status: number | null;
  status: "delivered" | "failed" | "pending";
  delivered_at: string | null;
  failed_at: string | null;
  created_at: string;
}

export default function WebhookDeliveriesPage() {
  const params = useParams();
  const webhookId = params.id as string;

  const [deliveries, setDeliveries] = useState<WebhookDelivery[]>([]);
  const [isLoading, setIsLoading] = useState(false);

  useEffect(() => {
    fetchDeliveries();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [webhookId]);

  const fetchDeliveries = async () => {
    setIsLoading(true);
    try {
      const response = await apiClient.get<{
        data: { data: WebhookDelivery[] };
      }>(`/settings/webhooks/${webhookId}/deliveries`);
      setDeliveries(response.data?.data?.data || []);
    } catch (error) {
      toast.error("Failed to fetch deliveries");
    } finally {
      setIsLoading(false);
    }
  };

  const handleRetry = async (deliveryId: string) => {
    try {
      await apiClient.post(
        `/settings/webhooks/${webhookId}/deliveries/${deliveryId}/retry`
      );
      toast.success("Delivery retry initiated");
      fetchDeliveries();
    } catch (error) {
      toast.error("Failed to retry delivery");
    }
  };

  const getStatusBadge = (status: string, responseStatus: number | null) => {
    switch (status) {
      case "delivered":
        return (
          <Badge className="bg-green-100 text-green-800">
            Delivered ({responseStatus})
          </Badge>
        );
      case "failed":
        return (
          <Badge className="bg-red-100 text-red-800">
            Failed ({responseStatus || "Error"})
          </Badge>
        );
      default:
        return <Badge variant="secondary">Pending</Badge>;
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-4">
        <Button variant="ghost" asChild>
          <Link href="/settings/webhooks">
            <ArrowLeft className="mr-2 h-4 w-4" />
            Back to Webhooks
          </Link>
        </Button>
      </div>

      <div>
        <h1 className="text-3xl font-bold">Webhook Deliveries</h1>
        <p className="text-sm text-muted-foreground">
          View delivery log and retry failed deliveries
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Delivery Log</CardTitle>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <p className="text-muted-foreground">Loading...</p>
          ) : deliveries.length === 0 ? (
            <p className="text-muted-foreground">No deliveries yet</p>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b text-left">
                    <th className="pb-3">Event</th>
                    <th className="pb-3">Status</th>
                    <th className="pb-3">Attempts</th>
                    <th className="pb-3">Timestamp</th>
                    <th className="pb-3">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {deliveries.map((delivery) => (
                    <tr key={delivery.id} className="border-b last:border-0">
                      <td className="py-3 font-mono text-sm">
                        {delivery.event}
                      </td>
                      <td className="py-3">
                        {getStatusBadge(
                          delivery.status,
                          delivery.response_status
                        )}
                      </td>
                      <td className="py-3">{delivery.attempt_count}</td>
                      <td className="py-3 text-muted-foreground">
                        {new Date(delivery.created_at).toLocaleString()}
                      </td>
                      <td className="py-3">
                        {delivery.status === "failed" &&
                          delivery.attempt_count < 5 && (
                            <Button
                              variant="ghost"
                              size="sm"
                              onClick={() => handleRetry(delivery.id)}
                            >
                              <RotateCcw className="mr-2 h-4 w-4" />
                              Retry
                            </Button>
                          )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
