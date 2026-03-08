import { notFound } from "next/navigation";
import { DocLayout } from "@/components/docs/doc-layout";
import { DOC_MODULES_BY_SLUG } from "@/lib/docs/config";
import { docsContentBySlug, readDocSource } from "@/lib/docs/content";
import { getTableOfContents } from "@/lib/docs/search-index";

type DocsCatchAllPageProps = {
  params: Promise<{ slug?: string[] }>;
};

export async function generateMetadata({ params }: DocsCatchAllPageProps) {
  const { slug } = await params;
  const resolvedSlug = slug?.join("/") ?? "getting-started";
  const moduleKey = resolvedSlug.split("/")[0];
  const meta = DOC_MODULES_BY_SLUG[moduleKey];

  if (!meta) {
    return {};
  }

  return {
    title: `${meta.title} | Documentation`,
    description: meta.description,
  };
}

export default async function DocsCatchAllPage({
  params,
}: DocsCatchAllPageProps) {
  const { slug } = await params;
  const resolvedSlug = slug?.join("/") ?? "getting-started";
  const Content = docsContentBySlug[resolvedSlug];
  const source = await readDocSource(resolvedSlug);
  const moduleKey = resolvedSlug.split("/")[0];
  const meta = DOC_MODULES_BY_SLUG[moduleKey];

  if (!Content || !source || !meta) {
    notFound();
  }

  return (
    <DocLayout
      title={meta.title}
      description={meta.description}
      headings={getTableOfContents(source)}
    >
      <Content />
    </DocLayout>
  );
}
