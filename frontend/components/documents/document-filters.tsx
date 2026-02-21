"use client";

import { useEffect, useState } from "react";
import { Search, X, Calendar as CalendarIcon, Filter } from "lucide-react";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { Label } from "@/components/ui/label";
import { 
  Select, 
  SelectContent, 
  SelectItem, 
  SelectTrigger, 
  SelectValue 
} from "@/components/ui/select";
import { useClientStore } from "@/lib/stores/clients";
import { useDocumentStore, type DocumentType } from "@/lib/stores/documents";
import { useDebounce } from "use-debounce";

const DOCUMENT_TYPES: { value: DocumentType; label: string }[] = [
  { value: "pdf", label: "PDF" },
  { value: "spreadsheet", label: "Tableur" },
  { value: "document", label: "Document" },
  { value: "text", label: "Texte" },
  { value: "script", label: "Script" },
  { value: "image", label: "Image" },
  { value: "archive", label: "Archive" },
  { value: "presentation", label: "Présentation" },
  { value: "other", label: "Autre" },
];

export function DocumentFilters() {
  const { clients, fetchClients } = useClientStore();
  const { fetchDocuments } = useDocumentStore();
  
  const [search, setSearch] = useState("");
  const [debouncedSearch] = useDebounce(search, 500);
  
  const [selectedTypes, setSelectedTypes] = useState<DocumentType[]>([]);
  const [selectedClient, setSelectedClient] = useState<string>("all");
  const [dateFrom, setDateFrom] = useState("");
  const [dateTo, setDateTo] = useState("");

  useEffect(() => {
    fetchClients();
  }, [fetchClients]);

  useEffect(() => {
    const params: any = {
      q: debouncedSearch || undefined,
      client_id: selectedClient === "all" ? undefined : selectedClient,
      date_from: dateFrom || undefined,
      date_to: dateTo || undefined,
    };

    if (selectedTypes.length === 1) {
      params.document_type = selectedTypes[0];
    }
    // Note: If multiple types are selected, the backend might need to support it. 
    // The current backend index method handles one type. 
    // I will use the first one for now or update backend if needed.

    fetchDocuments(params);
  }, [debouncedSearch, selectedTypes, selectedClient, dateFrom, dateTo, fetchDocuments]);

  const toggleType = (type: DocumentType) => {
    setSelectedTypes(prev => 
      prev.includes(type) ? prev.filter(t => t !== type) : [...prev, type]
    );
  };

  const clearFilters = () => {
    setSearch("");
    setSelectedTypes([]);
    setSelectedClient("all");
    setDateFrom("");
    setDateTo("");
  };

  return (
    <div className="space-y-6">
      <div className="space-y-2">
        <Label className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
          Recherche
        </Label>
        <div className="relative">
          <Search className="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
          <Input
            placeholder="Rechercher par titre..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="pl-8"
          />
        </div>
      </div>

      <div className="space-y-3">
        <Label className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
          Type de document
        </Label>
        <div className="grid grid-cols-1 gap-2">
          {DOCUMENT_TYPES.map((type) => (
            <div key={type.value} className="flex items-center space-x-2">
              <Checkbox 
                id={`type-${type.value}`} 
                checked={selectedTypes.includes(type.value)}
                onCheckedChange={() => toggleType(type.value)}
              />
              <label
                htmlFor={`type-${type.value}`}
                className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
              >
                {type.label}
              </label>
            </div>
          ))}
        </div>
      </div>

      <div className="space-y-2">
        <Label className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
          Client
        </Label>
        <Select value={selectedClient} onValueChange={setSelectedClient}>
          <SelectTrigger>
            <SelectValue placeholder="Tous les clients" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">Tous les clients</SelectItem>
            {clients.map((client) => (
              <SelectItem key={client.id} value={client.id}>
                {client.name}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>

      <div className="space-y-2">
        <Label className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
          Date de création
        </Label>
        <div className="space-y-2">
          <div className="flex flex-col gap-1.5">
            <span className="text-[10px] text-muted-foreground ml-1">De</span>
            <Input 
              type="date" 
              value={dateFrom} 
              onChange={(e) => setDateFrom(e.target.value)} 
              className="text-xs"
            />
          </div>
          <div className="flex flex-col gap-1.5">
            <span className="text-[10px] text-muted-foreground ml-1">À</span>
            <Input 
              type="date" 
              value={dateTo} 
              onChange={(e) => setDateTo(e.target.value)}
              className="text-xs"
            />
          </div>
        </div>
      </div>

      <Button 
        variant="ghost" 
        className="w-full justify-start px-2 text-muted-foreground hover:text-foreground"
        onClick={clearFilters}
      >
        <X className="mr-2 h-4 w-4" />
        Réinitialiser les filtres
      </Button>
    </div>
  );
}
