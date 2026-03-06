"use client";

import { useEffect, useMemo, useState } from "react";
import dynamic from "next/dynamic";
import { useRouter, useSearchParams } from "next/navigation";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { TemplateSelector } from "@/components/campaigns/template-selector";
import { CampaignPreview } from "@/components/campaigns/campaign-preview";
import { TestSendModal } from "@/components/campaigns/test-send-modal";
import { AbTestConfig } from "@/components/campaigns/ab-test-config";
import { DynamicContentEditor } from "@/components/campaigns/dynamic-content-editor";
import { PersonalizationVariablesPanel } from "@/components/campaigns/personalization-variables-panel";
import { StoConfig } from "@/components/campaigns/sto-config";
import { useCampaignStore } from "@/lib/stores/campaigns";
import type { CampaignVariant } from "@/lib/stores/campaigns";
import { useSegmentStore } from "@/lib/stores/segments";
import { useI18n } from "@/components/providers/i18n-provider";

const EmailEditor = dynamic(
  () =>
    import("@/components/campaigns/email-editor").then(
      (mod) => mod.EmailEditor
    ),
  {
    loading: () => (
      <div className="h-72 animate-pulse rounded-lg border border-border bg-muted/40" />
    ),
  }
);
const SmsComposer = dynamic(
  () =>
    import("@/components/campaigns/sms-composer").then(
      (mod) => mod.SmsComposer
    ),
  {
    loading: () => (
      <div className="h-40 animate-pulse rounded-lg border border-border bg-muted/40" />
    ),
  }
);
const SmsPreview = dynamic(() =>
  import("@/components/campaigns/sms-preview").then((mod) => mod.SmsPreview)
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
  const { t } = useI18n();
  const router = useRouter();
  const searchParams = useSearchParams();

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
  const [isAbTest, setIsAbTest] = useState(false);
  const [variants, setVariants] = useState<CampaignVariant[]>([
    { label: "A", subject: "", content: "", send_percent: 50 },
    { label: "B", subject: "", content: "", send_percent: 50 },
  ]);
  const [winnerCriteria, setWinnerCriteria] = useState<
    "open_rate" | "click_rate" | "manual"
  >("open_rate");
  const [autoSelectAfterHours, setAutoSelectAfterHours] = useState<
    number | null
  >(24);
  const [activeField, setActiveField] = useState<string | null>(null);
  const [useSto, setUseSto] = useState(false);
  const [stoWindowHours, setStoWindowHours] = useState(24);
  const [dynamicContentErrors, setDynamicContentErrors] = useState<string[]>(
    []
  );

  useEffect(() => {
    fetchTemplates().catch(() => undefined);
    fetchSegments().catch(() => undefined);
  }, [fetchTemplates, fetchSegments]);

  useEffect(() => {
    const initialSegmentId = searchParams.get("segment_id");
    if (initialSegmentId) {
      setSegmentId(initialSegmentId);
    }
  }, [searchParams]);

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

  useEffect(() => {
    if (type !== "email") {
      setIsAbTest(false);
    }
  }, [type]);

  const previewVariantA =
    variants.find((variant) => variant.label === "A") || variants[0];

  const payload = {
    name,
    type,
    segment_id: segmentId || null,
    subject:
      type === "email"
        ? isAbTest
          ? previewVariantA?.subject || null
          : subject
        : null,
    content:
      type === "email" && isAbTest
        ? previewVariantA?.content || "<p>Variant A</p>"
        : content,
    template_id: selectedTemplateId || null,
    scheduled_at: scheduledAt || null,
    status: scheduledAt ? "scheduled" : "draft",
    use_sto: type === "email" ? useSto : false,
    sto_window_hours: type === "email" ? stoWindowHours : null,
    is_ab_test: type === "email" ? isAbTest : false,
    variants: type === "email" && isAbTest ? variants : undefined,
    ab_winner_criteria: type === "email" && isAbTest ? winnerCriteria : null,
    ab_auto_select_after_hours:
      type === "email" && isAbTest && winnerCriteria !== "manual"
        ? autoSelectAfterHours
        : null,
  };

  const updateVariant = (
    label: "A" | "B",
    field: "subject" | "content" | "send_percent",
    value: string | number
  ) => {
    if (field === "send_percent") {
      const numeric = Math.max(1, Math.min(99, Number(value || 0)));
      const oppositeLabel = label === "A" ? "B" : "A";

      setVariants((current) =>
        current.map((variant) => {
          if (variant.label === label) {
            return { ...variant, send_percent: numeric };
          }
          if (variant.label === oppositeLabel) {
            return { ...variant, send_percent: 100 - numeric };
          }
          return variant;
        })
      );
      return;
    }

    setVariants((current) =>
      current.map((variant) => {
        if (variant.label !== label) {
          return variant;
        }

        return {
          ...variant,
          [field]: String(value),
        };
      })
    );
  };

  const handleInsertVariable = (token: string) => {
    if (!activeField) {
      setContent((current) => `${current}${token}`);
      return;
    }

    if (activeField === "subject") {
      setSubject((current) => `${current}${token}`);
      return;
    }

    if (activeField === "content") {
      setContent((current) => `${current}${token}`);
      return;
    }

    if (activeField.startsWith("variant-A-subject")) {
      updateVariant(
        "A",
        "subject",
        `${previewVariantA?.subject || ""}${token}`
      );
      return;
    }

    if (activeField.startsWith("variant-A-content")) {
      updateVariant(
        "A",
        "content",
        `${previewVariantA?.content || ""}${token}`
      );
      return;
    }

    if (activeField.startsWith("variant-B-subject")) {
      const variantB = variants.find((variant) => variant.label === "B");
      updateVariant("B", "subject", `${variantB?.subject || ""}${token}`);
      return;
    }

    if (activeField.startsWith("variant-B-content")) {
      const variantB = variants.find((variant) => variant.label === "B");
      updateVariant("B", "content", `${variantB?.content || ""}${token}`);
    }
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
      setDynamicContentErrors([]);
      const campaignId = await ensureDraftExists();
      toast.success("Draft campaign saved");
      router.push(`/campaigns/${campaignId}`);
    } catch (error) {
      setDynamicContentErrors(extractDynamicContentErrors(error));
      toast.error((error as Error).message || "Unable to save campaign");
    }
  };

  const saveAndSendNow = async () => {
    try {
      setDynamicContentErrors([]);
      const campaignId = await ensureDraftExists();
      await sendCampaign(campaignId);
      toast.success("Campaign queued for sending");
      router.push(`/campaigns/${campaignId}`);
    } catch (error) {
      setDynamicContentErrors(extractDynamicContentErrors(error));
      toast.error((error as Error).message || "Unable to send campaign");
    }
  };

  const handleTestSend = async (destination: {
    emails?: string[];
    phones?: string[];
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
        <h1 className="text-3xl font-bold">{t("campaigns.create.title")}</h1>
        <p className="text-sm text-muted-foreground">
          {t("campaigns.create.description")}
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
            {t(`campaigns.create.step${number}`)}
          </Button>
        ))}
      </div>

      {step === 1 && (
        <Card>
          <CardHeader>
            <CardTitle>{t("campaigns.create.audience")}</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="campaign-name">
                {t("campaigns.create.campaignName")}
              </Label>
              <Input
                id="campaign-name"
                value={name}
                onChange={(event) => setName(event.target.value)}
                placeholder={t("campaigns.create.campaignNamePlaceholder")}
              />
            </div>

            <div className="grid gap-4 md:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="campaign-type">
                  {t("campaigns.create.type")}
                </Label>
                <select
                  id="campaign-type"
                  value={type}
                  onChange={(event) =>
                    setType(event.target.value as "email" | "sms")
                  }
                  className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                >
                  <option value="email">{t("campaigns.create.email")}</option>
                  <option value="sms">{t("campaigns.create.sms")}</option>
                </select>
              </div>

              <div className="space-y-2">
                <Label htmlFor="campaign-segment">
                  {t("campaigns.create.segment")}
                </Label>
                <select
                  id="campaign-segment"
                  value={segmentId}
                  onChange={(event) => setSegmentId(event.target.value)}
                  className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                >
                  <option value="">{t("campaigns.create.allContacts")}</option>
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
            <CardTitle>{t("campaigns.create.compose")}</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <TemplateSelector
              templates={templates.filter((template) => template.type === type)}
              selectedTemplateId={selectedTemplateId}
              onSelect={setSelectedTemplateId}
            />

            {type === "email" ? (
              <div className="grid gap-4 lg:grid-cols-3">
                <div className="space-y-4 lg:col-span-2">
                  <AbTestConfig
                    enabled={isAbTest}
                    onToggle={setIsAbTest}
                    variants={variants}
                    onChangeVariant={updateVariant}
                    winnerCriteria={winnerCriteria}
                    onWinnerCriteriaChange={setWinnerCriteria}
                    autoSelectAfterHours={autoSelectAfterHours}
                    onAutoSelectAfterHoursChange={setAutoSelectAfterHours}
                    onFocusField={setActiveField}
                  />

                  {!isAbTest ? (
                    <>
                      <div className="space-y-2">
                        <Label htmlFor="campaign-subject">
                          {t("campaigns.create.subject")}
                        </Label>
                        <Input
                          id="campaign-subject"
                          value={subject}
                          onFocus={() => setActiveField("subject")}
                          onChange={(event) => setSubject(event.target.value)}
                          placeholder={t("campaigns.create.subjectPlaceholder")}
                        />
                      </div>
                      <div onFocusCapture={() => setActiveField("content")}>
                        <EmailEditor value={content} onChange={setContent} />
                      </div>
                    </>
                  ) : null}
                </div>
                <PersonalizationVariablesPanel
                  onInsert={handleInsertVariable}
                />
                <DynamicContentEditor onInsert={handleInsertVariable} />
                {dynamicContentErrors.length > 0 ? (
                  <Card className="border-destructive/40">
                    <CardHeader>
                      <CardTitle className="text-base text-destructive">
                        Dynamic content errors
                      </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm text-destructive">
                      {dynamicContentErrors.map((error) => (
                        <p key={error}>{error}</p>
                      ))}
                    </CardContent>
                  </Card>
                ) : null}
              </div>
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
              subject={
                isAbTest ? (previewVariantA?.subject ?? "Variante A") : subject
              }
              content={
                isAbTest
                  ? (previewVariantA?.content ?? "<p>Variante A</p>")
                  : content
              }
              recipients={sampleRecipients}
            />
          ) : (
            <Card>
              <CardHeader>
                <CardTitle>{t("campaigns.create.smsPreview")}</CardTitle>
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
            <CardTitle>{t("campaigns.create.schedule")}</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="scheduled-at">
                {t("campaigns.create.scheduleAt")}
              </Label>
              <Input
                id="scheduled-at"
                type="datetime-local"
                value={scheduledAt}
                onChange={(event) => setScheduledAt(event.target.value)}
              />
            </div>

            {type === "email" ? (
              <StoConfig
                enabled={useSto}
                windowHours={stoWindowHours}
                knownContactsCount={0}
                onEnabledChange={setUseSto}
                onWindowHoursChange={setStoWindowHours}
              />
            ) : null}

            <div className="flex flex-wrap justify-end gap-2">
              <Button
                variant="outline"
                onClick={saveDraft}
                disabled={isLoading}
              >
                {t("campaigns.create.saveDraft")}
              </Button>
              <Button onClick={saveAndSendNow} disabled={isLoading}>
                {t("campaigns.create.sendNow")}
              </Button>
            </div>
          </CardContent>
        </Card>
      )}

      <div className="flex justify-between">
        <Button
          data-testid="campaign-wizard-back"
          variant="outline"
          onClick={() => setStep((current) => Math.max(1, current - 1))}
          disabled={step === 1}
        >
          {t("campaigns.create.back")}
        </Button>
        <Button
          data-testid="campaign-wizard-next"
          onClick={() => setStep((current) => Math.min(4, current + 1))}
          disabled={step === 4}
        >
          {t("campaigns.create.next")}
        </Button>
      </div>
    </div>
  );
}

function extractDynamicContentErrors(error: unknown): string[] {
  const candidate = error as {
    data?: { errors?: Record<string, string[] | string> };
  };

  const errors = candidate?.data?.errors;
  if (!errors || typeof errors !== "object") {
    return [];
  }

  return Object.entries(errors)
    .filter(
      ([field]) =>
        field === "content" ||
        field === "subject" ||
        field.startsWith("variants.")
    )
    .flatMap(([, value]) => (Array.isArray(value) ? value : [String(value)]));
}
