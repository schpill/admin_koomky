"use client";

import { useState } from "react";
import { MessageCircle, Send, Loader2, X } from "lucide-react";
import { useRagStore } from "@/lib/stores/rag";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";

interface ChatWidgetProps {
  portalMode?: boolean;
}

export function ChatWidget({ portalMode = false }: ChatWidgetProps) {
  const [open, setOpen] = useState(false);
  const [question, setQuestion] = useState("");
  const { messages, loading, askQuestion, clearHistory } = useRagStore();

  const submit = async () => {
    const q = question.trim();
    if (!q) return;
    setQuestion("");
    await askQuestion(q, undefined, portalMode);
  };

  return (
    <div className="fixed bottom-6 right-6 z-50">
      {open ? (
        <div className="w-[360px] rounded-xl border bg-card shadow-xl">
          <div className="flex items-center justify-between border-b p-3">
            <div>
              <p className="text-sm font-semibold">Assistant documentaire</p>
              <p className="text-xs text-muted-foreground">
                Basé sur les documents partagés
              </p>
            </div>
            <Button variant="ghost" size="icon" onClick={() => setOpen(false)}>
              <X className="h-4 w-4" />
            </Button>
          </div>

          <div className="h-[320px] overflow-y-auto p-3">
            <div className="space-y-3">
              {messages.map((message) => (
                <div
                  key={message.id}
                  className={
                    message.role === "user" ? "text-right" : "text-left"
                  }
                >
                  <div
                    className={`inline-block max-w-[90%] rounded-lg px-3 py-2 text-sm ${
                      message.role === "user"
                        ? "bg-primary text-primary-foreground"
                        : "bg-muted text-foreground"
                    }`}
                  >
                    {message.content}
                  </div>
                  {message.role === "assistant" &&
                  message.sources &&
                  message.sources.length > 0 ? (
                    <div className="mt-2 space-y-1 text-xs text-muted-foreground">
                      {message.sources.map((source, idx) => (
                        <p
                          key={`${source.document_id}-${source.chunk_index}-${idx}`}
                        >
                          Source: {source.title || source.document_id}#
                          {source.chunk_index}
                        </p>
                      ))}
                    </div>
                  ) : null}
                </div>
              ))}
              {loading ? (
                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                  <Loader2 className="h-4 w-4 animate-spin" />
                  Réponse en cours...
                </div>
              ) : null}
            </div>
          </div>

          <div className="border-t p-3">
            <div className="flex gap-2">
              <Input
                value={question}
                placeholder="Posez votre question..."
                onChange={(event) => setQuestion(event.target.value)}
                onKeyDown={(event) => {
                  if (event.key === "Enter") {
                    void submit();
                  }
                }}
              />
              <Button onClick={() => void submit()} disabled={loading}>
                <Send className="h-4 w-4" />
              </Button>
            </div>
            <div className="mt-2 flex justify-end">
              <Button variant="ghost" size="sm" onClick={clearHistory}>
                Effacer
              </Button>
            </div>
          </div>
        </div>
      ) : null}

      {!open ? (
        <Button
          className="h-12 w-12 rounded-full"
          onClick={() => setOpen(true)}
        >
          <MessageCircle className="h-5 w-5" />
        </Button>
      ) : null}
    </div>
  );
}
