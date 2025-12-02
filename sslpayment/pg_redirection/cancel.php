<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="author" content="SSLCommerz">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Cancelled - SSLCommerz</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            background: linear-gradient(to bottom, #fff5f5, #ffeaea);
            color: #333;
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 15px;
        }

        .card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 400px;
            padding: 30px 20px;
            text-align: center;
            animation: fadeIn 0.8s ease-in-out;
        }

        .error-icon {
            font-size: 60px;
            color: #ff4d4d;
            margin-bottom: 15px;
            animation: pop 0.6s ease-out;
        }

        h2 {
            font-size: 22px;
            font-weight: 700;
            color: #ff4d4d;
            margin-bottom: 25px;
            animation: slideDown 0.6s ease-out;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            animation: fadeIn 1s ease-in;
        }

        table th {
            background: #ff8383;
            color: #fff;
            font-weight: 500;
            padding: 12px;
            border-radius: 10px 10px 0 0;
        }

        table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ffcccc;
            font-size: 15px;
        }

        td.text-right {
            text-align: right;
            font-weight: 500;
        }

        .error-message {
            color: #ff4d4d;
            font-weight: 600;
            font-size: 16px;
            animation: shake 0.5s;
            margin-bottom: 20px;
        }

        @keyframes fadeIn {
            0% {
                opacity: 0;
                transform: translateY(20px);
            }

            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideDown {
            0% {
                opacity: 0;
                transform: translateY(-20px);
            }

            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pop {
            0% {
                transform: scale(0);
            }

            70% {
                transform: scale(1.2);
            }

            100% {
                transform: scale(1);
            }
        }

        @keyframes shake {
            0%,
            100% {
                transform: translateX(0);
            }

            20%,
            60% {
                transform: translateX(-5px);
            }

            40%,
            80% {
                transform: translateX(5px);
            }
        }

        @media (min-width: 768px) {
            .card {
                padding: 40px 30px;
            }

            h2 {
                font-size: 26px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card">
            <?php
            if (empty($_POST['tran_id']) || empty($_POST['status'])) {
                echo '<div class="error-message">Invalid Information.</div>';
                exit;
            }

            include(__DIR__ . "/../db_connection.php");
            include(__DIR__ . "/../OrderTransaction.php");

            $tran_id = trim($_POST['tran_id']);
            $ot = new OrderTransaction();
            $sql = $ot->getRecordQuery($tran_id);
            $result = $conn_integration->query($sql);
            $row = $result->fetch_array(MYSQLI_ASSOC);

            if ($row['status'] == 'Pending' || $row['status'] == 'Canceled') :
                $sql = $ot->updateTransactionQuery($tran_id, 'Canceled');
                if ($conn_integration->query($sql) === TRUE) : ?>
                    <div class="error-icon">❌</div>
                    <h2>Transaction Cancelled</h2>
                    <table>
                        <thead>
                            <tr>
                                <th colspan="2">Payment Details</th>
                            </tr>
                        </thead>
                        <tr>
                            <td class="text-right">Description</td>
                            <td><?= $_POST['error'] ?? 'Payment cancelled' ?></td>
                        </tr>
                        <tr>
                            <td class="text-right">Transaction ID</td>
                            <td><?= $_POST['tran_id'] ?></td>
                        </tr>
                        <tr>
                            <td class="text-right">Amount</td>
                            <td><?= $_POST['amount'] . ' ' . $_POST['currency'] ?></td>
                        </tr>
                    </table>
                <?php else : ?>
                    <div class="error-message">Error updating record: <?= $conn_integration->error; ?></div>
                <?php endif;
            elseif ($row['status'] == 'Processing') : ?>
                <table>
                    <thead>
                        <tr>
                            <th colspan="2">Payment Details</th>
                        </tr>
                    </thead>
                    <tr>
                        <td class="text-right">Transaction ID</td>
                        <td><?= $_POST['tran_id'] ?></td>
                    </tr>
                    <tr>
                        <td class="text-right">Transaction Time</td>
                        <td><?= $_POST['tran_date'] ?></td>
                    </tr>
                    <tr>
                        <td class="text-right">Payment Method</td>
                        <td><?= $_POST['card_issuer'] ?></td>
                    </tr>
                    <tr>
                        <td class="text-right">Bank Transaction ID</td>
                        <td><?= $_POST['bank_tran_id'] ?></td>
                    </tr>
                    <tr>
                        <td class="text-right">Amount</td>
                        <td><?= $_POST['amount'] . ' ' . $_POST['currency'] ?></td>
                    </tr>
                </table>
            <?php else : ?>
                <div class="error-message">Invalid Information.</div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>
