<?php
$config = beautycore_site_config();
?>
<div class="floating-contact" aria-label="Liên hệ nhanh">
    <div class="ai-contact" id="ai-contact" data-zalo-url="<?php echo esc_url($config['zalo_url']); ?>" data-booking-url="<?php echo esc_url(beautycore_booking_section_url()); ?>" data-phone="<?php echo esc_attr($config['phone']); ?>" data-map-url="<?php echo esc_url($config['google_map_url']); ?>">
        <section id="ai-contact-popup" class="ai-contact__popup" aria-labelledby="ai-contact-title" hidden>
            <button type="button" class="ai-contact__close" aria-label="Đóng trợ lý AI">×</button>
            <p class="ai-contact__eyebrow">Trợ lý AI Beauty Core</p>
            <h2 id="ai-contact-title">Tư vấn cùng Beauty Core</h2>
            <div class="ai-contact__messages" aria-live="polite" aria-label="Nội dung trò chuyện">
                <p class="ai-contact__message ai-contact__message--assistant">Chào bạn, Beauty Core có thể giúp gì về dịch vụ hoặc đặt lịch hôm nay?</p>
            </div>
            <div class="ai-contact__quick-prompts" aria-label="Câu hỏi gợi ý">
                <p>Bạn muốn:</p>
                <button type="button" data-ai-prompt="Tôi muốn được tư vấn dịch vụ phù hợp.">Tư vấn dịch vụ</button>
                <button type="button" data-ai-prompt="Spa đang có ưu đãi gì?">Xem giá ưu đãi</button>
                <button type="button" data-ai-prompt="Tôi muốn đặt lịch.">Đặt lịch ngay</button>
            </div>
            <form class="ai-contact__form">
                <label class="sr-only" for="ai-contact-input">Nhập câu hỏi cho trợ lý AI</label>
                <textarea id="ai-contact-input" name="message" rows="2" maxlength="1500" placeholder="Ví dụ: Gội đầu 60 phút giá bao nhiêu?" required></textarea>
                <button type="submit" class="ai-contact__send">Gửi</button>
            </form>
            <a href="<?php echo esc_url($config['zalo_url']); ?>" target="_blank" rel="noopener noreferrer" data-track="ai_zalo_click">Cần nhân viên hỗ trợ? Nhắn Zalo</a>
        </section>
        <button type="button" class="ai-contact__trigger" aria-expanded="false" aria-controls="ai-contact-popup" aria-label="Mở trợ lý AI">AI</button>
    </div>
    <a href="<?php echo esc_url($config['zalo_url']); ?>" target="_blank" rel="noopener noreferrer" aria-label="Nhắn Zalo" data-track="zalo_click">Zalo</a>
    <a href="<?php echo esc_url($config['facebook_url']); ?>" target="_blank" rel="noopener noreferrer" aria-label="Mở Facebook" data-track="facebook_click">f</a>
    <a href="tel:<?php echo esc_attr($config['phone']); ?>" aria-label="Gọi <?php echo esc_attr($config['phone_display']); ?>" data-track="phone_click">☎</a>
</div>
