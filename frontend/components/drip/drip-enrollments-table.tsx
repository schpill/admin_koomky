"use client";

import { Button } from "@/components/ui/button";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import type { DripEnrollment } from "@/lib/stores/drip-sequences";

interface DripEnrollmentsTableProps {
  enrollments: DripEnrollment[];
  onPause: (id: string) => void;
  onCancel: (id: string) => void;
}

function contactName(enrollment: DripEnrollment): string {
  const firstName = enrollment.contact?.first_name || "";
  const lastName = enrollment.contact?.last_name || "";
  const fullName = `${firstName} ${lastName}`.trim();
  return fullName || enrollment.contact?.email || "Unknown contact";
}

export function DripEnrollmentsTable({
  enrollments,
  onPause,
  onCancel,
}: DripEnrollmentsTableProps) {
  return (
    <Table>
      <TableHeader>
        <TableRow>
          <TableHead>Contact</TableHead>
          <TableHead>Status</TableHead>
          <TableHead>Current step</TableHead>
          <TableHead>Actions</TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        {enrollments.map((enrollment) => (
          <TableRow key={enrollment.id}>
            <TableCell>{contactName(enrollment)}</TableCell>
            <TableCell>{enrollment.status}</TableCell>
            <TableCell>{enrollment.current_step_position}</TableCell>
            <TableCell className="flex gap-2">
              <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={() => onPause(enrollment.id)}
              >
                Pause
              </Button>
              <Button
                type="button"
                variant="destructive"
                size="sm"
                onClick={() => onCancel(enrollment.id)}
              >
                Cancel
              </Button>
            </TableCell>
          </TableRow>
        ))}
      </TableBody>
    </Table>
  );
}
