"use client";

import { useCallback, useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import {
  ChevronLeft,
  Mail,
  Phone,
  MapPin,
  Calendar,
  User,
  MoreVertical,
  Trash2,
  Edit,
  Archive,
  RefreshCw,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Badge } from "@/components/ui/badge";
import { apiClient } from "@/lib/api";
import { Skeleton } from "@/components/ui/skeleton";
import { toast } from "sonner";
import { Separator } from "@/components/ui/separator";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { ConfirmationDialog } from "@/components/common/confirmation-dialog";
import { ClientContactList } from "@/components/clients/client-contact-list";
import { ClientTimeline } from "@/components/clients/client-timeline";
import { ClientTagSelector } from "@/components/clients/client-tag-selector";

export default function ClientDetailPage() {
  const { id } = useParams();
  const router = useRouter();
  const [client, setClient] = useState<any>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [deleteDialogOpen, setDeleteOpen] = useState(false);
  const [restoreDialogOpen, setRestoreOpen] = useState(false);

  const fetchClient = useCallback(async () => {
    try {
      const response = await apiClient.get<any>(`/clients/${id}`);
      setClient(response.data);
    } catch (error) {
      toast.error("Failed to load client details");
      router.push("/clients");
    } finally {
      setIsLoading(false);
    }
  }, [id, router]);

  useEffect(() => {
    fetchClient();
  }, [fetchClient]);

  const handleDelete = async () => {
    try {
      await apiClient.delete(`/clients/${id}`);
      toast.success(
        client.deleted_at
          ? "Client deleted permanently"
          : "Client archived successfully",
      );
      router.push("/clients");
    } catch (error) {
      toast.error("Failed to archive client");
    }
  };

  const handleRestore = async () => {
    try {
      await apiClient.post(`/clients/${id}/restore`);
      toast.success("Client restored successfully");
      fetchClient();
    } catch (error) {
      toast.error("Failed to restore client");
    }
  };

  if (isLoading) {
    return (
      <div className="space-y-6">
        <Skeleton className="h-8 w-48" />
        <div className="grid gap-6 md:grid-cols-3">
          <Skeleton className="h-64 md:col-span-1" />
          <Skeleton className="h-64 md:col-span-2" />
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <Button
          variant="ghost"
          onClick={() => router.push("/clients")}
          className="-ml-2"
        >
          <ChevronLeft className="mr-2 h-4 w-4" /> Back to Clients
        </Button>
        <div className="flex gap-2">
          <Button
            variant="outline"
            size="sm"
            onClick={() => router.push(`/clients/${id}/edit`)}
          >
            <Edit className="mr-2 h-4 w-4" /> Edit
          </Button>
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button
                variant="ghost"
                size="icon"
                aria-label="Open client options"
              >
                <MoreVertical className="h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              {client.deleted_at ? (
                <DropdownMenuItem onClick={() => setRestoreOpen(true)}>
                  <RefreshCw className="mr-2 h-4 w-4" /> Restore Client
                </DropdownMenuItem>
              ) : (
                <DropdownMenuItem
                  className="text-destructive"
                  onClick={() => setDeleteOpen(true)}
                >
                  <Archive className="mr-2 h-4 w-4" /> Archive Client
                </DropdownMenuItem>
              )}
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </div>

      <ConfirmationDialog
        open={deleteDialogOpen}
        onOpenChange={setDeleteOpen}
        onConfirm={handleDelete}
        title="Archive Client"
        description={`Are you sure you want to archive ${client.name}? The client will be hidden from the main list but can be restored later.`}
        confirmText="Archive"
        variant="destructive"
      />

      <ConfirmationDialog
        open={restoreDialogOpen}
        onOpenChange={setRestoreOpen}
        onConfirm={handleRestore}
        title="Restore Client"
        description={`Do you want to restore ${client.name} to the active client list?`}
        confirmText="Restore"
      />

      <div className="grid gap-6 md:grid-cols-3">
        {/* Profile Summary */}
        <div className="md:col-span-1 space-y-6">
          <Card>
            <CardContent className="pt-6">
              <div className="flex flex-col items-center text-center space-y-4">
                <div className="relative">
                  <div className="h-20 w-20 rounded-full bg-primary/10 flex items-center justify-center">
                    <User className="h-10 w-10 text-primary" />
                  </div>
                  {client.deleted_at && (
                    <Badge
                      variant="destructive"
                      className="absolute -top-2 -right-2"
                    >
                      Archived
                    </Badge>
                  )}
                </div>
                <div>
                  <h2 className="text-xl font-bold">{client.name}</h2>
                  <p className="text-sm text-muted-foreground font-mono">
                    {client.reference}
                  </p>
                </div>
                <div className="flex gap-2">
                  <Badge
                    variant={
                      client.status === "active" ? "default" : "secondary"
                    }
                  >
                    {client.status}
                  </Badge>
                </div>

                <div className="w-full space-y-3 pt-4 text-sm text-left">
                  {client.email && (
                    <div className="flex items-center gap-3">
                      <Mail className="h-4 w-4 text-muted-foreground" />
                      <span className="truncate">{client.email}</span>
                    </div>
                  )}
                  {client.phone && (
                    <div className="flex items-center gap-3">
                      <Phone className="h-4 w-4 text-muted-foreground" />
                      <span>{client.phone}</span>
                    </div>
                  )}
                  {(client.city || client.country) && (
                    <div className="flex items-center gap-3">
                      <MapPin className="h-4 w-4 text-muted-foreground" />
                      <span className="truncate">
                        {[client.city, client.country]
                          .filter(Boolean)
                          .join(", ")}
                      </span>
                    </div>
                  )}
                  <div className="flex items-center gap-3">
                    <Calendar className="h-4 w-4 text-muted-foreground" />
                    <span>
                      Added {new Date(client.created_at).toLocaleDateString()}
                    </span>
                  </div>
                </div>

                <Separator className="my-2" />

                <div className="w-full">
                  <ClientTagSelector
                    clientId={id as string}
                    initialTags={client.tags || []}
                  />
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Content Tabs */}
        <div className="md:col-span-2">
          <Tabs defaultValue="details">
            <TabsList className="grid w-full grid-cols-3">
              <TabsTrigger value="details">Details</TabsTrigger>
              <TabsTrigger value="contacts">Contacts</TabsTrigger>
              <TabsTrigger value="history">Activity</TabsTrigger>
            </TabsList>

            <TabsContent value="details" className="space-y-4 pt-4">
              <Card>
                <CardHeader>
                  <CardTitle className="text-lg">Client Information</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="grid grid-cols-2 gap-4 text-sm">
                    <div>
                      <p className="text-muted-foreground">Reference</p>
                      <p className="font-medium">{client.reference}</p>
                    </div>
                    <div>
                      <p className="text-muted-foreground">Status</p>
                      <p className="font-medium capitalize">{client.status}</p>
                    </div>
                    <div className="col-span-2">
                      <p className="text-muted-foreground">Address</p>
                      <p className="font-medium">
                        {[
                          client.address,
                          client.zip_code,
                          client.city,
                          client.country,
                        ]
                          .filter(Boolean)
                          .join(", ") || "No address provided"}
                      </p>
                    </div>
                  </div>
                  <Separator />
                  <div>
                    <p className="text-sm text-muted-foreground mb-2">Notes</p>
                    <div className="text-sm bg-muted/30 p-3 rounded-md italic whitespace-pre-wrap">
                      {client.notes || "No notes available for this client."}
                    </div>
                  </div>
                </CardContent>
              </Card>
            </TabsContent>

            <TabsContent value="contacts" className="pt-4">
              <ClientContactList clientId={id as string} />
            </TabsContent>

            <TabsContent value="history" className="pt-4">
              <ClientTimeline clientId={id as string} />
            </TabsContent>
          </Tabs>
        </div>
      </div>
    </div>
  );
}
