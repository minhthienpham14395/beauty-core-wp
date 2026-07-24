import { GoogleGenAI } from "@google/genai";
import { aiKnowledge } from "../data/ai-knowledge.mjs";

const MODEL = "gemini-3.1-flash-lite";
const MAX_MESSAGES = 8;
const MAX_MESSAGE_LENGTH = 1_500;
const promotionQuestionPattern = /ưu đãi|khuyến mãi|uu dai|khuyen mai/i;
const bookingQuestionPattern = /đặt lịch|dat lich|book lịch|book lich|booking/i;
const consultationQuestionPattern = /tư vấn|tu van|hỏi thêm|hoi them/i;
const ownerQuestionPattern =
  /chủ spa|chu spa|chủ của spa|chu cua spa|nguyễn thị ngọc đức|nguyen thi ngoc duc/i;
const imageQuestionPattern = /ảnh|anh|hình|hinh|xem/i;
const bookingUrl = "https://booking.easysalon.vn/conamspa";
const zaloUrl = "https://zalo.me/0387972769";
const ownerPhotoUrl = "/images/nhanvien/655834777_26055994090690537_5060151072622391760_n.jpg";

const promotionResponse =
  "Cô Năm Spa hiện có gội đầu 50' từ 169k, gội đầu 60' từ 189k và các combo 90' từ 339k. Bạn xem đầy đủ tại mục Ưu đãi, hoặc nhắn Zalo để Cô Năm tư vấn gói phù hợp nhé.";

const bookingResponse = `Bạn có thể đặt lịch trực tuyến tại ${bookingUrl}.`;
const consultationResponse = `Bạn có thể nhắn Zalo để được tư vấn: ${zaloUrl}.`;
const bookingAndConsultationResponse = `Bạn có thể đặt lịch trực tuyến tại ${bookingUrl} hoặc nhắn Zalo để được tư vấn: ${zaloUrl}.`;

const systemInstruction = `Bạn là trợ lý tư vấn thân thiện của Cô Năm Spa tại TP. Hồ Chí Minh.
Trả lời bằng tiếng Việt, ngắn gọn, ấm áp, dễ hiểu và luôn kết thúc trọn vẹn ý: tối đa 3 câu, không quá 500 ký tự.
Dùng văn bản thường, không dùng Markdown,
ký tự *, # hoặc dấu gạch đầu dòng. Chỉ tư vấn về dịch vụ, giá, thời lượng,
đặt lịch, giờ mở cửa và địa chỉ của spa. Khi cần chốt lịch hoặc khi thiếu thông tin, mời khách
nhắn Zalo 0387 972 769. Không chẩn đoán, điều trị hay đưa ra lời khuyên y khoa; với triệu chứng
đau, dị ứng hoặc vấn đề sức khỏe, hãy khuyên khách tham khảo chuyên gia y tế.
Không trả lời các câu hỏi ngoài phạm vi Cô Năm Spa, gồm lịch sử, người nổi tiếng, tin tức,
chính trị, kiến thức phổ thông hoặc bất kỳ chủ đề không liên quan nào. Với các câu hỏi đó, chỉ trả lời:
"Cô Năm chỉ có thể hỗ trợ thông tin về dịch vụ, ưu đãi và đặt lịch tại spa. Bạn cần tư vấn gói nào ạ?"

Thông tin spa: Cô Năm Spa, 281/31/11 Lê Văn Sỹ, Phường Tân Sơn Hòa, TP. Hồ Chí Minh.
Mở cửa 9:00 sáng đến 20:00 tối mỗi ngày. Dịch vụ có gội đầu thư giãn từ 89k, massage cổ vai gáy từ
300k, massage toàn thân từ 330k và các combo từ 339k. Giá và liệu trình có thể
điều chỉnh theo tình trạng thực tế.

${aiKnowledge}`;

export class ChatRequestError extends Error {}

const normalizeMessages = (messages) => {
  if (!Array.isArray(messages)) {
    throw new ChatRequestError("Nội dung trò chuyện không hợp lệ.");
  }

  const contents = messages
    .slice(-MAX_MESSAGES)
    .map(({ role, text }) => ({
      role: role === "assistant" ? "model" : "user",
      parts: [
        {
          text: String(text ?? "")
            .trim()
            .slice(0, MAX_MESSAGE_LENGTH),
        },
      ],
    }))
    .filter((message) => message.parts[0].text);

  if (contents.length === 0 || contents.at(-1)?.role !== "user") {
    throw new ChatRequestError("Hãy nhập câu hỏi để bắt đầu tư vấn.");
  }

  return contents;
};

const isPromotionQuestion = (messages) => {
  if (!Array.isArray(messages)) return false;

  const latestUserMessage = [...messages]
    .reverse()
    .find((message) => message?.role === "user")?.text;

  return promotionQuestionPattern.test(String(latestUserMessage ?? ""));
};

const getLatestUserMessage = (messages) => {
  if (!Array.isArray(messages)) return "";

  return String([...messages].reverse().find((message) => message?.role === "user")?.text ?? "");
};

const getDirectResponse = (messages) => {
  const question = getLatestUserMessage(messages);

  if (ownerQuestionPattern.test(question)) {
    if (imageQuestionPattern.test(question)) {
      return `Đây là hình ảnh của chủ spa Nguyễn Thị Ngọc Đức: ${ownerPhotoUrl}`;
    }

    return "Chủ spa là Nguyễn Thị Ngọc Đức.";
  }

  const wantsBooking = bookingQuestionPattern.test(question);
  const wantsConsultation = consultationQuestionPattern.test(question);

  if (wantsBooking && wantsConsultation) return bookingAndConsultationResponse;
  if (wantsBooking) return bookingResponse;
  if (wantsConsultation) return consultationResponse;

  return null;
};

export async function generateChatReply(messages, apiKey) {
  if (!apiKey) {
    throw new Error("Dịch vụ tư vấn AI chưa được cấu hình.");
  }

  if (isPromotionQuestion(messages)) return promotionResponse;

  const directResponse = getDirectResponse(messages);
  if (directResponse) return directResponse;

  const ai = new GoogleGenAI({ apiKey });
  const result = await ai.models.generateContent({
    model: MODEL,
    contents: normalizeMessages(messages),
    config: {
      systemInstruction,
      // Thinking tokens are included in this budget. A larger budget prevents the
      // visible answer from being cut off before the sentence is complete.
      maxOutputTokens: 1024,
      temperature: 0.4,
      thinkingConfig: { thinkingLevel: "low" },
    },
  });
  const text = result.text?.trim();

  if (!text) {
    throw new Error("Trợ lý chưa thể phản hồi. Vui lòng thử lại.");
  }

  return text;
}
