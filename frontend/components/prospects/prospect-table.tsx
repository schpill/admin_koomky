"use client";

import { ProspectClient } from "@/lib/stores/prospects";

interface ProspectTableProps {
  prospects: ProspectClient[];
  selectedIds: string[];
  onSelect: (ids: string[]) => void;
  onConvert: (id: string) => void;
  onCreateCampaign: (id: string) => void;
}

export function ProspectTable({
  prospects,
  selectedIds,
  onSelect,
  onConvert,
  onCreateCampaign,
}: ProspectTableProps) {
  const toggle = (id: string) => {
    if (selectedIds.includes(id)) {
      onSelect(selectedIds.filter((item) => item !== id));
    } else {
      onSelect([...selectedIds, id]);
    }
  };

  return (
    <table className="w-full text-sm">
      <thead>
        <tr>
          <th />
          <th>Nom</th>
          <th>Secteur</th>
          <th>Département</th>
          <th>Ville</th>
          <th>Téléphone</th>
          <th>Email</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        {prospects.map((prospect) => (
          <tr key={prospect.id}>
            <td>
              <input
                type="checkbox"
                checked={selectedIds.includes(prospect.id)}
                onChange={() => toggle(prospect.id)}
              />
            </td>
            <td>{prospect.name}</td>
            <td>{prospect.industry || "-"}</td>
            <td>{prospect.department || "-"}</td>
            <td>{prospect.city || "-"}</td>
            <td>{prospect.phone || "-"}</td>
            <td>{prospect.email || "-"}</td>
            <td className="space-x-2">
              <button onClick={() => onConvert(prospect.id)}>Convertir</button>
              <button onClick={() => onCreateCampaign(prospect.id)}>Créer campagne</button>
            </td>
          </tr>
        ))}
      </tbody>
    </table>
  );
}
