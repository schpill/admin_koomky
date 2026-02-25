"use client";

import { use, useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { useTicketDetailStore } from "@/lib/stores/ticketDetail";
import { useTicketStore } from "@/lib/stores/tickets";
import { useAuthStore } from "@/lib/stores/auth";
import type { TicketStatus, TicketMessage } from "@/lib/stores/tickets";
import { TicketStatusBadge } from "@/components/tickets/ticket-status-badge";
import { TicketPriorityBadge } from "@/components/tickets/ticket-priority-badge";
import { TicketStatusChangeDialog } from "@/components/tickets/ticket-status-change-dialog";
import { TicketFormDialog } from "@/components/tickets/ticket-form-dialog";
import { TicketMessageThread } from "@/components/tickets/ticket-message-thread";
import { TicketMessageComposer } from "@/components/tickets/ticket-message-composer";
import { TicketAttachmentsPanel } from "@/components/tickets/ticket-attachments-panel";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import { ArrowLeft, Pencil, Trash2, AlertTriangle } from "lucide-react";
import { cn } from "@/lib/utils";
import { useI18n } from "@/components/providers/i18n-provider";

export default function TicketDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = use(params);
  const router = useRouter();
  const { t } = useI18n();
  const currentUser = useAuthStore((state) => state.user);
  const currentUserId = currentUser?.id ?? "";

  const {
    ticket,
    messages,
    documents,
    isLoading,
    fetchTicket,
    addMessage,
    editMessage,
    deleteMessage,
    uploadDocument,
    attachDocument,
    detachDocument,
  } = useTicketDetailStore();

  const { changeStatus, updateTicket, deleteTicket } = useTicketStore();

  const [showStatusDialog, setShowStatusDialog] = useState(false);
  const [showEditDialog, setShowEditDialog] = useState(false);
  const [showDeleteDialog, setShowDeleteDialog] = useState(false);
  const [editingMessage, setEditingMessage] = useState<TicketMessage | null>(
    null
  );

  useEffect(() => {
    fetchTicket(id);
  }, [id]);

  const isOwner = ticket?.user_id === currentUserId;
  const isAssignee = ticket?.assigned_to === currentUserId;
  const isOwnerOrAssignee = isOwner || isAssignee;

  const isOverdue = (): boolean => {
    if (!ticket?.deadline) return false;
    if (ticket.status === "resolved" || ticket.status === "closed")
      return false;
    return new Date(ticket.deadline) < new Date();
  };

  const handleStatusChange = async (status: TicketStatus, comment?: string) => {
    if (!ticket) return;
    await changeStatus(ticket.id, status, comment);
    await fetchTicket(id);
  };

  const handleEdit = async (data: any) => {
    if (!ticket) return;
    await updateTicket(ticket.id, data);
    await fetchTicket(id);
    setShowEditDialog(false);
  };

  const handleDelete = async () => {
    if (!ticket) return;
    await deleteTicket(ticket.id);
    router.push("/tickets");
  };

  const handleAddMessage = async (data: {
    content: string;
    is_internal: boolean;
  }) => {
    await addMessage(id, data);
  };

  const handleEditMessage = async (msg: TicketMessage) => {
    setEditingMessage(msg);
  };

  const handleDeleteMessage = async (msgId: string) => {
    await deleteMessage(id, msgId);
  };

  if (isLoading) {
    return (
      <div className="p-8 space-y-4">
        <div className="h-8 w-48 animate-pulse rounded bg-gray-200" />
        <div className="h-32 animate-pulse rounded bg-gray-100" />
        <div className="h-64 animate-pulse rounded bg-gray-100" />
      </div>
    );
  }

  if (!ticket) {
    return (
      <div className="flex flex-col items-center justify-center p-8 py-16">
        <p className="text-muted-foreground">{t("tickets.detail.notFound")}</p>
        <Button
          variant="outline"
          className="mt-4"
          onClick={() => router.push("/tickets")}
        >
          {t("tickets.detail.backToList")}
        </Button>
      </div>
    );
  }

  return (
    <div className="flex flex-col space-y-6 p-8">
      {/* Back button */}
      <Button
        variant="ghost"
        className="w-fit -ml-2"
        onClick={() => router.push("/tickets")}
      >
        <ArrowLeft className="mr-2 h-4 w-4" />
        {t("tickets.detail.backToList")}
      </Button>

      {/* Header card */}
      <Card>
        <CardContent className="p-6 space-y-4">
          {/* Title row */}
          <div className="flex items-start justify-between gap-4">
            <div className="flex-1">
              <h1 className="text-xl font-semibold">{ticket.title}</h1>
              <p className="mt-1 text-sm text-muted-foreground">
                {t("tickets.detail.createdOn", { id: ticket.id.slice(0, 8) })}{" "}
                {new Date(ticket.created_at).toLocaleDateString()}
              </p>
            </div>
            <div className="flex items-center gap-2 flex-wrap justify-end">
              <TicketStatusBadge status={ticket.status} />
              <TicketPriorityBadge priority={ticket.priority} />
            </div>
          </div>

          {/* Meta grid */}
          <div className="grid grid-cols-2 gap-x-8 gap-y-2 text-sm sm:grid-cols-4">
            <div>
              <span className="text-muted-foreground">{t("tickets.detail.client")}</span>
              <p className="font-medium">{ticket.client?.name ?? "Divers"}</p>
            </div>
            <div>
              <span className="text-muted-foreground">{t("tickets.detail.project")}</span>
              <p className="font-medium">{ticket.project?.name ?? "\u2014"}</p>
            </div>
            <div>
              <span className="text-muted-foreground">{t("tickets.detail.owner")}</span>
              <p className="font-medium">{ticket.owner?.name ?? "\u2014"}</p>
            </div>
            <div>
              <span className="text-muted-foreground">{t("tickets.detail.assignee")}</span>
              <p className="font-medium">{ticket.assignee?.name ?? "\u2014"}</p>
            </div>
            {ticket.deadline && (
              <div>
                <span className="text-muted-foreground">{t("tickets.detail.deadline")}</span>
                <p
                  className={cn(
                    "font-medium",
                    isOverdue() ? "text-red-600" : ""
                  )}
                >
                  {isOverdue() && (
                    <AlertTriangle className="mr-1 inline h-3 w-3" />
                  )}
                  {new Date(ticket.deadline).toLocaleDateString()}
                </p>
              </div>
            )}
            {ticket.category && (
              <div>
                <span className="text-muted-foreground">{t("tickets.detail.category")}</span>
                <p className="font-medium">{ticket.category}</p>
              </div>
            )}
          </div>

          {/* Tags */}
          {ticket.tags && ticket.tags.length > 0 && (
            <div className="flex flex-wrap gap-1">
              {ticket.tags.map((tag) => (
                <Badge key={tag} variant="secondary">
                  {tag}
                </Badge>
              ))}
            </div>
          )}

          {/* Description */}
          <p className="text-sm text-muted-foreground whitespace-pre-wrap">
            {ticket.description}
          </p>

          {/* Actions */}
          <div className="flex flex-wrap gap-2 border-t pt-4">
            <Button
              variant="outline"
              size="sm"
              onClick={() => setShowStatusDialog(true)}
            >
              {t("tickets.detail.changeStatus")}
            </Button>
            {isOwner && (
              <>
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => setShowEditDialog(true)}
                >
                  <Pencil className="mr-1 h-3 w-3" />
                  {t("tickets.detail.edit")}
                </Button>
                <Button
                  variant="destructive"
                  size="sm"
                  onClick={() => setShowDeleteDialog(true)}
                >
                  <Trash2 className="mr-1 h-3 w-3" />
                  {t("tickets.detail.delete")}
                </Button>
              </>
            )}
          </div>
        </CardContent>
      </Card>

      {/* Tabs */}
      <Tabs defaultValue="conversation">
        <TabsList>
          <TabsTrigger value="conversation">
            {t("tickets.detail.tabConversation", { count: String(messages.length) })}
          </TabsTrigger>
          <TabsTrigger value="attachments">
            {t("tickets.detail.tabAttachments", { count: String(documents.length) })}
          </TabsTrigger>
        </TabsList>

        <TabsContent value="conversation" className="mt-4 space-y-4">
          <TicketMessageThread
            messages={messages}
            currentUserId={currentUserId}
            isOwnerOrAssignee={isOwnerOrAssignee}
            onEdit={handleEditMessage}
            onDelete={handleDeleteMessage}
          />
          {editingMessage && (
            <div className="rounded-lg border bg-yellow-50 p-4 space-y-2">
              <p className="text-sm font-medium">{t("tickets.detail.editingMessage")}</p>
              <textarea
                className="w-full rounded border p-2 text-sm"
                defaultValue={editingMessage.content}
                rows={3}
                onKeyDown={async (e) => {
                  if (e.key === "Enter" && e.ctrlKey) {
                    await editMessage(
                      id,
                      editingMessage.id,
                      (e.target as HTMLTextAreaElement).value
                    );
                    setEditingMessage(null);
                  }
                }}
              />
              <div className="flex gap-2">
                <Button
                  size="sm"
                  onClick={async (e: React.MouseEvent<HTMLButtonElement>) => {
                    const ta = (
                      e.currentTarget.closest(".space-y-2") as HTMLElement
                    )?.querySelector("textarea");
                    if (ta) {
                      await editMessage(
                        id,
                        editingMessage.id,
                        (ta as HTMLTextAreaElement).value
                      );
                      setEditingMessage(null);
                    }
                  }}
                >
                  {t("tickets.detail.save")}
                </Button>
                <Button
                  size="sm"
                  variant="outline"
                  onClick={() => setEditingMessage(null)}
                >
                  {t("tickets.detail.cancel")}
                </Button>
              </div>
            </div>
          )}
          <TicketMessageComposer
            currentUserId={currentUserId}
            isOwnerOrAssignee={isOwnerOrAssignee}
            onSubmit={handleAddMessage}
          />
        </TabsContent>

        <TabsContent value="attachments" className="mt-4">
          <TicketAttachmentsPanel
            ticketId={id}
            documents={documents}
            onDetach={(docId) => detachDocument(id, docId)}
            onUpload={(fd) => uploadDocument(id, fd)}
            onAttach={(docId) => attachDocument(id, docId)}
          />
        </TabsContent>
      </Tabs>

      {/* Dialogs */}
      <TicketStatusChangeDialog
        ticket={ticket}
        open={showStatusDialog}
        onOpenChange={setShowStatusDialog}
        onStatusChange={handleStatusChange}
      />

      <TicketFormDialog
        open={showEditDialog}
        onOpenChange={setShowEditDialog}
        ticket={ticket}
        onSubmit={handleEdit}
      />

      <AlertDialog open={showDeleteDialog} onOpenChange={setShowDeleteDialog}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>{t("tickets.detail.deleteDialog.title")}</AlertDialogTitle>
            <AlertDialogDescription>
              {t("tickets.detail.deleteDialog.description")}
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>{t("tickets.detail.deleteDialog.cancel")}</AlertDialogCancel>
            <AlertDialogAction
              onClick={handleDelete}
              className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
            >
              {t("tickets.detail.deleteDialog.confirm")}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}
