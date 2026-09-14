<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== "student") {
    header("Location: index.php");
    exit;
}

include "db.php";
$student_id = $_SESSION['student_id'];
$today = date('Y-m-d');

$stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

$stmt = $conn->prepare("
    SELECT r.office_name, 
           IFNULL(cs.status, 'Unprocessed') AS status, 
           IFNULL(cs.remarks, 'Pending clearance audit verification check.') AS remarks, 
           cs.due_date, 
           IFNULL(cs.signed, 0) AS signed, 
           cs.signed_date,
           cs.signed_by
    FROM clearance_status cs
    RIGHT JOIN requirements r ON cs.requirement_id = r.requirement_id AND cs.student_id = ?
    ORDER BY r.requirement_id
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$records = $stmt->get_result();

$cleared = 0; $pending = 0; $flagged = 0; $signedCount = 0; $rows = [];
while ($row = $records->fetch_assoc()) {
    $rows[] = $row;
    if ($row['status'] === "Cleared") $cleared++;
    elseif ($row['status'] === "Pending") $pending++;
    elseif ($row['status'] === "Flagged") $flagged++;
    if ($row['signed']) $signedCount++;
}
$total = count($rows);
$percent = $total > 0 ? round($cleared / $total * 100) : 0;
$allSigned = ($total > 0 && $signedCount === $total);

function determineYearLevel($student_no, $type) {
    if ($type === 'Senior High') {
        return "Grade 12"; 
    } else {
        return "4th Year"; 
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Clearance</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="topbar">
    <div class="brand">
      <div class="seal">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 2L2 7l10 5 10-5-10-5z"/>
          <path d="M7 11.5V16c0 1.5 2.2 2.5 5 2.5s5-1 5-2.5v-4.5"/>
        </svg>
      </div>
      <span class="wm">MMCL Clearance</span>
    </div>
    <div class="right">
      <div class="who"><?php echo htmlspecialchars($student['first_name'] . " " . $student['last_name']); ?>
        <small>Student &middot; <?php echo htmlspecialchars($student['student_no']); ?></small></div>
      <a class="btn-out" href="logout.php">Sign out</a>
    </div>
  </div>

  <div class="wrap">
    <div class="eyebrow">My Clearance</div>
    <h1 class="page-h serif">Graduation Clearance</h1>
    <p class="page-sub">Second Semester, A.Y. 2025–2026</p>

    <?php if ($allSigned) { ?>
    <div class="complete-banner">
      <div class="cb-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg></div>
      <div>
        <div class="cb-title">You are fully cleared for graduation</div>
        <div class="cb-sub">All offices have signed your clearance.</div>
      </div>
    </div>
    <?php } ?>

    <div class="card">
      <p style="margin:0 0 2px; font-size:18px; font-weight:600;">
        <?php echo htmlspecialchars($student['last_name'] . ", " . $student['first_name']); ?>
      </p>
      <p class="muted" style="margin:0; font-size:13.5px;">
        <?php echo htmlspecialchars($student['student_no']); ?> &middot; 
        <?php echo htmlspecialchars($student['course']); ?> &middot; 
        <?php echo htmlspecialchars($student['student_type']); ?>
      </p>
      <div style="margin-top:18px;">
        <div style="font-size:14px; font-weight:600; margin-bottom:2px;">
          <?php echo "$cleared of $total"; ?> requirements cleared
        </div>
        <div class="progress-track"><div class="progress-fill" style="width: <?php echo $percent; ?>%;"></div></div>
        <div class="counts">
          <span><span class="pill cleared">Cleared</span> &nbsp;<b><?php echo $cleared; ?></b></span>
          <span><span class="pill pending">Pending</span> &nbsp;<b><?php echo $pending; ?></b></span>
          <span><span class="pill flagged">Flagged</span> &nbsp;<b><?php echo $flagged; ?></b></span>
        </div>
      </div>
    </div>

    <div class="card" style="padding:0; overflow:hidden;">
      <table class="data">
        <tr>
          <th>Office</th> 
          <th>Remarks</th> 
          <th>Status</th>
          <th>Signed</th>
          <th>Due Date</th>
        </tr>
        <?php 
        foreach ($rows as $row) { 
            $overdue = ($row['due_date'] && $row['due_date'] < $today && $row['status'] !== 'Cleared' && $row['status'] !== 'Unprocessed');
        ?>
        <tr>
          <td style="font-weight:600;"><?php echo htmlspecialchars($row['office_name']); ?></td>
          <td class="muted" style="font-style: italic;"><?php echo htmlspecialchars($row['remarks']); ?></td>
          <td><span class="pill <?php echo strtolower($row['status']); ?>"><?php echo $row['status']; ?></span></td>
          <td>
            <?php if ($row['signed']) { ?>
              <span class="signed" style="color: var(--cleared); font-weight: 600;">
                &#10003; Signed on <?php echo date('M j, Y', strtotime($row['signed_date'])); ?>
              </span>
              <?php if (!empty($row['signed_by'])) { ?>
                <br><small style="color: var(--muted); font-size: 11px;">By: <?php echo htmlspecialchars($row['signed_by']); ?></small>
              <?php } ?>
            <?php } else { ?>
              <span class="muted">—</span>
            <?php } ?>
          </td>
          <td class="muted" style="<?php echo $overdue ? 'color:var(--flagged);font-weight:600;' : ''; ?>">
            <?php echo $row['due_date'] ? $row['due_date'] : "—"; ?>
          </td>
        </tr>
        <?php } ?>
      </table>
    </div>

    <div class="student-note" style="margin-top: 20px; font-size: 13px; color: var(--muted);">
      Need to visit an office or have a concern about a requirement? Find the campus offices and directions here:
      <a href="https://mcl.edu.ph/maps-and-directories/" target="_blank" rel="noopener">MCL Maps &amp; Directories</a>.
    </div>
  </div>
</body>
</html>
