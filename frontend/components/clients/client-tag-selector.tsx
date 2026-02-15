"use client";

import { useState, useEffect } from "react";
import { apiClient } from "@/lib/api";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Plus, X, Tag as TagIcon, Loader2 } from "lucide-react";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/components/ui/popover";
import { Input } from "@/components/ui/input";
import { toast } from "sonner";

interface Tag {
  id: string;
  name: string;
}

interface ClientTagSelectorProps {
  clientId: string;
  initialTags?: Tag[];
}

export function ClientTagSelector({ clientId, initialTags = [] }: ClientTagSelectorProps) {
  const [tags, setTags] = useState<Tag[]>(initialTags);
  const [availableTags, setAvailableTags] = useState<Tag[]>([]);
  const [newTagName, setNewTagName] = useState("");
  const [loading, setLoading] = useState(false);
  const [open, setOpen] = useState(false);

  const fetchAvailableTags = async () => {
    try {
      const response = await apiClient.get<Tag[]>("/tags");
      setAvailableTags(response.data);
    } catch (error) {
      console.error("Failed to load tags");
    }
  };

  useEffect(() => {
    if (open) {
      fetchAvailableTags();
    }
  }, [open]);

  const addTag = async (tagName: string) => {
    if (!tagName.trim()) return;
    setLoading(true);
    try {
      await apiClient.post(`/clients/${clientId}/tags`, { name: tagName });
      toast.success(`Tag "${tagName}" added`);
      
      // We don't have the ID for the new tag immediately if we just send the name
      // So we refresh available and current tags
      const response = await apiClient.get<any>(`/clients/${clientId}`);
      setTags(response.data.tags || []);
      
      setNewTagName("");
      setOpen(false);
    } catch (error) {
      toast.error("Failed to add tag");
    } finally {
      setLoading(false);
    }
  };

  const removeTag = async (tagId: string) => {
    try {
      // Assuming DELETE /api/v1/clients/{client}/tags/{tag} exists based on PRD
      // If not, we'd need to implement it. For now let's assume it's part of client update or a dedicated route
      await apiClient.delete(`/clients/${clientId}/tags/${tagId}`);
      setTags(tags.filter((t) => t.id !== tagId));
      toast.success("Tag removed");
    } catch (error) {
      // Fallback if dedicated route doesn't exist
      toast.error("Failed to remove tag");
    }
  };

  return (
    <div className="flex flex-wrap items-center gap-2">
      <TagIcon className="h-4 w-4 text-muted-foreground mr-1" />
      {tags.map((tag) => (
        <Badge key={tag.id} variant="secondary" className="flex items-center gap-1 pr-1">
          {tag.name}
          <button
            onClick={() => removeTag(tag.id)}
            className="rounded-full p-0.5 hover:bg-muted-foreground/20"
          >
            <X className="h-3 w-3" />
          </button>
        </Badge>
      ))}
      <Popover open={open} onOpenChange={setOpen}>
        <PopoverTrigger asChild>
          <Button variant="outline" size="sm" className="h-7 border-dashed px-2">
            <Plus className="mr-1 h-3 w-3" /> Add Tag
          </Button>
        </PopoverTrigger>
        <PopoverContent className="w-60 p-3" align="start">
          <div className="space-y-3">
            <div className="text-xs font-medium">Available Tags</div>
            <div className="flex flex-wrap gap-1">
              {availableTags
                .filter((at) => !tags.find((t) => t.id === at.id))
                .map((at) => (
                  <button
                    key={at.id}
                    onClick={() => addTag(at.name)}
                    className="inline-flex"
                  >
                    <Badge variant="outline" className="cursor-pointer hover:bg-primary hover:text-primary-foreground">
                      {at.name}
                    </Badge>
                  </button>
                ))}
              {availableTags.length === 0 && (
                <span className="text-[10px] text-muted-foreground">No existing tags.</span>
              )}
            </div>
            <div className="pt-2">
              <div className="flex gap-2">
                <Input
                  placeholder="New tag..."
                  value={newTagName}
                  onChange={(e) => setNewTagName(e.target.value)}
                  className="h-8 text-xs"
                  onKeyDown={(e) => {
                    if (e.key === "Enter") addTag(newTagName);
                  }}
                />
                <Button size="sm" className="h-8" onClick={() => addTag(newTagName)} disabled={loading}>
                  {loading ? <Loader2 className="h-3 w-3 animate-spin" /> : "Add"}
                </Button>
              </div>
            </div>
          </div>
        </PopoverContent>
      </Popover>
    </div>
  );
}
