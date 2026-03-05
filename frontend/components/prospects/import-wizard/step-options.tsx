"use client";

interface StepOptionsProps {
  tags: string[];
  defaultStatus: "prospect" | "lead" | "active";
  duplicateStrategy: "skip" | "update";
  onChange: (payload: {
    tags: string[];
    defaultStatus: "prospect" | "lead" | "active";
    duplicateStrategy: "skip" | "update";
  }) => void;
}

export function StepOptions({
  tags,
  defaultStatus,
  duplicateStrategy,
  onChange,
}: StepOptionsProps) {
  return (
    <div className="space-y-4">
      <div className="space-y-2">
        <label htmlFor="tags">Tags (séparés par virgule)</label>
        <input
          id="tags"
          value={tags.join(",")}
          onChange={(event) =>
            onChange({
              tags: event.target.value
                .split(",")
                .map((item) => item.trim())
                .filter(Boolean),
              defaultStatus,
              duplicateStrategy,
            })
          }
        />
      </div>

      <div className="space-y-2">
        <label htmlFor="status">Statut par défaut</label>
        <select
          id="status"
          value={defaultStatus}
          onChange={(event) =>
            onChange({
              tags,
              defaultStatus: event.target.value as
                | "prospect"
                | "lead"
                | "active",
              duplicateStrategy,
            })
          }
        >
          <option value="prospect">prospect</option>
          <option value="lead">lead</option>
          <option value="active">active</option>
        </select>
      </div>

      <fieldset className="space-y-2">
        <legend>Gestion des doublons</legend>
        <label className="mr-4">
          <input
            type="radio"
            checked={duplicateStrategy === "skip"}
            onChange={() =>
              onChange({ tags, defaultStatus, duplicateStrategy: "skip" })
            }
          />
          Ignorer
        </label>
        <label>
          <input
            type="radio"
            checked={duplicateStrategy === "update"}
            onChange={() =>
              onChange({ tags, defaultStatus, duplicateStrategy: "update" })
            }
          />
          Mettre à jour
        </label>
      </fieldset>
    </div>
  );
}
