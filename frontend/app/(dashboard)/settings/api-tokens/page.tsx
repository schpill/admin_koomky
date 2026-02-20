"use client";

import { useEffect, useState } from "react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Badge } from "@/components/ui/badge";
import { Plus, Trash2, Copy, Check, Key } from "lucide-react";
import { apiClient } from "@/lib/api";
import { toast } from "sonner";
import { API_SCOPES } from "@/lib/constants/api-scopes";

interface ApiToken {
  id: string;
  name: string;
  abilities: string[];
  last_used_at: string | null;
  expires_at: string | null;
  created_at: string;
}

export default function ApiTokensPage() {
  const [tokens, setTokens] = useState<ApiToken[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [showCreateForm, setShowCreateForm] = useState(false);
  const [newTokenName, setNewTokenName] = useState("");
  const [selectedScopes, setSelectedScopes] = useState<string[]>([]);
  const [expiresAt, setExpiresAt] = useState("");
  const [generatedToken, setGeneratedToken] = useState<string | null>(null);
  const [copied, setCopied] = useState(false);

  useEffect(() => {
    fetchTokens();
  }, []);

  const fetchTokens = async () => {
    setIsLoading(true);
    try {
      const response = await apiClient.get<{ data: { data: ApiToken[] } }>(
        "/settings/api-tokens"
      );
      setTokens(response.data?.data?.data || []);
    } catch (error) {
      toast.error("Failed to fetch API tokens");
    } finally {
      setIsLoading(false);
    }
  };

  const handleCreateToken = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!newTokenName) {
      toast.error("Token name is required");
      return;
    }

    if (selectedScopes.length === 0) {
      toast.error("Select at least one scope");
      return;
    }

    try {
      const payload: Record<string, unknown> = {
        name: newTokenName,
        abilities: selectedScopes,
      };
      if (expiresAt) payload.expires_at = expiresAt;

      const response = await apiClient.post<{ data: { token: string } }>(
        "/settings/api-tokens",
        payload
      );
      setGeneratedToken(response.data?.data?.token || null);
      setNewTokenName("");
      setSelectedScopes([]);
      setExpiresAt("");
      fetchTokens();
      toast.success("API token created successfully");
    } catch (error) {
      toast.error("Failed to create API token");
    }
  };

  const handleDeleteToken = async (id: string) => {
    if (!confirm("Are you sure you want to revoke this token?")) return;

    try {
      await apiClient.delete(`/settings/api-tokens/${id}`);
      setTokens(tokens.filter((t) => t.id !== id));
      toast.success("API token revoked successfully");
    } catch (error) {
      toast.error("Failed to revoke API token");
    }
  };

  const copyToClipboard = async (text: string) => {
    await navigator.clipboard.writeText(text);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
    toast.success("Token copied to clipboard");
  };

  const toggleScope = (scope: string) => {
    setSelectedScopes((current) =>
      current.includes(scope)
        ? current.filter((s) => s !== scope)
        : [...current, scope]
    );
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">API Tokens</h1>
          <p className="text-sm text-muted-foreground">
            Manage Personal Access Tokens for API access
          </p>
        </div>
        <Button onClick={() => setShowCreateForm(!showCreateForm)}>
          <Plus className="mr-2 h-4 w-4" />
          Create Token
        </Button>
      </div>

      {generatedToken && (
        <Card className="border-green-200 bg-green-50">
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-green-700">
              <Key className="h-5 w-5" />
              Token Created
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p className="mb-2 text-sm text-green-700">
              Copy this token now - it will not be shown again!
            </p>
            <div className="flex items-center gap-2">
              <code className="flex-1 rounded bg-white p-2 text-sm">
                {generatedToken}
              </code>
              <Button
                variant="outline"
                size="sm"
                onClick={() => copyToClipboard(generatedToken)}
              >
                {copied ? (
                  <Check className="h-4 w-4" />
                ) : (
                  <Copy className="h-4 w-4" />
                )}
              </Button>
            </div>
          </CardContent>
        </Card>
      )}

      {showCreateForm && (
        <Card>
          <CardHeader>
            <CardTitle>Create New Token</CardTitle>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleCreateToken} className="space-y-4">
              <div className="grid gap-4 md:grid-cols-2">
                <div className="space-y-2">
                  <Label htmlFor="name">Token Name</Label>
                  <Input
                    id="name"
                    value={newTokenName}
                    onChange={(e) => setNewTokenName(e.target.value)}
                    placeholder="My API Token"
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="expires_at">Expiration Date (optional)</Label>
                  <Input
                    id="expires_at"
                    type="date"
                    value={expiresAt}
                    onChange={(e) => setExpiresAt(e.target.value)}
                    min={new Date().toISOString().split("T")[0]}
                  />
                </div>
              </div>

              <div className="space-y-2">
                <Label>Scopes</Label>
                <div className="grid gap-2 md:grid-cols-2 lg:grid-cols-3">
                  {API_SCOPES.map((scope) => (
                    <label
                      key={scope.name}
                      className="flex cursor-pointer items-center gap-2 rounded border p-2 hover:bg-muted"
                    >
                      <input
                        type="checkbox"
                        checked={selectedScopes.includes(scope.name)}
                        onChange={() => toggleScope(scope.name)}
                        className="h-4 w-4"
                      />
                      <div>
                        <p className="text-sm font-medium">{scope.name}</p>
                        <p className="text-xs text-muted-foreground">
                          {scope.description}
                        </p>
                      </div>
                    </label>
                  ))}
                </div>
              </div>

              <div className="flex gap-2">
                <Button type="submit">Create Token</Button>
                <Button
                  type="button"
                  variant="outline"
                  onClick={() => setShowCreateForm(false)}
                >
                  Cancel
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      )}

      <Card>
        <CardHeader>
          <CardTitle>Your Tokens</CardTitle>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <p className="text-muted-foreground">Loading...</p>
          ) : tokens.length === 0 ? (
            <p className="text-muted-foreground">No API tokens created yet</p>
          ) : (
            <div className="space-y-3">
              {tokens.map((token) => (
                <div
                  key={token.id}
                  className="flex items-center justify-between rounded-lg border p-4"
                >
                  <div>
                    <p className="font-medium">{token.name}</p>
                    <div className="mt-1 flex flex-wrap gap-1">
                      {token.abilities.map((ability) => (
                        <Badge
                          key={ability}
                          variant="outline"
                          className="text-xs"
                        >
                          {ability}
                        </Badge>
                      ))}
                    </div>
                    <p className="mt-1 text-xs text-muted-foreground">
                      Created: {new Date(token.created_at).toLocaleDateString()}
                      {token.last_used_at &&
                        ` | Last used: ${new Date(token.last_used_at).toLocaleDateString()}`}
                      {token.expires_at &&
                        ` | Expires: ${new Date(token.expires_at).toLocaleDateString()}`}
                    </p>
                  </div>
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => handleDeleteToken(token.id)}
                  >
                    <Trash2 className="h-4 w-4 text-red-500" />
                  </Button>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
