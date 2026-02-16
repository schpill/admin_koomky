"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import { useForm } from "react-hook-form";
import { z } from "zod";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";

const schema = z.object({
  duration_minutes: z.coerce
    .number({ invalid_type_error: "Duration must be a number" })
    .int("Duration must be a whole number")
    .min(1, "Duration must be greater than 0"),
  date: z.string().min(1, "Date is required"),
  description: z.string().optional(),
});

export type TimeEntryFormValues = z.infer<typeof schema>;

interface TimeEntryFormProps {
  onSubmit: (values: TimeEntryFormValues) => Promise<void> | void;
}

export function TimeEntryForm({ onSubmit }: TimeEntryFormProps) {
  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
    reset,
  } = useForm<TimeEntryFormValues>({
    resolver: zodResolver(schema),
    defaultValues: {
      duration_minutes: 60,
      date: new Date().toISOString().slice(0, 10),
      description: "",
    },
  });

  const submit = async (values: TimeEntryFormValues) => {
    await onSubmit({
      duration_minutes: Number(values.duration_minutes),
      date: values.date,
      description: values.description?.trim() || undefined,
    });
    reset({
      duration_minutes: values.duration_minutes,
      date: values.date,
      description: "",
    });
  };

  return (
    <form className="space-y-4" onSubmit={handleSubmit(submit)}>
      <div className="space-y-2">
        <Label htmlFor="duration_minutes">Duration (minutes)</Label>
        <Input
          id="duration_minutes"
          type="number"
          step={1}
          {...register("duration_minutes")}
        />
        {errors.duration_minutes && (
          <p className="text-sm text-destructive">
            {errors.duration_minutes.message}
          </p>
        )}
      </div>

      <div className="space-y-2">
        <Label htmlFor="date">Date</Label>
        <Input id="date" type="date" {...register("date")} />
        {errors.date && (
          <p className="text-sm text-destructive">{errors.date.message}</p>
        )}
      </div>

      <div className="space-y-2">
        <Label htmlFor="description">Description</Label>
        <Textarea id="description" rows={3} {...register("description")} />
      </div>

      <Button type="submit" disabled={isSubmitting}>
        Save time entry
      </Button>
    </form>
  );
}
