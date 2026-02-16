"use client";

import { useEffect } from "react";
import { useRouter } from "next/navigation";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { toast } from "sonner";
import Link from "next/link";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { useProjectStore } from "@/lib/stores/projects";
import { useClientStore } from "@/lib/stores/clients";

const schema = z
  .object({
    client_id: z.string().min(1, "Client is required"),
    name: z.string().min(2, "Project name is required"),
    description: z.string().optional(),
    billing_type: z.enum(["hourly", "fixed"]),
    hourly_rate: z.coerce.number().optional(),
    fixed_price: z.coerce.number().optional(),
    estimated_hours: z.coerce.number().optional(),
    start_date: z.string().optional(),
    deadline: z.string().optional(),
  })
  .superRefine((values, context) => {
    if (
      values.billing_type === "hourly" &&
      (!values.hourly_rate || values.hourly_rate <= 0)
    ) {
      context.addIssue({
        code: z.ZodIssueCode.custom,
        message: "Hourly rate is required",
        path: ["hourly_rate"],
      });
    }

    if (
      values.billing_type === "fixed" &&
      (!values.fixed_price || values.fixed_price <= 0)
    ) {
      context.addIssue({
        code: z.ZodIssueCode.custom,
        message: "Fixed price is required",
        path: ["fixed_price"],
      });
    }
  });

type FormValues = z.infer<typeof schema>;

export default function CreateProjectPage() {
  const router = useRouter();
  const { createProject } = useProjectStore();
  const { clients, fetchClients } = useClientStore();

  const {
    register,
    handleSubmit,
    watch,
    setValue,
    formState: { errors, isSubmitting },
  } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: {
      billing_type: "hourly",
    },
  });

  const billingType = watch("billing_type");

  useEffect(() => {
    fetchClients();
  }, [fetchClients]);

  const onSubmit = async (values: FormValues) => {
    try {
      const project = await createProject({
        ...values,
        description: values.description || undefined,
        hourly_rate:
          values.billing_type === "hourly" ? values.hourly_rate : undefined,
        fixed_price:
          values.billing_type === "fixed" ? values.fixed_price : undefined,
      });

      toast.success("Project created successfully");
      if (project?.id) {
        router.push(`/projects/${project.id}`);
        return;
      }
      router.push("/projects");
    } catch (error) {
      toast.error((error as Error).message || "Unable to create project");
    }
  };

  return (
    <div className="mx-auto w-full max-w-4xl space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-3xl font-bold">Create project</h1>
        <Button variant="outline" asChild>
          <Link href="/projects">Back to projects</Link>
        </Button>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Project details</CardTitle>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="name">Project name</Label>
              <Input id="name" {...register("name")} />
              {errors.name && (
                <p className="text-sm text-destructive">
                  {errors.name.message}
                </p>
              )}
            </div>

            <div className="space-y-2">
              <Label htmlFor="client">Client</Label>
              <Select onValueChange={(value) => setValue("client_id", value)}>
                <SelectTrigger id="client">
                  <SelectValue placeholder="Select a client" />
                </SelectTrigger>
                <SelectContent>
                  {clients.map((client) => (
                    <SelectItem key={client.id} value={client.id}>
                      {client.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              {errors.client_id && (
                <p className="text-sm text-destructive">
                  {errors.client_id.message}
                </p>
              )}
            </div>

            <div className="space-y-2">
              <Label htmlFor="description">Description</Label>
              <Textarea
                id="description"
                rows={4}
                {...register("description")}
              />
            </div>

            <div className="grid gap-4 md:grid-cols-2">
              <div className="space-y-2">
                <Label>Billing type</Label>
                <Select
                  defaultValue="hourly"
                  onValueChange={(value) =>
                    setValue("billing_type", value as "hourly" | "fixed")
                  }
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="hourly">Hourly</SelectItem>
                    <SelectItem value="fixed">Fixed</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              {billingType === "hourly" ? (
                <div className="space-y-2">
                  <Label htmlFor="hourly_rate">Hourly rate (EUR)</Label>
                  <Input
                    id="hourly_rate"
                    type="number"
                    step="0.01"
                    {...register("hourly_rate")}
                  />
                  {errors.hourly_rate && (
                    <p className="text-sm text-destructive">
                      {errors.hourly_rate.message}
                    </p>
                  )}
                </div>
              ) : (
                <div className="space-y-2">
                  <Label htmlFor="fixed_price">Fixed price (EUR)</Label>
                  <Input
                    id="fixed_price"
                    type="number"
                    step="0.01"
                    {...register("fixed_price")}
                  />
                  {errors.fixed_price && (
                    <p className="text-sm text-destructive">
                      {errors.fixed_price.message}
                    </p>
                  )}
                </div>
              )}
            </div>

            <div className="grid gap-4 md:grid-cols-3">
              <div className="space-y-2">
                <Label htmlFor="estimated_hours">Estimated hours</Label>
                <Input
                  id="estimated_hours"
                  type="number"
                  step="0.25"
                  {...register("estimated_hours")}
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="start_date">Start date</Label>
                <Input
                  id="start_date"
                  type="date"
                  {...register("start_date")}
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="deadline">Deadline</Label>
                <Input id="deadline" type="date" {...register("deadline")} />
              </div>
            </div>

            <div className="flex justify-end">
              <Button type="submit" disabled={isSubmitting}>
                {isSubmitting ? "Creating..." : "Create project"}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  );
}
