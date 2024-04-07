<?php
include('conn.php');

// Verify table
if (isset($_GET['table'])) {
    $t = $_GET['table'];
}

// Verify action
if (isset($_GET['action'])) {
    $action = $_GET['action'];

    // Create
    if ($action == "create") {
        if (isset($_GET['table']) && isset($_GET['make']) && isset($_GET['model']) && isset($_GET['year']) && isset($_GET['serial']) && isset($_GET['id_owner'])) {
            createCar($_GET['make'], $_GET['model'], $_GET['year'], $_GET['serial'], $_GET['id_owner']);
        } elseif (isset($_GET['table']) && isset($_GET['name']) && isset($_GET['email'])) {
            createClient($_GET['name'], $_GET['email']);
        } else {
            echo json_encode(array('Status' => 'Error', 'Message' => 'Incomplete parameters for create action'));
        }
    }

    // Read All
    if ($action == "readall") {
        readAll($_GET['table']);
    }

    // Read
    if ($action == "read") {
        if (isset($_GET['table']) && isset($_GET['conditions'])) {
            read($_GET['table'], $_GET['conditions']);
        } else {
            echo json_encode(array('Status' => 'Error', 'Message' => 'Incomplete parameters for read action'));
        }
    }

    // Update
    if ($action == "update") {
        if (isset($_GET['table']) && isset($_GET['id']) && isset($_GET['data'])) {
            update($_GET['table'], $_GET['id'], json_decode($_GET['data'], true));
        } else {
            echo json_encode(array('Status' => 'Error', 'Message' => 'Incomplete parameters for update action'));
        }
    }

    // Delete
    if ($action == "delete") {
        if (isset($_GET['table']) && isset($_GET['id'])) {
            delete($_GET['table'], $_GET['id']);
        } else {
            echo json_encode(array('Status' => 'Error', 'Message' => 'Incomplete parameters for delete action'));
        }
    }
}

// Create client function
function createClient($name, $email) {
    global $db;

    $response = array();

    $sql = "INSERT INTO client (name, email) VALUES (?,?)";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('ss', $name, $email);
    
    if ($stmt->execute()) {
        $response['Status'] = 'OK';
        $response['Message'] = 'Client record created successfully';
    } else {
        $response['Status'] = 'Error';
        $response['Message'] = 'Failed to create client record';
    }

    echo json_encode($response);
}

// Create car function
function createCar($make, $model, $year, $serial, $id_owner) {
    global $db;

    $response = array();

    $sql = "SELECT * FROM client WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $id_owner);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $sql = "INSERT INTO car (make, model, year, serial, id_owner) VALUES (?,?,?,?,?)";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('ssisi', $make, $model, $year, $serial, $id_owner);
        
        if ($stmt->execute()) {
            $response['Status'] = 'OK';
            $response['Message'] = 'Car record created successfully';
        } else {
            $response['Status'] = 'Error';
            $response['Message'] = 'Failed to create car record';
        }
    } else {
        $response['Status'] = 'Error';
        $response['Message'] = 'Invalid owner ID';
    }

    echo json_encode($response);
}

// ReadAll Function
function readAll($t) {
    global $db;

    $response = array();

    switch ($t) {
        case 'client':
            $sql = "SELECT client.id AS client_id, client.name, client.email, car.id AS car_id, car.make, car.model, car.year, car.serial 
                    FROM client LEFT JOIN car ON client.id = car.id_owner";
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $clients = array();
                while ($row = $result->fetch_assoc()) {
                    $client_id = $row['client_id'];
                    if (!isset($clients[$client_id])) {
                        $clients[$client_id] = array(
                            'ID' => $row['client_id'],
                            'Name' => $row['name'],
                            'Email' => $row['email'],
                            'Cars' => array()
                        );
                    }

                    if (!is_null($row['car_id'])) {
                        $clients[$client_id]['Cars'][] = array(
                            'ID' => $row['car_id'],
                            'Make' => $row['make'],
                            'Model' => $row['model'],
                            'Year' => $row['year'],
                            'Serial' => $row['serial']
                        );
                    }
                }

                $response['Status'] = 'OK';
                $response['Message'] = array_values($clients);
            } else {
                $response['Status'] = 'Error';
                $response['Message'] = 'No clients available';
            }
            break;
        case 'car':
            $stmt = $db->prepare("SELECT * FROM car");
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $cars = array();
                while ($row = $result->fetch_assoc()) {
                    $cars[] = array(
                        'ID' => $row['id'],
                        'Make' => $row['make'],
                        'Model' => $row['model'],
                        'Year' => $row['year'],
                        'Serial' => $row['serial'],
                        'Owner' => $row['id_owner']
                    );
                }
                $response['Status'] = 'OK';
                $response['Message'] = $cars;
            } else {
                $response['Status'] = 'Error';
                $response['Message'] = 'No cars available';
            }
            break;
        default:
            $response['Status'] = 'Error';
            $response['Message'] = 'Invalid table name';
            break;
    }

    echo json_encode($response);
}

// Read Function
function read($t, $conditions) {
    global $db;

    $response = array();

    $sql = "SELECT * FROM $t WHERE 1 AND $conditions";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        switch ($t) {
            case 'car':
                $cars = array();
                while ($row = $result->fetch_assoc()) {
                    $cars[] = array(
                        'ID' => $row['id'],
                        'Make' => $row['make'],
                        'Model' => $row['model'],
                        'Year' => $row['year'],
                        'Serial' => $row['serial'],
                        'Owner' => $row['id_owner']
                    );
                }
                $response['Status'] = 'OK';
                $response['Message'] = $cars;
                break;
            case 'client':
                $sql = "SELECT client.id AS client_id, client.name, client.email, car.id AS car_id, car.make, car.model, car.year, car.serial 
                        FROM client LEFT JOIN car ON client.id = car.id_owner WHERE 1 AND $conditions";
                $stmt = $db->prepare($sql);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $clients = array();
                    while ($row = $result->fetch_assoc()) {
                        $client_id = $row['client_id'];
                        if (!isset($clients[$client_id])) {
                            $clients[$client_id] = array(
                                'ID' => $row['client_id'],
                                'Name' => $row['name'],
                                'Email' => $row['email'],
                                'Cars' => array()
                            );
                        }

                        if (!is_null($row['car_id'])) {
                            $clients[$client_id]['Cars'][] = array(
                                'ID' => $row['car_id'],
                                'Make' => $row['make'],
                                'Model' => $row['model'],
                                'Year' => $row['year'],
                                'Serial' => $row['serial']
                            );
                        }
                    }

                    $response['Status'] = 'OK';
                    $response['Message'] = array_values($clients);
                } else {
                    $response['Status'] = 'Error';
                    $response['Message'] = 'No matching client record found';
                }
                break;
            default:
                $response['Status'] = 'Error';
                $response['Message'] = 'Invalid table name';
                break;
        }
    } else {
        $response['Status'] = 'Error';
        $response['Message'] = 'No matching record found';
    }

    echo json_encode($response);
}

// Update Function
function update($t, $id, $data) {
    global $db;

    $response = array();

    if ($t !== 'car' && $t !== 'client') {
        $response['Status'] = 'Error';
        $response['Message'] = 'Invalid table name';
        echo json_encode($response);
        return;
    }

    $setClause = '';
    $types = '';
    $params = array();

    foreach ($data as $key => $value) {
        $setClause .= "$key=?, ";
        $types .= is_numeric($value) ? 'i' : 's';
        $params[] = &$data[$key];
    }

    $setClause = rtrim($setClause, ', ');

    $sql = "UPDATE $t SET $setClause WHERE id=?";
    $types .= 'i';
    $params[] = &$id;
    $stmt = $db->prepare($sql);

    
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        $response['Status'] = 'OK';
        $response['Message'] = 'Record updated successfully';
    } else {
        $response['Status'] = 'Error';
        $response['Message'] = 'Failed to update record';
    }

    echo json_encode($response);
}

// Delete Fucntion
function delete($t, $id) {
    global $db;

    $response = array();

    if ($t === 'client') {
        $sql = "SELECT COUNT(*) AS car_count FROM car WHERE id_owner = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row['car_count'] > 0) {
            $response['Status'] = 'Error';
            $response['Message'] = 'Cannot delete client as there are associated cars';
        } else {
            $sql = "DELETE FROM $t WHERE id=?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $response['Status'] = 'OK';
                $response['Message'] = 'Client record deleted successfully';
            } else {
                $response['Status'] = 'Error';
                $response['Message'] = 'Failed to delete client record';
            }
        }
    } elseif ($t === 'car') {
        $sql = "DELETE FROM $t WHERE id=?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $response['Status'] = 'OK';
            $response['Message'] = 'Car record deleted successfully';
        } else {
            $response['Status'] = 'Error';
            $response['Message'] = 'Failed to delete car record';
        }
    } else {
        // If the table name is invalid
        $response['Status'] = 'Error';
        $response['Message'] = 'Invalid table name';
    }

    echo json_encode($response);
}