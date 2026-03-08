type DocInfographicProps = {
  src: string;
  alt: string;
  caption?: string;
};

export function DocInfographic({
  src,
  alt,
  caption,
}: DocInfographicProps) {
  return (
    <figure className="my-8 space-y-3 rounded-2xl border border-border/70 bg-gradient-to-br from-primary/5 via-background to-secondary/20 p-4 shadow-xl shadow-primary/5">
      <img src={src} alt={alt} className="w-full rounded-xl" />
      {caption ? (
        <figcaption className="text-sm text-muted-foreground">
          {caption}
        </figcaption>
      ) : null}
    </figure>
  );
}
