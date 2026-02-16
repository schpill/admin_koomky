import { NextResponse } from "next/server";
import type { NextRequest } from "next/server";
import {
  isLocale,
  localeCookieName,
  resolveLocaleFromAcceptLanguage,
} from "@/lib/i18n/config";

// Routes that don't require authentication
const publicRoutes = [
  "/auth/login",
  "/auth/register",
  "/auth/forgot-password",
  "/auth/reset-password",
];

// Public static assets served from /public
const publicAssetPrefixes = ["/brand/"];

// Routes that should redirect to dashboard if already authenticated
const guestOnlyRoutes = [
  "/auth/login",
  "/auth/register",
  "/auth/forgot-password",
  "/auth/reset-password",
];

function withLocaleCookie(
  request: NextRequest,
  response: NextResponse
): NextResponse {
  const localeFromCookie = request.cookies.get(localeCookieName)?.value;
  if (isLocale(localeFromCookie)) {
    return response;
  }

  const locale = resolveLocaleFromAcceptLanguage(
    request.headers.get("accept-language")
  );
  response.cookies.set(localeCookieName, locale, {
    path: "/",
    maxAge: 60 * 60 * 24 * 365,
    sameSite: "lax",
  });

  return response;
}

export function middleware(request: NextRequest) {
  const { pathname } = request.nextUrl;

  // Always allow public static assets
  if (publicAssetPrefixes.some((prefix) => pathname.startsWith(prefix))) {
    return NextResponse.next();
  }

  // Get tokens from cookies (more reliable than localStorage for SSR)
  const accessToken = request.cookies.get("koomky-access-token")?.value;
  const isAuthenticated = !!accessToken;
  let response: NextResponse;

  // Check if route is guest-only and user is authenticated
  if (guestOnlyRoutes.some((route) => pathname.startsWith(route))) {
    if (isAuthenticated) {
      response = NextResponse.redirect(new URL("/", request.url));
    } else {
      response = NextResponse.next();
    }
  } else if (publicRoutes.some((route) => pathname.startsWith(route))) {
    // Check if route is public
    response = NextResponse.next();
  } else if (!isAuthenticated) {
    // Check if route is protected
    const loginUrl = new URL("/auth/login", request.url);
    loginUrl.searchParams.set("redirect", pathname);
    response = NextResponse.redirect(loginUrl);
  } else {
    response = NextResponse.next();
  }

  return withLocaleCookie(request, response);
}

export const config = {
  matcher: [
    /*
     * Match all request paths except:
     * - _next/static (static files)
     * - _next/image (image optimization files)
     * - favicon.ico (favicon file)
     * - public folder
     * - api routes
     */
    "/((?!_next/static|_next/image|favicon.ico|public|api|brand).*)",
  ],
};
