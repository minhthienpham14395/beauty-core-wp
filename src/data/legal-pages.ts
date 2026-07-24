export type LegalPage = {
  slug: string;
  title: string;
  description: string;
  updatedAt: string;
  sections: { heading: string; paragraphs: string[]; items?: string[] }[];
};

export const legalPages: LegalPage[] = [
  {
    slug: "chinh-sach-bao-mat",
    title: "Chính sách bảo mật",
    description: "Cách Cô Năm Spa thu thập, sử dụng và bảo vệ thông tin cá nhân.",
    updatedAt: "13/07/2026",
    sections: [
      {
        heading: "Thông tin có thể được thu thập",
        paragraphs: [
          "Khi khách hàng liên hệ hoặc đặt lịch qua nền tảng đặt lịch bên thứ ba, Cô Năm Spa có thể nhận được thông tin như họ tên, số điện thoại, email và nội dung yêu cầu. Google Analytics 4 có thể thu thập dữ liệu sử dụng không trực tiếp định danh như lượt xem trang, loại thiết bị, trình duyệt và tương tác với website.",
        ],
      },
      {
        heading: "Mục đích sử dụng",
        paragraphs: [
          "Thông tin được dùng để phản hồi yêu cầu, xác nhận lịch hẹn, hỗ trợ khách hàng và cải thiện chất lượng phục vụ. Dữ liệu phân tích được dùng để hiểu cách website được sử dụng và cải thiện nội dung. Chúng tôi không bán thông tin cá nhân cho bên thứ ba.",
        ],
      },
      {
        heading: "Lưu trữ và chia sẻ",
        paragraphs: [
          "Thông tin đặt lịch được xử lý theo chính sách của nền tảng EasySalon. Chúng tôi chỉ chia sẻ thông tin khi cần thiết để thực hiện lịch hẹn, theo yêu cầu pháp luật hoặc khi có sự đồng ý của khách hàng.",
        ],
      },
      {
        heading: "Quảng cáo và lựa chọn quyền riêng tư",
        paragraphs: [
          "Nếu website triển khai quảng cáo của Google trong tương lai, thông tin về công nghệ quảng cáo, các đối tác liên quan và lựa chọn của người dùng sẽ được thông báo rõ ràng. Với người dùng tại các khu vực yêu cầu sự đồng ý theo quy định, website sẽ sử dụng cơ chế quản lý sự đồng ý phù hợp trước khi lưu trữ hoặc truy cập dữ liệu cho mục đích quảng cáo cá nhân hóa.",
        ],
      },
      {
        heading: "Quyền của bạn",
        paragraphs: [
          "Bạn có thể yêu cầu xem, chỉnh sửa hoặc xóa thông tin mà Cô Năm Spa đang lưu giữ bằng cách liên hệ qua email hoặc số điện thoại công bố trên website.",
        ],
      },
    ],
  },
  {
    slug: "dieu-khoan-su-dung",
    title: "Điều khoản sử dụng",
    description: "Điều khoản áp dụng khi sử dụng website Cô Năm Spa.",
    updatedAt: "13/07/2026",
    sections: [
      {
        heading: "Phạm vi",
        paragraphs: [
          "Website cung cấp thông tin tham khảo về dịch vụ, giá và cách đặt lịch tại Cô Năm Spa. Việc truy cập website đồng nghĩa với việc bạn đồng ý các điều khoản này.",
        ],
      },
      {
        heading: "Thông tin dịch vụ",
        paragraphs: [
          "Giá, thời lượng và quy trình có thể thay đổi theo tình trạng thực tế và được xác nhận khi đặt lịch. Nội dung trên website không phải là chẩn đoán hoặc chỉ định y khoa.",
        ],
      },
      {
        heading: "Sở hữu nội dung",
        paragraphs: [
          "Nội dung, hình ảnh và nhận diện trên website thuộc Cô Năm Spa hoặc bên cấp phép. Không sao chép hay sử dụng lại khi chưa được chấp thuận.",
        ],
      },
    ],
  },
  {
    slug: "chinh-sach-cookie",
    title: "Chính sách cookie",
    description: "Thông tin về cookie và lựa chọn quyền riêng tư trên website.",
    updatedAt: "13/07/2026",
    sections: [
      {
        heading: "Cookie là gì",
        paragraphs: [
          "Cookie là tệp nhỏ được trình duyệt lưu để ghi nhớ một số lựa chọn hoặc hỗ trợ hoạt động của website.",
        ],
      },
      {
        heading: "Cách chúng tôi sử dụng",
        paragraphs: [
          "Website sử dụng lưu trữ cần thiết để ghi nhớ thông báo cookie. Google Analytics 4 được dùng để đo lường lượt xem trang và tương tác tổng quát; chúng tôi không gửi tên, số điện thoại hoặc email của bạn vào Analytics.",
        ],
      },
      {
        heading: "Quản lý cookie",
        paragraphs: [
          "Bạn có thể xóa dữ liệu website hoặc chặn cookie trong phần cài đặt trình duyệt. Việc này có thể ảnh hưởng đến một số chức năng hoặc khiến thông báo cookie xuất hiện lại.",
        ],
      },
      {
        heading: "Khi có quảng cáo",
        paragraphs: [
          "Nếu website sử dụng Google AdSense, một nền tảng quản lý sự đồng ý được Google chứng nhận sẽ được hiển thị cho người dùng tại những khu vực áp dụng. Bạn có thể chọn, từ chối hoặc thay đổi lựa chọn về các mục đích quảng cáo theo hướng dẫn trong thông báo đó.",
        ],
      },
    ],
  },
  {
    slug: "mien-tru-trach-nhiem",
    title: "Miễn trừ trách nhiệm",
    description: "Giới hạn trách nhiệm đối với nội dung và dịch vụ tại Cô Năm Spa.",
    updatedAt: "13/07/2026",
    sections: [
      {
        heading: "Thông tin chăm sóc và thư giãn",
        paragraphs: [
          "Các dịch vụ tại Cô Năm Spa nhằm mục đích chăm sóc và thư giãn, không thay thế cho việc khám, chẩn đoán hoặc điều trị y khoa. Nếu có triệu chứng đau kéo dài, chấn thương, bệnh nền, đang mang thai hoặc đang điều trị, bạn nên hỏi ý kiến nhân viên y tế trước khi sử dụng dịch vụ.",
        ],
      },
      {
        heading: "Nội dung website",
        paragraphs: [
          "Chúng tôi nỗ lực giữ thông tin chính xác nhưng không bảo đảm toàn bộ nội dung luôn đầy đủ hoặc cập nhật tại mọi thời điểm. Vui lòng liên hệ trực tiếp để xác nhận trước khi đặt lịch.",
        ],
      },
    ],
  },
  {
    slug: "chinh-sach-dat-lich",
    title: "Chính sách đặt lịch",
    description: "Quy định đặt lịch dịch vụ tại Cô Năm Spa.",
    updatedAt: "13/07/2026",
    sections: [
      {
        heading: "Xác nhận lịch hẹn",
        paragraphs: [
          "Khách hàng có thể đặt lịch qua EasySalon hoặc liên hệ trực tiếp. Lịch hẹn chỉ được xem là xác nhận sau khi nhận được phản hồi từ Cô Năm Spa hoặc nền tảng đặt lịch.",
        ],
      },
      {
        heading: "Thông tin cần cung cấp",
        paragraphs: [
          "Vui lòng cung cấp thông tin liên hệ chính xác và thông báo trước các lưu ý liên quan đến sức khỏe, dị ứng hoặc nhu cầu đặc biệt để nhân viên hỗ trợ phù hợp.",
        ],
      },
      {
        heading: "Thay đổi dịch vụ",
        paragraphs: [
          "Dịch vụ và thời lượng có thể được điều chỉnh sau khi trao đổi với khách hàng, tùy thuộc vào tình trạng phục vụ thực tế.",
        ],
      },
    ],
  },
  {
    slug: "chinh-sach-huy-doi-lich",
    title: "Chính sách hủy, đổi lịch",
    description: "Hướng dẫn thay đổi hoặc hủy lịch hẹn tại Cô Năm Spa.",
    updatedAt: "13/07/2026",
    sections: [
      {
        heading: "Đổi hoặc hủy lịch",
        paragraphs: [
          "Nếu cần thay đổi hoặc hủy lịch, vui lòng liên hệ Cô Năm Spa sớm nhất có thể để chúng tôi hỗ trợ sắp xếp lại khung giờ.",
        ],
      },
      {
        heading: "Đến trễ",
        paragraphs: [
          "Khách đến trễ có thể cần rút ngắn thời lượng phục vụ để không ảnh hưởng đến lịch hẹn sau. Chúng tôi sẽ cố gắng hỗ trợ trong khả năng thực tế.",
        ],
      },
    ],
  },
  {
    slug: "chinh-sach-bien-soan-noi-dung",
    title: "Chính sách biên soạn nội dung",
    description: "Cách Cô Năm Spa xây dựng và cập nhật nội dung trên website.",
    updatedAt: "13/07/2026",
    sections: [
      {
        heading: "Mục đích nội dung",
        paragraphs: [
          "Website cung cấp thông tin về dịch vụ, cách đặt lịch và các bài viết tham khảo về chăm sóc tóc, thư giãn và trải nghiệm đi spa. Nội dung được xây dựng để giúp khách hàng hiểu rõ hơn trước khi lựa chọn dịch vụ.",
        ],
      },
      {
        heading: "Biên soạn và rà soát",
        paragraphs: [
          "Nội dung được biên soạn bởi đội ngũ Cô Năm Spa, dựa trên thông tin dịch vụ đang cung cấp và các lưu ý chăm sóc phổ biến. Chúng tôi rà soát thông tin về giá, thời lượng, lịch hẹn và liên hệ khi có thay đổi.",
        ],
      },
      {
        heading: "Giới hạn chuyên môn",
        paragraphs: [
          "Các bài viết chỉ mang tính tham khảo, không thay thế chẩn đoán hoặc điều trị y khoa. Với triệu chứng kéo dài, chấn thương, bệnh nền, đang mang thai hoặc đang điều trị, khách hàng nên tham khảo ý kiến nhân viên y tế phù hợp trước khi sử dụng dịch vụ.",
        ],
      },
      {
        heading: "Cập nhật và phản hồi",
        paragraphs: [
          "Nếu phát hiện thông tin cần điều chỉnh hoặc muốn góp ý về nội dung, bạn có thể liên hệ Cô Năm Spa qua email hoặc số điện thoại công bố trên website. Chúng tôi sẽ xem xét và cập nhật khi cần thiết.",
        ],
      },
    ],
  },
];
