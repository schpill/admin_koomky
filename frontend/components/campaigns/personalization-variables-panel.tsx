"use client";

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

interface PersonalizationVariablesPanelProps {
  onInsert?: (token: string) => void;
}

interface VariableItem {
  token: string;
  description: string;
}

const VARIABLES: VariableItem[] = [
  { token: "{{first_name}}", description: "Prénom du contact" },
  { token: "{{last_name}}", description: "Nom du contact" },
  { token: "{{company}}", description: "Nom de l'entreprise" },
  { token: "{{email}}", description: "Email du contact" },
  { token: "{{phone}}", description: "Téléphone du contact" },
  { token: "{{contact.position}}", description: "Poste du contact" },
  { token: "{{client.city}}", description: "Ville du client" },
  { token: "{{client.country}}", description: "Pays du client" },
  { token: "{{client.address}}", description: "Adresse du client" },
  { token: "{{client.zip_code}}", description: "Code postal du client" },
  { token: "{{client.industry}}", description: "Secteur du client" },
  { token: "{{client.department}}", description: "Département du client" },
  { token: "{{client.reference}}", description: "Référence client" },
];

export function PersonalizationVariablesPanel({
  onInsert,
}: PersonalizationVariablesPanelProps) {
  const handleCopy = async (token: string) => {
    if (typeof navigator === "undefined" || !navigator.clipboard) {
      return;
    }

    await navigator.clipboard.writeText(token);
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">Variables disponibles</CardTitle>
      </CardHeader>
      <CardContent className="space-y-2">
        {VARIABLES.map((item) => (
          <div
            key={item.token}
            className="flex flex-wrap items-center justify-between gap-2 rounded-md border p-2"
          >
            <div>
              <p className="text-sm font-medium">{item.token}</p>
              <p className="text-xs text-muted-foreground">{item.description}</p>
            </div>
            <div className="flex gap-2">
              <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={() => handleCopy(item.token)}
              >
                Copier
              </Button>
              {onInsert ? (
                <Button type="button" size="sm" onClick={() => onInsert(item.token)}>
                  Insérer
                </Button>
              ) : null}
            </div>
          </div>
        ))}
      </CardContent>
    </Card>
  );
}
