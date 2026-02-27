"use client";

import { useState, useEffect } from "react";
import { useRouter } from "next/navigation";
import { useProductsStore } from "@/lib/stores/products";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Badge } from "@/components/ui/badge";
import {
  AlertTriangle,
  CheckCircle,
  ChevronRight,
  Loader2,
  RotateCcw,
  Sparkles,
} from "lucide-react";

interface Segment {
  id: string;
  name: string;
  contacts_count?: number;
}

interface GeneratedCampaign {
  id: string;
  name: string;
  status: string;
}

type WizardStep = 1 | 2 | 3;

interface ProductCampaignWizardProps {
  productId: string;
}

export function ProductCampaignWizard({
  productId,
}: ProductCampaignWizardProps) {
  const router = useRouter();
  const { generateCampaign, isLoading } = useProductsStore();

  const [step, setStep] = useState<WizardStep>(1);
  const [segments, setSegments] = useState<Segment[]>([]);
  const [segmentsLoading, setSegmentsLoading] = useState(true);
  const [selectedSegmentId, setSelectedSegmentId] = useState<string>("");
  const [generatedCampaign, setGeneratedCampaign] =
    useState<GeneratedCampaign | null>(null);
  const [error, setError] = useState<string | null>(null);

  const QUALIFIED_LEADS_SEGMENT_ID = "__qualified_leads__";

  useEffect(() => {
    const fetchSegments = async () => {
      setSegmentsLoading(true);
      try {
        const response = await fetch("/api/v1/segments?per_page=100");
        if (response.ok) {
          const { data } = await response.json();
          setSegments(data ?? []);
        }
      } catch {
        // Segments failed to load — wizard still works with qualified leads fallback
      } finally {
        setSegmentsLoading(false);
      }
    };

    fetchSegments();
  }, []);

  const selectedSegment =
    selectedSegmentId === QUALIFIED_LEADS_SEGMENT_ID
      ? {
          id: QUALIFIED_LEADS_SEGMENT_ID,
          name: "Tous mes leads qualifiés",
          contacts_count: undefined,
        }
      : segments.find((s) => s.id === selectedSegmentId);

  const recipientCount = selectedSegment?.contacts_count;
  const hasNoRecipients = recipientCount !== undefined && recipientCount === 0;

  const handleGenerate = async () => {
    if (!selectedSegmentId) return;
    setError(null);
    setStep(2);

    try {
      const segmentId =
        selectedSegmentId === QUALIFIED_LEADS_SEGMENT_ID
          ? "qualified_leads"
          : selectedSegmentId;

      const campaign = await generateCampaign(productId, segmentId);
      setGeneratedCampaign(campaign);
      setStep(3);
    } catch (err) {
      setError(
        err instanceof Error ? err.message : "Erreur lors de la génération"
      );
      setStep(1);
    }
  };

  const handleRegenerate = async () => {
    setGeneratedCampaign(null);
    await handleGenerate();
  };

  const handleCreate = () => {
    if (generatedCampaign) {
      router.push(`/campaigns/${generatedCampaign.id}`);
    }
  };

  return (
    <div className="space-y-6">
      {/* Step indicators */}
      <div className="flex items-center gap-2">
        {([1, 2, 3] as WizardStep[]).map((s) => (
          <div key={s} className="flex items-center gap-2">
            <div
              className={`flex items-center justify-center w-8 h-8 rounded-full text-sm font-medium transition-colors ${
                step === s
                  ? "bg-primary text-primary-foreground"
                  : step > s
                    ? "bg-green-500 text-white"
                    : "bg-muted text-muted-foreground"
              }`}
            >
              {step > s ? <CheckCircle className="h-4 w-4" /> : s}
            </div>
            <span
              className={`text-sm ${step === s ? "font-medium" : "text-muted-foreground"}`}
            >
              {s === 1 ? "Cibler" : s === 2 ? "Générer" : "Réviser"}
            </span>
            {s < 3 && (
              <ChevronRight className="h-4 w-4 text-muted-foreground" />
            )}
          </div>
        ))}
      </div>

      {/* Step 1 — Select segment */}
      {step === 1 && (
        <Card>
          <CardHeader>
            <CardTitle>Étape 1 — Choisir votre audience</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <label className="text-sm font-medium">Segment cible</label>

              {segmentsLoading ? (
                <div className="h-10 bg-muted rounded animate-pulse" />
              ) : (
                <Select
                  value={selectedSegmentId}
                  onValueChange={setSelectedSegmentId}
                >
                  <SelectTrigger>
                    <SelectValue placeholder="Sélectionner un segment..." />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value={QUALIFIED_LEADS_SEGMENT_ID}>
                      Tous mes leads qualifiés
                    </SelectItem>
                    {segments.map((segment) => (
                      <SelectItem key={segment.id} value={segment.id}>
                        {segment.name}
                        {segment.contacts_count !== undefined && (
                          <span className="ml-2 text-muted-foreground">
                            ({segment.contacts_count} contacts)
                          </span>
                        )}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              )}
            </div>

            {selectedSegment && recipientCount !== undefined && (
              <div className="flex items-center gap-2">
                <Badge variant="secondary">
                  {recipientCount} prospects ciblés
                </Badge>
              </div>
            )}

            {hasNoRecipients && (
              <div className="flex items-start gap-2 rounded-md border border-yellow-200 bg-yellow-50 p-3 text-yellow-800">
                <AlertTriangle className="h-4 w-4 mt-0.5 shrink-0" />
                <p className="text-sm">
                  Ce segment ne contient aucun destinataire. Sélectionnez un
                  autre segment ou attendez que des contacts soient ajoutés.
                </p>
              </div>
            )}

            {error && (
              <div className="flex items-start gap-2 rounded-md border border-destructive/50 bg-destructive/10 p-3 text-destructive">
                <AlertTriangle className="h-4 w-4 mt-0.5 shrink-0" />
                <p className="text-sm">{error}</p>
              </div>
            )}

            <Button
              onClick={handleGenerate}
              disabled={!selectedSegmentId || hasNoRecipients || isLoading}
              className="w-full"
            >
              <Sparkles className="mr-2 h-4 w-4" />
              Générer avec l&#39;IA
            </Button>
          </CardContent>
        </Card>
      )}

      {/* Step 2 — Generating */}
      {step === 2 && (
        <Card>
          <CardHeader>
            <CardTitle>Étape 2 — Génération en cours...</CardTitle>
          </CardHeader>
          <CardContent className="space-y-6">
            <div className="flex flex-col items-center justify-center py-12 gap-4">
              <Loader2 className="h-12 w-12 animate-spin text-primary" />
              <p className="text-muted-foreground text-center">
                L&#39;IA génère votre campagne email personnalisée...
                <br />
                <span className="text-sm">
                  Cela peut prendre quelques secondes.
                </span>
              </p>
            </div>

            <div className="w-full bg-muted rounded-full h-2">
              <div
                className="bg-primary h-2 rounded-full animate-pulse"
                style={{ width: "60%" }}
              />
            </div>
          </CardContent>
        </Card>
      )}

      {/* Step 3 — Review */}
      {step === 3 && generatedCampaign && (
        <Card>
          <CardHeader>
            <CardTitle>Étape 3 — Réviser et créer</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="flex items-start gap-2 rounded-md border border-yellow-200 bg-yellow-50 p-3 text-yellow-800">
              <AlertTriangle className="h-4 w-4 mt-0.5 shrink-0" />
              <p className="text-sm">
                <strong>Campagne créée en BROUILLON</strong> — Relisez et
                modifiez le contenu avant d&#39;envoyer. Une campagne en
                brouillon n&#39;est jamais envoyée sans validation manuelle.
              </p>
            </div>

            <div className="rounded-lg border p-4 space-y-3">
              <div>
                <span className="text-sm font-medium text-muted-foreground">
                  Campagne générée
                </span>
                <p className="font-medium">{generatedCampaign.name}</p>
              </div>
              <div className="flex items-center gap-2">
                <Badge variant="secondary">
                  Statut : {generatedCampaign.status}
                </Badge>
              </div>
            </div>

            <div className="flex gap-3">
              <Button onClick={handleCreate} className="flex-1">
                <CheckCircle className="mr-2 h-4 w-4" />
                Voir la campagne brouillon
              </Button>
              <Button
                variant="outline"
                onClick={handleRegenerate}
                disabled={isLoading}
              >
                <RotateCcw className="mr-2 h-4 w-4" />
                Régénérer
              </Button>
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
