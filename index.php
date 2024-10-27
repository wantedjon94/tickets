<?php
// Ticket table save function
function saveTable($event_id = null, $event_date = null, $ticket_adult_price = null, $ticket_adult_quantity = null, $ticket_kid_price = null,
                   $ticket_kid_quantity = null)
{
  //generating barcode
    $barcode = generateBarcode();
  //initial variables
    $decodedData = null;
    $message = '';
  //sending data
    $data = [
        "event_id" => $event_id,
        "event_date" => $event_date,
        "ticket_adult_price" => $ticket_adult_price,
        "ticket_adult_quantity" => $ticket_adult_quantity,
        "ticket_kid_price" => $ticket_kid_price,
        "ticket_kid_quantity" => $ticket_kid_quantity,
        "barcode" => $barcode,
    ];
    //book url
    $url = "https://api.site.com/book";
    //sending request and getting response with error if exist
    $response = sendPost($url, $data);
  //checking error
    if (key_exists('error', $response)) {
      //regenerate barcode
        $barcode = generateBarcode();
      //set it to sending data
        $data["barcode"] = $barcode;
      //send again data
        $response = sendPost($url, $data);
    }
    // if no error and has message
    if (key_exists('message', $response)) {
      //set barcode from sending data
        $barcode = $data["barcode"];
      //approve barcode
        $result = approveBarcode($barcode);
      //checking status
        if ($result['is_success']) {
          //if success save data to table and show message
            $message = save($data);
        }else{
          // else show message
            $message = $result['error'];
        }
    }
    //cleaning variables
    $barcode = null;
    $response = null;
    $result = '';
    $data = null;
  // return message
    return $message;
}

function generateBarcode()
{
  // creating prefix to never generate same numbers
    $prefix = rand(100000, 999999);
    return $prefix . hexdec(uniqid());
}

function sendPost($url, $data)
{
  //preparing cURL to send post data
    $curl = curl_init($url);
    $send_data = json_encode($data);
  //setting header option to get data as json 
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json'));
  //set to get response
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  //set sending data
    curl_setopt($curl, CURLOPT_POSTFIELDS, $send_data);
//executing send
    $response = curl_exec($curl);
// if error return error
    if ($e = curl_error($curl)) {
        return $e;
    } else {
      //else decode to json
        $decodedData = json_decode($response, true);
    }
    curl_close($curl);

    return $decodedData;
}

function approveBarcode($barcode)
{
  //approving barcode
    $response = sendPost("https://api.site.com/approve", ["barcode" => $barcode]);
    $result = [];
    if (key_exists('error', $response)) {
      //if has error return error
        $result['is_success'] = false;
        $result['error'] = $response['error'];
    } else {
      //else return true
        $result['is_success'] = true;
    }
    return $result;
}

function save($data)
{
  //initializing database connection
    $servername = "localhost";
    $username = "username";
    $password = "password";
    $dbname = "ticket_app";

// Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
    if ($conn->connect_error) {
        return "Connection failed: " . $conn->connect_error;
    }
// insert sql script
    $sql = "INSERT INTO tickets (event_id, event_date, ticket_adult_price, ticket_adult_quantity, ticket_kid_price, ticket_kid_quantity, barcode, equal_price) 
            VALUES (".$data['event_id'].", '".$data['event_date']."', ".$data['ticket_adult_price'].", ".$data['ticket_adult_quantity'].", ".$data['ticket_kid_price'].", "
        .$data['ticket_kid_quantity'].", ".$data['barcode'].", ".($data['ticket_adult_price'] * $data['ticket_adult_quantity'] + $data['ticket_kid_price'] * $data['ticket_kid_quantity']).")";

    if ($conn->query($sql) === TRUE) {
      // if insertation success close connection
        $conn->close();
      //return message
        return "New record created successfully";
    } else {
      // if error accurs close connection
        $conn->close();
      //return error message
        return "Error: " . $sql . "<br>" . $conn->error;
    }
}

// call and show message
echo saveTable();
