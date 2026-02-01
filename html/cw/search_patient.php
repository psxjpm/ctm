<?php
require_once 'config/db.inc.php';
require_once 'includes/auth_check.php';

$pdo = getDBConnection();
$results = [];
$searchTerm = '';

// 处理搜索请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['search'])) {
    $searchTerm = $_POST['search'] ?? $_GET['search'] ?? '';
    
    if (!empty($searchTerm)) {
        // 修复的查询：移除 AND pa.Status = 'admitted' 条件
        $stmt = $pdo->prepare("
            SELECT 
                p.*,
                pa.Date as admission_date,
                pa.Time as admission_time,
                pa.Room_No,
                pa.Bed_No,
                pa.Status as admission_status,
                pa.Discharge_date,
                w.Name as ward_name,
                w.Phone as ward_phone,
                d.FirstName as doctor_fname,
                d.LastName as doctor_lname,
                d.Specialisation as doctor_specialisation
            FROM PATIENT p
            LEFT JOIN PATIENT_ADMISSION pa ON p.Patient_id = pa.Patient_id
            LEFT JOIN WARD w ON pa.Ward_id = w.Ward_id
            LEFT JOIN DOCTOR d ON pa.Doctor_id = d.Doctor_id
            WHERE p.Name LIKE ? OR p.Patient_id LIKE ?
            ORDER BY pa.Date DESC, pa.Time DESC
        ");
        
        $likeTerm = "%$searchTerm%";
        $stmt->execute([$likeTerm, $likeTerm]);
        $results = $stmt->fetchAll();
        
        // 获取每个患者的测试信息
        foreach ($results as &$patient) {
            $testStmt = $pdo->prepare("
                SELECT t.*, pt.Test_time, pt.Result as test_result
                FROM TEST t
                JOIN PATIENT_TEST pt ON t.Test_id = pt.Test_id
                WHERE pt.Patient_id = ?
                ORDER BY pt.Test_time DESC
            ");
            $testStmt->execute([$patient['Patient_id']]);
            $patient['tests'] = $testStmt->fetchAll();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Patients - QMC</title>
    <link rel="stylesheet" href="/cw/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <h1>Patient Search</h1>
        
        <!-- 搜索表单 -->
        <form method="POST" class="search-form">
            <input type="text" name="search" 
                   placeholder="Enter patient name or NHS number" 
                   value="<?php echo htmlspecialchars($searchTerm); ?>">
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
        
        <?php if (!empty($searchTerm)): ?>
            <?php if (empty($results)): ?>
                <!-- 无结果消息 -->
                <div class="message error">
                    No patients found matching "<?php echo htmlspecialchars($searchTerm); ?>"
                </div>
            <?php else: ?>
                <!-- 结果计数 -->
                <div class="results-count">
                    Found <?php echo count($results); ?> patient(s)
                </div>
                
                <!-- 患者结果列表 -->
                <?php foreach ($results as $patient): ?>
                <div class="card">
                    <h2><?php echo htmlspecialchars($patient['Name']); ?></h2>
                    <div class="details-grid">
                        <div class="detail-box">
                            <span class="detail-label">NHS Number</span>
                            <span class="detail-value"><?php echo htmlspecialchars($patient['Patient_id']); ?></span>
                        </div>
                        <div class="detail-box">
                            <span class="detail-label">Phone</span>
                            <span class="detail-value"><?php echo htmlspecialchars($patient['PrimaryPhone'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="detail-box">
                            <span class="detail-label">Date of Birth</span>
                            <span class="detail-value"><?php echo !empty($patient['Date_of_birth']) ? htmlspecialchars($patient['Date_of_birth']) : 'N/A'; ?></span>
                        </div>
                        <div class="detail-box">
                            <span class="detail-label">Gender</span>
                            <span class="detail-value"><?php echo htmlspecialchars($patient['Gender'] ?? 'N/A'); ?></span>
                        </div>
                    </div>
                    
                    <!-- 入院信息 -->
                    <?php if ($patient['admission_date']): ?>
                        <div class="card mt-15">
                            <h3>Admission Details</h3>
                            <div class="details-grid">
                                <div class="detail-box">
                                    <span class="detail-label">Ward</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($patient['ward_name'] ?? 'N/A'); ?></span>
                                </div>
                                <div class="detail-box">
                                    <span class="detail-label">Status</span>
                                    <span class="detail-value status-<?php echo $patient['admission_status'] ?? 'none'; ?>">
                                        <?php echo ucfirst($patient['admission_status'] ?? 'Not admitted'); ?>
                                    </span>
                                </div>
                                <div class="detail-box">
                                    <span class="detail-label">Admission Date</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($patient['admission_date']); ?></span>
                                </div>
                                <div class="detail-box">
                                    <span class="detail-label">Admission Time</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($patient['admission_time']); ?></span>
                                </div>
                                <?php if ($patient['Room_No']): ?>
                                    <div class="detail-box">
                                        <span class="detail-label">Room Number</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($patient['Room_No']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($patient['Bed_No']): ?>
                                    <div class="detail-box">
                                        <span class="detail-label">Bed Number</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($patient['Bed_No']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($patient['admission_status'] === 'discharged' && $patient['Discharge_date']): ?>
                                    <div class="detail-box">
                                        <span class="detail-label">Discharge Date</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($patient['Discharge_date']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="detail-box">
                                    <span class="detail-label">Doctor</span>
                                    <span class="detail-value"><?php echo htmlspecialchars(($patient['doctor_fname'] ?? '') . ' ' . ($patient['doctor_lname'] ?? '')); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="empty-message">No admission records found</p>
                    <?php endif; ?>
                    
                    <!-- 测试信息 -->
                    <h3 class="mt-15">Tests Performed</h3>
                    <?php if (!empty($patient['tests'])): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Test Name</th>
                                    <th>Category</th>
                                    <th>Result</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($patient['tests'] as $test): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($test['Name']); ?></td>
                                    <td><?php echo htmlspecialchars($test['Category'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($test['test_result'] ?? 'Pending'); ?></td>
                                    <td><?php echo htmlspecialchars($test['Test_time']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="empty-message">No tests performed</p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>