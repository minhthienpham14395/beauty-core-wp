import type { APIRoute } from "astro";
import { ChatRequestError, generateChatReply } from "../../server/gemini-chat.mjs";

export const POST: APIRoute = async ({ request }) => {
  try {
    const { messages } = await request.json();
    const text = await generateChatReply(messages, process.env.GOOGLE_GENAI_API_KEY);

    return Response.json({ text }, { headers: { "Cache-Control": "no-store" } });
  } catch (error) {
    console.error("Local Gemini chat request failed", error);
    const status = error instanceof ChatRequestError ? 400 : 502;
    const message = error instanceof Error ? error.message : "Không thể kết nối trợ lý AI.";

    return Response.json({ error: message }, { status });
  }
};
