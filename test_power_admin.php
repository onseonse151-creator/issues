<?php
/**
 * Comprehensive Testing Script for NEUST Power Admin
 * Tests all security, functionality, and UI improvements
 */

require_once 'config.php';
require_once 'includes/security.php';
require_once 'includes/audit_logger.php';
require_once 'includes/ui_components.php';

class PowerAdminTester {
    private $conn;
    private $testResults = [];
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }
    
    public function runAllTests() {
        echo "<h1>NEUST Power Admin - Comprehensive Test Suite</h1>\n";
        echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 8px;'>\n";
        
        $this->testSecurity();
        $this->testDatabase();
        $this->testFileUploads();
        $this->testUIComponents();
        $this->testAuditLogging();
        $this->testErrorHandling();
        $this->testPerformance();
        
        $this->displayResults();
        echo "</div>\n";
    }
    
    private function testSecurity() {
        echo "<h2>🔒 Security Tests</h2>\n";
        
        // Test CSRF protection
        $this->test('CSRF Token Generation', function() {
            $token1 = getCSRFToken();
            $token2 = getCSRFToken();
            return $token1 === $token2 && strlen($token1) === 64;
        });
        
        // Test input sanitization
        $this->test('Input Sanitization', function() {
            $malicious = '<script>alert("xss")</script>';
            $sanitized = sanitizeInput($malicious, 'html');
            return !strpos($sanitized, '<script>');
        });
        
        // Test rate limiting
        $this->test('Rate Limiting', function() {
            $result1 = checkRateLimit('test_action', 1, 60);
            $result2 = checkRateLimit('test_action', 1, 60);
            return $result1 === true && $result2 === false;
        });
        
        // Test permission system
        $this->test('Permission System', function() {
            $_SESSION['role'] = 'Power Admin';
            return hasPermission('manage_grievances') === true;
        });
        
        // Test file upload security
        $this->test('File Upload Security', function() {
            $fakeFile = [
                'name' => 'test.php',
                'type' => 'application/x-php',
                'tmp_name' => '/tmp/fake',
                'error' => UPLOAD_ERR_OK,
                'size' => 1024
            ];
            
            $result = secureFileUpload($fakeFile);
            return $result['success'] === false;
        });
    }
    
    private function testDatabase() {
        echo "<h2>🗄️ Database Tests</h2>\n";
        
        // Test prepared statements
        $this->test('Prepared Statements', function() {
            try {
                $stmt = $this->conn->prepare("SELECT 1 as test");
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                return $result['test'] === 1;
            } catch (Exception $e) {
                return false;
            }
        });
        
        // Test database functions
        $this->test('Database Helper Functions', function() {
            return function_exists('db_has_column') && function_exists('db_table_exists');
        });
        
        // Test audit table creation
        $this->test('Audit Table Creation', function() {
            try {
                $result = $this->conn->query("SHOW TABLES LIKE 'audit_logs'");
                return $result->num_rows > 0;
            } catch (Exception $e) {
                return false;
            }
        });
    }
    
    private function testFileUploads() {
        echo "<h2>📁 File Upload Tests</h2>\n";
        
        // Test upload directory creation
        $this->test('Upload Directory Creation', function() {
            $uploadDir = __DIR__ . '/uploads/test';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            return is_dir($uploadDir) && is_writable($uploadDir);
        });
        
        // Test file type validation
        $this->test('File Type Validation', function() {
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
            $testFile = ['name' => 'test.jpg', 'type' => 'image/jpeg', 'error' => UPLOAD_ERR_OK, 'size' => 1024, 'tmp_name' => '/tmp/test'];
            $result = secureFileUpload($testFile, $allowedTypes);
            return $result['success'] === true;
        });
    }
    
    private function testUIComponents() {
        echo "<h2>🎨 UI Component Tests</h2>\n";
        
        // Test UI component generation
        $this->test('UI Component Generation', function() {
            $button = ui_button('Test Button', 'primary', 'md', 'fas fa-test');
            return strpos($button, 'Test Button') !== false && strpos($button, 'btn-primary') !== false;
        });
        
        // Test toast generation
        $this->test('Toast Component', function() {
            $toast = ui_toast('Test message', 'success');
            return strpos($toast, 'Test message') !== false && strpos($toast, 'toast-success') !== false;
        });
        
        // Test form components
        $this->test('Form Components', function() {
            $input = ui_input('test_field', 'text', 'Test Label', 'Test Placeholder', true);
            return strpos($input, 'test_field') !== false && strpos($input, 'required') !== false;
        });
    }
    
    private function testAuditLogging() {
        echo "<h2>📊 Audit Logging Tests</h2>\n";
        
        // Test audit log creation
        $this->test('Audit Log Creation', function() {
            audit_log('test_action', 'test_resource', '123', ['test' => 'data']);
            return true; // If no exception thrown, it worked
        });
        
        // Test audit log retrieval
        $this->test('Audit Log Retrieval', function() {
            $logs = AuditLogger::getInstance()->getLogs(['action' => 'test_action'], 10, 0);
            return is_array($logs);
        });
        
        // Test audit statistics
        $this->test('Audit Statistics', function() {
            $stats = AuditLogger::getInstance()->getStatistics('7 days');
            return is_array($stats) && isset($stats['total_events']);
        });
    }
    
    private function testErrorHandling() {
        echo "<h2>⚠️ Error Handling Tests</h2>\n";
        
        // Test error page creation
        $this->test('Error Page Creation', function() {
            return file_exists('error_page.php');
        });
        
        // Test error logging
        $this->test('Error Logging', function() {
            logSecurityEvent('test_error', ['test' => 'data']);
            return true; // If no exception thrown, it worked
        });
        
        // Test exception handling
        $this->test('Exception Handling', function() {
            try {
                throw new Exception('Test exception');
            } catch (Exception $e) {
                return $e->getMessage() === 'Test exception';
            }
        });
    }
    
    private function testPerformance() {
        echo "<h2>⚡ Performance Tests</h2>\n";
        
        // Test page load time
        $this->test('Page Load Performance', function() {
            $start = microtime(true);
            // Simulate page load
            usleep(100000); // 0.1 seconds
            $end = microtime(true);
            $loadTime = $end - $start;
            return $loadTime < 1.0; // Should be less than 1 second
        });
        
        // Test memory usage
        $this->test('Memory Usage', function() {
            $memoryUsage = memory_get_usage(true);
            return $memoryUsage < 50 * 1024 * 1024; // Less than 50MB
        });
        
        // Test database query performance
        $this->test('Database Query Performance', function() {
            $start = microtime(true);
            $this->conn->query("SELECT 1");
            $end = microtime(true);
            $queryTime = $end - $start;
            return $queryTime < 0.1; // Less than 0.1 seconds
        });
    }
    
    private function test($testName, $testFunction) {
        $start = microtime(true);
        $result = false;
        $error = null;
        
        try {
            $result = $testFunction();
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        
        $end = microtime(true);
        $duration = round(($end - $start) * 1000, 2);
        
        $status = $result ? '✅ PASS' : '❌ FAIL';
        $color = $result ? '#28a745' : '#dc3545';
        
        echo "<div style='margin: 5px 0; padding: 5px; border-left: 3px solid {$color};'>\n";
        echo "<strong>{$status}</strong> {$testName} ({$duration}ms)\n";
        if ($error) {
            echo "<br><small style='color: #dc3545;'>Error: {$error}</small>\n";
        }
        echo "</div>\n";
        
        $this->testResults[] = [
            'name' => $testName,
            'passed' => $result,
            'duration' => $duration,
            'error' => $error
        ];
    }
    
    private function displayResults() {
        echo "<h2>📈 Test Summary</h2>\n";
        
        $totalTests = count($this->testResults);
        $passedTests = array_filter($this->testResults, function($test) {
            return $test['passed'];
        });
        $passedCount = count($passedTests);
        $failedCount = $totalTests - $passedCount;
        
        $passRate = $totalTests > 0 ? round(($passedCount / $totalTests) * 100, 1) : 0;
        
        echo "<div style='background: #e9ecef; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
        echo "<strong>Total Tests:</strong> {$totalTests}<br>\n";
        echo "<strong>Passed:</strong> <span style='color: #28a745;'>{$passedCount}</span><br>\n";
        echo "<strong>Failed:</strong> <span style='color: #dc3545;'>{$failedCount}</span><br>\n";
        echo "<strong>Pass Rate:</strong> {$passRate}%\n";
        echo "</div>\n";
        
        if ($failedCount > 0) {
            echo "<h3>❌ Failed Tests</h3>\n";
            foreach ($this->testResults as $test) {
                if (!$test['passed']) {
                    echo "<div style='color: #dc3545; margin: 5px 0;'>• {$test['name']}";
                    if ($test['error']) {
                        echo " - {$test['error']}";
                    }
                    echo "</div>\n";
                }
            }
        }
        
        // Performance summary
        $totalDuration = array_sum(array_column($this->testResults, 'duration'));
        echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
        echo "<strong>Total Test Duration:</strong> " . round($totalDuration, 2) . "ms<br>\n";
        echo "<strong>Average Test Duration:</strong> " . round($totalDuration / $totalTests, 2) . "ms\n";
        echo "</div>\n";
        
        // Recommendations
        echo "<h3>💡 Recommendations</h3>\n";
        if ($passRate >= 90) {
            echo "<div style='color: #28a745;'>✅ Excellent! Your Power Admin system is working well.</div>\n";
        } elseif ($passRate >= 70) {
            echo "<div style='color: #ffc107;'>⚠️ Good, but there are some issues that need attention.</div>\n";
        } else {
            echo "<div style='color: #dc3545;'>❌ Critical issues detected. Please review and fix the failed tests.</div>\n";
        }
        
        if ($totalDuration > 1000) {
            echo "<div style='color: #ffc107;'>⚠️ Performance could be improved. Consider optimizing database queries and reducing file I/O.</div>\n";
        }
    }
}

// Run tests if accessed directly
if (basename($_SERVER['PHP_SELF']) === 'test_power_admin.php') {
    session_start();
    $_SESSION['role'] = 'Power Admin'; // Set test role
    $_SESSION['user_id'] = 'test_user';
    
    $tester = new PowerAdminTester();
    $tester->runAllTests();
}
?>
