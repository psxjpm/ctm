<?php
require_once 'config/db.inc.php';
require_once 'includes/auth_check.php';

$pdo = getDBConnection();
$message = '';

// 获取当前医生的ID
$doctor_id = $_SESSION['doctor_id'] ?? null;

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        $patientId = null;
        
        // 情况1：搜索现有患者
        if (!empty($_POST['search_nhs'])) {
            $stmt = $pdo->prepare("SELECT Patient_id FROM PATIENT WHERE Patient_id = ?");
            $stmt->execute([$_POST['search_nhs']]);
            $patient = $stmt->fetch();
            
            if ($patient) {
                $patientId = $patient['Patient_id'];
            } else {
                throw new Exception("Patient with NHS number {$_POST['search_nhs']} not found.");
            }
        }
        // 情况2：添加新患者
        elseif (!empty($_POST['new_nhs'])) {
            // 检查患者是否已存在
            $stmt = $pdo->prepare("SELECT Patient_id FROM PATIENT WHERE Patient_id = ?");
            $stmt->execute([$_POST['new_nhs']]);
            if ($stmt->fetch()) {
                throw new Exception("Patient with NHS number {$_POST['new_nhs']} already exists.");
            }
            
            // 插入新患者
            $stmt = $pdo->prepare("
                INSERT INTO PATIENT (
                    Patient_id, Name, PrimaryPhone, EmergencyPhone, 
                    Gender, Address_street, Address_city, Address_code, Date_of_birth
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            // 设置默认值
            $gender = $_POST['new_gender'] ?? 'Other';
            $dob = !empty($_POST['new_dob']) ? $_POST['new_dob'] : date('Y-m-d');
            
            $stmt->execute([
                $_POST['new_nhs'],
                $_POST['new_name'] ?? 'Unknown',
                $_POST['new_phone'] ?? '',
                $_POST['new_emergency_phone'] ?? '',
                $gender,
                $_POST['new_address'] ?? '',
                $_POST['new_city'] ?? 'Nottingham',
                $_POST['new_postcode'] ?? '',
                $dob
            ]);
            
            $patientId = $_POST['new_nhs'];
        } else {
            throw new Exception("Please either search for an existing patient or add a new one.");
        }
        
        if (!$patientId) {
            throw new Exception("No patient selected or created.");
        }
        
        // 添加新测试到TEST表
        $stmt = $pdo->prepare("
            INSERT INTO TEST (Name, Category, Description)
            VALUES (?, ?, ?)
        ");
        
        $stmt->execute([
            $_POST['test_name'],
            $_POST['test_category'],
            $_POST['test_description'] ?? ''
        ]);
        
        $testId = $pdo->lastInsertId();
        
        // 关联测试与患者到PATIENT_TEST表
        $stmt = $pdo->prepare("
            INSERT INTO PATIENT_TEST (Test_id, Patient_id, Prescribed_by, Result)
            VALUES (?, ?, ?, ?)
        ");
        
        $result = $_POST['test_result'] ?? 'Pending';
        $stmt->execute([
            $testId,
            $patientId,
            $doctor_id,
            $result
        ]);
        
        $pdo->commit();
        
        // 记录审计日志
        logAudit('CREATE', 'TEST', $testId, null, json_encode([
            'test_name' => $_POST['test_name'],
            'patient_id' => $patientId,
            'doctor_id' => $doctor_id
        ]));
        
        // 获取患者姓名用于显示
        $stmt = $pdo->prepare("SELECT Name FROM PATIENT WHERE Patient_id = ?");
        $stmt->execute([$patientId]);
        $patient = $stmt->fetch();
        $patientName = $patient['Name'] ?? $patientId;
        
        $message = '<div class="message success">' .
                   'Test added successfully!<br>' .
                   '<strong>Test ID:</strong> ' . $testId . '<br>' .
                   '<strong>Test Name:</strong> ' . htmlspecialchars($_POST['test_name']) . '<br>' .
                   '<strong>Patient:</strong> ' . htmlspecialchars($patientName) . ' (NHS: ' . $patientId . ')<br>' .
                   '<strong>Prescribed by:</strong> Dr. ' . htmlspecialchars($_SESSION['doctor_name'] ?? 'Unknown') .
                   '</div>';
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = '<div class="message error">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Test - QMC Hospital</title>
    <link rel="stylesheet" href="/cw/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <h1>Add New Test</h1>
        
        <?php echo $message; ?>
        
        <div class="card">
            <!-- 标签页导航 -->
            <div class="form-tabs">
                <button type="button" class="tab-button" onclick="switchTab(1)">Search Existing Patient</button>
                <button type="button" class="tab-button active" onclick="switchTab(2)">Add New Patient</button>
            </div>
            
            <form method="POST" id="testForm">
                <!-- 标签页1：搜索现有患者 -->
                <div class="tab-content" id="tab1">
                    <div class="form-group">
                        <label>Search by NHS Number:</label>
                        <input type="text" id="searchNhs" 
                               placeholder="Enter NHS number">
                        <button type="button" class="btn btn-secondary btn-small mt-15" onclick="searchPatient()">
                            Search Patient
                        </button>
                    </div>
                    
                    <div id="searchResults" class="patient-search-results hidden">
                        <!-- 搜索结果将显示在这里 -->
                    </div>
                    
                    <input type="hidden" name="search_nhs" id="selectedNhs">
                </div>
                
                <!-- 标签页2：添加新患者 -->
                <div class="tab-content active" id="tab2">
                    <div class="form-section">
                        <h3>Personal Details</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label>NHS Number</label>
                                <input type="text" name="new_nhs" 
                                       placeholder="Any NHS number format">
                            </div>
                            <div class="form-group">
                                <label>Full Name</label>
                                <input type="text" name="new_name" 
                                       placeholder="Patient's full name">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Date of Birth</label>
                                <input type="date" name="new_dob">
                            </div>
                            <div class="form-group">
                                <label>Gender</label>
                                <select name="new_gender">
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3>Contact Information</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Primary Phone</label>
                                <input type="tel" name="new_phone" 
                                       placeholder="Any phone format">
                            </div>
                            <div class="form-group">
                                <label>Emergency Phone</label>
                                <input type="tel" name="new_emergency_phone" 
                                       placeholder="Any phone format">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Street Address</label>
                            <textarea name="new_address" rows="2" 
                                      placeholder="Address in any format"></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>City</label>
                                <input type="text" name="new_city" 
                                       placeholder="City">
                            </div>
                            <div class="form-group">
                                <label>Postcode</label>
                                <input type="text" name="new_postcode" 
                                       placeholder="Any postcode format">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 测试信息部分 -->
                <div class="form-section">
                    <h3>Test Details</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Test Name</label>
                            <input type="text" name="test_name"
                                   placeholder="Test name">
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <select name="test_category">
                                <option value="">Select Category</option>
                                <option value="Blood Test">Blood Test</option>
                                <option value="Urine Test">Urine Test</option>
                                <option value="MRI">MRI Scan</option>
                                <option value="X-Ray">X-Ray</option>
                                <option value="CT Scan">CT Scan</option>
                                <option value="ECG">ECG</option>
                                <option value="Ultrasound">Ultrasound</option>
                                <option value="Biopsy">Biopsy</option>
                                <option value="Endoscopy">Endoscopy</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Test Description</label>
                        <textarea name="test_description" rows="3" 
                                  placeholder="Describe the test procedure or requirements..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Test Result (if known)</label>
                        <textarea name="test_result" rows="4" 
                                  placeholder="Enter test results if available, otherwise leave blank for 'Pending'..."></textarea>
                    </div>
                </div>
                
                <div class="text-center mt-30">
                    <button type="submit" class="btn btn-primary btn-medium">
                        Add Test to Patient
                    </button>
                    <a href="/cw/doctor_dashboard.php" class="btn btn-secondary btn-medium">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        // 标签页切换
        function switchTab(tabNumber) {
            // 更新标签按钮
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // 更新标签内容
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById('tab' + tabNumber).classList.add('active');
            
            // 重置搜索选择
            if (tabNumber === 1) {
                resetSearchSelection();
            }
        }
        
        // 搜索患者
        function searchPatient() {
            const nhsNumber = document.getElementById('searchNhs').value.trim();
            
            if (!nhsNumber) {
                alert('Please enter an NHS number to search.');
                return;
            }
            
            const resultsDiv = document.getElementById('searchResults');
            
            // 模拟搜索结果
            resultsDiv.innerHTML = `
                <div class="patient-option" onclick="selectPatient('${nhsNumber}', 'Patient Found')">
                    <strong>NHS: ${nhsNumber}</strong><br>
                    <small>Click to select this patient</small>
                </div>
                <div class="patient-option" onclick="showNoPatientMessage()">
                    <em>Patient not found? Switch to "Add New Patient" tab</em>
                </div>
            `;
            resultsDiv.classList.remove('hidden');
        }
        
        // 选择患者
        function selectPatient(nhsNumber, patientName) {
            document.getElementById('selectedNhs').value = nhsNumber;
            
            // 高亮选择
            document.querySelectorAll('.patient-option').forEach(option => {
                option.classList.remove('selected');
            });
            event.target.classList.add('selected');
            
            // 显示确认信息
            document.getElementById('searchResults').innerHTML = `
                <div class="patient-option selected">
                    <strong>Selected Patient:</strong><br>
                    NHS: ${nhsNumber}<br>
                    Name: ${patientName}
                </div>
            `;
        }
        
        // 显示未找到患者消息
        function showNoPatientMessage() {
            alert('Patient not found. Please switch to "Add New Patient" tab to add a new patient.');
            switchTab(2);
        }
        
        // 重置搜索选择
        function resetSearchSelection() {
            document.getElementById('selectedNhs').value = '';
            document.getElementById('searchNhs').value = '';
            document.getElementById('searchResults').classList.add('hidden');
        }
        
        // 表单验证
        document.getElementById('testForm').addEventListener('submit', function(e) {
            const activeTab = document.querySelector('.tab-content.active').id;
            let isValid = true;
            let errorMessage = '';
            
            if (activeTab === 'tab1') {
                const selectedNhs = document.getElementById('selectedNhs').value;
                if (!selectedNhs) {
                    errorMessage = 'Please select a patient from the search results.';
                    isValid = false;
                }
            } else if (activeTab === 'tab2') {
                const nhs = document.querySelector('[name="new_nhs"]').value;
                const name = document.querySelector('[name="new_name"]').value;
                
                if (!nhs || !name) {
                    errorMessage = 'Please fill NHS number and patient name.';
                    isValid = false;
                }
            }
            
            // 验证测试信息
            const testName = document.querySelector('[name="test_name"]').value;
            const testCategory = document.querySelector('[name="test_category"]').value;
            
            if (!testName || !testCategory) {
                errorMessage = errorMessage || 'Please fill all required test details.';
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                alert('Error: ' + errorMessage);
            }
        });
    </script>
</body>
</html>