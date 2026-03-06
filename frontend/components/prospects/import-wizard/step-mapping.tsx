"use client";

const CRM_FIELDS = [
  "name",
  "email",
  "phone",
  "address",
  "city",
  "zip_code",
  "department",
  "country",
  "industry",
  "notes",
  "contact.first_name",
  "contact.last_name",
  "contact.position",
  "",
];

interface StepMappingProps {
  columnList: string[];
  previewRows: Array<Record<string, string | null>>;
  mapping: Record<string, string | null>;
  onChange: (mapping: Record<string, string | null>) => void;
}

export function StepMapping({
  columnList,
  previewRows,
  mapping,
  onChange,
}: StepMappingProps) {
  return (
    <div className="space-y-4">
      <div className="space-y-2">
        {columnList.map((column) => (
          <div key={column} className="grid grid-cols-2 gap-2">
            <span className="text-sm">{column}</span>
            <select
              value={mapping[column] ?? ""}
              onChange={(event) =>
                onChange({
                  ...mapping,
                  [column]: event.target.value || null,
                })
              }
            >
              {CRM_FIELDS.map((field) => (
                <option key={field || "ignore"} value={field}>
                  {field || "— ignorer"}
                </option>
              ))}
            </select>
          </div>
        ))}
      </div>

      <div>
        <h3 className="font-medium">Aperçu</h3>
        <pre className="overflow-auto rounded border p-2 text-xs">
          {JSON.stringify(previewRows, null, 2)}
        </pre>
      </div>
    </div>
  );
}
