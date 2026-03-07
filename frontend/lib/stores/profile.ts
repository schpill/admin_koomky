import { create } from "zustand";
import { apiClient } from "@/lib/api";
import { useAuthStore, type User } from "@/lib/stores/auth";

export interface ProfileUser extends User {}

interface ChangePasswordPayload {
  current_password: string;
  password: string;
  password_confirmation: string;
}

interface ProfileState {
  user: ProfileUser | null;
  isLoading: boolean;
  error: string | null;
  fetchProfile: () => Promise<ProfileUser>;
  updateProfile: (payload: FormData) => Promise<ProfileUser>;
  changePassword: (payload: ChangePasswordPayload) => Promise<void>;
}

function syncAuthUser(user: ProfileUser) {
  useAuthStore.getState().setUser(user);
}

export const useProfileStore = create<ProfileState>((set) => ({
  user: null,
  isLoading: false,
  error: null,

  fetchProfile: async () => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<ProfileUser>("/profile");
      set({ user: response.data, isLoading: false });
      syncAuthUser(response.data);
      return response.data;
    } catch (error) {
      const message = (error as Error).message;
      set({ error: message, isLoading: false });
      throw error;
    }
  },

  updateProfile: async (payload) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.patch<ProfileUser>("/profile", payload);
      set({ user: response.data, isLoading: false });
      syncAuthUser(response.data);
      return response.data;
    } catch (error) {
      const message = (error as Error).message;
      set({ error: message, isLoading: false });
      throw error;
    }
  },

  changePassword: async (payload) => {
    set({ isLoading: true, error: null });
    try {
      await apiClient.post("/profile/password", payload);
      set({ isLoading: false });
    } catch (error) {
      const message = (error as Error).message;
      set({ error: message, isLoading: false });
      throw error;
    }
  },
}));
