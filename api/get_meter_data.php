<?php
include '../frontend/includes/config.php';
$user_id = $_SESSION['user_id'];

$result = $conn->query("
    SELECT m.*, t.token_id 
    FROM prosumer_meter_data m
    LEFT JOIN trades t ON m.date = t.date 
        AND m.time_block = t.time_block 
        AND t.seller_id = $user_id
    WHERE m.user_id = $user_id
    ORDER BY m.date DESC, m.time_block ASC
");

if ($result->num_rows == 0) {
    echo "<tr><td colspan='6' class='text-center py-4'>No meter data recorded yet.</td></tr>";
    exit;
}

while($row = $result->fetch_assoc()) {
    echo "<tr>
        <td>{$row['date']}</td>
        <td>{$row['time_block']}</td>
        <td><strong>{$row['generated_kwh']}</strong></td>
        <td>{$row['self_consumed_kwh']}</td>
        <td>{$row['sold_kwh']}</td>
        <td><span class='badge bg-primary'>". ($row['token_id'] ?? '—') ."</span></td>
    </tr>";
}
?>