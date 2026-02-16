"use client";

import { useState } from "react";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";

interface ProjectFilterValues {
  search?: string;
  status?: string;
  client_id?: string;
  date_from?: string;
  date_to?: string;
}

interface ProjectFilterBarProps {
  clients: Array<{ id: string; name: string }>;
  onApply: (filters: ProjectFilterValues) => void;
}

export function ProjectFilterBar({ clients, onApply }: ProjectFilterBarProps) {
  const [search, setSearch] = useState("");
  const [status, setStatus] = useState("all");
  const [clientId, setClientId] = useState("all");
  const [dateFrom, setDateFrom] = useState("");
  const [dateTo, setDateTo] = useState("");

  const applyFilters = () => {
    onApply({
      search: search || undefined,
      status: status === "all" ? undefined : status,
      client_id: clientId === "all" ? undefined : clientId,
      date_from: dateFrom || undefined,
      date_to: dateTo || undefined,
    });
  };

  return (
    <div className="grid gap-3 rounded-lg border bg-card p-4 lg:grid-cols-6">
      <Input
        placeholder="Search projects..."
        value={search}
        onChange={(event) => setSearch(event.target.value)}
        className="lg:col-span-2"
      />

      <Select value={status} onValueChange={setStatus}>
        <SelectTrigger>
          <SelectValue placeholder="Status" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="all">All statuses</SelectItem>
          <SelectItem value="draft">Draft</SelectItem>
          <SelectItem value="proposal_sent">Proposal sent</SelectItem>
          <SelectItem value="in_progress">In progress</SelectItem>
          <SelectItem value="on_hold">On hold</SelectItem>
          <SelectItem value="completed">Completed</SelectItem>
          <SelectItem value="cancelled">Cancelled</SelectItem>
        </SelectContent>
      </Select>

      <Select value={clientId} onValueChange={setClientId}>
        <SelectTrigger>
          <SelectValue placeholder="Client" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="all">All clients</SelectItem>
          {clients.map((client) => (
            <SelectItem key={client.id} value={client.id}>
              {client.name}
            </SelectItem>
          ))}
        </SelectContent>
      </Select>

      <Input
        type="date"
        value={dateFrom}
        onChange={(event) => setDateFrom(event.target.value)}
      />
      <Input type="date" value={dateTo} onChange={(event) => setDateTo(event.target.value)} />

      <div className="lg:col-span-6 flex justify-end">
        <Button onClick={applyFilters}>Apply filters</Button>
      </div>
    </div>
  );
}
