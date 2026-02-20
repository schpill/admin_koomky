"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Badge } from "@/components/ui/badge";
import { Plus, Trash2, Edit, Send, Eye } from "lucide-react";
import { apiClient } from "@/lib/api";
import { toast } from "sonner";

interface WebhookEndpoint {
  id: string;
  name: string;
  url: string;
  events: string[];
  events_count: number;
  is_active: boolean;
  last_triggered_at: string | null;
  created_at: string;
}

const WEBHOOK_EVENTS = [
  "invoice.created",
  "invoice.sent",
  "invoice.paid",
  "invoice.overdue",
  "invoice.cancelled",
  "quote.sent",
  "quote.accepted",
  "quote.rejected",
  "quote.expired",
  "expense.created",
  "expense.updated",
  "expense.deleted",
  "project.completed",
  "project.cancelled",
  "payment.received",
  "lead.created",
  "lead.status_changed",
  "lead.converted",
];

export default function WebhooksPage() {
  const [endpoints, setEndpoints] = useState<WebhookEndpoint[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [showCreateForm, setShowCreateForm] = useState(false);
  const [formData, setFormData] = useState({
    name: "",
    url: "",
    events: [] as string[],
  });
  const [generatedSecret, setGeneratedSecret] = useState<string | null>(null);

  useEffect(() => {
    fetchEndpoints();
  }, []);

  const fetchEndpoints = async () => {
    setIsLoading(true);
    try {
      const response = await apiClient.get<{
        data: { data: WebhookEndpoint[] };
      }>("/settings/webhooks");
      setEndpoints(response.data?.data?.data || []);
    } catch (error) {
      toast.error("Failed to fetch webhooks");
    } finally {
      setIsLoading(false);
    }
  };

  const handleCreateWebhook = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!formData.name || !formData.url) {
      toast.error("Name and URL are required");
      return;
    }

    if (formData.events.length === 0) {
      toast.error("Select at least one event");
      return;
    }

    try {
      const response = await apiClient.post<{
        data: { id: string; secret: string };
      }>("/settings/webhooks", formData);
      setGeneratedSecret(response.data?.data?.secret || null);
      setFormData({ name: "", url: "", events: [] });
      fetchEndpoints();
      toast.success("Webhook created successfully");
    } catch (error) {
      toast.error("Failed to create webhook");
    }
  };

  const handleDeleteWebhook = async (id: string) => {
    if (!confirm("Are you sure you want to delete this webhook?")) return;

    try {
      await apiClient.delete(`/settings/webhooks/${id}`);
      setEndpoints(endpoints.filter((e) => e.id !== id));
      toast.success("Webhook deleted successfully");
    } catch (error) {
      toast.error("Failed to delete webhook");
    }
  };

  const handleTestWebhook = async (id: string) => {
    try {
      await apiClient.post(`/settings/webhooks/${id}/test`);
      toast.success("Test webhook sent successfully");
    } catch (error) {
      toast.error("Failed to send test webhook");
    }
  };

  const toggleEvent = (event: string) => {
    setFormData((current) => ({
      ...current,
      events: current.events.includes(event)
        ? current.events.filter((e) => e !== event)
        : [...current.events, event],
    }));
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Webhooks</h1>
          <p className="text-sm text-muted-foreground">
            Manage webhook endpoints for event notifications
          </p>
        </div>
        <Button onClick={() => setShowCreateForm(!showCreateForm)}>
          <Plus className="mr-2 h-4 w-4" />
          Add Endpoint
        </Button>
      </div>

      {generatedSecret && (
        <Card className="border-green-200 bg-green-50">
          <CardHeader>
            <CardTitle className="text-green-700">Webhook Secret</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="mb-2 text-sm text-green-700">
              Copy this secret now - it will not be shown again! Use it to
              verify webhook signatures.
            </p>
            <code className="block rounded bg-white p-2 text-sm">
              {generatedSecret}
            </code>
          </CardContent>
        </Card>
      )}

      {showCreateForm && (
        <Card>
          <CardHeader>
            <CardTitle>Create Webhook Endpoint</CardTitle>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleCreateWebhook} className="space-y-4">
              <div className="grid gap-4 md:grid-cols-2">
                <div className="space-y-2">
                  <Label htmlFor="name">Name</Label>
                  <Input
                    id="name"
                    value={formData.name}
                    onChange={(e) =>
                      setFormData({ ...formData, name: e.target.value })
                    }
                    placeholder="My Webhook"
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="url">URL (HTTPS only)</Label>
                  <Input
                    id="url"
                    type="url"
                    value={formData.url}
                    onChange={(e) =>
                      setFormData({ ...formData, url: e.target.value })
                    }
                    placeholder="https://example.com/webhook"
                    required
                  />
                </div>
              </div>

              <div className="space-y-2">
                <Label>Events to Subscribe</Label>
                <div className="grid max-h-60 gap-1 overflow-y-auto md:grid-cols-2 lg:grid-cols-3">
                  {WEBHOOK_EVENTS.map((event) => (
                    <label
                      key={event}
                      className="flex cursor-pointer items-center gap-2 rounded border p-2 text-sm hover:bg-muted"
                    >
                      <input
                        type="checkbox"
                        checked={formData.events.includes(event)}
                        onChange={() => toggleEvent(event)}
                        className="h-4 w-4"
                      />
                      <span>{event}</span>
                    </label>
                  ))}
                </div>
              </div>

              <div className="flex gap-2">
                <Button type="submit">Create Webhook</Button>
                <Button
                  type="button"
                  variant="outline"
                  onClick={() => setShowCreateForm(false)}
                >
                  Cancel
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      )}

      <Card>
        <CardHeader>
          <CardTitle>Your Webhooks</CardTitle>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <p className="text-muted-foreground">Loading...</p>
          ) : endpoints.length === 0 ? (
            <p className="text-muted-foreground">No webhooks configured yet</p>
          ) : (
            <div className="space-y-3">
              {endpoints.map((endpoint) => (
                <div
                  key={endpoint.id}
                  className="flex items-center justify-between rounded-lg border p-4"
                >
                  <div className="flex-1">
                    <div className="flex items-center gap-2">
                      <p className="font-medium">{endpoint.name}</p>
                      <Badge
                        variant={endpoint.is_active ? "default" : "secondary"}
                      >
                        {endpoint.is_active ? "Active" : "Inactive"}
                      </Badge>
                    </div>
                    <p className="mt-1 text-sm text-muted-foreground">
                      {endpoint.url}
                    </p>
                    <p className="mt-1 text-xs text-muted-foreground">
                      {endpoint.events_count} events subscribed
                      {endpoint.last_triggered_at &&
                        ` | Last triggered: ${new Date(endpoint.last_triggered_at).toLocaleString()}`}
                    </p>
                  </div>
                  <div className="flex items-center gap-2">
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => handleTestWebhook(endpoint.id)}
                    >
                      <Send className="h-4 w-4" />
                    </Button>
                    <Link href={`/settings/webhooks/${endpoint.id}/deliveries`}>
                      <Button variant="ghost" size="sm">
                        <Eye className="h-4 w-4" />
                      </Button>
                    </Link>
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => handleDeleteWebhook(endpoint.id)}
                    >
                      <Trash2 className="h-4 w-4 text-red-500" />
                    </Button>
                  </div>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
