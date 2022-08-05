<?php
    require_once("db.php");
    $curl = curl_init();
    $reference = isset($_GET['reference']) ? $_GET['reference'] : '';
    if(!$reference){
      die('No reference supplied');
    }
    $public_key = "Bearer ".$PAYSTACK_SECRET_KEY;
    
    $public_key_test = "Bearer ".$PAYSTACK_SECRET_KEY_TEST;
    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . rawurlencode($reference),
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_SSL_VERIFYHOST => false,
      CURLOPT_SSL_VERIFYPEER => false,
      
      CURLOPT_HTTPHEADER => [
        "accept: application/json",
        "authorization: $public_key",
        //for test
        // "Authorization: $public_key_test",
        "cache-control: no-cache"
      ],
    ));
    
    $response = curl_exec($curl);
    $err = curl_error($curl);
    
    if($err){
        // there was an error contacting the Paystack API
      die('Curl returned error: ' . $err);
    }
    
    $tranx = json_decode($response);
    // echo json_encode($tranx->data->customer->email);
    // exit;
    if(!$tranx->status){
      // there was an error from the API
      die('API returned error: ' . $tranx->message);
    }
    
    if('success' == $tranx->data->status){
        // return response()->json('success');
        // get customer id
        $id_select_sql = "SELECT id  FROM users WHERE email='".$tranx->data->customer->email."'";
        $customer_id = $conn->query($id_select_sql);
        
        // get temporal data of customer
        $temp_data_select_sql = "SELECT *  FROM temp_data WHERE id='".$customer_id->customer_id."'";
        $temp_data = $conn->query($temp_data_select_sql);

        // get shipping address of customer
        $shipping_select_sql = "SELECT *  FROM shipping_addresses WHERE id='".$temp_data->shipping_id."'";
        $shipping_details = $conn->query($shipping_select_sql);

        $shipping_details= DB::table('tbl_shipping')
                    ->where('shipping_id', $temp_data->shipping_id)
                    ->first();
        // return response()->json($temp_data);

        $products= $temp_data->cart;
        $products = json_decode($products);
        
        $address = "Street: ".$shipping_details->street_address. "<br>". "City: ".$shipping_details->city. "<br>". "State: ". $shipping_details->state. "<br>". "Postcode: ". $shipping_details->postcode. "Company Name: ".$shipping_details->company_name;

        $data=array();
        $data['name'] = $shipping_details->first_name.' '.$shipping_details->last_name;
        $data['address'] = $address;
        $data['phone'] = $shipping_details->phone;
        $data['email'] = $shipping_details->email;
        $data['shipping_details'] = json_decode($shipping_details);
        $data['order_id'] = $tranx->data->id;
        $data['order_details'] = $temp_data->cart;
        $data['order_total'] = $temp_data->total_price;
        $data['status'] = 'pending';

        // update stock
        foreach ($products as $product) {
            $select_product_sql = $temp_data_select_sql = "SELECT *  FROM products WHERE id='".$product->id."'";
            $table_product = $conn->query($select_product_sql);

            $initial_stock = $table_product->sold;
            $new_stock = $initial_stock - $product->quantity;

            // DB::update('update  tbl_products set sold ='.$new_sold.' where product_id  = ?', [$product->id]);
            $update_stock_sql = "UPDATE products SET stock='".$new_stock."' WHERE id=".$product_id;
            $conn->query($update_stock_sql);
            // exit;
        }
        
        // return json_encode($data);

        // insert order into order table
        $columns = implode(", ",array_keys($data));
        $escaped_values = array_map('mysql_real_escape_string', array_values($data));
        $values  = implode("', '", $escaped_values);
        $sql = "INSERT INTO `orders`($columns) VALUES ($values)";

        header("location: success.php");
                
        // $mail_body=array();
        // $mail_body['order_detail'] = $data['order_details'];

        // $mail_body['name'] = $data['name'];
        // $mail_body['address'] = $data['address'];
        // $mail_body['phone'] = $data['phone'];
        // $mail_body['email'] = $data['email'];
        // $mail_body['order_id'] = $data['order_id'];
        // $mail_body['order_total'] = $data['order_total'];
        // $mail_body['full_name'] = $data['name'];
        
        
        // Mail::send(new emailOrderMail($mail_body));
        // return view('callback');
      
    }

?>