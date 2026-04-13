<?php
session_start();
header('Content-Type: text/html; charset=utf-8');
include '../frontend/includes/config.php';

if (!isset($_SESSION['user_id'])) {
    echo "<tr><td colspan='7' class='text-center text-danger'>Please login first</td></tr>";
    exit;
}

$user_id = $_SESSION['user_id'];

$history = $conn->query("
    SELECT tl.*, 
           t.date,
           t.time_block,
           u.name AS other_user_name
    FROM token_ledger tl
    LEFT JOIN trades t ON tl.trade_id = t.id
    LEFT JOIN users u ON u.id = CASE 
        WHEN tl.from_user_id = $user_id THEN tl.to_user_id 
        ELSE tl.from_user_id 
    END
    WHERE tl.user_id = $user_id
    ORDER BY tl.created_at DESC
    LIMIT 50
");

$html = '';

while($row = $history->fetch_assoc()){
    $type = $row['token_type'];
    $units = $row['token_units'];
    $hash = $row['tx_hash'] ?? '—';
    $short_hash = ($hash !== '—' && strlen($hash) > 10) ? substr($hash, 0, 10) . '...' : $hash;
    
    // Format date - use created_at if date from trades is not available
    if (!empty($row['date'])) {
        $date = $row['date'];
    } elseif (!empty($row['created_at'])) {
        $date = date('Y-m-d', strtotime($row['created_at']));
    } else {
        $date = date('Y-m-d');
    }
    $formatted_date = date('d-m-Y', strtotime($date));
    
    // Time block
    $time_block = !empty($row['time_block']) ? $row['time_block'] : '—';

    $badge_class = [
        'mint' => 'success',
        'transfer_in' => 'primary',
        'transfer_out' => 'warning',
        'burn' => 'danger'
    ][$type] ?? 'secondary';

    $arrow = in_array($type, ['transfer_out', 'burn']) ? '↓' : '↑';
    $text_color = in_array($type, ['transfer_out', 'burn']) ? 'danger' : 'success';

    $other_user = $row['other_user_name'] ?? 'System';

    if($type === 'mint'){
        $other_user = 'System (Mint)';
    } elseif($type === 'burn'){
        $other_user = 'System (Burn)';
    }
    
    // Remarks - check multiple possible column names
    $remarks = $row['description'] ?? $row['remarks'] ?? '—';

    $html .= "
    <tr>
        <td>{$formatted_date}</td>
        <td>{$time_block}</td>
        <td><span class='badge bg-{$badge_class}'>" . strtoupper(str_replace('_', ' ', $type)) . "</span></td>
        <td class='fw-bold text-{$text_color}'>
            {$units} kWh 
            <span>{$arrow}</span>
        </td>
        <td><small class='text-muted' title='{$hash}'>{$short_hash}</small></td>
        <td><small>{$other_user}</small></td>
        <td><small class='text-muted'>{$remarks}</small></td>
    </tr>";
}

if(empty($html)){
    $html = "<tr><td colspan='7' class='text-center text-muted py-3'>No token transactions yet</td></tr>";
}

echo $html;
?>