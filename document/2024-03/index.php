<!--
如何硬件刷卡保证能记录, 签到记录
/home/mewmewte/portal.hbhinstitute.com/application/view/backend/superadmin/checkin

-->
<div class="container">
  <div class="row">
    <div class="col-md-8 offset-md-2">
      <div class="card">
        <div class="card-body">
          <form method="post">
            <div class="mb-1">
              <label for="card_number" class="form-label">Card Number:</label>
              <input type="text" name="card_number" id="card_number" class="form-control" required>
            </div>
            <button type="submit" name="submit" class="btn btn-primary">Submit</button>
          </form>

<?php
// connect to the database
$host = 'localhost';
$user = 'mewmewte_portal_user';
$password = 'hbhdbuser@2023';
$database = 'mewmewte_hbhportal';
$conn = mysqli_connect($host, $user, $password, $database);

// check if the connection was successful
if(!$conn) {
    die('Database connection failed.');
}
date_default_timezone_set('Asia/Dubai');

// check if the form was submitted
if(isset($_POST['submit'])) {
    // get the card number from the input field
    $card_number = $_POST['card_number'];

    // fetch data from the database based on the card number
    $sql = "SELECT * FROM users WHERE card_number='$card_number'";
    $result = mysqli_query($conn, $sql);

    // check if any results were found
    if(mysqli_num_rows($result) > 0) {
        // loop through the results and display them
        while($row = mysqli_fetch_assoc($result)) {
            $image_url = '/uploads/users/' . $row['id'] . '.jpg';
          //  echo '<img width=200 class="img-fluid rounded mx-auto d-block" src="'.$image_url.'" alt="Profile Image">';
            echo '<h2 style="font-family: Raleway,sans-serif; font-size: 30px; font-weight: 800; line-height: 70px; margin: 0 0 10px; text-align: center; text-transform: uppercase;" class="text-center">'.$row['name'].'</h2>';
            echo '<h2 style=" color: #4682B4; font-family: Raleway,sans-serif; font-size: 30px; font-weight: 600; line-height: 2px; margin: 0 0 20px; text-align: center; text-transform: uppercase;" class="text-center">Balance: '.$row['balance'].'</h2>';

            echo ' <br><br><p style="font-family: Raleway,sans-serif; font-size: 18px; font-weight: 500; line-height: 22px; margin: 0 0 14px;" class="text-center">Expiry: '.$row['expiry_date'];
            //echo ' <br><br><p style="font-family: Raleway,sans-serif; font-size: 18px; font-weight: 500; line-height: 22px; margin: 0 0 14px;" class="text-center">Email: '.$row['email'].'</p>';
            //echo '<p style="font-family: Raleway,sans-serif; font-size: 18px; font-weight: 500; line-height: 22px; margin: 0 0 14px;" class="text-center">Contact No: '.$row['phone'].'</p>';
            echo '<p style="font-family: Raleway,sans-serif; font-size: 18px; font-weight: 500; line-height: 22px; margin: 0 0 14px;" class="text-center">Acess Card Number: '.$row['card_number'].'</p>';
            echo '<p style="font-family: Raleway,sans-serif; font-size: 18px; font-weight: 500; line-height: 22px; margin: 0 0 14px;" class="text-center">Serial Number: '.$row['serial_num'].' <br><br></p>';
            echo '<p style="font-family: Raleway,sans-serif; font-size: 18px; font-weight: 500; line-height: 22px; margin: 0 0 14px;" class="text-center">Primary Class: '.$row['class'].' <br><br></p>';
            echo '<p style="font-family: Raleway,sans-serif; font-size: 18px; font-weight: 500; line-height: 22px; margin: 0 0 14px;" class="text-center">Second Class: '.$row['second_class'].' <br><br></p>';
            echo '<p style="font-family: Raleway,sans-serif; font-size: 18px; font-weight: 500; line-height: 22px; margin: 0 0 14px;" class="text-center">Third Class: '.$row['third_class'].' <br><br></p>';
            echo '<p style="font-family: Raleway,sans-serif; font-size: 18px; font-weight: 500; line-height: 22px; margin: 0 0 14px;" class="text-center">Other Details: '.$row['class_details'].' <br><br></p>';
            echo '<form method="post" class="text-center">
            
            
                    <div class="col-md-12 form-control">
                        <label for="balance">Add Balance:</label>
                        <input type="number" class="form-control" name="balance" id="balance">
                
                
                
                        <label for="expiry_date"> <br>Expiry Date:</label>
                        <input type="date" class="form-control" name="expiry_date" id="expiry_date" ><br>
                
                        <button class="btn btn-primary" type="submit" name="add_balance">Add Balance</button>
                    </div>
 
                <input type="hidden" name="user_id" value="'.$row['id'].'">
            </form>';



echo '<form method="post" class="text-center" >';
echo '<input type="hidden" name="user_id" value="'.$row['id'].'">';

echo '<label for="class_type"><br><br>Select Class Type:</label>';
echo '<select class="form-select" id="class_type" name="class_type">';
echo '<option value="Arabic">Arabic</option>';
echo '<option value="English">English</option>';
echo '<option value="French">French</option>';
echo '<option value="Math">Math</option>';
echo '<option value="Chinese">Chinese</option>';
echo '<option value="Book Reading">Book Reading</option>';
echo '<option value="Art_DIY">Art/DIY/Handicraft</option>';
echo '<option value="Oil_Acrylic Painting">Oil/Acrylic Painting</option>';
echo '<option value="Sketch Making">Sketch Making</option>';
echo '<option value="Lego (4-6 Y)">Lego (4-6 Y)</option>';
echo '<option value="Lego (6-10 Y)">Lego (6-10 Y)</option>';
echo '<option value="Lego Programming">Lego Programing</option>';
echo '<option value="3D Printing">3D Printing (14+Y)</option>';
echo '<option value="AI story">AI story</option>';
echo '<option value="Chess Playing">Chess Playing</option>';
echo '<option value="Kpop (Basic)">Kpop (Basic)</option>';
echo '<option value="Kpop(Advance)">Kpop (Advance)</option>';
echo '<option value="Hip Hop (Basic)">Hip Hop (Basic)</option>';
echo '<option value="Hip Hop (Advance)">Hip Hop (Advance)</option>';
echo '<option value="Jazz (Basic)">Jazz (Basic)</option>';
echo '<option value="Jazz (Advance)">Jazz (Advance)</option>';
echo '<option value="Zumba">Zumba</option>';
echo '<option value="Ballet">Ballet</option>';
echo '<option value="Belly">Belly</option>';
echo '<option value="Yoga Classes">Yoga Classes</option>';
echo '<option value="Private Classes">Private Classes</option>';

echo '</select> <br>' ;
echo '<button class="btn btn-primary" type="submit" name="checkin">Check In</button> <br><br>';
echo '</form>';

// fetch check-in records
$sql_checkin = "SELECT * FROM checkin WHERE user_id='".$row['id']."'";
$result_checkin = mysqli_query($conn, $sql_checkin);

// display check-in records in a table
if(mysqli_num_rows($result_checkin) > 0) {
echo '<table class="table">';
echo '<thead><tr><th>Date/Time</th><th>Class Type</th><th>Added Balance</th></tr></thead>';
echo '<tbody>';
while($row_checkin = mysqli_fetch_assoc($result_checkin)) {
$added_balance = $row_checkin['added_balance'];
if ($added_balance > 0) {
$row_color = 'style="background-color: #DAF7A6;"';

} else {
$row_color = '';
}
echo '<tr '.$row_color.'><td>'.$row_checkin['datetime'].'</td><td>'.$row_checkin['class_type'].'</td><td>'.$added_balance.'</td></tr>';
}
echo'</tbody></table>';
} else {
echo '<p class="text-center">No check-in records found.</p>';
}
}
} else {
echo '<p class="text-center">No user found with this card number.</p>';
}
}

// check if the add balance form was submitted
if(isset($_POST['add_balance'])) {
$user_id = $_POST['user_id'];
$balance = $_POST['balance'];
    $expiry_date = $_POST['expiry_date'];

    // update the balance in the database
$sql = "UPDATE users SET balance=balance+$balance, expiry_date='$expiry_date' WHERE id='$user_id'";
$result = mysqli_query($conn, $sql);

// check if the update was successful
if($result) {
// add checkin record to database
$datetime = date('Y-m-d H:i:s');
$type = 'balance';
$added_balance = $balance;
$sql_checkin = "INSERT INTO checkin (user_id, type, added_balance, datetime) VALUES ('$user_id', '$type', '$added_balance', '$datetime')";
$result_checkin = mysqli_query($conn, $sql_checkin);
if($result_checkin) {
echo '<div class="alert alert-success text-center">Balance updated and check-in recorded successfully.</div>';
} else {
echo '<div class="alert alert-danger text-center">Failed to record check-in.</div>';
}
} else {
echo '<div class="alert alert-danger text-center">Failed to update balance.</div>';
}
}

// check if the check-in button was clicked
if(isset($_POST['checkin'])) {
    $user_id = $_POST['user_id'];
    $class_type = $_POST['class_type'];

    // subtract 1 from the user's balance
    $sql = "UPDATE users SET balance = balance - 1 WHERE id='$user_id'";
    $result = mysqli_query($conn, $sql);

    // check if the balance was updated successfully
    if($result) {
        echo '<p class="text-success">Balance updated successfully.</p>';
    } else {
        echo '<p class="text-danger">Failed to update balance.</p>';
    }

    // insert the check-in record into the database
    $datetime = date('Y-m-d H:i:s');
$sql = "INSERT INTO checkin (user_id, class_type, datetime) VALUES ('$user_id', '$class_type', '$datetime')";
$result = mysqli_query($conn, $sql);

    // check if the record was inserted successfully
    if($result) {
        echo '<p class="text-success">Check-in recorded successfully.</p>';
    } else {
        echo '<p class="text-danger">Failed to record check-in.</p>';
    }
}





mysqli_close($conn); // close database connection
?>


</div>
</div>
</div>
</div>
</div>

