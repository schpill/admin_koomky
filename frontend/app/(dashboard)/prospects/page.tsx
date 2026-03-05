"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { Button } from "@/components/ui/button";
import { ProspectFilters } from "@/components/prospects/prospect-filters";
import { ProspectTable } from "@/components/prospects/prospect-table";
import { ConvertToClientDialog } from "@/components/prospects/convert-to-client-dialog";
import { CreateCampaignFromProspectsDialog } from "@/components/campaigns/create-campaign-from-prospects-dialog";
import { useProspectStore } from "@/lib/stores/prospects";

export default function ProspectsPage() {
  const {
    clients,
    filters,
    fetchProspects,
    convertToClient,
    bulkUpdateStatus,
    bulkAddTags,
    exportCsv,
  } = useProspectStore();

  const [selectedIds, setSelectedIds] = useState<string[]>([]);
  const [convertDialogOpen, setConvertDialogOpen] = useState(false);
  const [campaignDialogOpen, setCampaignDialogOpen] = useState(false);
  const [activeProspectId, setActiveProspectId] = useState<string | null>(null);

  useEffect(() => {
    fetchProspects();
  }, [fetchProspects]);

  const openConvert = (id: string) => {
    setActiveProspectId(id);
    setConvertDialogOpen(true);
  };

  const openCampaign = (_id: string) => {
    setCampaignDialogOpen(true);
  };

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-3xl font-bold">Prospects</h1>
        <div className="space-x-2">
          <Link href="/prospects/import">
            <Button>Importer des prospects</Button>
          </Link>
          <Button variant="outline" onClick={() => setCampaignDialogOpen(true)}>
            Créer une campagne
          </Button>
        </div>
      </div>

      <ProspectFilters value={filters} onChange={(next) => fetchProspects(next)} />

      {selectedIds.length > 0 ? (
        <div className="space-x-2">
          <Button variant="outline" onClick={() => bulkUpdateStatus(selectedIds, "active")}>Passer en clients</Button>
          <Button variant="outline" onClick={() => bulkAddTags(selectedIds, [])}>Ajouter tags</Button>
          <Button
            variant="outline"
            onClick={async () => {
              const blob = await exportCsv(filters);
              if (!blob) return;
              const url = URL.createObjectURL(blob);
              const anchor = document.createElement("a");
              anchor.href = url;
              anchor.download = "prospects.csv";
              anchor.click();
              URL.revokeObjectURL(url);
            }}
          >
            Export CSV
          </Button>
        </div>
      ) : null}

      <ProspectTable
        prospects={clients}
        selectedIds={selectedIds}
        onSelect={setSelectedIds}
        onConvert={openConvert}
        onCreateCampaign={openCampaign}
      />

      <ConvertToClientDialog
        open={convertDialogOpen}
        onOpenChange={setConvertDialogOpen}
        onConfirm={async () => {
          if (activeProspectId) {
            await convertToClient(activeProspectId);
          }
          setConvertDialogOpen(false);
        }}
      />

      <CreateCampaignFromProspectsDialog
        open={campaignDialogOpen}
        onOpenChange={setCampaignDialogOpen}
        filters={filters}
      />
    </div>
  );
}
