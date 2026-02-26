import { ProductCampaignWizard } from "@/components/products/product-campaign-wizard";

interface CampaignGeneratePageProps {
  params: { id: string };
}

export default function CampaignGeneratePage({
  params,
}: CampaignGeneratePageProps) {
  return (
    <div className="flex-1 space-y-4 p-4 md:p-8 pt-6">
      <div className="max-w-4xl mx-auto">
        <div className="mb-6">
          <h2 className="text-3xl font-bold tracking-tight">
            Générer une campagne email IA
          </h2>
          <p className="text-muted-foreground">
            Créez automatiquement un email de prospection personnalisé avec l'IA
          </p>
        </div>

        <ProductCampaignWizard productId={params.id} />
      </div>
    </div>
  );
}
