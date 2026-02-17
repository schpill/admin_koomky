"use client";

import { useEffect, useMemo, useState } from "react";
import dynamic from "next/dynamic";
import { useRouter } from "next/navigation";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { TemplateSelector } from "@/components/campaigns/template-selector";
import { CampaignPreview } from "@/components/campaigns/campaign-preview";
import { TestSendModal } from "@/components/campaigns/test-send-modal";
import { useCampaignStore } from "@/lib/stores/campaigns";
import { useSegmentStore } from "@/lib/stores/segments";

const EmailEditor = dynamic(
  () => import("@/components/campaigns/email-editor").then((mod) => mod.EmailEditor),
  {
    loading: () => (
      <div className="h-72 animate-pulse rounded-lg border border-border bg-muted/40" />
    ),
  }
);
const SmsComposer = dynamic(
  () => import("@/components/campaigns/sms-composer").then((mod) => mod.SmsComposer),
  {
    loading: () => (
      <div className="h-40 animate-pulse rounded-lg border border-border bg-muted/40" />
    ),
  }
);
const SmsPreview = dynamic(
  () => import("@/components/campaigns/sms-preview").then((mod) => mod.SmsPreview)
);

const sampleRecipients = [
  {
    id: "sample_1",
    first_name: "Alice",
    last_name: "Doe",
    email: "alice@example.com",
    company: "Acme",
  },
  {
    id: "sample_2",
    first_name: "Bob",
    last_name: "Moe",
    email: "bob@example.com",
    company: "Globex",
  },
];

export default function CreateCampaignPage() {
  const router = useRouter();

  const {
    templates,
    isLoading,
    fetchTemplates,
    createCampaign,
    updateCampaign,
    sendCampaign,
    testSendCampaign,
  } = useCampaignStore();
  const { segments, fetchSegments } = useSegmentStore();

  const [step, setStep] = useState(1);
  const [draftCampaignId, setDraftCampaignId] = useState<string | null>(null);

  const [name, setName] = useState("");
  const [type, setType] = useState<"email" | "sms">("email");
  const [segmentId, setSegmentId] = useState("");
  const [subject, setSubject] = useState("");
  const [content, setContent] = useState("");
  const [selectedTemplateId, setSelectedTemplateId] = useState("");
  const [scheduledAt, setScheduledAt] = useState("");

  useEffect(() => {
    fetchTemplates().catch(() => undefined);
    fetchSegments().catch(() => undefined);
  }, [fetchTemplates, fetchSegments]);

  const selectedTemplate = useMemo(
    () => templates.find((template) => template.id === selectedTemplateId),
    [templates, selectedTemplateId]
  );

  useEffect(() => {
    if (!selectedTemplate) {
      return;
    }

    if (selectedTemplate.type !== type) {
      return;
    }

    setContent(selectedTemplate.content || "");
    if (type === "email") {
      setSubject(selectedTemplate.subject || "");
    }
  }, [selectedTemplate, type]);

  const payload = {
    name,
    type,
    segment_id: segmentId || null,
    subject: type === "email" ? subject : null,
    content,
    template_id: selectedTemplateId || null,
    scheduled_at: scheduledAt || null,
    status: scheduledAt ? "scheduled" : "draft",
  };

  const ensureDraftExists = async () => {
    if (draftCampaignId) {
      await updateCampaign(draftCampaignId, payload);
      return draftCampaignId;
    }

    const created = await createCampaign(payload);
    if (!created?.id) {
      throw new Error("Unable to create draft campaign");
    }

    setDraftCampaignId(created.id);

    return created.id;
  };

  const saveDraft = async () => {
    try {
      const campaignId = await ensureDraftExists();
      toast.success("Draft campaign saved");
      router.push(`/campaigns/${campaignId}`);
    } catch (error) {
      toast.error((error as Error).message || "Unable to save campaign");
    }
  };

  const saveAndSendNow = async () => {
    try {
      const campaignId = await ensureDraftExists();
      await sendCampaign(campaignId);
      toast.success("Campaign queued for sending");
      router.push(`/campaigns/${campaignId}`);
    } catch (error) {
      toast.error((error as Error).message || "Unable to send campaign");
    }
  };

  const handleTestSend = async (destination: {
    email?: string;
    phone?: string;
  }) => {
    const campaignId = await ensureDraftExists();
    await testSendCampaign(campaignId, destination);
    toast.success("Test campaign sent");
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">Create campaign</h1>
        <p className="text-sm text-muted-foreground">
          Multi-step builder for email and SMS campaigns.
        </p>
      </div>

      <div className="grid gap-2 sm:grid-cols-4">
        {[1, 2, 3, 4].map((number) => (
          <Button
            key={number}
            variant={step === number ? "default" : "outline"}
            size="sm"
            onClick={() => setStep(number)}
            className="justify-start sm:justify-center"
          >
            Step {number}
          </Button>
        ))}
      </div>

      {step === 1 && (
        <Card>
          <CardHeader>
            <CardTitle>Audience and channel</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="campaign-name">Campaign name</Label>
              <Input
                id="campaign-name"
                value={name}
                onChange={(event) => setName(event.target.value)}
                placeholder="Spring newsletter"
              />
            </div>

            <div className="grid gap-4 md:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="campaign-type">Type</Label>
                <select
                  id="campaign-type"
                  value={type}
                  onChange={(event) =>
                    setType(event.target.value as "email" | "sms")
                  }
                  className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                >
                  <option value="email">Email</option>
                  <option value="sms">SMS</option>
                </select>
              </div>

              <div className="space-y-2">
                <Label htmlFor="campaign-segment">Segment</Label>
                <select
                  id="campaign-segment"
                  value={segmentId}
                  onChange={(event) => setSegmentId(event.target.value)}
                  className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                >
                  <option value="">All contacts</option>
                  {segments.map((segment) => (
                    <option key={segment.id} value={segment.id}>
                      {segment.name}
                    </option>
                  ))}
                </select>
              </div>
            </div>
          </CardContent>
        </Card>
      )}

      {step === 2 && (
        <Card>
          <CardHeader>
            <CardTitle>Compose message</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <TemplateSelector
              templates={templates.filter((template) => template.type === type)}
              selectedTemplateId={selectedTemplateId}
              onSelect={setSelectedTemplateId}
            />

            {type === "email" && (
              <div className="space-y-2">
                <Label htmlFor="campaign-subject">Subject</Label>
                <Input
                  id="campaign-subject"
                  value={subject}
                  onChange={(event) => setSubject(event.target.value)}
                  placeholder="Your April updates"
                />
              </div>
            )}

            {type === "email" ? (
              <EmailEditor value={content} onChange={setContent} />
            ) : (
              <SmsComposer value={content} onChange={setContent} />
            )}
          </CardContent>
        </Card>
      )}

      {step === 3 && (
        <div className="space-y-4">
          {type === "email" ? (
            <CampaignPreview
              subject={subject}
              content={content}
              recipients={sampleRecipients}
            />
          ) : (
            <Card>
              <CardHeader>
                <CardTitle>SMS preview</CardTitle>
              </CardHeader>
              <CardContent>
                <SmsPreview content={content} recipient={sampleRecipients[0]} />
              </CardContent>
            </Card>
          )}

          <TestSendModal
            type={type}
            onSend={handleTestSend}
            isSubmitting={isLoading}
          />
        </div>
      )}

      {step === 4 && (
        <Card>
          <CardHeader>
            <CardTitle>Schedule</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="scheduled-at">Schedule at (optional)</Label>
              <Input
                id="scheduled-at"
                type="datetime-local"
                value={scheduledAt}
                onChange={(event) => setScheduledAt(event.target.value)}
              />
            </div>

            <div className="flex flex-wrap justify-end gap-2">
              <Button
                variant="outline"
                onClick={saveDraft}
                disabled={isLoading}
              >
                Save draft
              </Button>
              <Button onClick={saveAndSendNow} disabled={isLoading}>
                Send now
              </Button>
            </div>
          </CardContent>
        </Card>
      )}

      <div className="flex justify-between">
        <Button
          variant="outline"
          onClick={() => setStep((current) => Math.max(1, current - 1))}
          disabled={step === 1}
        >
          Back
        </Button>
        <Button
          onClick={() => setStep((current) => Math.min(4, current + 1))}
          disabled={step === 4}
        >
          Next
        </Button>
      </div>
    </div>
  );
}
