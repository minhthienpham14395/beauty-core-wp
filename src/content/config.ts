import { defineCollection, z } from "astro:content";

const blog = defineCollection({
  type: "content",
  schema: z.object({
    title: z.string(),
    description: z.string(),
    publishedAt: z.date(),
    updatedAt: z.date(),
    author: z.string(),
    category: z.string(),
    image: z.string(),
    imageAlt: z.string(),
    faqs: z.array(z.object({ question: z.string(), answer: z.string() })).default([]),
    draft: z.boolean().default(false),
  }),
});

export const collections = { blog };
