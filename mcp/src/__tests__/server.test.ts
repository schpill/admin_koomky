import { describe, it, expect } from "vitest";
import { buildServer } from "../server.js";

describe("server", () => {
  it("builds server instance", async () => {
    const server = await buildServer();
    expect(server).toBeTruthy();
  });
});
