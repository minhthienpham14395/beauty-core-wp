import type { APIRoute } from "astro";
import { legalPages } from "../data/legal-pages";
import { getCollection } from "astro:content";
import { slugify } from "../utils/slugify";

export const prerender = true;

export const GET: APIRoute = async () => {
  const baseUrl = "https://conamspa.io.vn";
  const posts = await getCollection("blog", ({ data }) => !data.draft);
  const categories = [...new Set(posts.map((post) => post.data.category))];
  const blogPageCount = Math.ceil(posts.length / 6);
  const blogPages = Array.from(
    { length: Math.max(0, blogPageCount - 1) },
    (_, index) => `/blog/page/${index + 2}/`,
  );
  const urls = [
    "/",
    "/gioi-thieu/",
    "/lien-he/",
    "/cau-hoi-thuong-gap/",
    "/dich-vu/",
    "/blog/",
    ...blogPages,
    ...legalPages.map((page) => `/${page.slug}/`),
    ...categories.map((category) => `/danh-muc/${slugify(category)}/`),
    ...posts.map((post) => `/blog/${post.slug}/`),
  ];
  const body = `<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n${urls.map((url) => `  <url><loc>${baseUrl}${url}</loc></url>`).join("\n")}\n</urlset>`;
  return new Response(body, { headers: { "Content-Type": "application/xml; charset=utf-8" } });
};
