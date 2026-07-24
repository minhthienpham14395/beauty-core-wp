import type { ServiceGroup } from "../types/service";

export const serviceGroups: ServiceGroup[] = [
  {
    id: "goi-dau",
    eyebrow: "Menu Hair Care",
    title: "Dịch vụ gội đầu",
    description: "Gội đầu thư giãn và gội đầu chuyên sâu.",
    services: [
      {
        name: "Gội thư giãn",
        duration: "30'",
        price: "89k",
        details:
          "Tẩy trang - Rửa mặt - Massage đầu khô - Chườm túi thảo dược - Gội đầu lần 1 - Gội đầu lần 2 - Ủ xả tóc - Dưỡng tóc - Sấy tóc",
      },
      {
        name: "Gội thư giãn",
        duration: "40'",
        price: "129k",
        details:
          "Tẩy trang - Rửa mặt - Massage mặt - Massage đầu khô - Chườm túi thảo dược - Gội đầu lần 1 - Gội đầu lần 2 - Ủ xả tóc - Dưỡng tóc - Sấy tóc",
      },
      {
        name: "Gội thư giãn",
        duration: "50'",
        price: "179k",
        details:
          "Tẩy trang - Rửa mặt - Massage đầu khô - Massage mặt - Massage Cổ Vai Gáy - Chườm túi thảo dược - Gội đầu lần 1 - Gội đầu lần 2 - Ủ xả tóc - Dưỡng tóc - Sấy tóc",
      },
      {
        name: "Gội chuyên sâu",
        duration: "60'",
        price: "209k",
        details:
          "Tẩy trang - Rửa mặt - Massage đầu khô - Massage mặt - Massage Cổ Vai Gáy - Đắp mặt nạ thạch - Chườm túi thảo dược - Gội đầu lần 1 - Gội đầu lần 2 - Ủ xả tóc - Dưỡng tóc - Sấy tóc",
      },
      {
        name: "Gội chuyên sâu",
        duration: "80'",
        price: "279k",
        details:
          "Tẩy trang - Rửa mặt - Massage đầu khô - Massage mặt - Massage Cổ Vai Gáy - Massage tay - Đắp mặt nạ thạch - Chườm túi thảo dược - Gội đầu lần 1 - Gội đầu lần 2 - Ủ xả tóc - Dưỡng tóc - Sấy tóc",
      },
      {
        name: "Gội chuyên sâu",
        duration: "90'",
        price: "369k",
        details:
          "Tẩy trang - Rửa mặt - Massage đầu khô - Massage mặt - Massage Cổ Vai Gáy - Massage tay, chân - Đắp mặt nạ thạch - Chườm túi thảo dược - Gội đầu lần 1 - Gội đầu lần 2 - Ủ xả tóc - Dưỡng tóc - Sấy tóc (Dùng dầu gội nhập không tính phí)",
      },
    ],
    extras: [
      "Mặt nạ thạch Cool Bạc hà - 40k",
      "Mặt nạ cấp ẩm/ Trẻ hoá/ Trắng sáng - 60k",
      "Tẩy tế bào chết da mặt - 40k",
      "Dầu gội không tính phí: Dove, Sunsilk, Clear",
      "Dầu gội nhập: TIGI/ Nexxus/ Collagen - 40k",
      "Dầu gội thảo dược - 10k",
      "Tẩy tế bào chết da đầu - 40k",
      "Massage mặt 10' - 50k",
    ],
  },
  {
    id: "mat-xa",
    eyebrow: "Menu Massage",
    title: "Dịch vụ massage",
    description: "Massage thư giãn và massage trị liệu.",
    services: [
      {
        name: "Massage thư giãn cổ vai gáy",
        duration: "60'",
        price: "300k",
        details:
          "Ngâm chân - Massage lưng mặt úp: Thắt lưng, Cổ vai gáy, Tay - Massage ngửa: Tay, Cổ vai gáy ngửa, Đầu, Mặt - Chườm nóng thảo dược - Lau khăn ấm",
      },
      {
        name: "Massage thư giãn cổ vai gáy",
        duration: "90'",
        price: "420k",
        details:
          "Ngâm chân - Massage lưng mặt úp: Thắt lưng, Cổ vai gáy, Tay - Massage ngửa: Tay, Cổ vai gáy ngửa, Đầu, Mặt - Chườm nóng thảo dược - Lau khăn ấm",
      },
      {
        name: "Massage thư giãn toàn thân",
        duration: "60'",
        price: "330k",
        details:
          "Ngâm chân - Massage body mặt úp: Thắt lưng, Cổ vai gáy, Tay, Chân & Massage đá nóng lưng - Massage ngửa: Tay, Chân, đầu, mặt - Chườm nóng thảo dược - Lau khăn ấm",
      },
      {
        name: "Massage thư giãn toàn thân",
        duration: "90'",
        price: "460k",
        details:
          "Ngâm chân - Massage body mặt úp: Thắt lưng, Cổ vai gáy, Tay, Chân & Massage đá nóng lưng - Massage ngửa: Tay, Chân, đầu, mặt - Chườm nóng thảo dược - Lau khăn ấm",
      },
      {
        name: "Massage toàn thân đá nóng",
        duration: "90'",
        price: "480k",
        details:
          "Ngâm chân - Massage body mặt úp: Thắt lưng, Cổ vai gáy, Tay, Chân & Massage đá nóng toàn vùng - Massage ngửa: Tay, chân, Đầu, Mặt - Chườm nóng thảo dược - Lau khăn ấm",
      },
      {
        name: "Massage trị liệu cổ vai gáy",
        duration: "80'",
        price: "550k",
        details:
          "Ngâm chân - Làm nóng dốc mạch bằng rượu thuốc - Khai huyệt - Massage trị liệu truy vết điểm đau tắt nghẽn Thắt lưng, Cổ vai gáy - Massage đá nóng - Chườm nóng thảo dược - Massage tay - Massage đầu - Lau khăn nóng",
      },
      {
        name: "Massage trị liệu toàn thân",
        duration: "90'",
        price: "650k",
        details:
          "Ngâm chân - Làm nóng dốc mạch bằng rượu thuốc - Khai huyệt - Massage trị liệu truy vết điểm đau tắt nghẽn Thắt lưng, Cổ vai gáy - Massage tay, chân - Massage đá nóng - Đắp thuốc thảo dược - Chườm nóng thảo dược - Massage đầu - Lau khăn nóng",
      },
    ],
    extras: ["Trượt giác & giác hơi 20' - 150k", "Đắp thuốc thảo dược - 100k"],
  },
  {
    id: "combo",
    eyebrow: "Menu Combo",
    title: "Dịch vụ combo tiết kiệm",
    description: "Combo gội đầu chuyên sâu kết hợp massage.",
    services: [
      {
        name: "Gội đầu chuyên sâu kết hợp massage thư giãn tay chân",
        duration: "90'",
        price: "339k",
        details: "Kết hợp các bước gói gội đầu 60' + 30' massage tay chân",
      },
      {
        name: "Gội đầu chuyên sâu kết hợp massage thư giãn cổ vai gáy, lưng úp",
        duration: "90'",
        price: "349k",
        details: "Kết hợp các bước gói gội đầu 60' + 30' massage cổ vai gáy lưng úp",
      },
      {
        name: "Massage thư giãn cổ vai gáy & gội đầu",
        duration: "90'",
        price: "369k",
        details:
          "Kết hợp các bước gói Massage thư giãn CVG 60' + Các bước của gói gội đầu 30' theo menu",
      },
      {
        name: "Massage thư giãn toàn thân & gội đầu",
        duration: "90'",
        price: "389k",
        details:
          "Kết hợp các bước gói Massage thư giãn toàn thân 60' + Các bước của gói gội đầu 30' theo menu",
      },
      {
        name: "Massage CVG kết hợp trị liệu CVG",
        duration: "80'",
        price: "429k",
        details:
          "Kết hợp các bước gói Massage thư giãn CVG 60' + làm nóng đốc mạch, đẩy hàn khí CVG 20'",
      },
      {
        name: "Massage body kết hợp trị liệu CVG",
        duration: "90'",
        price: "499k",
        details:
          "Kết hợp các bước gói Massage thư giãn toàn thân 60' + làm nóng đốc mạch, đẩy hàn khí CVG 30'",
      },
    ],
    extras: [],
  },
];
