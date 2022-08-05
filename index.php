<?php
    include_once("db.php");
    // echo "payment page";
    
    $sql = "SELECT *  FROM temp_data WHERE user_email='".$_GET['email']."'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // output data of each row
        // while($row = $result->fetch_assoc()) {
        //     echo "id: " . $row["id"]. " - Email: " . $row["user_email"]. " " . $row["total_price"]. "<br>";
        // }
        $data = $result->fetch_assoc();
        echo json_encode($data['id']);
    } else {
        echo "0 results";
    }
    $conn->close();

    //for testing purpose
    $url = "https://api.paystack.co/transaction/initialize";
    $fields = [
    'email' => $data['user_email'],
    'amount' => $data['total_price']*100,
    ];
    $fields_string = http_build_query($fields);
    //open connection
    $ch = curl_init();

    //set the url, number of POST vars, POST data
    curl_setopt($ch,CURLOPT_URL, $url);
    curl_setopt($ch,CURLOPT_POST, true);

    $public_key = "Bearer ".$PAYSTACK_SECRET_KEY_TEST;
    curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    "Authorization: $public_key",
    "Cache-Control: no-cache",
    ));

    //So that curl_exec returns the contents of the cURL; rather than echoing it
    curl_setopt($ch,CURLOPT_RETURNTRANSFER, true); 
    curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false); 
    curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false); 
    //execute post
    $result = curl_exec($ch);

    $response = json_decode($result);
    //   return response()->json($response);
    $payment_gatway_url = $response->data->authorization_url;
    header("location: ".$payment_gatway_url);
      
    
?>