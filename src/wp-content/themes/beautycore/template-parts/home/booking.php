<?php
$services = beautycore_appointment_service_options();
$today = current_time('Y-m-d');
$settings = beautycore_appointment_settings();
?>
<section class="homepage-booking" id="dat-lich" aria-labelledby="homepage-booking-title">
    <div class="container">
        <div class="homepage-booking__inner">
            <header class="homepage-booking__intro">
                <span class="homepage-booking__eyebrow">Đặt lịch Beauty Core</span>
                <h2 id="homepage-booking-title">Dành một khoảng thời gian cho riêng bạn</h2>
                <p>Chọn dịch vụ và khung giờ phù hợp. Beauty Core sẽ liên hệ xác nhận lịch hẹn sớm nhất.</p>
                <div class="homepage-booking__intro-note">
                    <span>Phản hồi lịch hẹn</span>
                    <strong>Trong giờ hoạt động hằng ngày</strong>
                </div>
            </header>
            <div class="homepage-booking__form-wrap">
                <div class="homepage-booking__form-heading">
                    <h3>Thông tin lịch hẹn</h3>
                    <p>Vui lòng điền đủ các trường có dấu *</p>
                </div>
            <?php if (!empty($_GET['booking']) && sanitize_key(wp_unslash($_GET['booking'])) === 'success') : ?>
                <div class="homepage-booking__notice homepage-booking__notice--success" data-booking-notice role="status">
                    <p>Yêu cầu đặt lịch đã được tiếp nhận. Beauty Core sẽ sớm xác nhận với bạn.</p>
                    <button type="button" class="homepage-booking__notice-close" data-booking-notice-close aria-label="Đóng thông báo">&times;</button>
                </div>
            <?php endif; ?>
            <form class="homepage-booking__form" data-homepage-booking-form data-today="<?php echo esc_attr($today); ?>" data-opening-time="<?php echo esc_attr($settings['opening_time']); ?>" data-closing-time="<?php echo esc_attr($settings['closing_time']); ?>" data-workdays="<?php echo esc_attr(implode(',', $settings['workdays'])); ?>" method="post" action="<?php echo esc_url(beautycore_appointment_relative_url(admin_url('admin-post.php'))); ?>">
                <input type="hidden" name="action" value="beautycore_public_create_appointment">
                <?php wp_nonce_field('beautycore_public_create_appointment'); ?>
                <label class="homepage-booking__field">
                    <span>Họ tên *</span>
                    <input name="customer_name" autocomplete="name" placeholder="Nhập họ và tên" required>
                </label>
                <label class="homepage-booking__field">
                    <span>Số điện thoại *</span>
                    <input name="customer_phone" inputmode="tel" autocomplete="tel" placeholder="Nhập số điện thoại" required>
                </label>
                <label class="homepage-booking__field homepage-booking__field--wide">
                    <span>Dịch vụ *</span>
                    <select name="service_id" required>
                        <option value="">Chọn dịch vụ</option>
                        <?php foreach ($services as $service_id => $service) : ?>
                            <?php $service_post = get_post(absint($service_id)); ?>
                            <?php if ($service_post && $service_post->post_status === 'publish' && get_post_meta($service_post->ID, '_beautycore_booking_enabled', true) !== '0') : ?>
                                <option value="<?php echo esc_attr($service_id); ?>" data-duration="<?php echo esc_attr(max(1, absint($service['duration']))); ?>"><?php echo esc_html($service['name']); ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="homepage-booking__field">
                    <span>Ngày check-in *</span>
                    <input type="date" name="date" min="<?php echo esc_attr($today); ?>" required>
                </label>
                <label class="homepage-booking__field">
                    <span>Giờ check-in *</span>
                    <input type="time" name="start_time" min="<?php echo esc_attr($settings['opening_time']); ?>" max="<?php echo esc_attr($settings['closing_time']); ?>" required>
                </label>
                <button class="btn btn-primary homepage-booking__submit" type="submit">Gửi yêu cầu</button>
            </form>
            </div>
        </div>
    </div>
</section>
