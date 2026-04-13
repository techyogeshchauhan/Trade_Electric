<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'seller') {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';
$user_id = $_SESSION['user_id'];
$date = date('Y-m-d'); // today
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Meter Data - 96 Blocks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include '../includes/header.php'; ?>

<div class="main-content p-4">
    <h2>Add Meter Data for <?= $date ?></h2>
    <p class="text-muted">Fill data for all 96 time blocks (15-min intervals)</p>

    <form method="POST" action="../api/save_meter_data.php">
        <input type="hidden" name="date" value="<?= $date ?>">

        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Time Block</th>
                        <th>Generated (kWh)</th>
                        <th>Self Consumed (kWh)</th>
                        <th>Sold (kWh)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    for ($h = 0; $h < 24; $h++) {
                        for ($m = 0; $m < 60; $m += 15) {
                            $start = sprintf("%02d:%02d", $h, $m);
                            $end   = sprintf("%02d:%02d", $h, $m + 15);
                            if ($end == "24:00") $end = "00:00";
                            $block = "$start-$end";
                            echo "<tr>
                                <td><strong>$block</strong></td>
                                <td><input type='number' step='0.01' name='generated[$block]' class='form-control' value='0'></td>
                                <td><input type='number' step='0.01' name='self[$block]' class='form-control' value='0'></td>
                                <td><input type='number' step='0.01' name='sold[$block]' class='form-control' value='0'></td>
                            </tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100 mt-3">Save All 96 Blocks Data</button>
    </form>
</div>

</body>
</html>