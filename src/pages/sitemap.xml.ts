import type { APIRoute } from "astro";
import { rows } from "@/lib/db";

const site = (import.meta.env.PUBLIC_SITE_URL || "https://fit360.mx").replace(/\/$/, "");

function url(loc: string, priority = "0.7", changefreq = "weekly") {
  return `<url><loc>${site}${loc}</loc><lastmod>${new Date().toISOString().slice(0, 10)}</lastmod><changefreq>${changefreq}</changefreq><priority>${priority}</priority></url>`;
}

export const GET: APIRoute = async () => {
  const products = await rows<any>("SELECT id_producto FROM producto WHERE esta_activo = 1 ORDER BY id_producto");
  const urls = [
    url("/", "1.0", "weekly"),
    url("/products", "0.9", "weekly"),
    ...products.map((product) => url(`/products/${product.id_producto}`, "0.8", "weekly")),
  ];
  return new Response(`<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">${urls.join("")}</urlset>`, {
    headers: { "content-type": "application/xml; charset=utf-8" },
  });
};
