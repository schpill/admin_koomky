import Link from "next/link";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { DOC_GROUPS, DOC_MODULES, getDocHref } from "@/lib/docs/config";

export default function DocsHomePage() {
  return (
    <div className="space-y-8">
      <section className="rounded-3xl border border-border/70 bg-gradient-to-br from-background via-background to-primary/10 p-8 shadow-xl shadow-primary/10">
        <p className="text-xs font-semibold uppercase tracking-[0.3em] text-primary">
          Documentation integree
        </p>
        <h1 className="mt-4 text-4xl font-semibold tracking-tight">
          Documentation intégrée
        </h1>
        <p className="mt-4 max-w-3xl text-base leading-7 text-muted-foreground">
          Un espace unique pour comprendre le CRM, visualiser les workflows clefs et retrouver rapidement les guides operatoires.
        </p>
        <p className="mt-4 text-sm text-muted-foreground">
          {DOC_MODULES.length} modules documentes, relies a la navigation globale et a la Command Palette.
        </p>
      </section>

      {DOC_GROUPS.map((group) => (
        <section key={group.key} className="space-y-4">
          <div>
            <h2 className="text-2xl font-semibold">{group.label}</h2>
          </div>
          <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            {DOC_MODULES.filter((module) => module.category === group.key).map(
              (module) => (
                <Link key={module.slug} href={getDocHref(module.slug)}>
                  <Card className="h-full border border-border/70 transition hover:-translate-y-1 hover:shadow-2xl hover:shadow-primary/10">
                    <CardHeader>
                      <div className="mb-3 inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                        <module.icon className="h-6 w-6" />
                      </div>
                      <CardTitle>{module.title}</CardTitle>
                      <CardDescription>{module.description}</CardDescription>
                    </CardHeader>
                    <CardContent className="text-sm text-muted-foreground">
                      Ouvrir la documentation du module
                    </CardContent>
                  </Card>
                </Link>
              )
            )}
          </div>
        </section>
      ))}
    </div>
  );
}
