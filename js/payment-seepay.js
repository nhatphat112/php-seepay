/**
 * Payment Sepay - Realtime Status Polling
 * Handles order status polling and realtime updates
 */

class PaymentSepay {
    constructor() {
        this.orderCode = null;
        this.expiredAt = null;
        this.pollInterval = null;
        this.countdownInterval = null;
        this.maxAttempts = 100; // 5 minutes max (100 * 3s)
        this.attempts = 0;
    }
    
    init(orderCode, expiredAt) {
        this.orderCode = orderCode;
        this.expiredAt = expiredAt;
        
        // Start countdown timer
        this.startCountdown();
        
        // Start polling
        this.startPolling();
    }
    
    startCountdown() {
        if (!this.expiredAt) return;
        
        const updateCountdown = () => {
            const now = new Date().getTime();
            const expired = new Date(this.expiredAt).getTime();
            const distance = expired - now;
            
            if (distance < 0) {
                document.getElementById('countdownTimer').innerHTML = 
                    '<span style="color: #dc3545 !important;">Đã hết hạn</span>';
                this.stopPolling();
                this.updateStatus('expired', 'Đơn hàng đã hết hạn');
                return;
            }
            
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            document.getElementById('countdownTimer').innerHTML = 
                `⏰ Còn lại: ${minutes}:${seconds.toString().padStart(2, '0')}`;
        };
        
        updateCountdown();
        this.countdownInterval = setInterval(updateCountdown, 1000);
    }
    
    startPolling() {
        if (!this.orderCode) return;
        
        // Poll immediately first time
        this.checkStatus();
        
        // Then poll every 3 seconds
        this.pollInterval = setInterval(() => {
            this.checkStatus();
        }, 5000);
    }
    
    stopPolling() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
        if (this.countdownInterval) {
            clearInterval(this.countdownInterval);
            this.countdownInterval = null;
        }
    }
    
    async checkStatus() {
        if (this.attempts >= this.maxAttempts) {
            this.stopPolling();
            this.updateStatus('timeout', 'Đã quá thời gian chờ. Vui lòng kiểm tra lại sau.');
            return;
        }
        
        this.attempts++;
        
        try {
            const response = await fetch(
                `/api/sepay/get_order_status.php?order_code=${this.orderCode}`
            );
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success && data.order) {
                this.updateOrderStatus(data.order);
                
                // Stop polling if order is in final state
                if (data.order.Status === 'completed' || 
                    data.order.Status === 'failed' || 
                    data.order.Status === 'cancelled' ||
                    data.order.Status === 'expired') {
                    this.stopPolling();
                    
                    if (data.order.Status === 'completed') {
                        this.handleCompleted(data.order);
                    } else {
                        this.updateStatus(data.order.Status, this.getStatusMessage(data.order.Status));
                    }
                }
            } else {
                console.error('Get order status error:', data.error);
            }
        } catch (error) {
            console.error('Polling error:', error);
            // Continue polling on error (network issues, etc.)
        }
    }
    
    updateOrderStatus(order) {
        // Update status indicator
        this.updateStatus(order.Status, this.getStatusMessage(order.Status));
        
        // Update UI based on status
        const statusIndicator = document.getElementById('statusIndicator');
        if (statusIndicator) {
            statusIndicator.className = `status-indicator status-${order.Status.toLowerCase()}`;
        }
    }
    
    updateStatus(status, message) {
        const statusText = document.getElementById('statusText');
        if (statusText) {
            statusText.innerHTML = message;
        }
        
        const statusIndicator = document.getElementById('statusIndicator');
        if (statusIndicator) {
            statusIndicator.className = `status-indicator status-${status.toLowerCase()}`;
        }
    }
    
    getStatusMessage(status) {
        const messages = {
            'pending': 'Đang chờ thanh toán...',
            'processing': 'Đang xử lý...',
            'completed': '✅ Thanh toán thành công!',
            'failed': '❌ Thanh toán thất bại',
            'cancelled': '❌ Đơn hàng đã bị hủy',
            'expired': '⏰ Đơn hàng đã hết hạn'
        };
        
        return messages[status] || 'Đang xử lý...';
    }
    
    handleCompleted(order) {
        // Update status
        this.updateStatus('completed', '✅ Thanh toán thành công! Silk đã được cộng vào tài khoản.');
        
        // Refresh Silk amount
        this.refreshSilkAmount();
        
        // Show success message
        this.showSuccessMessage(order);
        
        // Hide payment info after 5 seconds
        setTimeout(() => {
            const paymentInfoBox = document.getElementById('paymentInfoBox');
            if (paymentInfoBox) {
                paymentInfoBox.style.opacity = '0';
                paymentInfoBox.style.transition = 'opacity 0.5s';
                setTimeout(() => {
                    paymentInfoBox.style.display = 'none';
                    document.getElementById('paymentForm').style.display = 'block';
                }, 500);
            }
        }, 5000);
    }
    
    async refreshSilkAmount() {
        try {
            // Fetch current silk from API
            const response = await fetch('/api/sepay/get_silk.php');
            const data = await response.json();
            
            if (data.success) {
                const currentSilk = document.getElementById('currentSilk');
                if (currentSilk) {
                    currentSilk.textContent = parseInt(data.silk).toLocaleString() + ' Silk';
                }
            }
        } catch (error) {
            console.error('Error refreshing silk:', error);
            // Fallback: reload page after 2 seconds
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        }
    }
    
    showSuccessMessage(order) {
        const successBox = document.createElement('div');
        successBox.className = 'alert alert-success';
        successBox.style.marginTop = '20px';
        successBox.innerHTML = `
            <i class="fas fa-check-circle"></i>
            <strong>Thanh toán thành công!</strong><br>
            Bạn đã nhận được <strong>${parseInt(order.SilkAmount || order.silk_amount || 0).toLocaleString()} Silk</strong>.
            Vui lòng kiểm tra lại tài khoản.
        `;
        
        const paymentInfoBox = document.getElementById('paymentInfoBox');
        if (paymentInfoBox) {
            paymentInfoBox.insertBefore(successBox, paymentInfoBox.firstChild);
        }
    }
}

// Global instance
const PaymentSepay = new PaymentSepay();

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PaymentSepay;
}

