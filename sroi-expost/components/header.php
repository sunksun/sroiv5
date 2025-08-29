<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงาน SROI Ex-post Analysis</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>📊 SROI Ex-post Analysis</h1>
            <?php if (isset($selected_project) && $selected_project): ?>
                <p><?php echo htmlspecialchars($selected_project['project_code'] . ' : ' . $selected_project['name']); ?></p>
            <?php endif; ?>
        </div>