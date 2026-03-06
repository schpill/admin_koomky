import { beforeEach, describe, expect, it, vi } from "vitest";
import { useScoringRuleStore } from "@/lib/stores/scoring-rules";

vi.mock("@/lib/api", () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
  },
}));

import { apiClient } from "@/lib/api";

describe("useScoringRuleStore", () => {
  beforeEach(() => {
    useScoringRuleStore.setState({
      rules: [],
      isLoading: false,
      error: null,
    });
    vi.clearAllMocks();
  });

  it("fetches and mutates scoring rules", async () => {
    (apiClient.get as any).mockResolvedValueOnce({
      data: [
        {
          id: "rule_1",
          event: "email_opened",
          points: 10,
          expiry_days: 90,
          is_active: true,
        },
      ],
    });

    await useScoringRuleStore.getState().fetchRules();
    expect(useScoringRuleStore.getState().rules).toHaveLength(1);

    (apiClient.post as any).mockResolvedValueOnce({
      data: {
        id: "rule_2",
        event: "email_clicked",
        points: 20,
        expiry_days: 90,
        is_active: true,
      },
    });

    await useScoringRuleStore.getState().createRule({
      event: "email_clicked",
      points: 20,
      expiry_days: 90,
      is_active: true,
    });

    (apiClient.put as any).mockResolvedValueOnce({
      data: {
        id: "rule_1",
        event: "email_opened",
        points: 15,
        expiry_days: 60,
        is_active: false,
      },
    });

    await useScoringRuleStore.getState().updateRule("rule_1", {
      points: 15,
      expiry_days: 60,
      is_active: false,
    });

    (apiClient.delete as any).mockResolvedValueOnce({});
    await useScoringRuleStore.getState().deleteRule("rule_2");

    const rules = useScoringRuleStore.getState().rules;
    expect(rules).toHaveLength(1);
    expect(rules[0]?.points).toBe(15);
    expect(rules[0]?.is_active).toBe(false);
  });
});
