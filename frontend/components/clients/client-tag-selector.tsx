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
import { useI18n } from "@/components/providers/i18n-provider";

interface Tag {
  id: string;
  name: string;
}

interface ClientTagSelectorProps {
  clientId: string;
  initialTags?: Tag[];
}

export function ClientTagSelector({
  clientId,
  initialTags = [],
}: ClientTagSelectorProps) {
  const { t } = useI18n();
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

  useEffect(() => {
    setTags(initialTags);
  }, [initialTags]);

  const addTag = async (tagName: string) => {
    if (!tagName.trim()) return;
    setLoading(true);
    try {
      await apiClient.post(`/clients/${clientId}/tags`, { name: tagName });
      toast.success(t("clients.tags.toasts.added", { name: tagName }));

      // Refresh tags by fetching the client
      const response = await apiClient.get<any>(`/clients/${clientId}`);
      setTags(response.data.tags || []);

      setNewTagName("");
      setOpen(false);
    } catch (error) {
      toast.error(t("clients.tags.toasts.addFailed"));
    } finally {
      setLoading(false);
    }
  };

  const removeTag = async (tagId: string) => {
    try {
      await apiClient.delete(`/clients/${clientId}/tags/${tagId}`);
      setTags(tags.filter((t) => t.id !== tagId));
      toast.success(t("clients.tags.toasts.removed"));
    } catch (error) {
      toast.error(t("clients.tags.toasts.removeFailed"));
    }
  };

  return (
    <div className="flex flex-wrap items-center gap-2">
      <TagIcon className="h-4 w-4 text-muted-foreground mr-1" />
      {tags.map((tag) => (
        <Badge
          key={tag.id}
          variant="secondary"
          className="flex items-center gap-1 pr-1"
        >
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
          <Button
            variant="outline"
            size="sm"
            className="h-7 border-dashed px-2"
          >
            <Plus className="mr-1 h-3 w-3" /> {t("clients.tags.addTag")}
          </Button>
        </PopoverTrigger>
        <PopoverContent className="w-60 p-3" align="start">
          <div className="space-y-3">
            <div className="text-xs font-medium">{t("clients.tags.availableTags")}</div>
            <div className="flex flex-wrap gap-1">
              {availableTags
                .filter((at) => !tags.find((t) => t.id === at.id))
                .map((at) => (
                  <button
                    key={at.id}
                    onClick={() => addTag(at.name)}
                    className="inline-flex"
                    type="button"
                  >
                    <Badge
                      variant="outline"
                      className="cursor-pointer hover:bg-primary hover:text-primary-foreground"
                    >
                      {at.name}
                    </Badge>
                  </button>
                ))}
              {availableTags.length === 0 && (
                <span className="text-[10px] text-muted-foreground">
                  {t("clients.tags.noExistingTags")}
                </span>
              )}
            </div>
            <div className="pt-2">
              <div className="flex gap-2">
                <Input
                  placeholder={t("clients.tags.newTagPlaceholder")}
                  value={newTagName}
                  onChange={(e) => setNewTagName(e.target.value)}
                  className="h-8 text-xs"
                  onKeyDown={(e) => {
                    if (e.key === "Enter") {
                      e.preventDefault();
                      addTag(newTagName);
                    }
                  }}
                />
                <Button
                  size="sm"
                  className="h-8"
                  onClick={() => addTag(newTagName)}
                  disabled={loading}
                >
                  {loading ? (
                    <Loader2 className="h-3 w-3 animate-spin" />
                  ) : (
                    t("common.add")
                  )}
                </Button>
              </div>
            </div>
          </div>
        </PopoverContent>
      </Popover>
    </div>
  );
}
