<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảng Xếp Hạng - Con Đường Tơ Lụa Mobile</title>
    
    <!-- Favicon -->
    <link rel="icon" href="images/favicon.ico"/>
    
    <!-- CSS - Sử dụng giống trang chính -->
    <link rel="stylesheet" href="assets/css/swiper-bundle.min.css" />
    <link rel="stylesheet" href="assets/css/vendor.css" />
    <link rel="stylesheet" href="assets/css/main1bce.css?v=6" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/font-override.css" />
    <link rel="stylesheet" href="css/auth-enhanced.css" />
    
    <style>
        /* Ranking overlay - Nổi trên nền trang chính */
        .ranking-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 20px;
            overflow-y: auto;
        }
        
        .ranking-container {
            width: 100%;
            max-width: 1100px;
            background: rgba(20, 20, 30, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 215, 0, 0.2);
            animation: slideIn 0.4s ease-out;
            margin: 20px auto;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .ranking-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(255, 215, 0, 0.3);
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #999;
            text-decoration: none;
            font-size: 14px;
            margin-bottom: 20px;
            transition: color 0.3s;
        }
        
        .back-link:hover {
            color: #ffd700;
        }
        
        .ranking-header h1 {
            font-size: 28px;
            color: #ffd700;
            margin: 10px 0;
            text-shadow: 0 0 10px rgba(255, 215, 0, 0.3);
        }
        
        .ranking-header p {
            color: #999;
            font-size: 15px;
        }
        
        /* Tabs */
        .ranking-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .tab-btn {
            padding: 12px 25px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .tab-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 215, 0, 0.4);
        }
        
        .tab-btn.active {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            color: #000;
            border-color: #ffd700;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.4);
        }
        
        .tab-btn i {
            font-size: 16px;
        }
        
        /* Loading */
        .loading {
            text-align: center;
            padding: 60px 20px;
            color: #888;
        }
        
        .loading i {
            font-size: 48px;
            color: #ffd700;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        /* Ranking Table */
        .ranking-table-container {
            overflow-x: auto;
            margin-bottom: 20px;
        }
        
        .ranking-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            overflow: hidden;
        }
        
        .ranking-table thead {
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.2) 0%, rgba(255, 237, 78, 0.2) 100%);
        }
        
        .ranking-table th {
            padding: 15px;
            text-align: left;
            color: #ffd700;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            border-bottom: 2px solid rgba(255, 215, 0, 0.4);
        }
        
        .ranking-table th:first-child {
            text-align: center;
            width: 80px;
        }
        
        .ranking-table tbody tr {
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s;
        }
        
        .ranking-table tbody tr:hover {
            background: rgba(255, 215, 0, 0.1);
        }
        
        .ranking-table td {
            padding: 15px;
            color: #fff;
            font-size: 14px;
        }
        
        .rank-cell {
            text-align: center;
            font-weight: bold;
            font-size: 16px;
        }
        
        .rank-1 {
            color: #ffd700;
            text-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
        }
        
        .rank-2 {
            color: #c0c0c0;
        }
        
        .rank-3 {
            color: #cd7f32;
        }
        
        .rank-medal {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: rgba(255, 215, 0, 0.2);
            border: 2px solid currentColor;
        }
        
        .char-name {
            font-weight: 600;
            color: #ffd700;
        }
        
        .guild-name {
            font-size: 12px;
            color: #888;
            font-style: italic;
        }
        
        .stat-value {
            font-weight: 600;
        }
        
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #888;
        }
        
        .no-data i {
            font-size: 48px;
            color: #444;
            margin-bottom: 15px;
        }
        
        .back-home {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .back-home a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #888;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }
        
        .back-home a:hover {
            color: #ffd700;
        }
        
        @media (max-width: 768px) {
            .ranking-container {
                padding: 30px 20px;
            }
            
            .ranking-tabs {
                gap: 5px;
            }
            
            .tab-btn {
                padding: 10px 15px;
                font-size: 12px;
            }
            
            .ranking-table th,
            .ranking-table td {
                padding: 10px 8px;
                font-size: 12px;
            }
        }
    </style>
    
    <!-- jQuery -->
    <script type="text/javascript" src="assets/js/jquery-1.11.2.min.js"></script>
</head>
<body class="home-page">
    <!-- Ranking Overlay - Nổi trên nền trang chính -->
    <div class="auth-overlay">
        <div class="ranking-container">
            <!-- Header -->
            <div class="ranking-header">
                <a href="index.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Quay lại trang chủ
                </a>
                <h1 class="f-utm_nyala t-upper">
                    <i class="fas fa-trophy"></i> Bảng Xếp Hạng
                </h1>
                <p class="f-calibri">Top 25 nhân vật hàng đầu server</p>
            </div>

            <!-- Tabs -->
            <div class="ranking-tabs">
                <button class="tab-button active" data-type="level">
                    <i class="fas fa-star"></i> Cấp Độ
                </button>
                <button class="tab-button" data-type="ch">
                    <i class="fas fa-yin-yang"></i> Chinese
                </button>
                <button class="tab-button" data-type="eu">
                    <i class="fas fa-chess-knight"></i> European
                </button>
                <button class="tab-button" data-type="trader">
                    <i class="fas fa-store"></i> Thương Nhân
                </button>
                <button class="tab-button" data-type="hunter">
                    <i class="fas fa-crosshairs"></i> Thợ Săn
                </button>
                <button class="tab-button" data-type="thief">
                    <i class="fas fa-mask"></i> Kẻ Trộm
                </button>
                <button class="tab-button" data-type="gold">
                    <i class="fas fa-coins"></i> Giàu Nhất
                </button>
            </div>

            <!-- Content -->
            <div id="ranking-content">
                <div class="loading">
                    <i class="fas fa-spinner"></i>
                    <p>Đang tải dữ liệu...</p>
                </div>
            </div>

            <!-- Back to Home -->
            <div class="back-home">
                <a href="index.php">
                    <i class="fas fa-home"></i> Quay lại trang chủ
                </a>
            </div>
        </div>
    </div>

    <script>
        let currentType = 'level';
        
        // Load ranking data
        function loadRanking(type) {
            currentType = type;
            
            // Update active tab
            $('.tab-button').removeClass('active');
            $(`.tab-button[data-type="${type}"]`).addClass('active');
            
            // Show loading
            $('#ranking-content').html(`
                <div class="loading">
                    <i class="fas fa-spinner"></i>
                    <p>Đang tải dữ liệu...</p>
                </div>
            `);
            
            // Fetch data
            $.ajax({
                url: 'api/ranking_data.php',
                method: 'GET',
                data: { type: type },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        renderRankingTable(response.data, type);
                    } else {
                        $('#ranking-content').html(`
                            <div class="no-data">
                                <i class="fas fa-inbox"></i>
                                <p>Chưa có dữ liệu xếp hạng</p>
                            </div>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    $('#ranking-content').html(`
                        <div class="no-data">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p>Lỗi khi tải dữ liệu: ${error}</p>
                        </div>
                    `);
                }
            });
        }
        
        // Render ranking table
        function renderRankingTable(data, type) {
            let html = '<div class="ranking-table-container"><table class="ranking-table">';
            
            // Header
            html += '<thead><tr>';
            html += '<th>Hạng</th>';
            html += '<th>Tên Nhân Vật</th>';
            
            switch(type) {
                case 'trader':
                case 'hunter':
                case 'thief':
                    html += '<th>Cấp Job</th>';
                    html += '<th>Kinh Nghiệm</th>';
                    html += '<th>Biệt Danh</th>';
                    break;
                case 'gold':
                    html += '<th>Cấp Độ</th>';
                    html += '<th>Kinh Nghiệm</th>';
                    html += '<th>Vàng</th>';
                    break;
                case 'ch':
                case 'eu':
                    html += '<th>Cấp Độ</th>';
                    html += '<th>Kinh Nghiệm</th>';
                    html += '<th>Điểm KN</th>';
                    html += '<th>Sức Mạnh</th>';
                    html += '<th>Trí Tuệ</th>';
                    break;
                default: // level
                    html += '<th>Cấp Độ</th>';
                    html += '<th>Kinh Nghiệm</th>';
                    html += '<th>Vàng</th>';
                    break;
            }
            
            html += '</tr></thead><tbody>';
            
            // Rows
            data.forEach((row, index) => {
                const rank = index + 1;
                let rankClass = '';
                let rankDisplay = rank;
                
                if (rank === 1) {
                    rankClass = 'rank-1';
                    rankDisplay = '<span class="rank-medal">🥇</span>';
                } else if (rank === 2) {
                    rankClass = 'rank-2';
                    rankDisplay = '<span class="rank-medal">🥈</span>';
                } else if (rank === 3) {
                    rankClass = 'rank-3';
                    rankDisplay = '<span class="rank-medal">🥉</span>';
                }
                
                html += `<tr>`;
                html += `<td class="rank-cell ${rankClass}">${rankDisplay}</td>`;
                html += `<td><span class="char-name">${escapeHtml(row.CharName16)}</span></td>`;
                
                switch(type) {
                    case 'trader':
                    case 'hunter':
                    case 'thief':
                        html += `<td class="stat-value">${row.Level || 0}</td>`;
                        html += `<td>${formatNumber(row.Exp || 0)}</td>`;
                        html += `<td><span class="guild-name">${escapeHtml(row.NickName16 || '-')}</span></td>`;
                        break;
                    case 'gold':
                        html += `<td class="stat-value">${row.CurLevel || 0}</td>`;
                        html += `<td>${formatNumber(row.ExpOffset || 0)}</td>`;
                        html += `<td class="stat-value">${formatNumber(row.RemainGold || 0)}</td>`;
                        break;
                    case 'ch':
                    case 'eu':
                        html += `<td class="stat-value">${row.CurLevel || 0}</td>`;
                        html += `<td>${formatNumber(row.ExpOffset || 0)}</td>`;
                        html += `<td>${row.RemainSkillPoint || 0}</td>`;
                        html += `<td>${row.Strength || 0}</td>`;
                        html += `<td>${row.Intellect || 0}</td>`;
                        break;
                    default: // level
                        html += `<td class="stat-value">${row.CurLevel || 0}</td>`;
                        html += `<td>${formatNumber(row.ExpOffset || 0)}</td>`;
                        html += `<td>${formatNumber(row.RemainGold || 0)}</td>`;
                        break;
                }
                
                html += `</tr>`;
            });
            
            html += '</tbody></table></div>';
            $('#ranking-content').html(html);
        }
        
        // Helper functions
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function formatNumber(num) {
            return new Intl.NumberFormat('vi-VN').format(num);
        }
        
        // Tab click handlers
        $('.tab-button').on('click', function() {
            const type = $(this).data('type');
            loadRanking(type);
        });
        
        // Load default ranking on page load
        $(document).ready(function() {
            loadRanking('level');
        });
    </script>
</body>
</html>

