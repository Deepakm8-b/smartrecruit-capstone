<?php
/**
 * Smart Recruit - Authentication Portal (Login / Register)
 */
require_once __DIR__ . '/includes/auth.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    $user = getCurrentUser();
    if ($user) {
        if ($user['role'] === 'student') header("Location: student/dashboard.php");
        elseif ($user['role'] === 'organisation') header("Location: org/dashboard.php");
        elseif ($user['role'] === 'admin') header("Location: admin/dashboard.php");
        exit;
    }
}

$errorMsg = '';
$successMsg = '';

// Handle POST submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // LOGIN ACTION
    if (isset($_POST['action']) && $_POST['action'] === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $errorMsg = 'Please enter both email and password.';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Check if user is verified
                if (!$user['is_verified']) {
                    $errorMsg = 'Your account has not been verified yet. Please contact the administrator.';
                } else {
                    // Set session variables
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['email'] = $user['email'];
                    
                    // Route to correct dashboard
                    if ($user['role'] === 'student') {
                        header("Location: student/dashboard.php");
                    } elseif ($user['role'] === 'organisation') {
                        header("Location: org/dashboard.php");
                    } elseif ($user['role'] === 'admin') {
                        header("Location: admin/dashboard.php");
                    }
                    exit;
                }
            } else {
                $errorMsg = 'Invalid email or password.';
            }
        }
    }
    
    // REGISTER ACTION
    elseif (isset($_POST['action']) && $_POST['action'] === 'register') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'student';
        
        // Common validations
        if (empty($email) || empty($password)) {
            $errorMsg = 'Please enter email and password.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMsg = 'Please enter a valid email address.';
        } else {
            // Check email uniqueness
            $stmtEmail = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmtEmail->execute([$email]);
            if ($stmtEmail->fetch()) {
                $errorMsg = 'This email address is already registered.';
            } else {
                // Process Role-Specific Validations
                $isValid = true;
                
                if ($role === 'student') {
                    $fullName = trim($_POST['full_name'] ?? '');
                    $university = trim($_POST['university'] ?? '');
                    $degree = trim($_POST['degree'] ?? '');
                    $gpa = floatval($_POST['gpa'] ?? 0);
                    
                    if (empty($fullName) || empty($university) || empty($degree) || $gpa <= 0) {
                        $errorMsg = 'Please fill out all student profile fields and ensure GPA is valid.';
                        $isValid = false;
                    }
                } elseif ($role === 'organisation') {
                    $orgName = trim($_POST['org_name'] ?? '');
                    $abn = trim($_POST['abn'] ?? '');
                    $industry = trim($_POST['industry'] ?? '');
                    $location = trim($_POST['location'] ?? '');
                    
                    if (empty($orgName) || empty($abn) || empty($industry) || empty($location)) {
                        $errorMsg = 'Please fill out all organization profile fields.';
                        $isValid = false;
                    } else {
                        // Check ABN uniqueness
                        $stmtAbn = $pdo->prepare("SELECT org_id FROM organisations WHERE abn = ?");
                        $stmtAbn->execute([$abn]);
                        if ($stmtAbn->fetch()) {
                            $errorMsg = 'This ABN is already registered.';
                            $isValid = false;
                        }
                    }
                } else {
                    $errorMsg = 'Invalid registration role.';
                    $isValid = false;
                }
                
                if ($isValid) {
                    try {
                        $pdo->beginTransaction();
                        
                        // 1. Insert into users (by default, new users are verified for this prototype setup)
                        $hashedPass = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
                        $stmtInsUser = $pdo->prepare("INSERT INTO users (email, password, role, is_verified) VALUES (?, ?, ?, 1)");
                        $stmtInsUser->execute([$email, $hashedPass, $role]);
                        $newUserId = $pdo->lastInsertId();
                        
                        // 2. Insert into profiles
                        if ($role === 'student') {
                            $stmtInsStudent = $pdo->prepare("
                                INSERT INTO students (user_id, full_name, university, degree, gpa, profile_score, is_premium)
                                VALUES (?, ?, ?, ?, ?, 60, 0)
                            ");
                            $stmtInsStudent->execute([$newUserId, $fullName, $university, $degree, $gpa]);
                            
                            // Seed default student skills for a basic profile
                            $stmtGetSkills = $pdo->query("SELECT skill_id FROM skills LIMIT 3");
                            $skills = $stmtGetSkills->fetchAll(PDO::FETCH_COLUMN);
                            $stmtInsStudSkill = $pdo->prepare("INSERT INTO student_skills (student_id, skill_id, proficiency, verified) VALUES (?, ?, 'Beginner', 0)");
                            
                            $studentId = $pdo->lastInsertId();
                            foreach ($skills as $sid) {
                                $stmtInsStudSkill->execute([$studentId, $sid]);
                            }
                            
                        } elseif ($role === 'organisation') {
                            $stmtInsOrg = $pdo->prepare("
                                INSERT INTO organisations (user_id, org_name, abn, industry, location, is_partner)
                                VALUES (?, ?, ?, ?, ?, 0)
                            ");
                            $stmtInsOrg->execute([$newUserId, $orgName, $abn, $industry, $location]);
                        }
                        
                        $pdo->commit();
                        $successMsg = 'Registration completed successfully! You can now log in.';
                        
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $errorMsg = 'Error during registration: ' . $e->getMessage();
                    }
                }
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="login-container">
  <!-- Left Panel: Branding and Summary -->
  <div class="login-left-panel">
    <h2 class="login-left-title"><i class="fa-solid fa-graduation-cap"></i> Smart Recruit</h2>
    <p>Connecting elite Australian graduates with high-impact industry firms directly.</p>
    
    <div class="login-feature-list">
      <div class="login-feature-item">
        <i class="fa-solid fa-circle-check"></i>
        <div>
          <strong>Unified Skill Engine</strong>
          <p style="font-size: 0.85rem; opacity: 0.85;">No more keyword clutter. Verify profile skills and check real-time job match percentages.</p>
        </div>
      </div>
      
      <div class="login-feature-item">
        <i class="fa-solid fa-circle-check"></i>
        <div>
          <strong>Interactive Career Maps</strong>
          <p style="font-size: 0.85rem; opacity: 0.85;">Premium users visualize structured milestones and check required skill gap guidelines.</p>
        </div>
      </div>
      
      <div class="login-feature-item">
        <i class="fa-solid fa-circle-check"></i>
        <div>
          <strong>Direct Recruiter Access</strong>
          <p style="font-size: 0.85rem; opacity: 0.85;">Eliminate intermediary recruitment agencies with direct referral channels and partner networks.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Right Panel: Login / Register Form -->
  <div class="login-right-panel">
    
    <!-- Tab Switchers -->
    <div class="login-tabs">
      <div class="login-tab active" data-tab="form-login">Sign In</div>
      <div class="login-tab" data-tab="form-register">Create Account</div>
    </div>

    <!-- Alert Messages -->
    <?php if ($errorMsg): ?>
      <div class="alert alert-danger">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <div><?php echo htmlspecialchars($errorMsg); ?></div>
      </div>
    <?php endif; ?>
    
    <?php if ($successMsg): ?>
      <div class="alert alert-success">
        <i class="fa-solid fa-circle-check"></i>
        <div><?php echo htmlspecialchars($successMsg); ?></div>
      </div>
    <?php endif; ?>

    <!-- 1. LOGIN FORM -->
    <div id="form-login" class="login-form-container active">
      <form action="login.php" method="POST">
        <input type="hidden" name="action" value="login">
        
        <div class="form-group">
          <label for="login-email">Email Address</label>
          <input type="email" id="login-email" name="email" class="form-control" placeholder="enter your registered email" required>
        </div>
        
        <div class="form-group">
          <label for="login-password">Password</label>
          <input type="password" id="login-password" name="password" class="form-control" placeholder="••••••••" required>
        </div>
        
        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">
          <i class="fa-solid fa-right-to-bracket"></i> Sign In
        </button>
      </form>
    </div>

    <!-- 2. REGISTRATION FORM -->
    <div id="form-register" class="login-form-container">
      <form action="login.php" method="POST">
        <input type="hidden" name="action" value="register">
        
        <div class="form-group">
          <label for="reg-role">I want to register as a:</label>
          <select id="reg-role" name="role" class="form-control select-control">
            <option value="student">University Student</option>
            <option value="organisation">Hiring Organisation</option>
          </select>
        </div>
        
        <div class="form-group">
          <label for="reg-email">Email Address</label>
          <input type="email" id="reg-email" name="email" class="form-control" placeholder="e.g., student@university.edu.au" required>
        </div>
        
        <div class="form-group">
          <label for="reg-password">Password</label>
          <input type="password" id="reg-password" name="password" class="form-control" placeholder="Minimum 8 characters" required>
        </div>

        <!-- Student Dynamic Profile Fields -->
        <div id="fields-student" class="role-specific-fields">
          <h4 style="margin: 15px 0 10px 0; font-size: 0.95rem; border-bottom: 1px solid var(--border-color); padding-bottom: 5px;">Student Profile Details</h4>
          
          <div class="form-group">
            <label for="student-name">Full Name</label>
            <input type="text" id="student-name" name="full_name" class="form-control" placeholder="Deepak Bhandari">
          </div>
          
          <div class="form-group">
            <label for="student-uni">University</label>
            <input type="text" id="student-uni" name="university" class="form-control" placeholder="Excelsia University College">
          </div>
          
          <div class="form-group">
            <label for="student-degree">Degree Program</label>
            <input type="text" id="student-degree" name="degree" class="form-control" placeholder="Bachelor of Information Technology">
          </div>
          
          <div class="form-group">
            <label for="student-gpa">Current GPA (out of 4.0 or 7.0)</label>
            <input type="number" step="0.01" min="0" max="7" id="student-gpa" name="gpa" class="form-control" placeholder="3.80">
          </div>
        </div>

        <!-- Organisation Dynamic Profile Fields -->
        <div id="fields-org" class="role-specific-fields" style="display: none;">
          <h4 style="margin: 15px 0 10px 0; font-size: 0.95rem; border-bottom: 1px solid var(--border-color); padding-bottom: 5px;">Organisation Details</h4>
          
          <div class="form-group">
            <label for="org-name">Company Name</label>
            <input type="text" id="org-name" name="org_name" class="form-control" placeholder="Canva Pty Ltd">
          </div>
          
          <div class="form-group">
            <label for="org-abn">Australian Business Number (ABN)</label>
            <input type="text" id="org-abn" name="abn" class="form-control" placeholder="88 294 719 238">
          </div>
          
          <div class="form-group">
            <label for="org-industry">Industry Sector</label>
            <input type="text" id="org-industry" name="industry" class="form-control" placeholder="Design & Tech">
          </div>
          
          <div class="form-group">
            <label for="org-loc">Corporate Location</label>
            <input type="text" id="org-loc" name="location" class="form-control" placeholder="Sydney, NSW">
          </div>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 20px;">
          <i class="fa-solid fa-user-plus"></i> Complete Registration
        </button>
      </form>
    </div>
  </div>
</div>

<script>
// Dynamic Role field toggles on Register Form
document.addEventListener('DOMContentLoaded', function() {
    const roleSelect = document.getElementById('reg-role');
    const studFields = document.getElementById('fields-student');
    const orgFields = document.getElementById('fields-org');
    
    if (roleSelect) {
        roleSelect.addEventListener('change', function() {
            if (this.value === 'student') {
                studFields.style.display = 'block';
                orgFields.style.display = 'none';
                
                // Add required tags dynamically
                document.getElementById('student-name').required = true;
                document.getElementById('student-uni').required = true;
                document.getElementById('student-degree').required = true;
                document.getElementById('student-gpa').required = true;
                
                document.getElementById('org-name').required = false;
                document.getElementById('org-abn').required = false;
                document.getElementById('org-industry').required = false;
                document.getElementById('org-loc').required = false;
            } else {
                studFields.style.display = 'none';
                orgFields.style.display = 'block';
                
                // Add required tags dynamically
                document.getElementById('student-name').required = false;
                document.getElementById('student-uni').required = false;
                document.getElementById('student-degree').required = false;
                document.getElementById('student-gpa').required = false;
                
                document.getElementById('org-name').required = true;
                document.getElementById('org-abn').required = true;
                document.getElementById('org-industry').required = true;
                document.getElementById('org-loc').required = true;
            }
        });
        
        // Initial trigger
        roleSelect.dispatchEvent(new Event('change'));
    }
});
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
