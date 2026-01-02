<?php
session_start();
require_once 'connection_manager.php';
require_once 'payment_manager.php';

// Get gateway and transaction info
$gateway = $_GET['gateway'] ?? '';
$isNotify = isset($_GET['notify']) && $_GET['notify'] == '1';


try {
    switch ($gateway) {
        case 'vnpay':
            handleVNPayCallback();
            break;
        case 'momo':
            handleMoMoCallback();
            break;
        case 'zalopay':
            handleZaloPayCallback();
            break;
        default:
            throw new Exception("Gateway không được hỗ trợ: $gateway");
    }
} catch (Exception $e) {
    if (!$isNotify) {
        // Redirect to payment page with error
        header('Location: payment.php?error=' . urlencode($e->getMessage()));
        exit();
    }
}

/**
 * Handle VNPay callback
 */
function handleVNPayCallback() {
    global $isNotify;
    
    $vnp_TxnRef = $_GET['vnp_TxnRef'] ?? '';
    $vnp_ResponseCode = $_GET['vnp_ResponseCode'] ?? '';
    $vnp_TransactionStatus = $_GET['vnp_TransactionStatus'] ?? '';
    $vnp_SecureHash = $_GET['vnp_SecureHash'] ?? '';
    $vnp_Amount = $_GET['vnp_Amount'] ?? '';
    $vnp_OrderInfo = $_GET['vnp_OrderInfo'] ?? '';
    
    // Validate required parameters
    if (empty($vnp_TxnRef)) {
        throw new Exception("Thiếu mã giao dịch (vnp_TxnRef)");
    }
    
    if (empty($vnp_ResponseCode)) {
        throw new Exception("Thiếu mã phản hồi (vnp_ResponseCode)");
    }
    
    // Verify signature (simplified for demo)
    if ($vnp_ResponseCode == '00' && $vnp_TransactionStatus == '00') {
        // Payment successful
        $result = PaymentManager::completeTransaction($vnp_TxnRef, $vnp_TxnRef);
        
        if ($result['success']) {
            if (!$isNotify) {
                header('Location: payment.php?success=' . urlencode('Thanh toán thành công! Bạn đã nhận được ' . number_format($result['silk_amount']) . ' Silk.'));
                exit();
            }
        } else {
            if (!$isNotify) {
                header('Location: payment.php?error=' . urlencode($result['error']));
                exit();
            }
        }
    } else {
        // Payment failed
        $errorMsg = "Thanh toán thất bại. Mã lỗi: $vnp_ResponseCode";
        if ($vnp_TransactionStatus != '00') {
            $errorMsg .= ", Trạng thái: $vnp_TransactionStatus";
        }
        
        if (!$isNotify) {
            header('Location: payment.php?error=' . urlencode($errorMsg));
            exit();
        }
    }
}

/**
 * Handle MoMo callback
 */
function handleMoMoCallback() {
    global $isNotify;
    
    $partnerCode = $_GET['partnerCode'] ?? '';
    $accessKey = $_GET['accessKey'] ?? '';
    $requestId = $_GET['requestId'] ?? '';
    $amount = $_GET['amount'] ?? '';
    $orderId = $_GET['orderId'] ?? '';
    $orderInfo = $_GET['orderInfo'] ?? '';
    $orderType = $_GET['orderType'] ?? '';
    $transId = $_GET['transId'] ?? '';
    $resultCode = $_GET['resultCode'] ?? '';
    $message = $_GET['message'] ?? '';
    $payType = $_GET['payType'] ?? '';
    $responseTime = $_GET['responseTime'] ?? '';
    $extraData = $_GET['extraData'] ?? '';
    $m2signature = $_GET['signature'] ?? '';
    
    if ($resultCode == '0') {
        // Payment successful
        $result = PaymentManager::completeTransaction($orderId, $transId);
        
        if ($result['success']) {
            if (!$isNotify) {
                header('Location: payment.php?success=' . urlencode('Thanh toán thành công! Bạn đã nhận được ' . number_format($result['silk_amount']) . ' Silk.'));
                exit();
            }
        } else {
            if (!$isNotify) {
                header('Location: payment.php?error=' . urlencode($result['error']));
                exit();
            }
        }
    } else {
        // Payment failed
        if (!$isNotify) {
            $errorMsg = "Thanh toán thất bại. Lỗi: $message";
            header('Location: payment.php?error=' . urlencode($errorMsg));
            exit();
        }
    }
}

/**
 * Handle ZaloPay callback
 */
function handleZaloPayCallback() {
    global $isNotify;
    
    $data = $_GET['data'] ?? '';
    $mac = $_GET['mac'] ?? '';
    $type = $_GET['type'] ?? '';
    
    // Decode data
    $decodedData = json_decode(base64_decode($data), true);
    
    if ($decodedData && $decodedData['return_code'] == 1) {
        // Payment successful
        $orderId = $decodedData['app_trans_id'] ?? '';
        $zpTransId = $decodedData['zp_trans_id'] ?? '';
        
        $result = PaymentManager::completeTransaction($orderId, $zpTransId);
        
        if ($result['success']) {
            if (!$isNotify) {
                header('Location: payment.php?success=' . urlencode('Thanh toán thành công! Bạn đã nhận được ' . number_format($result['silk_amount']) . ' Silk.'));
                exit();
            }
        } else {
            if (!$isNotify) {
                header('Location: payment.php?error=' . urlencode($result['error']));
                exit();
            }
        }
    } else {
        // Payment failed
        if (!$isNotify) {
            $errorMsg = "Thanh toán thất bại";
            header('Location: payment.php?error=' . urlencode($errorMsg));
            exit();
        }
    }
}

// If this is a notify callback, just return success
if ($isNotify) {
    http_response_code(200);
    echo "OK";
    exit();
}
?>
