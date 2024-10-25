<?php
function saveTable($event_id = null, $event_date = null, $ticket_adult_price = null, $ticket_adult_quantity = null, $ticket_kid_price = null,
                   $ticket_kid_quantity = null)
{
    $barcode = generateBarcode();
    $decodedData = null;
    $message = '';
    $data = [
        "event_id" => $event_id,
        "event_date" => $event_date,
        "ticket_adult_price" => $ticket_adult_price,
        "ticket_adult_quantity" => $ticket_adult_quantity,
        "ticket_kid_price" => $ticket_kid_price,
        "ticket_kid_quantity" => $ticket_kid_quantity,
        "barcode" => $barcode,
    ];

    $url = "https://api.site.com/book";

    $response = sendPost($url, $data);
    if (key_exists('error', $response)) {
        $barcode = generateBarcode();
        $data["barcode"] = $barcode;
        $response = sendPost($url, $data);
    }

    if (key_exists('message', $response)) {
        $barcode = $data["barcode"];
        $result = approveBarcode($barcode);

        if ($result['is_success']) {
            $message = save($data);
        }else{
            $message = $result['error'];
        }
    }

    $barcode = null;
    $response = null;
    $result = '';
    $data = null;

    return $message;
}

function generateBarcode()
{
    $prefix = rand(100000, 999999);
    return $prefix . hexdec(uniqid());
}

function sendPost($url, $data)
{
    $curl = curl_init($url);
    $send_data = json_encode($data);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json'));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $send_data);

    $response = curl_exec($curl);

    if ($e = curl_error($curl)) {
        return $e;
    } else {
        $decodedData = json_decode($response, true);
    }

    curl_close($curl);

    return $decodedData;
}

function checkResponse($url, $data, $response)
{
    if (key_exists('error', $response)) {
        $barcode = generateBarcode();
        $data["barcode"] = $barcode;
        $response = sendPost($url, $data);
        return [$response, $barcode];
    }
    return $response;
}

function approveBarcode($barcode)
{
    $response = sendPost("https://api.site.com/approve", ["barcode" => $barcode]);
    $result = [];
    if (key_exists('error', $response)) {
        $result['is_success'] = false;
        $result['error'] = $response['error'];
    } else {
        $result['is_success'] = true;
    }
    return $result;
}

function save($data)
{
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

    $sql = "INSERT INTO tickets (event_id, event_date, ticket_adult_price, ticket_adult_quantity, ticket_kid_price, ticket_kid_quantity, barcode, equal_price) 
            VALUES (".$data['event_id'].", '".$data['event_date']."', ".$data['ticket_adult_price'].", ".$data['ticket_adult_quantity'].", ".$data['ticket_kid_price'].", "
        .$data['ticket_kid_quantity'].", ".$data['barcode'].", ".($data['ticket_adult_price'] * $data['ticket_adult_quantity'] + $data['ticket_kid_price'] * $data['ticket_kid_quantity']).")";

    if ($conn->query($sql) === TRUE) {
        $conn->close();
        return "New record created successfully";
    } else {
        $conn->close();
        return "Error: " . $sql . "<br>" . $conn->error;
    }
}

echo saveTable();