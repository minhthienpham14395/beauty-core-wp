import { ChatRequestError, generateChatReply } from "../src/server/gemini-chat.mjs";

const parseBody = (body) => (typeof body === "string" ? JSON.parse(body) : body);

export default async function handler(request, response) {
  response.setHeader("Cache-Control", "no-store");

  if (request.method !== "POST") {
    response.setHeader("Allow", "POST");
    return response.status(405).json({ error: "Phương thức không được hỗ trợ." });
  }

  try {
    const { messages } = parseBody(request.body ?? {});
    const text = await generateChatReply(messages, process.env.GOOGLE_GENAI_API_KEY);

    return response.status(200).json({ text });
  } catch (error) {
    console.error("Gemini chat request failed", error);
    const status = error instanceof ChatRequestError ? 400 : 502;
    const message = error instanceof Error ? error.message : "Không thể kết nối trợ lý AI.";
    return response.status(status).json({ error: message });
  }
}
