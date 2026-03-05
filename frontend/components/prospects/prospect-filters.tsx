"use client";

import { useEffect, useState } from "react";
import { apiClient } from "@/lib/api";
import { FRENCH_DEPARTMENTS } from "@/lib/prospects/departments";
import type { ProspectFilters as ProspectFiltersValue } from "@/lib/stores/prospects";

interface ProspectFiltersProps {
  value: ProspectFiltersValue;
  onChange: (value: ProspectFiltersValue) => void;
}

export function ProspectFilters({ value, onChange }: ProspectFiltersProps) {
  const [industries, setIndustries] = useState<string[]>([]);

  useEffect(() => {
    apiClient
      .get<string[]>("/prospects/industries")
      .then((response) => setIndustries(response.data || []))
      .catch(() => setIndustries([]));
  }, []);

  return (
    <div className="space-y-3 rounded border p-3">
      <input
        placeholder="Rechercher"
        value={value.search || ""}
        onChange={(event) => onChange({ ...value, search: event.target.value })}
      />

      <input
        list="industry-options"
        placeholder="Secteur"
        value={value.industry || ""}
        onChange={(event) =>
          onChange({ ...value, industry: event.target.value })
        }
      />
      <datalist id="industry-options">
        {industries.map((industry) => (
          <option key={industry} value={industry} />
        ))}
      </datalist>

      <select
        value={value.department || ""}
        onChange={(event) =>
          onChange({ ...value, department: event.target.value || undefined })
        }
      >
        <option value="">Tous les départements</option>
        {FRENCH_DEPARTMENTS.map((department) => (
          <option key={department.code} value={department.code}>
            {department.code} - {department.name}
          </option>
        ))}
      </select>

      <input
        placeholder="Ville"
        value={value.city || ""}
        onChange={(event) => onChange({ ...value, city: event.target.value })}
      />

      <button onClick={() => onChange({})}>Réinitialiser</button>
    </div>
  );
}
