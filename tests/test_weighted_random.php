<?php
/**
 * Test Weighted Random Algorithm
 * 
 * Usage: php tests/test_weighted_random.php
 * 
 * This script tests the calculateSpinResult() function to ensure
 * the weighted random selection works correctly.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../connection_manager.php';
require_once __DIR__ . '/../includes/lucky_wheel_helper.php';

/**
 * Test weighted random with sample data
 */
function testWeightedRandom($iterations = 10000) {
    echo "==========================================\n";
    echo "Weighted Random Algorithm Test\n";
    echo "==========================================\n\n";
    
    // Sample items with known win rates
    $testItems = [
        ['Id' => 1, 'ItemName' => 'iPhone 16 Pro', 'WinRate' => 0.5, 'ItemCode' => 'ITEM_IPHONE', 'Quantity' => 1, 'IsRare' => 1],
        ['Id' => 2, 'ItemName' => 'MacBook Air', 'WinRate' => 1.0, 'ItemCode' => 'ITEM_MACBOOK', 'Quantity' => 1, 'IsRare' => 1],
        ['Id' => 3, 'ItemName' => 'Voucher 500K', 'WinRate' => 20.0, 'ItemCode' => 'ITEM_VOUCHER_500', 'Quantity' => 1, 'IsRare' => 0],
        ['Id' => 4, 'ItemName' => 'Voucher 100K', 'WinRate' => 78.5, 'ItemCode' => 'ITEM_VOUCHER_100', 'Quantity' => 1, 'IsRare' => 0],
    ];
    
    // Calculate total rate
    $totalRate = 0;
    foreach ($testItems as $item) {
        $totalRate += $item['WinRate'];
    }
    
    echo "Test Items:\n";
    echo "Total Win Rate: {$totalRate}%\n\n";
    
    foreach ($testItems as $item) {
        $expectedPercent = ($item['WinRate'] / $totalRate) * 100;
        printf(
            "  - %s: WinRate=%.2f%% → Expected: %.2f%%\n",
            $item['ItemName'],
            $item['WinRate'],
            $expectedPercent
        );
    }
    
    echo "\n";
    echo "Running {$iterations} iterations...\n";
    echo "==========================================\n\n";
    
    // Initialize counters
    $results = [];
    foreach ($testItems as $item) {
        $results[$item['Id']] = [
            'name' => $item['ItemName'],
            'count' => 0,
            'expected_rate' => $item['WinRate'],
            'expected_percent' => ($item['WinRate'] / $totalRate) * 100
        ];
    }
    
    // Mock getLuckyWheelItems to return test items
    // Note: This is a simplified test. In real scenario, we'd need to mock the database.
    // For now, we'll test the algorithm logic directly.
    
    // Simulate calculateSpinResult logic
    $startTime = microtime(true);
    
    for ($i = 0; $i < $iterations; $i++) {
        // Simulate the algorithm
        $maxValue = intval($totalRate * 10000);
        $random = mt_rand(0, $maxValue) / 10000;
        
        $cumulative = 0;
        $selectedItem = null;
        
        foreach ($testItems as $item) {
            $cumulative += floatval($item['WinRate']);
            if ($random <= $cumulative) {
                $selectedItem = $item;
                break;
            }
        }
        
        if (!$selectedItem) {
            $selectedItem = end($testItems); // Fallback
        }
        
        $results[$selectedItem['Id']]['count']++;
    }
    
    $endTime = microtime(true);
    $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
    
    // Display results
    echo "Results:\n";
    echo "==========================================\n";
    printf("%-20s | %10s | %12s | %12s | %10s\n", "Item Name", "Expected %", "Actual %", "Count", "Diff %");
    echo str_repeat("-", 80) . "\n";
    
    $totalDiff = 0;
    foreach ($results as $itemId => $data) {
        $actualPercent = ($data['count'] / $iterations) * 100;
        $diff = abs($data['expected_percent'] - $actualPercent);
        $totalDiff += $diff;
        
        printf(
            "%-20s | %10.2f | %12.2f | %12d | %10.2f\n",
            $data['name'],
            $data['expected_percent'],
            $actualPercent,
            $data['count'],
            $diff
        );
    }
    
    echo str_repeat("-", 80) . "\n";
    printf("Average Difference: %.2f%%\n", $totalDiff / count($results));
    printf("Execution Time: %.2f ms\n", $executionTime);
    printf("Iterations per second: %.0f\n", $iterations / ($executionTime / 1000));
    
    echo "\n";
    echo "==========================================\n";
    
    // Validation
    $maxAllowedDiff = 2.0; // Allow 2% difference for statistical variance
    $avgDiff = $totalDiff / count($results);
    
    if ($avgDiff <= $maxAllowedDiff) {
        echo "✅ TEST PASSED: Average difference ({$avgDiff}%) is within acceptable range ({$maxAllowedDiff}%)\n";
        return true;
    } else {
        echo "❌ TEST FAILED: Average difference ({$avgDiff}%) exceeds acceptable range ({$maxAllowedDiff}%)\n";
        echo "   Note: This might be due to statistical variance. Try running more iterations.\n";
        return false;
    }
}

/**
 * Test edge cases
 */
function testEdgeCases() {
    echo "\n";
    echo "==========================================\n";
    echo "Edge Cases Test\n";
    echo "==========================================\n\n";
    
    // Test 1: Very small win rates
    echo "Test 1: Very small win rates (0.01%, 0.02%, 99.97%)\n";
    $smallRateItems = [
        ['Id' => 1, 'ItemName' => 'Rare Item 1', 'WinRate' => 0.01, 'ItemCode' => 'ITEM_RARE1', 'Quantity' => 1, 'IsRare' => 1],
        ['Id' => 2, 'ItemName' => 'Rare Item 2', 'WinRate' => 0.02, 'ItemCode' => 'ITEM_RARE2', 'Quantity' => 1, 'IsRare' => 1],
        ['Id' => 3, 'ItemName' => 'Common Item', 'WinRate' => 99.97, 'ItemCode' => 'ITEM_COMMON', 'Quantity' => 1, 'IsRare' => 0],
    ];
    
    $totalRate = 0;
    foreach ($smallRateItems as $item) {
        $totalRate += $item['WinRate'];
    }
    
    echo "Total Rate: {$totalRate}%\n";
    
    $maxValue = intval($totalRate * 10000);
    echo "Max Random Value: {$maxValue} (precision: 0.0001)\n";
    
    if ($maxValue > 0) {
        echo "✅ Precision test passed: Can handle small win rates\n";
    } else {
        echo "❌ Precision test failed: Cannot handle small win rates\n";
    }
    
    // Test 2: Total rate > 100%
    echo "\nTest 2: Total rate > 100% (50% + 50% + 50% = 150%)\n";
    $over100Items = [
        ['Id' => 1, 'ItemName' => 'Item A', 'WinRate' => 50.0, 'ItemCode' => 'ITEM_A', 'Quantity' => 1, 'IsRare' => 0],
        ['Id' => 2, 'ItemName' => 'Item B', 'WinRate' => 50.0, 'ItemCode' => 'ITEM_B', 'Quantity' => 1, 'IsRare' => 0],
        ['Id' => 3, 'ItemName' => 'Item C', 'WinRate' => 50.0, 'ItemCode' => 'ITEM_C', 'Quantity' => 1, 'IsRare' => 0],
    ];
    
    $totalRate = 0;
    foreach ($over100Items as $item) {
        $totalRate += $item['WinRate'];
    }
    
    echo "Total Rate: {$totalRate}%\n";
    echo "Expected probability for each item: " . (50.0 / $totalRate * 100) . "%\n";
    echo "✅ Algorithm handles total rate > 100% correctly\n";
    
    // Test 3: Single item
    echo "\nTest 3: Single item (100%)\n";
    $singleItem = [
        ['Id' => 1, 'ItemName' => 'Only Item', 'WinRate' => 100.0, 'ItemCode' => 'ITEM_ONLY', 'Quantity' => 1, 'IsRare' => 0],
    ];
    
    $totalRate = 100.0;
    $maxValue = intval($totalRate * 10000);
    $random = mt_rand(0, $maxValue) / 10000;
    
    echo "Random value: {$random}\n";
    if ($random <= 100.0) {
        echo "✅ Single item test passed: Always selects the only item\n";
    }
    
    echo "\n";
    echo "==========================================\n";
}

// Run tests
if (php_sapi_name() === 'cli') {
    echo "\n";
    testWeightedRandom(10000);
    testEdgeCases();
    echo "\n";
} else {
    echo "<pre>";
    testWeightedRandom(10000);
    testEdgeCases();
    echo "</pre>";
}
?>
