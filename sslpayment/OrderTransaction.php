<?php

class OrderTransaction
{
    // 🔍 Fetch existing transaction
    public function getRecordQuery($tran_id)
    {
        return "SELECT * FROM orders WHERE transaction_id = '" . addslashes($tran_id) . "'";
    }

    // 💾 Save new transaction (insert order)
    public function saveTransactionQuery($post_data)
    {
        // Basic fields
        $name = addslashes($post_data['cus_name']);
        $email = addslashes($post_data['cus_email']);
        $phone = addslashes($post_data['cus_phone']);
        $transaction_amount = floatval($post_data['total_amount']);
        $address = addslashes($post_data['cus_add1']);
        $transaction_id = addslashes($post_data['tran_id']);
        $currency = addslashes($post_data['currency']);

        $user_id = intval($post_data['value_d']);       // from Android (user_id)
        $service_id = intval($post_data['cus_add2']);   // from Android (service_id)
        $promo_code = addslashes($post_data['value_a']); // promo_code
        $pickup_date = addslashes($post_data['value_b']);
        $delivery_date = addslashes($post_data['value_c']);

        // ✅ Insert query with correct quoting
        $sql = "INSERT INTO orders (
                    user_id,
                    name,
                    email,
                    phone,
                    address,
                    service_id,
                    amount,
                    status,
                    noti_status,
                    transaction_id,
                    currency,
                    promo_code,
                    pickup_time,
                    delivery_time
                ) VALUES (
                    $user_id,
                    '$name',
                    '$email',
                    '$phone',
                    '$address',
                    $service_id,
                    '$transaction_amount',
                    'Pending',
                    'Pending',
                    '$transaction_id',
                    '$currency',
                    '$promo_code',
                    '$pickup_date',
                    '$delivery_date'
                )";

        return $sql;
    }

      // 🔄 Update transaction status (Success / Failed / Cancelled)
    public function updateTransactionQuery($tran_id, $type)
    {
        $sql = "UPDATE orders 
                SET status = '$type' 
                WHERE transaction_id = '$tran_id'";
        return $sql;
    }
	
	   // 🔄 Update transaction status (Success / Failed / Cancelled) and payment info
public function updateTransactionQuery_for_success($tran_id, $payment_method, $payment_transaction_id)
{
    $payment_method_sql = $payment_method ? "'$payment_method'" : 'NULL';
$payment_transaction_id_sql = $payment_transaction_id ? "'$payment_transaction_id'" : 'NULL';

$sql = "UPDATE orders 
        SET status = 'Successful',
          
            payment_method = $payment_method_sql,
            payment_transaction_id = $payment_transaction_id_sql
        WHERE transaction_id = '$tran_id'";


    return $sql;
}
}
?>
