<?php
session_start();
include "db.php";

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        
        // 1. FIRST CHECK: Is this a Staff Member email account?
        $stmt = $conn->prepare("SELECT * FROM requirements WHERE staff_email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $staff_res = $stmt->get_result();

        if ($staff_res->num_rows > 0) {
            $staff = $staff_res->fetch_assoc();
            
            $expected_staff_password = "admin";
            if (isset($staff['password']) && !empty($staff['password'])) {
                $expected_staff_password = $staff['password'];
            } elseif (isset($staff['staff_password']) && !empty($staff['staff_password'])) {
                $expected_staff_password = $staff['staff_password'];
            } else {
                if ($password === "admin" || $password === $staff['staff_email']) {
                    $expected_staff_password = $password;
                }
            }

            if ($password === $expected_staff_password) {
                $_SESSION['role'] = "staff";
                $_SESSION['staff_username'] = $staff['staff_email'];
                $_SESSION['office_name']    = $staff['office_name'];
                header("Location: staff.php");
                exit;
            }
        }

        // 2. SECOND CHECK: Is this a Student email account?
        $stmt = $conn->prepare("SELECT * FROM students WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $student_res = $stmt->get_result();

        if ($student_res->num_rows > 0) {
            $student = $student_res->fetch_assoc();

            if (empty($student['password'])) {
                $allowed_pass = $student['student_no'];
            } else {
                $allowed_pass = $student['password'];
            }

            if ($password === $allowed_pass) {
                $_SESSION['role'] = "student";
                $_SESSION['student_id']   = $student['student_id'];
                $_SESSION['student_no']   = $student['student_no'];
                $_SESSION['student_name'] = $student['first_name'] . " " . $student['last_name'];
                header("Location: student.php");
                exit;
            }
        }

        $error = "Invalid institutional email or password combination.";
    } else {
        $error = "Please fill out all credentials.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>MMCL Clearance Portal — Sign In</title>
  <link rel="stylesheet" href="style.css">
</head>
<body class="login-bg">

  <div class="login-card">
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:24px;">
       <div class="seal">
         <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
           <path d="M12 2L2 7l10 5 10-5-10-5z"/>
           <path d="M7 11.5V16c0 1.5 2.2 2.5 5 2.5s5-1 5-2.5v-4.5"/>
         </svg>
       </div>
       <span style="font-weight:700; font-size:17px; letter-spacing:-.01em; color: var(--mmcl-blue);">MMCL Clearance</span>
    </div>

    <h1>Sign in</h1>
    <div class="sub">Access your clearance records path metrics.</div>

    <?php if (!empty($error)) { echo "<div class='error'>$error</div>"; } ?>

    <form method="POST" action="index.php">
      <div class="field">
        <label>Institutional Email</label>
        <input type="text" name="email" placeholder="username@live.mcl.edu.ph" style="width:100%;" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required autocomplete="off">
      </div>

      <div class="field" style="margin-top:14px;">
        <label>Password</label>
        <input type="password" id="passInput" name="password" placeholder="••••••••" style="width:100%;" required>
      </div>

      <div style="display:flex; align-items:center; gap:8px; margin: 14px 0 24px; font-size:13.5px; color:var(--muted);">
         <input type="checkbox" id="showCheck" onchange="togglePasswordVisibility()">
         <label for="showCheck" style="font-weight:500; cursor:pointer; user-select:none;">Show password</label>
      </div>

      <button type="submit" class="btn">Sign In</button>
    </form>

    <div style="margin-top:24px; text-align:center; border-top:1px dashed var(--line); padding-top:16px;">
       <a href="#" class="muted" style="font-size:13px; font-weight:600; text-decoration:none; color: var(--mmcl-blue);" onclick="toggleHelpSection(event)">Have trouble logging in?</a>
       
       <div id="inlineHelp" class="help-accordion">
          <div style="font-weight: 700; color: var(--ink); margin-bottom: 4px;">Information Technology Services Office</div>
          <div>Second Floor, J.P. Rizal Building</div>
          <div style="color: var(--muted); margin-top: 2px;">(049) 832 &ndash; 4000, local 1200</div>
       </div>
    </div>
  </div>

  <script>
    function togglePasswordVisibility() {
        var pInput = document.getElementById('passInput');
        var check = document.getElementById('showCheck');
        if(check.checked) {
            pInput.type = "text";
        } else {
            pInput.type = "password";
        }
    }

    function toggleHelpSection(e) {
        e.preventDefault();
        var helpBox = document.getElementById('inlineHelp');
        helpBox.classList.toggle('open');
    }
  </script>
</body>
</html>
