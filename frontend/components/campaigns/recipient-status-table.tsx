"use client";

interface RecipientStatusItem {
  id: string;
  status: string;
  contact?: {
    first_name?: string;
    last_name?: string;
  } | null;
  email?: string | null;
  phone?: string | null;
  sent_at?: string | null;
  opened_at?: string | null;
  clicked_at?: string | null;
  delivered_at?: string | null;
  failed_at?: string | null;
}

interface RecipientStatusTableProps {
  recipients: RecipientStatusItem[];
}

export function RecipientStatusTable({ recipients }: RecipientStatusTableProps) {
  return (
    <div className="overflow-x-auto rounded-lg border">
      <table className="w-full text-sm">
        <thead>
          <tr className="border-b bg-muted/40 text-left text-muted-foreground">
            <th className="px-3 py-2 font-medium">Recipient</th>
            <th className="px-3 py-2 font-medium">Email</th>
            <th className="px-3 py-2 font-medium">Phone</th>
            <th className="px-3 py-2 font-medium">Status</th>
            <th className="px-3 py-2 font-medium">Sent</th>
            <th className="px-3 py-2 font-medium">Opened</th>
            <th className="px-3 py-2 font-medium">Clicked</th>
            <th className="px-3 py-2 font-medium">Delivered</th>
            <th className="px-3 py-2 font-medium">Failed</th>
          </tr>
        </thead>
        <tbody>
          {recipients.map((recipient) => (
            <tr key={recipient.id} className="border-b last:border-0">
              <td className="px-3 py-2">
                {(recipient.contact?.first_name || "") +
                  " " +
                  (recipient.contact?.last_name || "")}
              </td>
              <td className="px-3 py-2">{recipient.email || "-"}</td>
              <td className="px-3 py-2">{recipient.phone || "-"}</td>
              <td className="px-3 py-2 capitalize">{recipient.status}</td>
              <td className="px-3 py-2">{recipient.sent_at || "-"}</td>
              <td className="px-3 py-2">{recipient.opened_at || "-"}</td>
              <td className="px-3 py-2">{recipient.clicked_at || "-"}</td>
              <td className="px-3 py-2">{recipient.delivered_at || "-"}</td>
              <td className="px-3 py-2">{recipient.failed_at || "-"}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
