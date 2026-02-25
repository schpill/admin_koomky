"use client";

import { useParams, useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import Link from "next/link";
import { toast } from "sonner";
import { ArrowLeft, Edit, Trash2, UserPlus, Plus } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { CurrencyAmount } from "@/components/shared/currency-amount";
import { useLeadStore, LeadActivity } from "@/lib/stores/leads";
import { useI18n } from "@/components/providers/i18n-provider";

const STATUS_COLORS: Record<string, string> = {
  new: "bg-blue-100 text-blue-800",
  contacted: "bg-yellow-100 text-yellow-800",
  qualified: "bg-purple-100 text-purple-800",
  proposal_sent: "bg-orange-100 text-orange-800",
  negotiating: "bg-pink-100 text-pink-800",
  won: "bg-green-100 text-green-800",
  lost: "bg-red-100 text-red-800",
};

const ACTIVITY_ICONS: Record<string, string> = {
  note: "📝",
  email_sent: "📧",
  call: "📞",
  meeting: "📅",
  follow_up: "⏰",
};

export default function LeadDetailPage() {
  const { t } = useI18n();
  const params = useParams();
  const router = useRouter();
  const leadId = params.id as string;

  const {
    currentLead,
    fetchLead,
    updateStatus,
    deleteLead,
    convertToClient,
    fetchActivities,
    createActivity,
    deleteActivity,
    isLoading,
  } = useLeadStore();

  const [activities, setActivities] = useState<LeadActivity[]>([]);
  const [showActivityForm, setShowActivityForm] = useState(false);
  const [activityForm, setActivityForm] = useState({
    type: "note",
    content: "",
    scheduled_at: "",
  });
  const [showLostDialog, setShowLostDialog] = useState(false);
  const [lostReason, setLostReason] = useState("");

  useEffect(() => {
    fetchLead(leadId);
    loadActivities();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [leadId]);

  const loadActivities = async () => {
    try {
      const acts = await fetchActivities(leadId);
      setActivities(acts);
    } catch (error) {
      console.error("Failed to load activities:", error);
    }
  };

  const handleStatusChange = async (newStatus: string) => {
    if (newStatus === "lost") {
      setShowLostDialog(true);
      return;
    }

    try {
      await updateStatus(leadId, newStatus);
      toast.success(t("leads.detail.toasts.statusUpdated"));
    } catch (error) {
      toast.error((error as Error).message || t("leads.detail.toasts.statusError"));
    }
  };

  const handleMarkAsLost = async () => {
    if (!lostReason.trim()) {
      toast.error(t("leads.detail.lostReasonRequired"));
      return;
    }

    try {
      await updateStatus(leadId, "lost", lostReason);
      setShowLostDialog(false);
      setLostReason("");
      toast.success(t("leads.detail.toasts.markedAsLost"));
    } catch (error) {
      toast.error((error as Error).message || t("leads.detail.toasts.statusError"));
    }
  };

  const handleDelete = async () => {
    if (!confirm(t("leads.detail.deleteConfirm"))) return;

    try {
      await deleteLead(leadId);
      toast.success(t("leads.detail.toasts.deleted"));
      router.push("/leads");
    } catch (error) {
      toast.error((error as Error).message || t("leads.detail.toasts.deleteError"));
    }
  };

  const handleConvert = async () => {
    if (!confirm(t("leads.detail.convertConfirm"))) return;

    try {
      const result = await convertToClient(leadId);
      if (result) {
        toast.success(t("leads.detail.toasts.converted"));
        router.push(`/clients/${result.client.id}`);
      }
    } catch (error) {
      toast.error((error as Error).message || t("leads.detail.toasts.convertError"));
    }
  };

  const handleCreateActivity = async (e: React.FormEvent) => {
    e.preventDefault();

    try {
      const payload: Record<string, unknown> = {
        type: activityForm.type,
        content: activityForm.content,
      };

      if (activityForm.type === "follow_up" && activityForm.scheduled_at) {
        payload.scheduled_at = activityForm.scheduled_at;
      }

      await createActivity(leadId, payload);
      setActivityForm({ type: "note", content: "", scheduled_at: "" });
      setShowActivityForm(false);
      loadActivities();
      toast.success(t("leads.detail.toasts.activityLogged"));
    } catch (error) {
      toast.error((error as Error).message || t("leads.detail.toasts.activityError"));
    }
  };

  if (!currentLead && !isLoading) {
    return (
      <div className="p-8 text-center text-muted-foreground">
        {t("leads.leadNotFound")}
        <div className="mt-4">
          <Button asChild>
            <Link href="/leads">
              <ArrowLeft className="mr-2 h-4 w-4" />
              {t("leads.detail.backToLeads")}
            </Link>
          </Button>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <Button variant="ghost" asChild className="mb-2">
            <Link href="/leads">
              <ArrowLeft className="mr-2 h-4 w-4" />
              {t("leads.detail.back")}
            </Link>
          </Button>
          <h1 className="text-3xl font-bold">
            {currentLead?.company_name || currentLead?.full_name}
          </h1>
          <div className="mt-2 flex items-center gap-2">
            <Badge className={STATUS_COLORS[currentLead?.status || "new"]}>
              {currentLead?.status ? t(`leads.status.${currentLead.status}`) : ""}
            </Badge>
            {currentLead?.source && (
              <Badge variant="outline" className="capitalize">
                {currentLead.source}
              </Badge>
            )}
          </div>
        </div>
        <div className="flex gap-2">
          {currentLead?.can_convert && (
            <Button onClick={handleConvert}>
              <UserPlus className="mr-2 h-4 w-4" />
              {t("leads.detail.convertToClient")}
            </Button>
          )}
          <Button variant="outline" asChild>
            <Link href={`/leads/${leadId}/edit`}>
              <Edit className="mr-2 h-4 w-4" />
              {t("leads.detail.edit")}
            </Link>
          </Button>
          <Button variant="destructive" onClick={handleDelete}>
            <Trash2 className="mr-2 h-4 w-4" />
            {t("leads.detail.delete")}
          </Button>
        </div>
      </div>

      <div className="grid gap-6 lg:grid-cols-3">
        <div className="lg:col-span-2 space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>{t("leads.detail.cardDetails")}</CardTitle>
            </CardHeader>
            <CardContent className="grid gap-4 md:grid-cols-2">
              <div>
                <span className="text-sm text-muted-foreground">{t("leads.detail.contact")}</span>
                <p className="font-medium">{currentLead?.full_name}</p>
              </div>
              <div>
                <span className="text-sm text-muted-foreground">{t("leads.detail.company")}</span>
                <p className="font-medium">
                  {currentLead?.company_name || "-"}
                </p>
              </div>
              <div>
                <span className="text-sm text-muted-foreground">{t("leads.detail.email")}</span>
                <p className="font-medium">{currentLead?.email || "-"}</p>
              </div>
              <div>
                <span className="text-sm text-muted-foreground">{t("leads.detail.phone")}</span>
                <p className="font-medium">{currentLead?.phone || "-"}</p>
              </div>
              <div>
                <span className="text-sm text-muted-foreground">
                  {t("leads.detail.estimatedValue")}
                </span>
                <p className="font-medium">
                  {currentLead?.estimated_value ? (
                    <CurrencyAmount
                      amount={currentLead.estimated_value}
                      currency={currentLead.currency}
                    />
                  ) : (
                    "-"
                  )}
                </p>
              </div>
              <div>
                <span className="text-sm text-muted-foreground">
                  {t("leads.detail.probability")}
                </span>
                <p className="font-medium">
                  {currentLead?.probability
                    ? `${currentLead.probability}%`
                    : "-"}
                </p>
              </div>
              <div>
                <span className="text-sm text-muted-foreground">
                  {t("leads.detail.expectedCloseDate")}
                </span>
                <p className="font-medium">
                  {currentLead?.expected_close_date || "-"}
                </p>
              </div>
              <div>
                <span className="text-sm text-muted-foreground">{t("leads.detail.created")}</span>
                <p className="font-medium">
                  {currentLead?.created_at
                    ? new Date(currentLead.created_at).toLocaleDateString()
                    : "-"}
                </p>
              </div>
              {currentLead?.notes && (
                <div className="md:col-span-2">
                  <span className="text-sm text-muted-foreground">{t("leads.detail.notes")}</span>
                  <p className="font-medium">{currentLead.notes}</p>
                </div>
              )}
              {currentLead?.lost_reason && (
                <div className="md:col-span-2">
                  <span className="text-sm text-muted-foreground">
                    {t("leads.detail.lostReason")}
                  </span>
                  <p className="font-medium text-red-600">
                    {currentLead.lost_reason}
                  </p>
                </div>
              )}
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between">
              <CardTitle>{t("leads.detail.activityTimeline")}</CardTitle>
              <Button size="sm" onClick={() => setShowActivityForm(true)}>
                <Plus className="mr-2 h-4 w-4" />
                {t("leads.detail.logActivity")}
              </Button>
            </CardHeader>
            <CardContent>
              {showActivityForm && (
                <form
                  onSubmit={handleCreateActivity}
                  className="mb-4 space-y-3 rounded-lg border p-4"
                >
                  <div className="grid gap-3 md:grid-cols-2">
                    <div>
                      <Label className="text-sm">{t("leads.detail.activityType")}</Label>
                      <select
                        className="mt-1 h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        value={activityForm.type}
                        onChange={(e) =>
                          setActivityForm({
                            ...activityForm,
                            type: e.target.value,
                          })
                        }
                      >
                        <option value="note">{t("leads.activityType.note")}</option>
                        <option value="call">{t("leads.activityType.call")}</option>
                        <option value="email_sent">{t("leads.activityType.email_sent")}</option>
                        <option value="meeting">{t("leads.activityType.meeting")}</option>
                        <option value="follow_up">{t("leads.activityType.follow_up")}</option>
                      </select>
                    </div>
                    {activityForm.type === "follow_up" && (
                      <div>
                        <Label className="text-sm">{t("leads.detail.activityScheduledAt")}</Label>
                        <Input
                          type="datetime-local"
                          className="mt-1"
                          value={activityForm.scheduled_at}
                          onChange={(e) =>
                            setActivityForm({
                              ...activityForm,
                              scheduled_at: e.target.value,
                            })
                          }
                          required
                        />
                      </div>
                    )}
                  </div>
                  <textarea
                    className="min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    placeholder={t("leads.detail.activityPlaceholder")}
                    value={activityForm.content}
                    onChange={(e) =>
                      setActivityForm({
                        ...activityForm,
                        content: e.target.value,
                      })
                    }
                  />
                  <div className="flex justify-end gap-2">
                    <Button
                      type="button"
                      variant="outline"
                      size="sm"
                      onClick={() => setShowActivityForm(false)}
                    >
                      {t("leads.detail.cancel")}
                    </Button>
                    <Button type="submit" size="sm">
                      {t("leads.detail.save")}
                    </Button>
                  </div>
                </form>
              )}

              {activities.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                  {t("leads.detail.noActivities")}
                </p>
              ) : (
                <div className="space-y-4">
                  {activities.map((activity) => (
                    <div key={activity.id} className="flex gap-3">
                      <div className="text-2xl">
                        {ACTIVITY_ICONS[activity.type] || "📝"}
                      </div>
                      <div className="flex-1">
                        <div className="flex items-center justify-between">
                          <span className="font-medium capitalize">
                            {t(`leads.activityType.${activity.type}`)}
                          </span>
                          <span className="text-xs text-muted-foreground">
                            {new Date(activity.created_at).toLocaleString()}
                          </span>
                        </div>
                        {activity.content && (
                          <p className="text-sm text-muted-foreground">
                            {activity.content}
                          </p>
                        )}
                        {activity.scheduled_at && (
                          <p className="text-xs text-blue-600">
                            {t("leads.detail.scheduled")}{" "}
                            {new Date(activity.scheduled_at).toLocaleString()}
                          </p>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>
        </div>

        <div className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>{t("leads.detail.updateStatus")}</CardTitle>
            </CardHeader>
            <CardContent className="space-y-2">
              {[
                "new",
                "contacted",
                "qualified",
                "proposal_sent",
                "negotiating",
                "won",
                "lost",
              ].map((status) => (
                <Button
                  key={status}
                  variant={
                    currentLead?.status === status ? "default" : "outline"
                  }
                  className="w-full justify-start capitalize"
                  onClick={() => handleStatusChange(status)}
                  disabled={currentLead?.status === status}
                >
                  {t(`leads.status.${status}`)}
                </Button>
              ))}
            </CardContent>
          </Card>

          {showLostDialog && (
            <Card className="border-red-200">
              <CardHeader>
                <CardTitle className="text-red-600">{t("leads.detail.markAsLost")}</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <textarea
                  className="min-h-[100px] w-full rounded-md border border-red-300 bg-background px-3 py-2 text-sm"
                  placeholder={t("leads.detail.lostReasonPlaceholder")}
                  value={lostReason}
                  onChange={(e) => setLostReason(e.target.value)}
                />
                <div className="flex gap-2">
                  <Button
                    variant="outline"
                    onClick={() => setShowLostDialog(false)}
                  >
                    {t("leads.detail.cancel")}
                  </Button>
                  <Button variant="destructive" onClick={handleMarkAsLost}>
                    {t("leads.detail.confirm")}
                  </Button>
                </div>
              </CardContent>
            </Card>
          )}
        </div>
      </div>
    </div>
  );
}
