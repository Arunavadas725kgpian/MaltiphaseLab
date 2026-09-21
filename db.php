<?php
// Database Configuration
$host     = "localhost";
$username = "root";
$password = ""; // XAMPP-এ বাই-ডিফল্ট পাসওয়ার্ড ফাঁকা থাকে
$database = "Multiphase_lab_db";

// কানেকশন তৈরি
$conn = new mysqli($host, $username, $password, $database);

// কানেকশন চেক করা
if ($conn->connect_error) {
    die("<div style='color:red; font-family:sans-serif; padding:20px;'>Database Connection Failed: " . $conn->connect_error . "</div>");
}

// ইউনিকোড এনকোডিং সেট করা
$conn->set_charset("utf8mb4");
?>