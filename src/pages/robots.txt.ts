import type { APIRoute } from "astro";

const site = (import.meta.env.PUBLIC_SITE_URL || "https://fit360.mx").replace(/\/$/, "");

export const GET: APIRoute = () => new Response([
  "User-agent: *",
  "Allow: /",
  "Disallow: /admin/",
  "Disallow: /api/",
  "Disallow: /profile",
  "Disallow: /checkout/",
  "Disallow: /services/",
  "",
  `Sitemap: ${site}/sitemap.xml`,
  "",
].join("\n"), {
  headers: { "content-type": "text/plain; charset=utf-8" },
});
