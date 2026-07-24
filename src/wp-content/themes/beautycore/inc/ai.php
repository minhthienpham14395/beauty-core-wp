<?php
if (!defined('ABSPATH')) {
    exit;
}

function beautycore_ai_knowledge() {
    return <<<'TEXT'
DỮ LIỆU DỊCH VỤ CHÍNH THỨC

Gội đầu: Gội thư giãn 30' 89k, 40' 129k, 50' 179k. Gội chuyên sâu 60' 209k, 80' 279k, 90' 369k.
Massage: Massage thư giãn cổ vai gáy 60' 300k, 90' 420k. Massage thư giãn toàn thân 60' 330k, 90' 460k. Massage toàn thân đá nóng 90' 480k. Massage trị liệu cổ vai gáy 80' 550k. Massage trị liệu toàn thân 90' 650k.
Combo: Gội đầu chuyên sâu kết hợp massage tay chân 90' 339k; kết hợp massage cổ vai gáy lưng úp 90' 349k; massage cổ vai gáy & gội đầu 90' 369k; massage toàn thân & gội đầu 90' 389k; massage CVG kết hợp trị liệu CVG 80' 429k; massage body kết hợp trị liệu CVG 90' 499k.
Ưu đãi hiện có: gội đầu thư giãn 50' 169k; gội đầu 60' 189k; gội đầu 90' kết hợp massage tay chân CVG 339k; combo massage body và gội đầu 90' 389k.
Địa chỉ: 281/31/11 Lê Văn Sỹ, Phường Tân Sơn Hòa, TP. Hồ Chí Minh. Mở cửa 9:00 đến 20:00 mỗi ngày.
TEXT;
}

function beautycore_ai_key() {
    if (defined('BEAUTYCORE_GEMINI_API_KEY') && BEAUTYCORE_GEMINI_API_KEY) {
        return BEAUTYCORE_GEMINI_API_KEY;
    }

    $key = getenv('GOOGLE_GENAI_API_KEY');
    return $key ? $key : '';
}

function beautycore_latest_user_message($messages) {
    if (!is_array($messages)) {
        return '';
    }

    for ($index = count($messages) - 1; $index >= 0; $index--) {
        if (isset($messages[$index]['role']) && $messages[$index]['role'] === 'user') {
            return (string) ($messages[$index]['text'] ?? '');
        }
    }

    return '';
}

function beautycore_direct_chat_reply($messages) {
    $question = beautycore_latest_user_message($messages);
    $booking_url = beautycore_site_config()['booking_url'];
    $zalo_url = beautycore_site_config()['zalo_url'];
    $photo_url = beautycore_asset_url('/images/nhanvien/655834777_26055994090690537_5060151072622391760_n.jpg');

    if (preg_match('/chủ spa|chủ của spa|nguyễn thị ngọc đức/i', $question)) {
        if (preg_match('/ảnh|hình|xem/i', $question)) {
            return 'Đây là hình ảnh của chủ spa Nguyễn Thị Ngọc Đức: ' . $photo_url;
        }

        return 'Chủ spa là Nguyễn Thị Ngọc Đức.';
    }

    $booking = preg_match('/đặt lịch|dat lich|book lịch|book lich|booking/i', $question);
    $consultation = preg_match('/tư vấn|tu van|hỏi thêm|hoi them/i', $question);

    if ($booking && $consultation) {
        return 'Bạn có thể đặt lịch trực tuyến tại ' . $booking_url . ' hoặc nhắn Zalo để được tư vấn: ' . $zalo_url . '.';
    }
    if ($booking) {
        return 'Bạn có thể đặt lịch trực tuyến tại ' . $booking_url . '.';
    }
    if ($consultation) {
        return 'Bạn có thể nhắn Zalo để được tư vấn: ' . $zalo_url . '.';
    }

    return '';
}

function beautycore_chat_reply($messages) {
    if (!is_array($messages)) {
        throw new Exception('Nội dung trò chuyện không hợp lệ.');
    }

    $normalized = array();
    foreach (array_slice($messages, -8) as $message) {
        $text = trim(substr((string) ($message['text'] ?? ''), 0, 1500));
        if (!$text) {
            continue;
        }

        $normalized[] = array(
            'role' => (($message['role'] ?? '') === 'assistant') ? 'model' : 'user',
            'parts' => array(array('text' => $text)),
        );
    }

    if (!$normalized || end($normalized)['role'] !== 'user') {
        throw new Exception('Hãy nhập câu hỏi để bắt đầu tư vấn.');
    }

    $question = beautycore_latest_user_message($messages);
    if (preg_match('/ưu đãi|khuyến mãi|uu dai|khuyen mai/i', $question)) {
        return 'Beauty Core hiện có gội đầu 50\' từ 169k, gội đầu 60\' từ 189k và các combo 90\' từ 339k. Bạn xem đầy đủ tại mục Ưu đãi, hoặc nhắn Zalo để Beauty Core tư vấn gói phù hợp nhé.';
    }

    $direct = beautycore_direct_chat_reply($messages);
    if ($direct) {
        return $direct;
    }

    $api_key = beautycore_ai_key();
    if (!$api_key) {
        throw new Exception('Dịch vụ tư vấn AI chưa được cấu hình.');
    }

    $system = 'Bạn là trợ lý tư vấn thân thiện của Beauty Core tại TP. Hồ Chí Minh. Trả lời bằng tiếng Việt, ngắn gọn, ấm áp, dễ hiểu và tối đa 3 câu, không quá 500 ký tự. Dùng văn bản thường, không dùng Markdown. Chỉ tư vấn về dịch vụ, giá, thời lượng, đặt lịch, giờ mở cửa và địa chỉ của spa. Khi cần chốt lịch hoặc khi thiếu thông tin, mời khách nhắn Zalo 0387 972 769. Không chẩn đoán, điều trị hay đưa ra lời khuyên y khoa; với triệu chứng đau, dị ứng hoặc vấn đề sức khỏe, hãy khuyên khách tham khảo chuyên gia y tế. Không trả lời chủ đề ngoài phạm vi Beauty Core. Với câu hỏi ngoài phạm vi, chỉ trả lời: "Beauty Core chỉ có thể hỗ trợ thông tin về dịch vụ, ưu đãi và đặt lịch tại spa. Bạn cần tư vấn gói nào ạ?"\n\n' . beautycore_ai_knowledge();
    $payload = array(
        'systemInstruction' => array('parts' => array(array('text' => $system))),
        'contents' => $normalized,
        'generationConfig' => array('maxOutputTokens' => 1024, 'temperature' => 0.4),
    );
    $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.1-flash-lite:generateContent?key=' . rawurlencode($api_key);
    $response = wp_remote_post($endpoint, array(
        'timeout' => 25,
        'headers' => array('Content-Type' => 'application/json'),
        'body' => wp_json_encode($payload),
    ));

    if (is_wp_error($response)) {
        throw new Exception('Không thể kết nối trợ lý AI.');
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    $text = trim((string) ($body['candidates'][0]['content']['parts'][0]['text'] ?? ''));
    if (!$text) {
        throw new Exception('Trợ lý chưa thể phản hồi. Vui lòng thử lại.');
    }

    return $text;
}

function beautycore_chat_ajax() {
    check_ajax_referer('beautycore_chat', 'nonce');
    $messages = json_decode(wp_unslash($_POST['messages'] ?? ''), true);

    try {
        wp_send_json(array('text' => beautycore_chat_reply($messages)));
    } catch (Exception $error) {
        status_header(502);
        wp_send_json(array('error' => $error->getMessage()), 502);
    }
}
add_action('wp_ajax_beautycore_chat', 'beautycore_chat_ajax');
add_action('wp_ajax_nopriv_beautycore_chat', 'beautycore_chat_ajax');
