<?php
/**
 * Sepay Service Class
 * Handles Sepay Payment Gateway integration
 * 
 * Requirements:
 * - composer require sepay/sepay-pg
 * - PHP >= 7.4
 * - ext-json, ext-curl
 */

// Load vendor autoload if exists (for Sepay SDK)
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

require_once __DIR__ . '/../connection_manager.php';

/**
 * Load .env file into environment variables
 * @param bool $forceReload Force reload even if already loaded
 */
function loadSepayEnv($forceReload = false) {
    static $loaded = false;
    if ($loaded && !$forceReload) {
        return; // Already loaded
    }
    
    $envFile = __DIR__ . '/../.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $loadedCount = 0;
        foreach ($lines as $line) {
            // Skip comments and empty lines
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            // Parse KEY=VALUE format
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                // Remove quotes if present
                $value = trim($value, '"\'');
                // Force reload if requested, otherwise only set if not already in environment
                if ($forceReload || !getenv($key)) {
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                    $loadedCount++;
                }
            }
        }
        $loaded = true;
    } else {
        $loaded = true; // Mark as loaded even if file doesn't exist to avoid repeated warnings
    }
}

// Load .env file on include
loadSepayEnv();

// Prevent redeclaration if class already exists (e.g., from autoloader)
if (!class_exists('SepayService')) {
class SepayService {
    private static $client = null;
    private static $config = null;
    
    /**
     * Initialize Sepay client
     */
    public static function init() {
        if (self::$client !== null && self::$config !== null) {
            return self::$client;
        }
        
        // Ensure .env is loaded (force reload to ensure latest values)
        loadSepayEnv(true);
        
        // Load config from environment variables or config file
        self::$config = [
            'merchant_id' => getenv('sepay_MERCHANT_ID') ?: 'your_merchant_id_here',
            'api_secret' => getenv('sepay_API_SECRET') ?: 'your_api_secret_here',
            'environment' => getenv('sepay_ENV') ?: 'sandbox', // sandbox | production
            'webhook_secret' => getenv('sepay_WEBHOOK_SECRET') ?: 'your_webhook_secret_here',
            // Bank account info for QR code generation
            'bank_account' => getenv('sepay_BANK_ACCOUNT') ?: '',
            'bank_name' => getenv('sepay_BANK_NAME') ?: '',
            'account_name' => getenv('sepay_ACCOUNT_NAME') ?: '',
            'qr_template' => getenv('sepay_QR_TEMPLATE') ?: 'compact', // compact | full
            'qr_download' => getenv('sepay_QR_DOWNLOAD') ?: '0' // 0 | 1
        ];
        
        
        try {
            // Map environment to SDK constants
            $env = (self::$config['environment'] === 'production') 
                ? \SePay\SePayClient::ENVIRONMENT_PRODUCTION 
                : \SePay\SePayClient::ENVIRONMENT_SANDBOX;
            
            self::$client = new \SePay\SePayClient(
                self::$config['merchant_id'],
                self::$config['api_secret'],
                $env
            );
        } catch (Exception $e) {
            throw new Exception("Failed to initialize Sepay client: " . $e->getMessage());
        }
        
        return self::$client;
    }
    
    /**
     * Create payment order
     * 
     * @param int $userJID User ID
     * @param string $username Username
     * @param float $amount Amount in VND
     * @param string $paymentMethod Payment method (QR_CODE, BANK_TRANSFER)
     * @return array
     */
    public static function createOrder(
        int $userJID,
        string $username,
        float $amount,
        string $paymentMethod = 'QR_CODE'
    ): array {
        try {
            
    
            /* --------------------------------------------------
             * 1. Validate
             * -------------------------------------------------- */
            if ($amount < 10000 || $amount > 10000000) {
                throw new Exception("Số tiền phải từ 1,000 đến 10,000,000 VNĐ");
            }
            
            $db = ConnectionManager::getAccountDB();
            
            /* --------------------------------------------------
             * 2. Generate order_code (KEY for webhook)
             * -------------------------------------------------- */
            $orderCode = 'ORDER' . time() . random_int(1000, 9999);
            
            /* --------------------------------------------------
             * 3. Create DB order (PENDING)
             * -------------------------------------------------- */
            $stmt = $db->prepare("
                INSERT INTO TB_Order (
                    JID,
                    OrderCode,
                    Amount,
                    SilkAmount,
                    PaymentMethod,
                    Status,
                    IPAddress,
                    UserAgent,
                    ExpiredAt
                ) VALUES (
                    ?, ?, ?, ?, ?, 'pending', ?, ?, DATEADD(minute, 15, GETDATE())
                )
            ");
            
            // Calculate Silk amount based on conversion rate
            // Tỉ lệ: 100,000 VNĐ = 4,000 Silk (1 VNĐ = 0.04 Silk)
            require_once __DIR__ . '/../database.php';
            $silkAmount = (int)($amount * DatabaseConfig::SILK_RATE);
            
            $stmt->execute([
                $userJID,
                $orderCode,
                (int)$amount,
                $silkAmount, // Calculated: Amount × 0.04
                $paymentMethod,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
            
            $orderID = $db->lastInsertId();
            
    
            /* --------------------------------------------------
             * 4. Init Sepay SDK (ensure .env is loaded)
             * -------------------------------------------------- */
            if (!class_exists(\SePay\Builders\CheckoutBuilder::class)) {
                throw new Exception("Sepay SDK not installed");
            }
    
            // Force reload .env and init config
            loadSepayEnv(true);
            $client = self::init();
            
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
            $returnUrl  = "{$protocol}://{$host}/payment.php?order={$orderCode}";
            $webhookUrl = "{$protocol}://{$host}/api/sepay/webhook.php";
    
            /* --------------------------------------------------
             * 5. Build checkout data (SDK)
             * -------------------------------------------------- */
                $checkoutData = \SePay\Builders\CheckoutBuilder::make()
                    ->currency('VND')
                ->orderAmount((int)$amount)
                    ->operation('PURCHASE')
                ->orderInvoiceNumber($orderCode)   // 🔑 webhook mapping
                ->orderDescription("Nap Silk - {$username}")
                    ->successUrl($returnUrl)
                    ->errorUrl($returnUrl)
                    ->cancelUrl($returnUrl)
                ->paymentMethod('BANK_TRANSFER')   // QR nằm trong flow này
                    ->customerId((string)$userJID)
                    ->build();
    
            /* --------------------------------------------------
             * 6. Generate checkout form
             * -------------------------------------------------- */
            $checkout     = $client->checkout();
            $formFields   = $checkout->generateFormFields($checkoutData);
            $checkoutUrl  = $checkout->getCheckoutUrl(self::$config['environment']);
    
    
            /* --------------------------------------------------
             * 7. QR FALLBACK (DISPLAY L)
             * -------------------------------------------------- */
          
    
            // Initialize bank info variables
            $qrUrl = null;
            $bankAccount = self::$config['bank_account'] ?? '';
            $bankName = self::$config['bank_name'] ?? '';
            $accountName = self::$config['account_name'] ?? '';
            $qrTemplate = self::$config['qr_template'] ?? 'compact';
            $qrDownload = self::$config['qr_download'] ?? '0';
            
            if (
                !empty($bankAccount) &&
                !empty($bankName)
            ) {
                // Use same format as get_order_status.php: number_format($amount, 0, '', '')
                $qrUrl = sprintf(
                        'https://qr.sepay.vn/img?acc=%s&bank=%s&amount=%s&des=%s&template=%s&download=%s',
                        urlencode($bankAccount),
                    urlencode($bankName),
                    urlencode(number_format($amount, 0, '', '')),  // Same format as get_order_status.php
                        urlencode($orderCode),
                        urlencode($qrTemplate),
                        urlencode($qrDownload)
                    );
            }
            
            /* --------------------------------------------------
             * 8. Update DB with bank info (still PENDING – webhook will update)
             * -------------------------------------------------- */
            $stmt = $db->prepare("
                UPDATE TB_Order 
                SET
                    QRCode = ?, 
                    BankAccount = ?, 
                    BankName = ?, 
                    AccountName = ?, 
                    Content = ?,
                    Notes = ?,
                    UpdatedDate = GETDATE()
                WHERE OrderID = ?
            ");
            
            $stmt->execute([
                $qrUrl,  // QRCode
                $bankAccount,  // BankAccount
                $bankName,  // BankName
                $accountName,  // AccountName
                $orderCode,  // Content (order code for transfer)
                json_encode([
                    'checkout_url' => $checkoutUrl,
                    'form_fields'  => array_keys($formFields),
                    'qr_fallback'  => $qrUrl,
                    'webhook_url'  => $webhookUrl
                ], JSON_UNESCAPED_UNICODE),
                $orderID
            ]);
            
            /* --------------------------------------------------
             * 9. Return for frontend (match get_order_status.php format)
             * -------------------------------------------------- */
            $result = [
                'success'      => true,
                'order_id'     => $orderID,
                'order_code'   => $orderCode,
    
                // Sepay official flow
                'checkout_url' => $checkoutUrl,
                'form_fields'  => $formFields,
    
                // QR and bank info (same format as get_order_status.php)
                'QRCode'       => $qrUrl,
                'BankAccount'  => $bankAccount,
                'BankName'     => $bankName,
                'AccountName'  => $accountName,
                'Content'      => $orderCode,  // Order code for transfer content
    
                'expires_at'   => date('Y-m-d H:i:s', strtotime('+15 minutes')),
                'note'         => 'Frontend POST form_fields to checkout_url. Payment confirmation via webhook only.'
            ];
    
    
            return $result;
            
        } catch (Throwable $e) {
    
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }
    
    /**
     * Verify webhook signature
     * 
     * @param string $payload JSON payload
     * @param string $signature Signature from header
     * @return bool
     */
    public static function verifyWebhook($payload, $signature) {
        if (self::$config === null) {
            self::init();
        }
        
        $expectedSignature = hash_hmac('sha256', $payload, self::$config['webhook_secret']);
        return hash_equals($expectedSignature, $signature);
    }
    
    /**
     * Process webhook from Sepay
     * 
     * @param array $data Webhook data
     * @return array
     */
    public static function processWebhook($data, $rawInput = '') {
        
        // Ensure .env is loaded and config is initialized
        loadSepayEnv(true);
        if (self::$config === null) {
            self::init();
        }
        
        // Detect webhook type: Sepay API or Bank API
        $isBankWebhook = isset($data['gateway']) || isset($data['accountNumber']) || isset($data['transferAmount']);
        $isSepayWebhook = isset($data['order_code']) || isset($data['orderCode']) || isset($data['status']);
        
        // Extract order code based on webhook type
        $orderCode = '';
        if ($isSepayWebhook) {
            // Sepay webhook format
            $orderCode = $data['order_code'] ?? $data['orderCode'] ?? '';
        } else if ($isBankWebhook) {
            // Bank API webhook format - extract from content field
            $content = $data['content'] ?? $data['description'] ?? '';
            if (preg_match('/ORDER(\d+)/i', $content, $matches)) {
                $orderCode = 'ORDER' . $matches[1];
            }
            // Also check code field
            if (empty($orderCode) && !empty($data['code'])) {
                $orderCode = $data['code'];
            }
        }
        
        if (empty($orderCode)) {
            throw new Exception("Order code not found in webhook data");
        }
        
        // Get signature from header
        $signature = $_SERVER['HTTP_X_SEPAY_SIGNATURE'] ?? '';
        
        // Get Authorization header
        $authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['HTTP_X_AUTHORIZATION'] ?? '';
        $allHeaders = getallheaders();
        if (empty($authorization) && isset($allHeaders['Authorization'])) {
            $authorization = $allHeaders['Authorization'];
        }
        
        // Get raw input (if not provided)
        if (empty($rawInput)) {
            $rawInput = file_get_contents('php://input');
        }
        
        // Extract Authorization header value (handle "Bearer token", "Apikey token", or direct token)
        if (empty($authorization) && isset($allHeaders['Authorization'])) {
            $authorization = $allHeaders['Authorization'];
        }
        
        
        // Check if it's Apikey format - if yes, just split and keep original (no trim, no other processing)
        $isApikeyFormat = stripos($authorization, 'Apikey ') === 0;
        
        if ($isApikeyFormat) {
            // Apikey format: just split, keep original (no trim, no encode/decode)
            $authorization = substr($authorization, 7); // Remove "Apikey " (7 chars)
        } else {
            // Other formats: process normally
            // Remove "Bearer " prefix if present
            if (strpos($authorization, 'Bearer ') === 0) {
                $authorization = substr($authorization, 7);
            }
            $authorization = trim($authorization);
            
            // Handle format: "sepay_WEBHOOK_SECRET=value" - extract value after =
            if (strpos($authorization, 'sepay_WEBHOOK_SECRET=') === 0) {
                $authorization = substr($authorization, strlen('sepay_WEBHOOK_SECRET='));
            }
            // Handle format: "key=value" - extract value after =
            if (strpos($authorization, '=') !== false) {
                $parts = explode('=', $authorization, 2);
                if (count($parts) === 2) {
                    $authorization = trim($parts[1]);
                }
            }
            $authorization = trim($authorization);
        }
        
        
        try {
            $db = ConnectionManager::getAccountDB();
            
            // Verify webhook signature (only for Sepay webhooks)
            if ($isSepayWebhook) {
                if (empty($rawInput)) {
                    throw new Exception("Raw input is required for signature verification");
                }
                $signatureValid = self::verifyWebhook($rawInput, $signature);
                
                if (!$signatureValid) {
                    throw new Exception("Invalid webhook signature");
                }
            } else {
                // Bank API webhook - verify Authorization header
                $webhookSecret = self::$config['webhook_secret'] ?? '';
                if (empty($webhookSecret)) {
                    throw new Exception("Webhook secret not configured");
                }
                
                if (empty($authorization)) {
                    throw new Exception("Missing Authorization header");
                }
                
                $authValid = hash_equals($webhookSecret, $authorization);
                
                if (!$authValid) {
                    throw new Exception("Invalid Authorization header");
                }
            }
            
            
            // Map webhook data to common format
            if ($isBankWebhook) {
                // Bank API format -> Sepay format
                $status = 'completed';
                $transactionID = $data['referenceCode'] ?? $data['id'] ?? 'BANK_' . time();
                $amount = $data['transferAmount'] ?? 0;
            } else {
                // Sepay API format
                $status = strtolower(trim($data['status'] ?? $data['Status'] ?? ''));
                $transactionID = $data['transaction_id'] ?? $data['transactionId'] ?? '';
                $amount = $data['amount'] ?? 0;
            }
            
            // Trim and normalize order code
            $orderCode = trim($orderCode);
            
            // Get order from database with row lock
            $stmt = $db->prepare("SELECT * FROM TB_Order WITH (UPDLOCK, ROWLOCK) WHERE OrderCode = ?");
            $stmt->execute([$orderCode]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                // Try case-insensitive search
                $stmt2 = $db->prepare("SELECT * FROM TB_Order WITH (UPDLOCK, ROWLOCK) WHERE LOWER(OrderCode) = LOWER(?)");
                $stmt2->execute([$orderCode]);
                $order = $stmt2->fetch(PDO::FETCH_ASSOC);
                
                if (!$order) {
                    throw new Exception("Order not found: $orderCode");
                }
            }
            
            $oldStatus = $order['Status'];
            
            // Check if already processed
            if ($order['Status'] === 'completed') {
                return [
                    'success' => true,
                    'message' => 'Order already processed',
                    'order_code' => $orderCode
                ];
            }
            
            // Check idempotency
            if (!empty($transactionID)) {
                if (!empty($order['SepayTransactionID']) && $order['SepayTransactionID'] === $transactionID) {
                    return [
                        'success' => true,
                        'message' => 'Order already processed',
                        'order_code' => $orderCode
                    ];
                }
            }
            
            // Validate amount matches
            if ((int)$amount > 0 && (int)$amount !== (int)$order['Amount']) {
                throw new Exception("Amount mismatch");
            }
            
            // Update order status based on webhook status
            if ($status === 'success' || $status === 'completed') {
                // Start transaction
                $db->beginTransaction();
                
                try {
                    // Update Silk for user
                    $stmt = $db->prepare("UPDATE SK_Silk SET silk_own = silk_own + ? WHERE JID = ?");
                    $stmt->execute([$order['SilkAmount'], $order['JID']]);
                    
                    if ($stmt->rowCount() == 0) {
                        // Create SK_Silk record if not exists
                        $stmt = $db->prepare("INSERT INTO SK_Silk (JID, silk_own) VALUES (?, ?)");
                        $stmt->execute([$order['JID'], $order['SilkAmount']]);
                    }
                    
                    // Update order status
                    $stmt = $db->prepare("
                        UPDATE TB_Order 
                        SET Status = 'completed', 
                            SepayTransactionID = ?,
                            CompletedDate = GETDATE(),
                            UpdatedDate = GETDATE()
                        WHERE OrderID = ?
                    ");
                    $stmt->execute([$transactionID, $order['OrderID']]);
                    
                    // Cộng tích lũy vào column AccumulatedDeposit trong TB_User
                    try {
                        // Convert Amount từ decimal sang int (BIGINT) và JID sang int
                        $userJID = (int)$order['JID'];
                        $totalMoney = (int)round((float)$order['Amount']); // Convert decimal to int
                        
                        // Cộng vào AccumulatedDeposit
                        $stmt = $db->prepare("
                            UPDATE TB_User 
                            SET AccumulatedDeposit = AccumulatedDeposit + ?
                            WHERE JID = ?
                        ");
                        $stmt->execute([$totalMoney, $userJID]);
                    } catch (Exception $e) {
                        // Log error nhưng không ảnh hưởng đến payment processing
                        error_log("Error updating AccumulatedDeposit: " . $e->getMessage());
                    }
                    
                    // Commit transaction
                    $db->commit();
                    
                    // Log transaction
                    try {
                        $logStmt = $db->prepare("
                            INSERT INTO Sk_SilkLog (UserName, Silk_nap, Text, Time_log) 
                            VALUES (?, ?, ?, GETDATE())
                        ");
                        $logText = "Sepay Recharge: $orderCode - " . number_format($order['Amount']) . " VND";
                        $logStmt->execute([
                            $order['JID'], 
                            $order['SilkAmount'],
                            $logText
                        ]);
                    } catch (Exception $e) {
                        // Don't throw - logging is not critical
                    }
                    
                    $result = [
                        'success' => true,
                        'message' => 'Order completed successfully',
                        'order_code' => $orderCode,
                        'silk_amount' => $order['SilkAmount'],
                        'transaction_id' => $transactionID,
                        'status' => 'completed'
                    ];
                    
                    return $result;
                    
                } catch (Exception $e) {
                    $db->rollBack();
                    throw $e;
                }
                
            } else if ($status === 'failed' || $status === 'cancelled') {
                $statusValue = ($status === 'cancelled') ? 'cancelled' : 'failed';
                
                // Update order status to failed
                $notes = $data['message'] ?? $data['reason'] ?? '';
                $stmt = $db->prepare("
                    UPDATE TB_Order 
                    SET Status = ?, 
                        UpdatedDate = GETDATE(),
                        Notes = ?
                    WHERE OrderID = ?
                ");
                $stmt->execute([$statusValue, $notes, $order['OrderID']]);
                
                $result = [
                    'success' => true,
                    'message' => 'Order status updated',
                    'order_code' => $orderCode,
                    'status' => $statusValue
                ];
                
                return $result;
            }
            
            // Unknown status
            $result = [
                'success' => true,
                'message' => 'Webhook received but status not processed',
                'order_code' => $orderCode,
                'status' => $status
            ];
            
            return $result;
            
        } catch (Exception $e) {
            $errorResult = [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'order_code' => $orderCode ?? null,
            ];
            
            return $errorResult;
        }
        
    }
    
    /**
     * Get order status
     * 
     * @param string $orderCode Order code
     * @return array
     */
    public static function getOrderStatus($orderCode) {
        try {
            $db = ConnectionManager::getAccountDB();
            
            $stmt = $db->prepare("SELECT * FROM TB_Order WHERE OrderCode = ?");
            $stmt->execute([$orderCode]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                return [
                    'success' => false,
                    'error' => 'Order not found'
                ];
            }
            
            // Check if order is expired
            if ($order['Status'] === 'pending' || $order['Status'] === 'processing') {
                $expiredAt = new DateTime($order['ExpiredAt']);
                $now = new DateTime();
                
                if ($now > $expiredAt) {
                    // Update status to expired
                    $stmt = $db->prepare("
                        UPDATE TB_Order 
                        SET Status = 'expired', 
                            UpdatedDate = GETDATE()
                        WHERE OrderID = ?
                    ");
                    $stmt->execute([$order['OrderID']]);
                    $order['Status'] = 'expired';
                }
            }
            
            return [
                'success' => true,
                'order' => $order
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get user's order history
     * 
     * @param int $userJID User ID
     * @param int $limit Limit results
     * @return array
     */
    public static function getUserOrders($userJID, $limit = 10) {
        try {
            $db = ConnectionManager::getAccountDB();
            
            $stmt = $db->prepare("
                SELECT * FROM TB_Order 
                WHERE JID = ? 
                ORDER BY CreatedDate DESC 
                OFFSET 0 ROWS FETCH NEXT ? ROWS ONLY
            ");
            $stmt->execute([$userJID, $limit]);
            
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'orders' => $orders
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'orders' => []
            ];
        }
    }
}
} // End of class_exists check

