<?php
$conn = new mysqli("localhost", "root", "", "moodle_db");

if ($conn->connect_error) {
    die("Connection failed");
}

$sql = "SELECT id, username, firstname, lastname, email FROM mdl_user";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Moodle Users</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(to right, #667eea, #764ba2);
            margin: 0;
            padding: 0;
        }

        h2 {
            text-align: center;
            color: white;
            margin-top: 20px;
        }

        .container {
            width: 90%;
            margin: 30px auto;
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            overflow: hidden;
            border-radius: 10px;
        }

        th {
            background: #4CAF50;
            color: white;
            padding: 12px;
            text-transform: uppercase;
        }

        td {
            padding: 10px;
            text-align: center;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        tr:hover {
            background-color: #d1e7dd;
            transform: scale(1.01);
            transition: 0.2s;
        }

        .footer {
            text-align: center;
            margin-top: 15px;
            color: white;
        }
    </style>
</head>

<body>

<h2>🚀 Moodle Users List</h2>

<div class="container">

<table>
<tr>
<th>ID</th>
<th>Username</th>
<th>Name</th>
<th>Email</th>
</tr>

<?php
while($row = $result->fetch_assoc()) {
    echo "<tr>
    <td>".$row['id']."</td>
    <td>".$row['username']."</td>
    <td>".$row['firstname']." ".$row['lastname']."</td>
    <td>".$row['email']."</td>
    </tr>";
}
?>

</table>

</div>

<div class="footer">
    © AeroBay LMS 2026
</div>

</body>
</html>