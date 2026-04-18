<?php
session_start();
include '../frontend/includes/config.php';

if (!isset($_SESSION['user_id'])) {
    echo "<div class='alert alert-danger m-3'>Login required</div>";
    exit();
}

$user_id = (int)$_SESSION['user_id'];

$result = $conn->query("
    SELECT *
    FROM demand_listings
    WHERE user_id = $user_id
    ORDER BY id DESC
");

if (!$result) {
    echo "<div class='alert alert-danger m-3'>Query Error: " . htmlspecialchars($conn->error) . "</div>";
    exit();
}

echo "<table class='table table-hover table-bordered mb-0'>";
echo "<thead>
        <tr>
            <th>Date</th>
            <th>Time Slot</th>
            <th>Units Required</th>
            <th>Remaining</th>
            <th>Max Price</th>
            <th>Status</th>
        </tr>
      </thead>";
echo "<tbody>";

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $date = !empty($row['date']) ? date('d-m-Y', strtotime($row['date'])) : 'N/A';
        $time = !empty($row['time_block']) ? $row['time_block'] : 'N/A';
        $units = number_format((float)$row['units_required'], 0);
        $remaining = number_format((float)$row['remaining_units'], 0);
        $price = number_format((float)$row['max_price'], 1);
        $status = ucfirst($row['status']);
        
        $statusBadge = '';
        if ($row['status'] == 'active') {
            $statusBadge = "<span class='badge bg-success'>Active</span>";
        } else if ($row['status'] == 'matched') {
            $statusBadge = "<span class='badge bg-primary'>Matched</span>";
        } else {
            $statusBadge = "<span class='badge bg-secondary'>$status</span>";
        }

        echo "<tr>
                <td>$date</td>
                <td>$time</td>
                <td>$units kWh</td>
                <td>$remaining kWh</td>
                <td>₹$price</td>
                <td>$statusBadge</td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='6' class='text-center text-muted py-4'>
            <i class='bi bi-inbox' style='font-size: 48px; opacity: 0.3;'></i>
            <p class='mt-2'>No demands found</p>
          </td></tr>";
}

echo "</tbody>";
echo "</table>";
?>