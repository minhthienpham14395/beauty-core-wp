import { defineConfig } from "astro/config";
import { loadEnv } from "vite";

export default defineConfig(({ mode }) => {
  const { GOOGLE_GENAI_API_KEY } = loadEnv(mode, process.cwd(), "");

  if (GOOGLE_GENAI_API_KEY) {
    process.env.GOOGLE_GENAI_API_KEY = GOOGLE_GENAI_API_KEY;
  }

  return {
    site: "https://conamspa.io.vn",
  };
});
