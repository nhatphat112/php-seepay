<?php
/**
 * Payment Manager Class
 * Handles payment gateway integration and transaction management
 */

class PaymentManager {
    private static $gateways = [];
    private static $methods = [];
    
    /**
     * Initialize payment gateways and methods
     */
    public static function init() {
        try {
            $db = ConnectionManager::getAccountDB();
            
            // Load payment gateways
            $stmt = $db->query("SELECT * FROM TB_PaymentGateways WHERE IsActive = 1");
            self::$gateways = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Load payment methods
            $stmt = $db->query("SELECT * FROM TB_PaymentMethods WHERE IsActive = 1");
            self::$methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            // Silently fail initialization
        }
    }
    
    /**
     * Get available payment gateways
     */
    public static function getGateways() {
        if (empty(self::$gateways)) {
            self::init();
        }
        return self::$gateways;
    }
    
    /**
     * Get available payment methods
     */
    public static function getMethods() {
        if (empty(self::$methods)) {
            self::init();
        }
        return self::$methods;
    }
    
    /**
     * Get payment methods by gateway
     */
    public static function getMethodsByGateway($gatewayCode) {
        $methods = self::getMethods();
        return array_filter($methods, function($method) use ($gatewayCode) {
            return $method['GatewayCode'] === $gatewayCode;
        });
    }
    
    /**
     * Create payment transaction
     */
    public static function createTransaction($userJID, $username, $amount, $paymentMethod, $bankCode, $bankName, $ipAddress = null, $userAgent = null) {
        try {
            $db = ConnectionManager::getAccountDB();
            
            // Validate amount
            if ($amount < 10000 || $amount > 10000000) {
                throw new Exception("Số tiền phải từ 10,000 VNĐ đến 10,000,000 VNĐ");
            }
            
            // Get payment method info
            $method = self::getMethodByCode($paymentMethod);
            if (!$method) {
                throw new Exception("Phương thức thanh toán không hợp lệ");
            }
            
            // Generate transaction ID
            $transactionID = 'TXN' . time() . rand(1000, 9999);
            
            // Insert transaction
            $stmt = $db->prepare("
                INSERT INTO TB_RechargeTransactions (
                    UserJID, Username, Amount, SilkAmount, PaymentMethod, 
                    BankCode, BankName, TransactionID, IPAddress, UserAgent, Status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            
            $stmt->execute([
                $userJID, $username, $amount, $amount, $paymentMethod,
                $bankCode, $bankName, $transactionID, $ipAddress, $userAgent
            ]);
            
            $rechargeID = $db->lastInsertId();
            
            // Calculate Silk amount based on conversion rate
            // Tỉ lệ: 100,000 VNĐ = 4,000 Silk (1 VNĐ = 0.04 Silk)
            require_once __DIR__ . '/database.php';
            $silkAmount = (int)($amount * DatabaseConfig::SILK_RATE);
            
            return [
                'success' => true,
                'recharge_id' => $rechargeID,
                'transaction_id' => $transactionID,
                'amount' => $amount,
                'silk_amount' => $silkAmount // Calculated: Amount × 0.04
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Process payment with gateway
     */
    public static function processPayment($rechargeID, $gatewayCode) {
        try {
            $db = ConnectionManager::getAccountDB();
            
            // Get transaction details
            $stmt = $db->prepare("SELECT * FROM TB_RechargeTransactions WHERE ID = ?");
            $stmt->execute([$rechargeID]);
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$transaction) {
                throw new Exception("Giao dịch không tồn tại");
            }
            
            // Get gateway config
            $gateway = self::getGatewayByCode($gatewayCode);
            if (!$gateway) {
                throw new Exception("Cổng thanh toán không hợp lệ");
            }
            
            // Process based on gateway
            switch ($gatewayCode) {
                case 'VNPAY':
                    return self::processVNPay($transaction, $gateway);
                case 'MOMO':
                    return self::processMoMo($transaction, $gateway);
                case 'ZALOPAY':
                    return self::processZaloPay($transaction, $gateway);
                default:
                    throw new Exception("Cổng thanh toán không được hỗ trợ");
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Process VNPay payment
     */
    private static function processVNPay($transaction, $gateway) {
        $config = json_decode($gateway['ConfigData'], true);
        
        // VNPay parameters
        $vnp_TmnCode = $config['merchant_id'];
        $vnp_HashSecret = $config['secret_key'];
        $vnp_Url = $config['url'];
        
        $vnp_TxnRef = $transaction['TransactionID'];
        $vnp_OrderInfo = 'Nạp Silk - ' . $transaction['Username'];
        $vnp_OrderType = 'other';
        $vnp_Amount = $transaction['Amount'] * 100; // VNPay amount in cents
        $vnp_Locale = 'vn';
        $vnp_ReturnUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/payment_callback.php?gateway=vnpay';
        
        $vnp_Params = array(
            'vnp_Version' => '2.1.0',
            'vnp_Command' => 'pay',
            'vnp_TmnCode' => $vnp_TmnCode,
            'vnp_Amount' => $vnp_Amount,
            'vnp_CurrCode' => 'VND',
            'vnp_TxnRef' => $vnp_TxnRef,
            'vnp_OrderInfo' => $vnp_OrderInfo,
            'vnp_OrderType' => $vnp_OrderType,
            'vnp_Locale' => $vnp_Locale,
            'vnp_ReturnUrl' => $vnp_ReturnUrl,
            'vnp_IpAddr' => $_SERVER['REMOTE_ADDR']
        );
        
        ksort($vnp_Params);
        $query = http_build_query($vnp_Params);
        $vnp_SecureHash = hash_hmac('sha512', $query, $vnp_HashSecret);
        $vnp_Params['vnp_SecureHash'] = $vnp_SecureHash;
        
        $paymentUrl = $vnp_Url . '?' . http_build_query($vnp_Params);
        
        return [
            'success' => true,
            'payment_url' => $paymentUrl,
            'gateway' => 'VNPay'
        ];
    }
    
    /**
     * Process MoMo payment
     */
    private static function processMoMo($transaction, $gateway) {
        $config = json_decode($gateway['ConfigData'], true);
        
        $partnerCode = $config['partner_code'];
        $accessKey = $config['access_key'];
        $secretKey = $config['secret_key'];
        $endpoint = $config['endpoint'];
        
        $orderId = $transaction['TransactionID'];
        $orderInfo = 'Nạp Silk - ' . $transaction['Username'];
        $amount = $transaction['Amount'];
        $returnUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/payment_callback.php?gateway=momo';
        $notifyUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/payment_callback.php?gateway=momo&notify=1';
        
        $requestId = time() . rand(1000, 9999);
        $extraData = '';
        
        // Create signature
        $rawHash = "accessKey=" . $accessKey . "&amount=" . $amount . "&extraData=" . $extraData . "&ipnUrl=" . $notifyUrl . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo . "&partnerCode=" . $partnerCode . "&redirectUrl=" . $returnUrl . "&requestId=" . $requestId . "&requestType=captureWallet";
        $signature = hash_hmac('sha256', $rawHash, $secretKey);
        
        $data = array(
            'partnerCode' => $partnerCode,
            'accessKey' => $accessKey,
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $returnUrl,
            'ipnUrl' => $notifyUrl,
            'extraData' => $extraData,
            'requestType' => 'captureWallet',
            'signature' => $signature
        );
        
        // Send request to MoMo
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if ($result && $result['resultCode'] == 0) {
            return [
                'success' => true,
                'payment_url' => $result['payUrl'],
                'gateway' => 'MoMo'
            ];
        } else {
            throw new Exception("Lỗi tạo giao dịch MoMo: " . ($result['message'] ?? 'Unknown error'));
        }
    }
    
    /**
     * Process ZaloPay payment
     */
    private static function processZaloPay($transaction, $gateway) {
        $config = json_decode($gateway['ConfigData'], true);
        
        $app_id = $config['app_id'];
        $key1 = $config['key1'];
        $key2 = $config['key2'];
        $endpoint = $config['endpoint'];
        
        $orderId = $transaction['TransactionID'];
        $amount = $transaction['Amount'];
        $description = 'Nạp Silk - ' . $transaction['Username'];
        $callback_url = 'http://' . $_SERVER['HTTP_HOST'] . '/payment_callback.php?gateway=zalopay';
        
        $data = array(
            'app_id' => $app_id,
            'app_trans_id' => $orderId,
            'app_user' => $transaction['Username'],
            'amount' => $amount,
            'app_time' => time() * 1000,
            'item' => $description,
            'description' => $description,
            'bank_code' => 'zalopayapp',
            'callback_url' => $callback_url
        );
        
        // Create signature
        $data_string = $app_id . '|' . $data['app_trans_id'] . '|' . $data['app_user'] . '|' . $data['amount'] . '|' . $data['app_time'] . '|' . $data['item'] . '|' . $data['description'];
        $data['mac'] = hash_hmac('sha256', $data_string, $key1);
        
        // Send request to ZaloPay
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if ($result && $result['return_code'] == 1) {
            return [
                'success' => true,
                'payment_url' => $result['order_url'],
                'gateway' => 'ZaloPay'
            ];
        } else {
            throw new Exception("Lỗi tạo giao dịch ZaloPay: " . ($result['return_message'] ?? 'Unknown error'));
        }
    }
    
    /**
     * Complete transaction
     */
    public static function completeTransaction($transactionID, $gatewayTransactionID = null) {
        try {
            $db = ConnectionManager::getAccountDB();
            
            // Get transaction
            $stmt = $db->prepare("SELECT * FROM TB_RechargeTransactions WHERE TransactionID = ?");
            $stmt->execute([$transactionID]);
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$transaction) {
                throw new Exception("Giao dịch không tồn tại");
            }
            
            if ($transaction['Status'] !== 'pending') {
                throw new Exception("Giao dịch đã được xử lý");
            }
            
            // Update Silk
            $stmt = $db->prepare("UPDATE SK_Silk SET silk_own = silk_own + ? WHERE JID = ?");
            $stmt->execute([$transaction['SilkAmount'], $transaction['UserJID']]);
            
            if ($stmt->rowCount() == 0) {
                $stmt = $db->prepare("INSERT INTO SK_Silk (JID, silk_own) VALUES (?, ?)");
                $stmt->execute([$transaction['UserJID'], $transaction['SilkAmount']]);
            }
            
            // Update transaction status
            $stmt = $db->prepare("
                UPDATE TB_RechargeTransactions 
                SET Status = 'completed', CompletedDate = GETDATE(), GatewayTransactionID = ?
                WHERE TransactionID = ?
            ");
            $stmt->execute([$gatewayTransactionID, $transactionID]);
            
            // Log transaction vào Sk_SilkLog (bảng có sẵn trong Silkroad)
            try {
                $accountDb = ConnectionManager::getAccountDB();
                
                // Log vào Sk_SilkLog với cấu trúc thực tế: ID, UserName, Silk_nap, Text, Time_log
                $logStmt = $accountDb->prepare("
                    INSERT INTO Sk_SilkLog (UserName, Silk_nap, Text, Time_log) 
                    VALUES (?, ?, ?, GETDATE())
                ");
                $logText = "Recharge: " . $transactionID . " - " . $transaction['Amount'] . " VND";
                $logStmt->execute([
                    $transaction['Username'], 
                    $transaction['SilkAmount'],
                    $logText
                ]);
                
            } catch (Exception $e) {
                // Silently fail logging
            }
            
            return [
                'success' => true,
                'message' => 'Giao dịch hoàn tất thành công',
                'silk_amount' => $transaction['SilkAmount']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get method by code
     */
    private static function getMethodByCode($code) {
        $methods = self::getMethods();
        foreach ($methods as $method) {
            if ($method['MethodCode'] === $code) {
                return $method;
            }
        }
        return null;
    }
    
    /**
     * Get gateway by code
     */
    private static function getGatewayByCode($code) {
        $gateways = self::getGateways();
        foreach ($gateways as $gateway) {
            if ($gateway['GatewayCode'] === $code) {
                return $gateway;
            }
        }
        return null;
    }
    
    /**
     * Get user transaction history
     */
    public static function getUserTransactions($userJID, $limit = 10) {
        try {
            $db = ConnectionManager::getAccountDB();
            
            $stmt = $db->prepare("
                SELECT * FROM TB_RechargeTransactions 
                WHERE UserJID = ? 
                ORDER BY CreatedDate DESC 
                LIMIT ?
            ");
            $stmt->execute([$userJID, $limit]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return [];
        }
    }
}
