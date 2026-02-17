"use client";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import type {
  SegmentBoolean,
  SegmentCriterion,
  SegmentFilters,
} from "@/lib/stores/segments";

interface SegmentBuilderProps {
  value: SegmentFilters;
  onChange: (next: SegmentFilters) => void;
  previewCount?: number;
  onPreview?: () => void;
}

const criterionTypes = [
  "tag",
  "last_interaction",
  "project_status",
  "revenue",
  "location",
  "created_at",
  "custom_field",
];

const criterionOperators = [
  "equals",
  "not_equals",
  ">",
  "<",
  "contains",
  "exists",
  "not_exists",
  "before",
  "after",
];

function cloneFilters(filters: SegmentFilters): SegmentFilters {
  return {
    group_boolean: filters.group_boolean ?? "and",
    criteria_boolean: filters.criteria_boolean ?? "or",
    groups: (filters.groups || []).map((group) => ({
      criteria: (group.criteria || []).map((criterion) => ({ ...criterion })),
    })),
  };
}

function fallbackCriterion(): SegmentCriterion {
  return {
    type: "tag",
    operator: "equals",
    value: "",
  };
}

export function SegmentBuilder({
  value,
  onChange,
  previewCount,
  onPreview,
}: SegmentBuilderProps) {
  const filters = cloneFilters(value);

  const setGroupBoolean = (nextValue: SegmentBoolean) => {
    onChange({ ...filters, group_boolean: nextValue });
  };

  const setCriteriaBoolean = (nextValue: SegmentBoolean) => {
    onChange({ ...filters, criteria_boolean: nextValue });
  };

  const addGroup = () => {
    onChange({
      ...filters,
      groups: [...filters.groups, { criteria: [fallbackCriterion()] }],
    });
  };

  const removeGroup = (groupIndex: number) => {
    const groups = filters.groups.filter((_, index) => index !== groupIndex);

    onChange({
      ...filters,
      groups:
        groups.length === 0 ? [{ criteria: [fallbackCriterion()] }] : groups,
    });
  };

  const addCriterion = (groupIndex: number) => {
    const groups = filters.groups.map((group, index) => {
      if (index !== groupIndex) {
        return group;
      }

      return {
        ...group,
        criteria: [...group.criteria, fallbackCriterion()],
      };
    });

    onChange({ ...filters, groups });
  };

  const removeCriterion = (groupIndex: number, criterionIndex: number) => {
    const groups = filters.groups.map((group, currentGroupIndex) => {
      if (currentGroupIndex !== groupIndex) {
        return group;
      }

      const criteria = group.criteria.filter(
        (_, index) => index !== criterionIndex
      );

      return {
        ...group,
        criteria: criteria.length === 0 ? [fallbackCriterion()] : criteria,
      };
    });

    onChange({ ...filters, groups });
  };

  const updateCriterion = (
    groupIndex: number,
    criterionIndex: number,
    patch: Partial<SegmentCriterion>
  ) => {
    const groups = filters.groups.map((group, currentGroupIndex) => {
      if (currentGroupIndex !== groupIndex) {
        return group;
      }

      return {
        ...group,
        criteria: group.criteria.map((criterion, currentCriterionIndex) => {
          if (currentCriterionIndex !== criterionIndex) {
            return criterion;
          }

          return {
            ...criterion,
            ...patch,
          };
        }),
      };
    });

    onChange({ ...filters, groups });
  };

  return (
    <div className="space-y-4">
      <div className="grid gap-4 md:grid-cols-2">
        <div className="space-y-2">
          <Label>Between groups</Label>
          <Select
            value={filters.group_boolean ?? "and"}
            onValueChange={(next) => setGroupBoolean(next as SegmentBoolean)}
          >
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="and">AND</SelectItem>
              <SelectItem value="or">OR</SelectItem>
            </SelectContent>
          </Select>
        </div>

        <div className="space-y-2">
          <Label>Within group criteria</Label>
          <Select
            value={filters.criteria_boolean ?? "or"}
            onValueChange={(next) => setCriteriaBoolean(next as SegmentBoolean)}
          >
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="and">AND</SelectItem>
              <SelectItem value="or">OR</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      {typeof previewCount === "number" && (
        <div className="rounded-md border bg-muted/40 px-3 py-2 text-sm font-medium">
          {previewCount} matching contacts
        </div>
      )}

      <div className="space-y-4">
        {filters.groups.map((group, groupIndex) => (
          <div
            key={`group-${groupIndex}`}
            className="space-y-3 rounded-lg border p-4"
          >
            <div className="flex items-center justify-between">
              <p className="text-sm font-semibold">Group {groupIndex + 1}</p>
              <Button
                type="button"
                variant="ghost"
                size="sm"
                onClick={() => removeGroup(groupIndex)}
              >
                Remove group
              </Button>
            </div>

            {group.criteria.map((criterion, criterionIndex) => {
              const criterionNumber = criterionIndex + 1;

              return (
                <div
                  key={`criterion-${groupIndex}-${criterionIndex}`}
                  className="grid gap-2 rounded-md bg-muted/30 p-3 md:grid-cols-4"
                >
                  <div className="space-y-1">
                    <Label htmlFor={`type-${groupIndex}-${criterionIndex}`}>
                      Criterion type {criterionNumber}
                    </Label>
                    <select
                      id={`type-${groupIndex}-${criterionIndex}`}
                      aria-label={`Criterion type ${criterionNumber}`}
                      value={criterion.type}
                      onChange={(event) =>
                        updateCriterion(groupIndex, criterionIndex, {
                          type: event.target.value,
                        })
                      }
                      className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                    >
                      {criterionTypes.map((type) => (
                        <option key={type} value={type}>
                          {type}
                        </option>
                      ))}
                    </select>
                  </div>

                  <div className="space-y-1">
                    <Label htmlFor={`operator-${groupIndex}-${criterionIndex}`}>
                      Operator
                    </Label>
                    <select
                      id={`operator-${groupIndex}-${criterionIndex}`}
                      value={criterion.operator}
                      onChange={(event) =>
                        updateCriterion(groupIndex, criterionIndex, {
                          operator: event.target.value,
                        })
                      }
                      className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                    >
                      {criterionOperators.map((operator) => (
                        <option key={operator} value={operator}>
                          {operator}
                        </option>
                      ))}
                    </select>
                  </div>

                  <div className="space-y-1 md:col-span-2">
                    <Label htmlFor={`value-${groupIndex}-${criterionIndex}`}>
                      Criterion value {criterionNumber}
                    </Label>
                    <Input
                      id={`value-${groupIndex}-${criterionIndex}`}
                      aria-label={`Criterion value ${criterionNumber}`}
                      value={String(criterion.value ?? "")}
                      onChange={(event) =>
                        updateCriterion(groupIndex, criterionIndex, {
                          value: event.target.value,
                        })
                      }
                    />
                  </div>

                  <div className="md:col-span-4 flex justify-end">
                    <Button
                      type="button"
                      variant="outline"
                      size="sm"
                      onClick={() =>
                        removeCriterion(groupIndex, criterionIndex)
                      }
                    >
                      Remove criterion
                    </Button>
                  </div>
                </div>
              );
            })}

            <Button
              type="button"
              variant="secondary"
              size="sm"
              onClick={() => addCriterion(groupIndex)}
            >
              Add criterion
            </Button>
          </div>
        ))}
      </div>

      <div className="flex flex-wrap gap-2">
        <Button type="button" variant="outline" onClick={addGroup}>
          Add group
        </Button>
        {onPreview && (
          <Button type="button" onClick={onPreview}>
            Refresh preview
          </Button>
        )}
      </div>
    </div>
  );
}
