"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { DripStepForm } from "@/components/drip/drip-step-form";
import {
  useDripSequencesStore,
  type DripStepInput,
} from "@/lib/stores/drip-sequences";

export default function CreateDripSequencePage() {
  const router = useRouter();
  const { createSequence } = useDripSequencesStore();
  const [name, setName] = useState("");
  const [steps, setSteps] = useState<DripStepInput[]>([
    {
      position: 1,
      delay_hours: 0,
      condition: "none",
      subject: "Welcome",
      content: "<p>Hello {{ first_name }}</p>",
    },
  ]);

  const save = async () => {
    const sequence = await createSequence({
      name,
      trigger_event: "manual",
      status: "active",
      steps,
    });

    if (sequence?.id) {
      router.push(`/campaigns/drip/${sequence.id}`);
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">Create Drip Sequence</h1>
        <p className="text-sm text-muted-foreground">
          Compose a sequence of automated campaign steps.
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Sequence settings</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="sequence-name">Name</Label>
            <Input
              id="sequence-name"
              value={name}
              onChange={(event) => setName(event.target.value)}
            />
          </div>

          {steps.map((step, index) => (
            <DripStepForm
              key={step.position}
              value={step}
              onChange={(next) => {
                const updated = [...steps];
                updated[index] = next;
                setSteps(updated);
              }}
            />
          ))}

          <Button type="button" onClick={save}>
            Save sequence
          </Button>
        </CardContent>
      </Card>
    </div>
  );
}
