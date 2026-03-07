import { beforeEach, describe, expect, it, vi } from "vitest";
import { fireEvent, render, screen } from "@testing-library/react";
import { AvatarUpload } from "@/components/profile/avatar-upload";

describe("AvatarUpload", () => {
  const createObjectURL = vi.fn(() => "blob:preview-avatar");
  const revokeObjectURL = vi.fn();

  beforeEach(() => {
    vi.clearAllMocks();
    URL.createObjectURL = createObjectURL;
    URL.revokeObjectURL = revokeObjectURL;
  });

  it("shows a preview after selecting an image", () => {
    const onChange = vi.fn();

    render(
      <AvatarUpload
        label="Avatar"
        value={null}
        initialPreviewUrl={null}
        onChange={onChange}
      />
    );

    const file = new File(["avatar"], "avatar.png", { type: "image/png" });

    fireEvent.change(screen.getByLabelText("Avatar"), {
      target: { files: [file] },
    });

    expect(onChange).toHaveBeenCalledWith(file);
    expect(screen.getByAltText("Avatar preview")).toHaveAttribute(
      "src",
      "blob:preview-avatar"
    );
  });

  it("clears the preview and restores the placeholder on remove", () => {
    const onChange = vi.fn();

    render(
      <AvatarUpload
        label="Avatar"
        value={null}
        initialPreviewUrl="/storage/avatars/user.png"
        onChange={onChange}
      />
    );

    fireEvent.click(screen.getByRole("button", { name: "Remove avatar" }));

    expect(onChange).toHaveBeenCalledWith(null);
    expect(screen.getByText("No avatar selected")).toBeInTheDocument();
  });
});
