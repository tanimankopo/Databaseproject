<?php
session_start();

// ✅ Protect page
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

require 'db.php';

// ✅ Fetch dynamic stats for the about page
$userCount = $conn->query("SELECT COUNT(*) as count FROM usermanagement")->fetch_assoc()['count'];
$productCount = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
$supplierCount = $conn->query("SELECT COUNT(*) as count FROM supplier")->fetch_assoc()['count'];
$clientCount = $conn->query("SELECT COUNT(*) as count FROM clientinfo")->fetch_assoc()['count'];
$salesCount = $conn->query("SELECT COUNT(*) as count FROM sales")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - 1-GARAGE</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #1a1d29;
            color: #e4e7eb;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 220px;
            background: #252836;
            padding: 20px 0;
            display: flex;
            flex-direction: column;
        }

        .logo {
            padding: 0 20px 30px;
            font-size: 20px;
            font-weight: bold;
            color: #fff;
        }

        .nav-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-links li {
            margin: 0;
        }

        .nav-links a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: #b0b3ba;
            text-decoration: none;
            transition: all 0.3s;
        }

        .nav-links a:hover {
            background: #2d303e;
            color: #fff;
        }

        .nav-links a.active {
            background: #3b3f51;
            color: #fff;
            border-left: 3px solid #5c9eff;
        }

        .sidebar-footer {
            margin-top: auto;
            padding: 20px;
        }

        .sidebar-footer button {
            width: 100%;
            padding: 12px;
            background: #ff6b6b;
            border: none;
            border-radius: 8px;
            color: #fff;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }

        .sidebar-footer button:hover {
            background: #ff5252;
        }

        .main-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }

        .header {
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 32px;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
        }

        .header p {
            color: #b0b3ba;
            font-size: 16px;
        }

        .content-section {
            background: #252836;
            padding: 30px;
            border-radius: 12px;
            border: 1px solid #2d303e;
            margin-bottom: 20px;
        }

        .content-section h2 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .content-section p {
            line-height: 1.8;
            color: #b0b3ba;
            margin-bottom: 15px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 20px;
        }

        @media (max-width: 1200px) {
            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .features-grid {
                grid-template-columns: 1fr;
            }
        }

        .feature-card {
            background: #2d303e;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #3b3f51;
            transition: all 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-4px);
            border-color: #5c9eff;
        }

        .feature-icon {
            font-size: 32px;
            margin-bottom: 12px;
        }

        .feature-title {
            font-size: 18px;
            font-weight: 600;
            color: #fff;
            margin-bottom: 8px;
        }

        .feature-desc {
            font-size: 14px;
            color: #b0b3ba;
            line-height: 1.6;
        }

        .benefits-list {
            list-style: none;
            margin-top: 15px;
        }

        .benefits-list li {
            padding: 12px 0;
            border-bottom: 1px solid #2d303e;
            color: #b0b3ba;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .benefits-list li:last-child {
            border-bottom: none;
        }

        .benefits-list li::before {
            content: "✓";
            display: flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            background: rgba(76, 175, 80, 0.2);
            color: #4caf50;
            border-radius: 50%;
            font-weight: bold;
            flex-shrink: 0;
        }

        .contact-info {
            background: #2d303e;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #3b3f51;
        }

        .contact-item:last-child {
            border-bottom: none;
        }

        .contact-icon {
            width: 45px;
            height: 45px;
            background: rgba(92, 158, 255, 0.1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .contact-details {
            flex: 1;
        }

        .contact-label {
            font-size: 12px;
            color: #b0b3ba;
            margin-bottom: 4px;
        }

        .contact-value {
            font-size: 15px;
            color: #fff;
            font-weight: 500;
        }

        .contact-value a {
            color: #5c9eff;
            text-decoration: none;
            transition: all 0.3s;
        }

        .contact-value a:hover {
            color: #4a8de8;
        }

        .system-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .info-card {
            background: #2d303e;
            padding: 15px;
            border-radius: 8px;
            border-left: 3px solid #5c9eff;
        }

        .info-label {
            font-size: 12px;
            color: #b0b3ba;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 18px;
            font-weight: 600;
            color: #fff;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .stat-card {
            background: #2d303e;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border: 1px solid #3b3f51;
        }

        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: #5c9eff;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 12px;
            color: #b0b3ba;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <?php include("sidebar-admin.php"); ?>

        <main class="main-content">
            <div class="header">
                <h1><span>ℹ️</span> About Inventory System</h1>
                <p>Comprehensive inventory management solution for automotive businesses</p>
            </div>

            <div class="content-section">
                <h2>📋 Introduction</h2>
                <p>
                    Welcome to the 1-GARAGE Inventory Management System, a comprehensive tool designed to streamline inventory operations for automotive businesses. This system helps track products, manage suppliers, handle clients, and monitor sales efficiently.
                </p>
                <p>
                    Built with modern web technologies and a focus on user experience, our system provides real-time insights into your business operations, helping you make informed decisions and maintain optimal stock levels.
                </p>
            </div>

            <div class="content-section">
                <h2>✨ Key Features</h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">👤</div>
                        <div class="feature-title">User Management</div>
                        <div class="feature-desc">Secure role-based access for admins, managers, and staff members with comprehensive permission controls.</div>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">📦</div>
                        <div class="feature-title">Product Tracking</div>
                        <div class="feature-desc">Monitor stock levels, add/update products, and set alerts for low inventory with real-time updates.</div>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">🏭</div>
                        <div class="feature-title">Supplier Management</div>
                        <div class="feature-desc">Maintain supplier details, track orders, and integrate seamlessly with product management.</div>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">👥</div>
                        <div class="feature-title">Client Management</div>
                        <div class="feature-desc">Store client information, track purchase history, and link directly to sales records.</div>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">💰</div>
                        <div class="feature-title">Sales Monitoring</div>
                        <div class="feature-desc">Track sales transactions, calculate totals, generate reports, and analyze performance metrics.</div>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">🔒</div>
                        <div class="feature-title">Security</div>
                        <div class="feature-desc">Includes password reset via email, session-based protection, and encrypted data handling.</div>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <h2>🎯 Benefits</h2>
                <p>
                    This system enhances efficiency by reducing manual errors, providing real-time insights, and ensuring secure data handling. With features like email notifications and user roles, it's built for scalability and ease of use.
                </p>
                <ul class="benefits-list">
                    <li>Reduce manual data entry errors and streamline operations</li>
                    <li>Real-time inventory tracking and low stock alerts</li>
                    <li>Comprehensive reporting and analytics dashboard</li>
                    <li>Secure user authentication and role-based access control</li>
                    <li>Easy-to-use interface with minimal training required</li>
                    <li>Scalable architecture to grow with your business</li>
                    <li>Automated email notifications for critical events</li>
                    <li>Mobile-responsive design for on-the-go access</li>
                </ul>
            </div>

            <div class="content-section">
                <h2>📊 System Statistics</h2>
                <p>Current system usage and data overview:</p>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?= $userCount ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?= $productCount ?></div>
                        <div class="stat-label">Products</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?= $supplierCount ?></div>
                        <div class="stat-label">Suppliers</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?= $clientCount ?></div>
                        <div class="stat-label">Clients</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?= $salesCount ?></div>
                        <div class="stat-label">Sales Records</div>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <h2>📞 Contact Us</h2>
                <p>
                    For support, inquiries, or feedback about the 1-GARAGE Inventory Management System, please reach out to us through any of the following channels:
                </p>
                
                <div class="contact-info">
                    <div class="contact-item">
                        <div class="contact-icon">📧</div>
                        <div class="contact-details">
                            <div class="contact-label">Email Support</div>
                            <div class="contact-value">
                                <a href="mailto:support@1garage.com">support@1garage.com</a>
                            </div>
                        </div>
                    </div>

                    <div class="contact-item">
                        <div class="contact-icon">🌐</div>
                        <div class="contact-details">
                            <div class="contact-label">Website</div>
                            <div class="contact-value">
                                <a href="https://1garage.com" target="_blank">www.1garage.com</a>
                            </div>
                        </div>
                    </div>

                    <div class="contact-item">
                        <div class="contact-icon">📱</div>
                        <div class="contact-details">
                            <div class="contact-label">Phone Support</div>
                            <div class="contact-value">+63 917 123 4567</div>
                        </div>
                    </div>

                    <div class="contact-item">
                        <div class="contact-icon">📍</div>
                        <div class="contact-details">
                            <div class="contact-label">Location</div>
                            <div class="contact-value">Cainta, Calabarzon, Philippines</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <h2>💡 System Information</h2>
                <div class="system-info">
                    <div class="info-card">
                        <div class="info-label">Version</div>
                        <div class="info-value">1.0.0</div>
                    </div>
                    <div class="info-card">
                        <div class="info-label">Release Date</div>
                        <div class="info-value">October 2025</div>
                    </div>
                    <div class="info-card">
                        <div class="info-label">Platform</div>
                        <div class="info-value">Web-Based</div>
                    </div>
                    <div class="info-card">
                        <div class="info-label">Status</div>
                        <div class="info-value" style="color: #4caf50;">Active</div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>