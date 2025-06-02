<?php
echo "Running all tests...\n\n";

// Test files to run
$testFiles = [
    'test-connection.php',
    'system-test.php'
];

$allPassed = true;
foreach ($testFiles as $testFile) {
    echo "Running {$testFile}...\n";
    echo "======================\n";
    
    ob_start();
    require_once __DIR__ . '/' . $testFile;
    $output = ob_get_clean();
    
    echo $output;
    
    if (strpos($output, '❌') !== false) {
        $allPassed = false;
    }
    
    echo "\n";
}

echo "\nTest Suite Summary:\n";
echo "==================\n";
if ($allPassed) {
    echo "✅ All tests passed successfully!\n";
} else {
    echo "❌ Some tests failed. Please check the details above.\n";
} 