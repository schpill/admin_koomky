import { create } from "zustand";
import {
  createJSONStorage,
  persist,
  type StateStorage,
} from "zustand/middleware";

export interface User {
  id: string;
  name: string;
  email: string;
  avatar_path?: string;
  avatar_url?: string | null;
  business_name?: string;
  two_factor_confirmed_at?: string | null;
}

interface AuthState {
  user: User | null;
  accessToken: string | null;
  refreshToken: string | null;
  rememberMe: boolean;
  isAuthenticated: boolean;
  isLoading: boolean;

  // Actions
  setAuth: (
    user: User,
    accessToken: string,
    refreshToken: string,
    options?: { rememberMe?: boolean }
  ) => void;
  setTokens: (
    accessToken: string,
    refreshToken: string,
    options?: { rememberMe?: boolean }
  ) => void;
  setUser: (user: User) => void;
  logout: () => void;
  setLoading: (loading: boolean) => void;
}

const authStorage: StateStorage = {
  getItem: (name) => {
    if (typeof window === "undefined") {
      return null;
    }

    return (
      window.sessionStorage.getItem(name) ?? window.localStorage.getItem(name)
    );
  },
  setItem: (name, value) => {
    if (typeof window === "undefined") {
      return;
    }

    const parsed = JSON.parse(value) as {
      state?: { rememberMe?: boolean };
    };

    if (parsed.state?.rememberMe) {
      window.sessionStorage.removeItem(name);
      window.localStorage.setItem(name, value);
      return;
    }

    window.localStorage.removeItem(name);
    window.sessionStorage.setItem(name, value);
  },
  removeItem: (name) => {
    if (typeof window === "undefined") {
      return;
    }

    window.localStorage.removeItem(name);
    window.sessionStorage.removeItem(name);
  },
};

function writeAccessCookie(accessToken: string) {
  document.cookie = `koomky-access-token=${accessToken}; path=/; max-age=86400; SameSite=Lax`;
}

function writeRefreshCookie(refreshToken: string, rememberMe: boolean) {
  if (!refreshToken) {
    document.cookie =
      "koomky-refresh-token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT";
    return;
  }

  document.cookie = rememberMe
    ? `koomky-refresh-token=${refreshToken}; path=/; max-age=2592000; SameSite=Lax`
    : `koomky-refresh-token=${refreshToken}; path=/; SameSite=Lax`;
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      user: null,
      accessToken: null,
      refreshToken: null,
      rememberMe: true,
      isAuthenticated: false,
      isLoading: true,

      setAuth: (user, accessToken, refreshToken, options) => {
        const rememberMe = options?.rememberMe ?? get().rememberMe;

        // Set cookies for middleware
        if (typeof window !== "undefined") {
          writeAccessCookie(accessToken);
          writeRefreshCookie(refreshToken, rememberMe);
        }
        set({
          user,
          accessToken,
          refreshToken,
          rememberMe,
          isAuthenticated: true,
          isLoading: false,
        });
      },

      setTokens: (accessToken, refreshToken, options) => {
        const rememberMe = options?.rememberMe ?? get().rememberMe;

        // Update cookies
        if (typeof window !== "undefined") {
          writeAccessCookie(accessToken);
          writeRefreshCookie(refreshToken, rememberMe);
        }
        set({ accessToken, refreshToken, rememberMe });
      },

      setUser: (user) => set({ user }),

      logout: () => {
        // Clear cookies
        if (typeof window !== "undefined") {
          document.cookie =
            "koomky-access-token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT";
          document.cookie =
            "koomky-refresh-token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT";
        }
        set({
          user: null,
          accessToken: null,
          refreshToken: null,
          rememberMe: true,
          isAuthenticated: false,
          isLoading: false,
        });
      },

      setLoading: (loading) => set({ isLoading: loading }),
    }),
    {
      name: "koomky-auth",
      storage: createJSONStorage(() => authStorage),
      partialize: (state) => ({
        accessToken: state.accessToken,
        refreshToken: state.refreshToken,
        rememberMe: state.rememberMe,
        user: state.user,
        isAuthenticated: state.isAuthenticated,
      }),
      onRehydrateStorage: () => (state) => {
        state?.setLoading(false);
      },
    }
  )
);
