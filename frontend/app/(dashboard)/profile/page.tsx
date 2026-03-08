"use client";

import { useEffect, useState } from "react";
import { toast } from "sonner";
import { AvatarUpload } from "@/components/profile/avatar-upload";
import { useI18n } from "@/components/providers/i18n-provider";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useAuthStore } from "@/lib/stores/auth";
import { useProfileStore } from "@/lib/stores/profile";

export default function ProfilePage() {
  const { t } = useI18n();
  const authUser = useAuthStore((state) => state.user);
  const { fetchProfile, updateProfile, changePassword, isLoading } =
    useProfileStore();
  const [name, setName] = useState(authUser?.name ?? "");
  const [email, setEmail] = useState(authUser?.email ?? "");
  const [avatarFile, setAvatarFile] = useState<File | null>(null);
  const [avatarRemoved, setAvatarRemoved] = useState(false);
  const [currentPassword, setCurrentPassword] = useState("");
  const [newPassword, setNewPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");

  useEffect(() => {
    if (!authUser) {
      void fetchProfile().catch(() => {
        toast.error(t("profile.toasts.loadFailed"));
      });
    }
  }, [authUser, fetchProfile, t]);

  useEffect(() => {
    setName(authUser?.name ?? "");
    setEmail(authUser?.email ?? "");
  }, [authUser]);

  async function handleProfileSave() {
    try {
      const formData = new FormData();
      formData.append("name", name);
      formData.append("email", email);
      if (avatarFile) {
        formData.append("avatar", avatarFile);
      }
      if (avatarRemoved && !avatarFile) {
        formData.append("remove_avatar", "1");
      }

      const user = await updateProfile(formData);
      setName(user.name);
      setEmail(user.email);
      setAvatarFile(null);
      setAvatarRemoved(false);
      toast.success(t("profile.toasts.updated"));
    } catch (error) {
      toast.error((error as Error).message || t("profile.toasts.updateFailed"));
    }
  }

  async function handlePasswordSave() {
    if (newPassword !== passwordConfirmation) {
      toast.error(t("profile.toasts.passwordMismatch"));
      return;
    }

    try {
      await changePassword({
        current_password: currentPassword,
        password: newPassword,
        password_confirmation: passwordConfirmation,
      });
      setCurrentPassword("");
      setNewPassword("");
      setPasswordConfirmation("");
      toast.success(t("profile.toasts.passwordUpdated"));
    } catch (error) {
      toast.error(
        (error as Error).message || t("profile.toasts.passwordUpdateFailed")
      );
    }
  }

  return (
    <div className="mx-auto max-w-3xl space-y-6">
      <div>
        <h1 className="text-3xl font-semibold tracking-tight">
          {t("profile.title")}
        </h1>
        <p className="text-sm text-muted-foreground">
          {t("profile.description")}
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>{t("profile.personal.title")}</CardTitle>
          <CardDescription>{t("profile.personal.description")}</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <AvatarUpload
            label={t("profile.fields.avatar")}
            value={avatarFile}
            initialPreviewUrl={
              avatarRemoved ? null : (authUser?.avatar_url ?? null)
            }
            onChange={(file) => {
              setAvatarFile(file);
              setAvatarRemoved(file === null);
            }}
          />
          <div className="space-y-2">
            <Label htmlFor="profile-name">{t("profile.fields.name")}</Label>
            <Input
              id="profile-name"
              value={name}
              onChange={(event) => setName(event.target.value)}
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="profile-email">{t("profile.fields.email")}</Label>
            <Input
              id="profile-email"
              type="email"
              value={email}
              onChange={(event) => setEmail(event.target.value)}
            />
          </div>
        </CardContent>
        <CardFooter className="justify-end border-t bg-muted/40 px-6 py-4">
          <Button
            type="button"
            onClick={handleProfileSave}
            disabled={isLoading}
          >
            {t("profile.actions.saveProfile")}
          </Button>
        </CardFooter>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>{t("profile.security.title")}</CardTitle>
          <CardDescription>{t("profile.security.description")}</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="current-password">
              {t("profile.fields.currentPassword")}
            </Label>
            <Input
              id="current-password"
              type="password"
              value={currentPassword}
              onChange={(event) => setCurrentPassword(event.target.value)}
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="new-password">
              {t("profile.fields.newPassword")}
            </Label>
            <Input
              id="new-password"
              type="password"
              value={newPassword}
              onChange={(event) => setNewPassword(event.target.value)}
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="password-confirmation">
              {t("profile.fields.passwordConfirmation")}
            </Label>
            <Input
              id="password-confirmation"
              type="password"
              value={passwordConfirmation}
              onChange={(event) => setPasswordConfirmation(event.target.value)}
            />
          </div>
        </CardContent>
        <CardFooter className="justify-end border-t bg-muted/40 px-6 py-4">
          <Button
            type="button"
            onClick={handlePasswordSave}
            disabled={isLoading}
          >
            {t("profile.actions.savePassword")}
          </Button>
        </CardFooter>
      </Card>
    </div>
  );
}
