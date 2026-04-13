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
           u.name AS other_user_name
    FROM token_ledger tl
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
    $short_hash = substr($hash, 0, 10) . '...';

    $badge_class = [
        'mint' => 'success',
        'transfer_in' => 'primary',
        'transfer_out' => 'warning',
        'burn' => 'danger'
    ][$type] ?? 'secondary';

    $sign = '';  // Remove negative sign, show all as positive
    $arrow = in_array($type, ['transfer_out', 'burn']) ? '↓' : '↑';
    $text_color = in_array($type, ['transfer_out', 'burn']) ? 'danger' : 'success';

    $other_user = $row['other_user_name'] ?? 'System';

    if($type === 'mint'){
        $other_user = 'System (Mint)';
    } elseif($type === 'burn'){
        $other_user = 'System (Burn)';
    }

    $html .= "
    <tr>
        <td>{$row['date']}</td>
        <td>{$row['time_block']}</td>
        <td><span class='badge bg-{$badge_class}'>" . strtoupper(str_replace('_', ' ', $type)) . "</span></td>
        <td class='fw-bold text-{$text_color}'>
            {$units} kWh 
            <span>{$arrow}</span>
        </td>
        <td><small class='text-muted' title='{$hash}'>{$short_hash}</small></td>
        <td><small>{$other_user}</small></td>
        <td><small class='text-muted'>{$row['remarks']}</small></td>
    </tr>";
}

if(empty($html)){
    $html = "<tr><td colspan='7' class='text-center text-muted py-3'>No token transactions yet</td></tr>";
}

echo $html;
?>