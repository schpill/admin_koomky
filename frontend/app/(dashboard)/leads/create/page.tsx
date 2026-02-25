"use client";

import { useRouter } from "next/navigation";
import { useState } from "react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useLeadStore } from "@/lib/stores/leads";
import { useI18n } from "@/components/providers/i18n-provider";

export default function CreateLeadPage() {
  const { t } = useI18n();
  const router = useRouter();
  const { createLead, isLoading } = useLeadStore();

  const [form, setForm] = useState({
    company_name: "",
    first_name: "",
    last_name: "",
    email: "",
    phone: "",
    source: "manual",
    estimated_value: "",
    probability: "",
    expected_close_date: "",
    notes: "",
  });

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!form.first_name || !form.last_name) {
      toast.error(t("leads.form.nameRequired"));
      return;
    }

    try {
      const payload: Record<string, unknown> = {
        first_name: form.first_name,
        last_name: form.last_name,
        source: form.source,
      };

      if (form.company_name) payload.company_name = form.company_name;
      if (form.email) payload.email = form.email;
      if (form.phone) payload.phone = form.phone;
      if (form.estimated_value)
        payload.estimated_value = parseFloat(form.estimated_value);
      if (form.probability) payload.probability = parseInt(form.probability);
      if (form.expected_close_date)
        payload.expected_close_date = form.expected_close_date;
      if (form.notes) payload.notes = form.notes;

      const lead = await createLead(payload);
      if (lead) {
        toast.success(t("leads.form.toasts.createSuccess"));
        router.push(`/leads/${lead.id}`);
      }
    } catch (error) {
      toast.error(
        (error as Error).message || t("leads.form.toasts.createError")
      );
    }
  };

  return (
    <div className="mx-auto max-w-2xl space-y-6">
      <div>
        <h1 className="text-3xl font-bold">{t("leads.createLead")}</h1>
        <p className="text-sm text-muted-foreground">
          {t("leads.createSubtitle")}
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>{t("leads.leadDetails")}</CardTitle>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="grid gap-4 md:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="company_name">
                  {t("leads.form.companyName")}
                </Label>
                <Input
                  id="company_name"
                  value={form.company_name}
                  onChange={(e) =>
                    setForm({ ...form, company_name: e.target.value })
                  }
                  placeholder="Acme Corp"
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="source">{t("leads.form.source")}</Label>
                <select
                  id="source"
                  className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                  value={form.source}
                  onChange={(e) => setForm({ ...form, source: e.target.value })}
                >
                  <option value="manual">{t("leads.source.manual")}</option>
                  <option value="referral">{t("leads.source.referral")}</option>
                  <option value="website">{t("leads.source.website")}</option>
                  <option value="campaign">{t("leads.source.campaign")}</option>
                  <option value="other">{t("leads.source.other")}</option>
                </select>
              </div>
            </div>

            <div className="grid gap-4 md:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="first_name">{t("leads.form.firstName")}</Label>
                <Input
                  id="first_name"
                  value={form.first_name}
                  onChange={(e) =>
                    setForm({ ...form, first_name: e.target.value })
                  }
                  placeholder="John"
                  required
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="last_name">{t("leads.form.lastName")}</Label>
                <Input
                  id="last_name"
                  value={form.last_name}
                  onChange={(e) =>
                    setForm({ ...form, last_name: e.target.value })
                  }
                  placeholder="Doe"
                  required
                />
              </div>
            </div>

            <div className="grid gap-4 md:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="email">{t("leads.form.email")}</Label>
                <Input
                  id="email"
                  type="email"
                  value={form.email}
                  onChange={(e) => setForm({ ...form, email: e.target.value })}
                  placeholder="john@example.com"
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="phone">{t("leads.form.phone")}</Label>
                <Input
                  id="phone"
                  value={form.phone}
                  onChange={(e) => setForm({ ...form, phone: e.target.value })}
                  placeholder="+1234567890"
                />
              </div>
            </div>

            <div className="grid gap-4 md:grid-cols-3">
              <div className="space-y-2">
                <Label htmlFor="estimated_value">
                  {t("leads.form.estimatedValue")}
                </Label>
                <Input
                  id="estimated_value"
                  type="number"
                  step="0.01"
                  value={form.estimated_value}
                  onChange={(e) =>
                    setForm({ ...form, estimated_value: e.target.value })
                  }
                  placeholder="10000"
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="probability">
                  {t("leads.form.probability")}
                </Label>
                <Input
                  id="probability"
                  type="number"
                  min="0"
                  max="100"
                  value={form.probability}
                  onChange={(e) =>
                    setForm({ ...form, probability: e.target.value })
                  }
                  placeholder="50"
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="expected_close_date">
                  {t("leads.form.expectedCloseDate")}
                </Label>
                <Input
                  id="expected_close_date"
                  type="date"
                  value={form.expected_close_date}
                  onChange={(e) =>
                    setForm({ ...form, expected_close_date: e.target.value })
                  }
                />
              </div>
            </div>

            <div className="space-y-2">
              <Label htmlFor="notes">{t("leads.form.notes")}</Label>
              <textarea
                id="notes"
                className="min-h-[100px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                value={form.notes}
                onChange={(e) => setForm({ ...form, notes: e.target.value })}
                placeholder={t("leads.form.notesPlaceholder")}
              />
            </div>

            <div className="flex justify-end gap-2">
              <Button
                type="button"
                variant="outline"
                onClick={() => router.back()}
              >
                {t("leads.form.cancel")}
              </Button>
              <Button type="submit" disabled={isLoading}>
                {isLoading ? t("leads.form.creating") : t("leads.form.create")}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  );
}
