"use client";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";

interface DynamicContentEditorProps {
  onInsert: (snippet: string) => void;
}

const ATTRIBUTE_OPTIONS = [
  "contact.first_name",
  "contact.email",
  "client.industry",
  "client.department",
  "email_score",
];

const OPERATOR_OPTIONS = ["==", "!=", ">=", "<=", ">", "<"];

export function DynamicContentEditor({ onInsert }: DynamicContentEditorProps) {
  const buildSnippet = (formData: FormData) => {
    const attribute = String(formData.get("attribute") || "contact.first_name");
    const operator = String(formData.get("operator") || "==");
    const value = String(formData.get("value") || "");
    const truthy = String(formData.get("truthy") || "");
    const falsy = String(formData.get("falsy") || "");

    const formattedValue =
      attribute === "email_score" || /^-?\d+(\.\d+)?$/.test(value)
        ? value
        : `"${value}"`;

    return `{{#if ${attribute} ${operator} ${formattedValue}}}${truthy}{{else}}${falsy}{{/if}}`;
  };

  return (
    <form
      className="space-y-4 rounded-lg border p-4"
      onSubmit={(event) => {
        event.preventDefault();
        const formData = new FormData(event.currentTarget);
        onInsert(buildSnippet(formData));
      }}
    >
      <div className="grid gap-4 md:grid-cols-3">
        <div className="space-y-2">
          <Label htmlFor="dynamic-attribute">Attribute</Label>
          <select
            id="dynamic-attribute"
            name="attribute"
            className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
            defaultValue="contact.first_name"
          >
            {ATTRIBUTE_OPTIONS.map((option) => (
              <option key={option} value={option}>
                {option}
              </option>
            ))}
          </select>
        </div>
        <div className="space-y-2">
          <Label htmlFor="dynamic-operator">Operator</Label>
          <select
            id="dynamic-operator"
            name="operator"
            className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
            defaultValue="=="
          >
            {OPERATOR_OPTIONS.map((option) => (
              <option key={option} value={option}>
                {option}
              </option>
            ))}
          </select>
        </div>
        <div className="space-y-2">
          <Label htmlFor="dynamic-value">Value</Label>
          <Input
            id="dynamic-value"
            name="value"
            defaultValue="Wedding Planner"
          />
        </div>
      </div>

      <div className="grid gap-4 md:grid-cols-2">
        <div className="space-y-2">
          <Label htmlFor="dynamic-truthy">If true</Label>
          <Textarea
            id="dynamic-truthy"
            name="truthy"
            rows={4}
            defaultValue="VIP content"
          />
        </div>
        <div className="space-y-2">
          <Label htmlFor="dynamic-falsy">If false</Label>
          <Textarea
            id="dynamic-falsy"
            name="falsy"
            rows={4}
            defaultValue="Standard content"
          />
        </div>
      </div>

      <div className="flex justify-end">
        <Button type="submit">Insert block</Button>
      </div>
    </form>
  );
}
