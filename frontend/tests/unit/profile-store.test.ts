import { beforeEach, describe, expect, it, vi } from "vitest";
import { useProfileStore } from "../../lib/stores/profile";
import { useAuthStore } from "../../lib/stores/auth";

vi.mock("../../lib/api", () => ({
  apiClient: {
    get: vi.fn(),
    patch: vi.fn(),
    post: vi.fn(),
  },
}));

import { apiClient } from "../../lib/api";

describe("useProfileStore", () => {
  beforeEach(() => {
    useProfileStore.setState({
      user: null,
      isLoading: false,
      error: null,
    });
    useAuthStore.setState({
      user: null,
      accessToken: null,
      refreshToken: null,
      isAuthenticated: false,
      isLoading: false,
    });
    vi.clearAllMocks();
  });

  it("fetches the current profile and syncs auth state", async () => {
    (apiClient.get as any).mockResolvedValue({
      data: {
        id: "user_1",
        name: "Profile User",
        email: "profile@example.test",
      },
    });

    await useProfileStore.getState().fetchProfile();

    expect(apiClient.get).toHaveBeenCalledWith("/profile");
    expect(useProfileStore.getState().user?.name).toBe("Profile User");
    expect(useAuthStore.getState().user?.email).toBe("profile@example.test");
  });

  it("updates the profile via multipart payload and syncs auth state", async () => {
    const payload = new FormData();
    payload.append("name", "Updated User");
    payload.append("email", "updated@example.test");

    (apiClient.patch as any).mockResolvedValue({
      data: {
        id: "user_1",
        name: "Updated User",
        email: "updated@example.test",
        avatar_url: "/storage/avatars/user_1.png",
      },
    });

    const result = await useProfileStore.getState().updateProfile(payload);

    expect(apiClient.patch).toHaveBeenCalledWith("/profile", payload);
    expect(result.name).toBe("Updated User");
    expect(useAuthStore.getState().user?.name).toBe("Updated User");
  });

  it("posts password changes to the profile password endpoint", async () => {
    (apiClient.post as any).mockResolvedValue({
      data: null,
    });

    await useProfileStore.getState().changePassword({
      current_password: "CurrentPassword123!",
      password: "NewPassword123!",
      password_confirmation: "NewPassword123!",
    });

    expect(apiClient.post).toHaveBeenCalledWith("/profile/password", {
      current_password: "CurrentPassword123!",
      password: "NewPassword123!",
      password_confirmation: "NewPassword123!",
    });
    expect(useProfileStore.getState().error).toBeNull();
  });
});
