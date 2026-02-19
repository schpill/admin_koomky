"use client";

import { FormEvent, useEffect, useState } from "react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useExpenseCategoryStore } from "@/lib/stores/expense-categories";
import { useI18n } from "@/components/providers/i18n-provider";

export default function ExpenseCategoriesSettingsPage() {
  const { t } = useI18n();
  const {
    categories,
    isLoading,
    fetchCategories,
    createCategory,
    updateCategory,
    deleteCategory,
  } = useExpenseCategoryStore();

  const [newName, setNewName] = useState("");
  const [newColor, setNewColor] = useState("#2459ff");
  const [newIcon, setNewIcon] = useState("tag");
  const [editingId, setEditingId] = useState<string | null>(null);

  useEffect(() => {
    fetchCategories();
  }, [fetchCategories]);

  const addCategory = async (event: FormEvent) => {
    event.preventDefault();
    if (!newName.trim()) {
      return;
    }

    try {
      await createCategory({
        name: newName,
        color: newColor,
        icon: newIcon || undefined,
      });
      setNewName("");
      setNewIcon("tag");
      toast.success(t("settings.expenseCategories.toasts.added"));
    } catch (error) {
      toast.error(
        (error as Error).message ||
          t("settings.expenseCategories.toasts.addFailed")
      );
    }
  };

  const saveCategory = async (
    id: string,
    payload: { name: string; color?: string; icon?: string }
  ) => {
    try {
      await updateCategory(id, payload);
      setEditingId(null);
      toast.success(t("settings.expenseCategories.toasts.updated"));
    } catch (error) {
      toast.error(
        (error as Error).message ||
          t("settings.expenseCategories.toasts.updateFailed")
      );
    }
  };

  const removeCategory = async (id: string) => {
    try {
      await deleteCategory(id);
      toast.success(t("settings.expenseCategories.toasts.deleted"));
    } catch (error) {
      toast.error(
        (error as Error).message ||
          t("settings.expenseCategories.toasts.deleteFailed")
      );
    }
  };

  return (
    <div className="space-y-6">
      <div className="space-y-1">
        <h1 className="text-3xl font-bold">
          {t("settings.expenseCategories.title")}
        </h1>
        <p className="text-sm text-muted-foreground">
          {t("settings.expenseCategories.description")}
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>{t("settings.expenseCategories.addCategory")}</CardTitle>
        </CardHeader>
        <CardContent>
          <form className="grid gap-3 md:grid-cols-4" onSubmit={addCategory}>
            <div className="space-y-2 md:col-span-2">
              <Label htmlFor="expense-category-name">
                {t("settings.expenseCategories.name")}
              </Label>
              <Input
                id="expense-category-name"
                value={newName}
                onChange={(event) => setNewName(event.target.value)}
                placeholder="Travel"
                required
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="expense-category-color">
                {t("settings.expenseCategories.color")}
              </Label>
              <Input
                id="expense-category-color"
                type="color"
                value={newColor}
                onChange={(event) => setNewColor(event.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="expense-category-icon">
                {t("settings.expenseCategories.icon")}
              </Label>
              <Input
                id="expense-category-icon"
                value={newIcon}
                onChange={(event) => setNewIcon(event.target.value)}
                placeholder="plane"
              />
            </div>
            <div className="md:col-span-4">
              <Button type="submit" disabled={isLoading}>
                {t("settings.expenseCategories.addCategory")}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>{t("settings.expenseCategories.categories")}</CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          {categories.map((category) => (
            <CategoryRow
              key={category.id}
              category={category}
              editing={editingId === category.id}
              onEdit={() => setEditingId(category.id)}
              onCancel={() => setEditingId(null)}
              onSave={saveCategory}
              onDelete={removeCategory}
            />
          ))}
        </CardContent>
      </Card>
    </div>
  );
}

function CategoryRow({
  category,
  editing,
  onEdit,
  onCancel,
  onSave,
  onDelete,
}: {
  category: {
    id: string;
    name: string;
    color?: string | null;
    icon?: string | null;
    is_default: boolean;
  };
  editing: boolean;
  onEdit: () => void;
  onCancel: () => void;
  onSave: (
    id: string,
    payload: { name: string; color?: string; icon?: string }
  ) => void;
  onDelete: (id: string) => void;
}) {
  const { t } = useI18n();
  const [name, setName] = useState(category.name);
  const [color, setColor] = useState(category.color || "#2459ff");
  const [icon, setIcon] = useState(category.icon || "tag");

  return (
    <div className="rounded-md border p-3">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div className="flex items-center gap-2">
          <span
            className="h-3 w-3 rounded-full border"
            style={{ backgroundColor: color }}
          />
          {!editing ? <p className="font-medium">{name}</p> : null}
          {!editing && icon ? (
            <span className="text-xs text-muted-foreground">{icon}</span>
          ) : null}
          {category.is_default ? (
            <span className="rounded border px-2 py-0.5 text-xs">
              {t("settings.expenseCategories.default")}
            </span>
          ) : null}
        </div>

        {!editing ? (
          <div className="flex gap-2">
            {!category.is_default ? (
              <Button variant="outline" size="sm" onClick={onEdit}>
                {t("settings.expenseCategories.edit")}
              </Button>
            ) : null}
            {!category.is_default ? (
              <Button
                variant="outline"
                size="sm"
                onClick={() => onDelete(category.id)}
              >
                {t("settings.expenseCategories.delete")}
              </Button>
            ) : null}
          </div>
        ) : null}
      </div>

      {editing ? (
        <div className="mt-3 grid gap-3 md:grid-cols-3">
          <Input
            value={name}
            onChange={(event) => setName(event.target.value)}
          />
          <Input
            type="color"
            value={color}
            onChange={(event) => setColor(event.target.value)}
          />
          <Input
            value={icon}
            onChange={(event) => setIcon(event.target.value)}
          />
          <div className="md:col-span-3 flex gap-2">
            <Button
              size="sm"
              onClick={() =>
                onSave(category.id, {
                  name,
                  color,
                  icon,
                })
              }
            >
              {t("settings.expenseCategories.save")}
            </Button>
            <Button size="sm" variant="outline" onClick={onCancel}>
              {t("settings.expenseCategories.cancel")}
            </Button>
          </div>
        </div>
      ) : null}
    </div>
  );
}
