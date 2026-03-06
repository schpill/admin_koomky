"use client";

import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import type { SuppressionEntry } from "@/lib/stores/suppression-list";

interface SuppressionListTableProps {
  entries: SuppressionEntry[];
  onRemove: (id: string) => void;
}

export function SuppressionListTable({
  entries,
  onRemove,
}: SuppressionListTableProps) {
  return (
    <Table>
      <TableHeader>
        <TableRow>
          <TableHead>Email</TableHead>
          <TableHead>Reason</TableHead>
          <TableHead>Actions</TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        {entries.map((entry) => (
          <TableRow key={entry.id}>
            <TableCell>{entry.email}</TableCell>
            <TableCell>
              <Badge variant="secondary">{entry.reason}</Badge>
            </TableCell>
            <TableCell>
              <Button
                type="button"
                variant="destructive"
                size="sm"
                onClick={() => onRemove(entry.id)}
              >
                Remove
              </Button>
            </TableCell>
          </TableRow>
        ))}
      </TableBody>
    </Table>
  );
}
