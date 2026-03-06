"use client";

import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import {
  addEdge,
  Background,
  BackgroundVariant,
  Connection,
  Controls,
  Edge,
  EdgeChange,
  Handle,
  MiniMap,
  Node,
  NodeChange,
  NodeProps,
  Position,
  ReactFlow,
  ReactFlowInstance,
  ReactFlowProvider,
  applyEdgeChanges,
  applyNodeChanges,
} from "@xyflow/react";
import "@xyflow/react/dist/style.css";
import {
  Clock3,
  Flag,
  GitBranch,
  Mail,
  Plus,
  Sparkles,
  Tags,
  Target,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import {
  type Workflow,
  type WorkflowStep,
  type WorkflowStepType,
} from "@/lib/stores/workflows";
import { WorkflowNodeConfig } from "@/components/workflows/workflow-node-config";

interface WorkflowBuilderProps {
  value: Workflow;
  onChange: (next: Workflow) => void;
  onSave: () => Promise<void>;
}

type WorkflowNodeData = {
  step: WorkflowStep;
  isEntry: boolean;
  onSelect: (stepId: string) => void;
  onRemove: (stepId: string) => void;
  onMakeEntry: (stepId: string) => void;
};

const STEP_TYPES: WorkflowStepType[] = [
  "send_email",
  "wait",
  "condition",
  "update_score",
  "add_tag",
  "end",
];

const DEFAULT_VIEWPORT = { x: 0, y: 0, zoom: 0.95 };
const NODE_X_SPACING = 280;
const NODE_Y_SPACING = 180;

const STEP_META: Record<
  WorkflowStepType,
  {
    label: string;
    subtitle: string;
    icon: typeof Mail;
    accent: string;
    className: string;
  }
> = {
  send_email: {
    label: "Send email",
    subtitle: "Message delivery",
    icon: Mail,
    accent: "#1f93ff",
    className:
      "border-sky-400/50 bg-linear-to-br from-sky-100/85 via-white to-cyan-100/75 text-sky-950",
  },
  wait: {
    label: "Wait",
    subtitle: "Delay execution",
    icon: Clock3,
    accent: "#f59e0b",
    className:
      "border-amber-400/50 bg-linear-to-br from-amber-100/85 via-white to-orange-100/75 text-amber-950",
  },
  condition: {
    label: "Condition",
    subtitle: "Route logic",
    icon: GitBranch,
    accent: "#8b5cf6",
    className:
      "border-violet-400/50 bg-linear-to-br from-violet-100/85 via-white to-fuchsia-100/70 text-violet-950",
  },
  update_score: {
    label: "Update score",
    subtitle: "Adjust signal",
    icon: Sparkles,
    accent: "#10b981",
    className:
      "border-emerald-400/50 bg-linear-to-br from-emerald-100/85 via-white to-teal-100/75 text-emerald-950",
  },
  add_tag: {
    label: "Add tag",
    subtitle: "Tag contact",
    icon: Tags,
    accent: "#ec4899",
    className:
      "border-pink-400/50 bg-linear-to-br from-pink-100/85 via-white to-rose-100/75 text-pink-950",
  },
  end: {
    label: "End",
    subtitle: "Finish workflow",
    icon: Flag,
    accent: "#334155",
    className:
      "border-slate-400/50 bg-linear-to-br from-slate-100/85 via-white to-slate-200/80 text-slate-950",
  },
  remove_tag: {
    label: "Remove tag",
    subtitle: "Tag cleanup",
    icon: Tags,
    accent: "#ef4444",
    className:
      "border-red-400/50 bg-linear-to-br from-red-100/85 via-white to-orange-100/75 text-red-950",
  },
  enroll_drip: {
    label: "Enroll drip",
    subtitle: "Sequence handoff",
    icon: Target,
    accent: "#0f766e",
    className:
      "border-teal-500/50 bg-linear-to-br from-teal-100/85 via-white to-emerald-100/70 text-teal-950",
  },
  update_field: {
    label: "Update field",
    subtitle: "Contact profile",
    icon: Target,
    accent: "#2563eb",
    className:
      "border-blue-400/50 bg-linear-to-br from-blue-100/85 via-white to-indigo-100/70 text-blue-950",
  },
};

function createStep(
  type: WorkflowStepType,
  position?: { x: number; y: number }
): WorkflowStep {
  return {
    id: `tmp-${type}-${Date.now()}-${Math.random().toString(16).slice(2, 8)}`,
    type,
    config:
      type === "send_email"
        ? { subject: "", content: "" }
        : type === "wait"
          ? { duration: 1, unit: "hours" }
          : type === "condition"
            ? { attribute: "email_score", operator: "gte", value: 50 }
            : type === "update_score"
              ? { delta: 5 }
              : type === "add_tag"
                ? { tag: "" }
                : {},
    next_step_id: null,
    else_step_id: null,
    position_x: position?.x ?? 0,
    position_y: position?.y ?? 0,
  };
}

function getStepSummary(step: WorkflowStep): string {
  const config = step.config || {};

  switch (step.type) {
    case "send_email":
      return String(config.subject || "Draft email");
    case "wait":
      return `${Number(config.duration || 1)} ${String(config.unit || "hours")}`;
    case "condition":
      return `${String(config.attribute || "attribute")} ${String(config.operator || "eq")} ${String(config.value || "")}`.trim();
    case "update_score":
      return `${Number(config.delta || 0) >= 0 ? "+" : ""}${Number(config.delta || 0)} points`;
    case "add_tag":
      return String(config.tag || "Tag to apply");
    case "end":
      return "Workflow completion";
    default:
      return STEP_META[step.type]?.subtitle || "Workflow step";
  }
}

function getDefaultPosition(stepCount: number): { x: number; y: number } {
  const row = Math.floor(stepCount / 3);
  const column = stepCount % 3;

  return {
    x: column * NODE_X_SPACING,
    y: row * NODE_Y_SPACING,
  };
}

function autoLayoutSteps(
  steps: WorkflowStep[],
  entryStepId?: string | null
): WorkflowStep[] {
  if (steps.length === 0) {
    return steps;
  }

  const stepMap = new Map(
    steps.map((step) => [step.id, { ...step } satisfies WorkflowStep] as const)
  );
  const ordered: WorkflowStep[] = [];
  const visited = new Set<string>();

  const visit = (stepId: string | null | undefined, lane: number): void => {
    if (!stepId || visited.has(stepId)) {
      return;
    }

    const step = stepMap.get(stepId);
    if (!step) {
      return;
    }

    visited.add(stepId);
    ordered.push({
      ...step,
      position_x: (ordered.length % 4) * NODE_X_SPACING,
      position_y: lane * NODE_Y_SPACING,
    });

    visit(step.next_step_id, lane);
    if (step.type === "condition") {
      visit(step.else_step_id, lane + 1);
    }
  };

  visit(entryStepId || steps[0]?.id, 0);

  for (const step of steps) {
    if (step.id && !visited.has(step.id)) {
      ordered.push({
        ...step,
        position_x: (ordered.length % 4) * NODE_X_SPACING,
        position_y:
          Math.floor(ordered.length / 4) * NODE_Y_SPACING + NODE_Y_SPACING,
      });
    }
  }

  return ordered;
}

function workflowToNodes(
  workflow: Workflow,
  nodeData: Omit<WorkflowNodeData, "step" | "isEntry">
): Node<WorkflowNodeData>[] {
  return workflow.steps.map((step) => ({
    id: step.id || `missing-${Math.random().toString(16).slice(2, 8)}`,
    type: "workflow-step",
    position: {
      x: step.position_x || 0,
      y: step.position_y || 0,
    },
    data: {
      ...nodeData,
      step,
      isEntry: workflow.entry_step_id === step.id,
    },
    draggable: true,
    selectable: true,
  }));
}

function workflowToEdges(steps: WorkflowStep[]): Edge[] {
  const edges: Edge[] = [];

  for (const step of steps) {
    if (!step.id) {
      continue;
    }

    if (step.next_step_id) {
      edges.push({
        id: `${step.id}-next-${step.next_step_id}`,
        source: step.id,
        target: step.next_step_id,
        sourceHandle: "next",
        label: step.type === "condition" ? "yes" : undefined,
        animated: step.type !== "end",
        className: "stroke-primary",
      });
    }

    if (step.type === "condition" && step.else_step_id) {
      edges.push({
        id: `${step.id}-else-${step.else_step_id}`,
        source: step.id,
        target: step.else_step_id,
        sourceHandle: "else",
        label: "no",
        animated: true,
        className: "stroke-amber-500",
      });
    }
  }

  return edges;
}

function WorkflowFlowNode({
  data,
  selected,
}: NodeProps<Node<WorkflowNodeData>>) {
  const { step, isEntry, onSelect, onRemove, onMakeEntry } = data;
  const meta = STEP_META[step.type];
  const Icon = meta.icon;

  return (
    <div
      className={`w-64 rounded-3xl border shadow-xl shadow-slate-950/5 backdrop-blur ${meta.className} ${
        selected
          ? "ring-2 ring-primary/60 ring-offset-2 ring-offset-background"
          : ""
      }`}
      onClick={() => step.id && onSelect(step.id)}
      role="button"
      tabIndex={0}
      onKeyDown={(event) => {
        if ((event.key === "Enter" || event.key === " ") && step.id) {
          event.preventDefault();
          onSelect(step.id);
        }
      }}
    >
      <Handle
        type="target"
        position={Position.Left}
        className="!h-3 !w-3 !border-2 !border-white !bg-slate-500"
      />

      <div className="space-y-4 p-4">
        <div className="flex items-start justify-between gap-3">
          <div className="flex items-center gap-3">
            <div className="rounded-2xl border border-white/60 bg-white/75 p-2 shadow-sm">
              <Icon className="h-4 w-4" />
            </div>
            <div>
              <p className="text-sm font-semibold">{meta.label}</p>
              <p className="text-xs opacity-70">{meta.subtitle}</p>
            </div>
          </div>
          {isEntry ? (
            <span className="rounded-full bg-slate-950 px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-white">
              Entry
            </span>
          ) : null}
        </div>

        <div className="rounded-2xl border border-white/70 bg-white/65 px-3 py-2 text-sm shadow-sm">
          {getStepSummary(step)}
        </div>

        <div className="flex items-center justify-between gap-2">
          <button
            type="button"
            className="rounded-full border border-white/70 bg-white/70 px-3 py-1 text-[11px] font-medium"
            onClick={(event) => {
              event.stopPropagation();
              if (step.id) {
                onMakeEntry(step.id);
              }
            }}
          >
            Make entry
          </button>
          <button
            type="button"
            className="rounded-full border border-white/70 bg-white/70 px-3 py-1 text-[11px] font-medium"
            onClick={(event) => {
              event.stopPropagation();
              if (step.id) {
                onRemove(step.id);
              }
            }}
          >
            Remove
          </button>
        </div>
      </div>

      <Handle
        type="source"
        id="next"
        position={Position.Right}
        className="!h-3 !w-3 !border-2 !border-white !bg-primary"
      />
      {step.type === "condition" ? (
        <Handle
          type="source"
          id="else"
          position={Position.Bottom}
          className="!h-3 !w-3 !border-2 !border-white !bg-amber-500"
        />
      ) : null}
    </div>
  );
}

const nodeTypes = {
  "workflow-step": WorkflowFlowNode,
};

function WorkflowBuilderCanvas({
  value,
  onChange,
  onSave,
}: WorkflowBuilderProps) {
  const reactFlowRef = useRef<ReactFlowInstance<
    Node<WorkflowNodeData>,
    Edge
  > | null>(null);
  const [selectedStepId, setSelectedStepId] = useState<string | null>(
    value.entry_step_id || value.steps[0]?.id || null
  );
  const [nodes, setNodes] = useState<Node<WorkflowNodeData>[]>([]);
  const [edges, setEdges] = useState<Edge[]>([]);

  const selectedStep =
    value.steps.find((step) => step.id === selectedStepId) || null;

  const updateWorkflow = useCallback(
    (steps: WorkflowStep[], patch?: Partial<Workflow>) => {
      onChange({
        ...value,
        ...patch,
        steps,
      });
    },
    [onChange, value]
  );

  const handleSelectStep = useCallback((stepId: string) => {
    setSelectedStepId(stepId);
  }, []);

  const handleRemoveStep = useCallback(
    (stepId: string) => {
      const nextSteps = value.steps
        .filter((step) => step.id !== stepId)
        .map((step) => ({
          ...step,
          next_step_id: step.next_step_id === stepId ? null : step.next_step_id,
          else_step_id: step.else_step_id === stepId ? null : step.else_step_id,
        }));

      updateWorkflow(nextSteps, {
        entry_step_id:
          value.entry_step_id === stepId
            ? nextSteps[0]?.id || null
            : value.entry_step_id,
      });

      if (selectedStepId === stepId) {
        setSelectedStepId(nextSteps[0]?.id || null);
      }
    },
    [selectedStepId, updateWorkflow, value.entry_step_id, value.steps]
  );

  const handleMakeEntry = useCallback(
    (stepId: string) => {
      updateWorkflow(value.steps, { entry_step_id: stepId });
    },
    [updateWorkflow, value.steps]
  );

  useEffect(() => {
    setNodes(
      workflowToNodes(value, {
        onSelect: handleSelectStep,
        onRemove: handleRemoveStep,
        onMakeEntry: handleMakeEntry,
      })
    );
    setEdges(workflowToEdges(value.steps));
  }, [handleMakeEntry, handleRemoveStep, handleSelectStep, value]);

  const addStepToWorkflow = (
    type: WorkflowStepType,
    position = getDefaultPosition(value.steps.length)
  ) => {
    const step = createStep(type, position);
    updateWorkflow([...value.steps, step], {
      entry_step_id: value.entry_step_id || step.id || null,
    });
    setSelectedStepId(step.id || null);
  };

  const updateSelectedStep = (updatedStep: WorkflowStep) => {
    updateWorkflow(
      value.steps.map((step) =>
        step.id === updatedStep.id ? updatedStep : step
      )
    );
  };

  const handleNodeChanges = (changes: NodeChange<Node<WorkflowNodeData>>[]) => {
    setNodes((currentNodes) => applyNodeChanges(changes, currentNodes));

    const positionChanges = changes.filter(
      (
        change
      ): change is Extract<
        NodeChange<Node<WorkflowNodeData>>,
        { type: "position" }
      > => change.type === "position" && !!change.position
    );

    if (positionChanges.length === 0) {
      return;
    }

    updateWorkflow(
      value.steps.map((step) => {
        const change = step.id
          ? positionChanges.find((item) => item.id === step.id)
          : undefined;

        if (!change?.position) {
          return step;
        }

        return {
          ...step,
          position_x: change.position.x,
          position_y: change.position.y,
        };
      })
    );
  };

  const handleEdgeChanges = (changes: EdgeChange<Edge>[]) => {
    setEdges((currentEdges) => applyEdgeChanges(changes, currentEdges));

    const removedEdges = changes.filter(
      (change): change is Extract<EdgeChange<Edge>, { type: "remove" }> =>
        change.type === "remove"
    );

    if (removedEdges.length === 0) {
      return;
    }

    updateWorkflow(
      value.steps.map((step) => {
        if (!step.id) {
          return step;
        }

        const nextRemoved = removedEdges.some((edge) =>
          edge.id.startsWith(`${step.id}-next-`)
        );
        const elseRemoved = removedEdges.some((edge) =>
          edge.id.startsWith(`${step.id}-else-`)
        );

        return {
          ...step,
          next_step_id: nextRemoved ? null : step.next_step_id,
          else_step_id: elseRemoved ? null : step.else_step_id,
        };
      })
    );
  };

  const handleConnect = (connection: Connection) => {
    if (
      !connection.source ||
      !connection.target ||
      connection.source === connection.target
    ) {
      return;
    }

    const sourceHandle = connection.sourceHandle || "next";

    updateWorkflow(
      value.steps.map((step) => {
        if (step.id !== connection.source) {
          return step;
        }

        if (sourceHandle === "else" && step.type === "condition") {
          return {
            ...step,
            else_step_id: connection.target,
          };
        }

        return {
          ...step,
          next_step_id: connection.target,
        };
      })
    );

    setEdges((currentEdges) =>
      addEdge(
        {
          ...connection,
          id: `${connection.source}-${sourceHandle}-${connection.target}`,
          label: sourceHandle === "else" ? "no" : undefined,
        },
        currentEdges.filter(
          (edge) =>
            !(
              edge.source === connection.source &&
              (edge.sourceHandle || "next") === sourceHandle
            )
        )
      )
    );
  };

  const handleDrop = (event: React.DragEvent<HTMLDivElement>) => {
    event.preventDefault();

    const stepType = event.dataTransfer.getData("application/workflow-step");
    if (!stepType) {
      return;
    }

    const position = reactFlowRef.current?.screenToFlowPosition({
      x: event.clientX,
      y: event.clientY,
    });

    addStepToWorkflow(
      stepType as WorkflowStepType,
      position ? { x: position.x, y: position.y } : undefined
    );
  };

  const tidyLayout = () => {
    const nextSteps = autoLayoutSteps(value.steps, value.entry_step_id);
    updateWorkflow(nextSteps);
    requestAnimationFrame(() => {
      reactFlowRef.current?.fitView({ duration: 250, padding: 0.2 });
    });
  };

  const selectedNodeMeta = selectedStep ? STEP_META[selectedStep.type] : null;

  return (
    <div className="grid gap-6 xl:grid-cols-[1.3fr_0.7fr]">
      <div className="space-y-6">
        <Card>
          <CardHeader>
            <CardTitle>Workflow settings</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-4 md:grid-cols-2">
            <div className="space-y-2 md:col-span-2">
              <Label htmlFor="workflow-name">Name</Label>
              <Input
                id="workflow-name"
                value={value.name}
                onChange={(event) =>
                  onChange({ ...value, name: event.target.value })
                }
              />
            </div>
            <div className="space-y-2 md:col-span-2">
              <Label htmlFor="workflow-description">Description</Label>
              <Textarea
                id="workflow-description"
                value={value.description || ""}
                rows={3}
                onChange={(event) =>
                  onChange({ ...value, description: event.target.value })
                }
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="workflow-trigger">Trigger</Label>
              <select
                id="workflow-trigger"
                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                value={value.trigger_type}
                onChange={(event) =>
                  onChange({
                    ...value,
                    trigger_type: event.target
                      .value as Workflow["trigger_type"],
                  })
                }
              >
                <option value="manual">Manual</option>
                <option value="contact_created">Contact created</option>
                <option value="email_opened">Email opened</option>
                <option value="email_clicked">Email clicked</option>
                <option value="score_threshold">Score threshold</option>
                <option value="segment_entered">Segment entered</option>
              </select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="workflow-status">Status</Label>
              <select
                id="workflow-status"
                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                value={value.status}
                onChange={(event) =>
                  onChange({
                    ...value,
                    status: event.target.value as Workflow["status"],
                  })
                }
              >
                <option value="draft">Draft</option>
                <option value="active">Active</option>
                <option value="paused">Paused</option>
                <option value="archived">Archived</option>
              </select>
            </div>
          </CardContent>
        </Card>

        <Card className="overflow-hidden">
          <CardHeader className="gap-4">
            <div className="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
              <div className="space-y-1">
                <CardTitle>Workflow builder</CardTitle>
                <p className="text-sm text-muted-foreground">
                  Drag a node onto the canvas or click to add it, then connect
                  steps with visual branches.
                </p>
              </div>
              <div className="flex flex-wrap gap-2">
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  onClick={tidyLayout}
                >
                  Tidy layout
                </Button>
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  onClick={() =>
                    reactFlowRef.current?.fitView({
                      duration: 250,
                      padding: 0.18,
                    })
                  }
                >
                  Fit workflow
                </Button>
              </div>
            </div>

            <div className="flex flex-wrap gap-2">
              {STEP_TYPES.map((type) => {
                const meta = STEP_META[type];
                const Icon = meta.icon;

                return (
                  <button
                    key={type}
                    type="button"
                    draggable
                    className={`inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-medium shadow-sm transition hover:-translate-y-0.5 ${meta.className}`}
                    onDragStart={(event) =>
                      event.dataTransfer.setData(
                        "application/workflow-step",
                        type
                      )
                    }
                    onClick={() => addStepToWorkflow(type)}
                  >
                    <Icon className="h-3.5 w-3.5" />
                    <Plus className="h-3.5 w-3.5 opacity-60" />
                    Add {meta.label.toLowerCase()}
                  </button>
                );
              })}
            </div>
          </CardHeader>
          <CardContent className="space-y-4">
            <div
              data-testid="workflow-builder-canvas"
              className="relative h-[620px] overflow-hidden rounded-[28px] border border-border/70 bg-linear-to-br from-white via-sky-50/60 to-slate-100/90 shadow-inner shadow-slate-950/5"
            >
              <div className="pointer-events-none absolute inset-x-0 top-0 z-10 flex items-center justify-between px-4 py-3">
                <div className="rounded-full border border-white/70 bg-white/80 px-3 py-1 text-xs font-medium text-slate-600 shadow-sm backdrop-blur">
                  Connect right handles for next steps, bottom amber handles for
                  else branches.
                </div>
                <div className="rounded-full border border-white/70 bg-white/80 px-3 py-1 text-xs font-medium text-slate-600 shadow-sm backdrop-blur">
                  Nodes: {value.steps.length}
                </div>
              </div>

              <ReactFlow
                nodes={nodes}
                edges={edges}
                nodeTypes={nodeTypes}
                defaultViewport={DEFAULT_VIEWPORT}
                onInit={(instance) => {
                  reactFlowRef.current = instance;
                }}
                onNodeClick={(_, node) => setSelectedStepId(node.id)}
                onPaneClick={() => setSelectedStepId(null)}
                onNodesChange={handleNodeChanges}
                onEdgesChange={handleEdgeChanges}
                onConnect={handleConnect}
                onDragOver={(event) => {
                  event.preventDefault();
                  event.dataTransfer.dropEffect = "move";
                }}
                onDrop={handleDrop}
                fitView
                fitViewOptions={{ padding: 0.18 }}
                proOptions={{ hideAttribution: true }}
              >
                <Background
                  color="#cbd5e1"
                  gap={20}
                  size={1}
                  variant={BackgroundVariant.Dots}
                />
                <Controls position="bottom-right" showInteractive={false} />
                <MiniMap
                  pannable
                  zoomable
                  position="top-right"
                  className="!h-28 !w-44 !overflow-hidden !rounded-2xl !border !border-white/80 !bg-white/85 !shadow-lg"
                  nodeColor={(node) => {
                    const workflowNode = node as Node<WorkflowNodeData>;
                    return (
                      STEP_META[workflowNode.data.step.type]?.accent ||
                      "#2459ff"
                    );
                  }}
                />
              </ReactFlow>

              <div className="pointer-events-none absolute right-4 top-4 z-10 rounded-2xl border border-white/80 bg-white/90 px-3 py-2 text-xs font-medium text-slate-700 shadow-lg backdrop-blur">
                Navigator
              </div>
            </div>

            <div className="grid gap-3 md:grid-cols-3">
              <div className="rounded-2xl border border-border/70 bg-background/70 p-4">
                <p className="text-xs font-semibold uppercase tracking-[0.18em] text-muted-foreground">
                  Entry step
                </p>
                <p className="mt-2 text-sm font-medium">
                  {value.steps
                    .find((step) => step.id === value.entry_step_id)
                    ?.type.replaceAll("_", " ") || "Not defined yet"}
                </p>
              </div>
              <div className="rounded-2xl border border-border/70 bg-background/70 p-4">
                <p className="text-xs font-semibold uppercase tracking-[0.18em] text-muted-foreground">
                  Selected node
                </p>
                <p className="mt-2 text-sm font-medium">
                  {selectedNodeMeta?.label || "None"}
                </p>
              </div>
              <div className="rounded-2xl border border-border/70 bg-background/70 p-4">
                <p className="text-xs font-semibold uppercase tracking-[0.18em] text-muted-foreground">
                  Connection mode
                </p>
                <p className="mt-2 text-sm font-medium">
                  Visual next / else branching
                </p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Button type="button" onClick={() => void onSave()}>
          Save workflow
        </Button>
      </div>

      <div className="space-y-4">
        <div className="rounded-3xl border border-border/70 bg-linear-to-br from-background via-background to-sky-50/40 p-4 shadow-sm">
          <p className="text-xs font-semibold uppercase tracking-[0.18em] text-muted-foreground">
            Node inspector
          </p>
          <p className="mt-2 text-sm text-muted-foreground">
            Select a node in the flow to edit its behavior, content or branch
            conditions.
          </p>
        </div>

        <WorkflowNodeConfig
          step={selectedStep}
          onChange={updateSelectedStep}
          onClose={() => setSelectedStepId(null)}
        />
      </div>
    </div>
  );
}

export function WorkflowBuilder(props: WorkflowBuilderProps) {
  return (
    <ReactFlowProvider>
      <WorkflowBuilderCanvas {...props} />
    </ReactFlowProvider>
  );
}
