export const locales = ["fr", "en"] as const;

export type Locale = (typeof locales)[number];

export const defaultLocale: Locale = "fr";
export const localeCookieName = "koomky-locale";
export const localeStorageKey = "koomky-locale";

export function isLocale(value: string | null | undefined): value is Locale {
  return !!value && locales.includes(value as Locale);
}

export function resolveLocale(value: string | null | undefined): Locale {
  return isLocale(value) ? value : defaultLocale;
}

export function resolveLocaleFromAcceptLanguage(
  acceptLanguage: string | null | undefined,
): Locale {
  if (!acceptLanguage) {
    return defaultLocale;
  }

  const parsedCandidates = acceptLanguage
    .split(",")
    .map((entry, index) => {
      const [rawTag, ...params] = entry.trim().split(";");
      const tag = rawTag.toLowerCase();
      if (!tag) {
        return null;
      }

      const qualityParam = params.find((param) =>
        param.trim().startsWith("q="),
      );
      const parsedQuality = qualityParam
        ? Number.parseFloat(qualityParam.trim().slice(2))
        : 1;
      const quality = Number.isFinite(parsedQuality) ? parsedQuality : 1;

      return { tag, quality, index };
    })
    .filter(
      (
        candidate,
      ): candidate is {
        tag: string;
        quality: number;
        index: number;
      } => candidate !== null,
    )
    .sort((a, b) => {
      if (b.quality !== a.quality) {
        return b.quality - a.quality;
      }
      return a.index - b.index;
    });

  for (const candidate of parsedCandidates) {
    if (candidate.tag === "*") {
      return defaultLocale;
    }

    const baseTag = candidate.tag.split("-")[0];
    if (isLocale(baseTag)) {
      return baseTag;
    }
  }

  return defaultLocale;
}
