<?php
$myPassword = "sghms_test_123";
// This creates a fresh hash specifically for this password
$generatedHash = password_hash($myPassword, PASSWORD_DEFAULT);

echo "<h3>Server Hash Compatibility Test</h3>";
echo "Password: " . $myPassword . "<br>";
echo "Generated Hash: " . $generatedHash . "<br>";
echo "Hash Length: " . strlen($generatedHash) . " characters.<br><br>";

if (password_verify($myPassword, $generatedHash)) {
    echo "<p style='color:green; font-weight:bold;'>✅ SUCCESS: Your server is hashing and verifying correctly!</p>";
} else {
    echo "<p style='color:red; font-weight:bold;'>❌ FAIL: Something is wrong with the PHP environment.</p>";
}
?>