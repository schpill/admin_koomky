import type { MDXComponents } from "mdx/types";
import { DocBadge } from "@/components/docs/doc-badge";
import { DocCallout } from "@/components/docs/doc-callout";
import { DocDiagram } from "@/components/docs/doc-diagram";
import { DocInfographic } from "@/components/docs/doc-infographic";
import { DocScreenshot } from "@/components/docs/doc-screenshot";
import { DocSteps } from "@/components/docs/doc-steps";
import { slugifyHeading } from "@/lib/docs/search-index";

export function useMDXComponents(components: MDXComponents): MDXComponents {
  return {
    h2: ({ children, ...props }) => {
      const text = typeof children === "string" ? children : String(children);
      const id = slugifyHeading(text);
      return (
        <h2 id={id} {...props}>
          {children}
        </h2>
      );
    },
    h3: ({ children, ...props }) => {
      const text = typeof children === "string" ? children : String(children);
      const id = slugifyHeading(text);
      return (
        <h3 id={id} {...props}>
          {children}
        </h3>
      );
    },
    DocBadge,
    DocCallout,
    DocDiagram,
    DocInfographic,
    DocScreenshot,
    DocSteps,
    ...components,
  };
}
