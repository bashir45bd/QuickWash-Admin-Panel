<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="author" content="SSLCommerz">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Status - SSLCommerz</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            background: #fff5f5;
            color: #333;
        }

        .container {
            padding: 15px;
        }

        .card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-top: 15%;
            text-align: center;
            animation: fadeIn 0.8s ease-in-out;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }

        h2 {
            color: #ff8383;
            font-weight: 700;
            margin-bottom: 15px;
            font-size: 1.4rem;
            animation: slideDown 0.6s ease-out;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 0.9rem;
            animation: fadeIn 1s ease-in;
        }

        table th,
        table td {
            padding: 8px 6px;
        }

        table th {
            background-color: #ff8383;
            color: #fff;
            font-weight: 500;
            font-size: 0.9rem;
        }

        table td {
            border-bottom: 1px solid #ffcccc;
            word-break: break-word;
        }

        table tr:last-child td {
            border-bottom: none;
        }

        td.text-right {
            text-align: left; /* mobile-friendly: stack labels on left */
            font-weight: 500;
        }

        .success-icon {
            font-size: 40px;
            color: #ff8383;
            margin-bottom: 12px;
            animation: pop 0.6s ease-out;
        }

        .error-message {
            color: #ff4d4d;
            font-weight: 600;
            font-size: 0.95rem;
            animation: shake 0.5s;
        }

        @keyframes fadeIn {
            0% {
                opacity: 0;
                transform: translateY(15px);
            }

            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideDown {
            0% {
                opacity: 0;
                transform: translateY(-15px);
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
                transform: translateX(-4px);
            }

            40%,
            80% {
                transform: translateX(4px);
            }
        }

        /* Mobile-first adjustments */
        @media (min-width: 480px) {
            .card {
                padding: 25px;
            }

            h2 {
                font-size: 1.6rem;
            }

            table th,
            table td {
                font-size: 1rem;
            }

            .success-icon {
                font-size: 50px;
            }
        }

        @media (min-width: 768px) {
            .container {
                padding: 40px;
            }

            .card {
                margin-top: 5%;
                max-width: 500px;
            }

            h2 {
                font-size: 1.8rem;
            }

            table th,
            table td {
                font-size: 1rem;
            }

            .success-icon {
                font-size: 60px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card">
            <?php
            // PHP code unchanged
            require_once(__DIR__ . "/../lib/SslCommerzNotification.php");
            include_once(__DIR__ . "/../db_connection.php");
            include_once(__DIR__ . "/../OrderTransaction.php");

            use SslCommerz\SslCommerzNotification;

            $sslc = new SslCommerzNotification();
            $tran_id = $_POST['tran_id'];
            $amount = $_POST['amount'];
            $currency = $_POST['currency'];
            $payment_method = $_POST['card_issuer'];
            $payment_transaction_id = $_POST['bank_tran_id']; 

            $ot = new OrderTransaction();
            $sql = $ot->getRecordQuery($tran_id);
            $result = $conn_integration->query($sql);
            $row = $result->fetch_array(MYSQLI_ASSOC);

            if ($row['status'] == 'Pending' || $row['status'] == 'Processing') {
                $validated = $sslc->orderValidate($_POST, $tran_id, $amount, $currency);

                if ($validated) {
                    $sql = $ot->updateTransactionQuery_for_success($tran_id, $payment_method, $payment_transaction_id);

                    if ($conn_integration->query($sql) === TRUE) { ?>
                        <div class="success-icon">✔️</div>
                        <h2>Transaction Successful!</h2>
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
                    <?php
                    } else {
                        echo '<div class="error-message">Error updating record: ' . $conn_integration->error . '</div>';
                    }
                } else {
                    echo '<div class="error-message">Payment was not valid. Please contact the merchant.</div>';
                }
            } else {
                echo '<div class="error-message">Invalid Information.</div>';
            }
            ?>
        </div>
    </div>
</body>

</html>
