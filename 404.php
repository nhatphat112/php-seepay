<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Không Tìm Thấy Trang</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .error-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 20px;
        }
        .error-content {
            max-width: 600px;
        }
        .error-code {
            font-size: 8rem;
            font-family: 'Cinzel', serif;
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        .error-message {
            font-size: 1.5rem;
            margin-bottom: 30px;
        }
        .error-icon {
            font-size: 5rem;
            color: var(--text-muted);
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="error-page">
        <div class="error-content">
            <div class="error-icon">
                <i class="fas fa-ghost"></i>
            </div>
            <div class="error-code">404</div>
            <h1 class="error-message">Không Tìm Thấy Trang</h1>
            <p style="color: var(--text-muted); margin-bottom: 30px;">
                Xin lỗi, trang bạn đang tìm kiếm không tồn tại hoặc đã được di chuyển.
            </p>
            <a href="index.php" class="btn btn-primary btn-large">
                <i class="fas fa-home"></i> Về Trang Chủ
            </a>
        </div>
    </div>
</body>
</html>

