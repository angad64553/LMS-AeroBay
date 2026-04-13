<?php
$conn = new mysqli("localhost", "root", "", "moodle_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// COUNT DATA
$user_count = $conn->query("SELECT COUNT(*) as total FROM mdl_user")->fetch_assoc()['total'];
$course_count = $conn->query("SELECT COUNT(*) as total FROM mdl_course")->fetch_assoc()['total'];

?>

<!-- PAGE STYLE -->
<style>
body {
    background: #eef2f7;
    font-family: Arial;
    margin: 0;
}

.container {
    width: 80%;
    margin: auto;
    margin-top: 20px;
}

/* HEADER */
.header {
    background: #2980b9;
    color: white;
    padding: 15px;
    font-size: 22px;
    text-align: center;
    font-weight: bold;
}

/* CARDS */
.cards {
    display: flex;
    gap: 20px;
    margin: 20px 0;
}

.card {
    flex: 1;
    padding: 20px;
    color: white;
    border-radius: 10px;
    text-align: center;
}

.blue { background: #3498db; }
.green { background: #2ecc71; }

/* TABLE */
table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

th {
    background: #34495e;
    color: white;
    padding: 10px;
}

td {
    padding: 8px;
    border: 1px solid #ddd;
    text-align: center;
}

tr:nth-child(even) {
    background: #f2f2f2;
}

/* COURSE CARDS */
.course-box {
    background: white;
    padding: 15px;
    margin: 10px 0;
    border-left: 5px solid #3498db;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    border-radius: 5px;
}
</style>

<!-- HEADER -->
<div class="header">
    AeroBay LMS Dashboard
</div>

<div class="container">

<!-- CARDS -->
<div class="cards">
    <div class="card blue">
        <h3>Total Users</h3>
        <h2><?php echo $user_count; ?></h2>
    </div>

    <div class="card green">
        <h3>Total Courses</h3>
        <h2><?php echo $course_count; ?></h2>
    </div>
</div>

<!-- USER TABLE -->
<h2>User List</h2>

<table>
<tr>
<th>ID</th>
<th>Name</th>
<th>Email</th>
</tr>

<?php
$sql = "SELECT id, firstname, lastname, email FROM mdl_user";
$result = $conn->query($sql);

while($row = $result->fetch_assoc()) {
    echo "<tr>
    <td>".$row["id"]."</td>
    <td>".$row["firstname"]." ".$row["lastname"]."</td>
    <td>".$row["email"]."</td>
    </tr>";
}
?>

</table>

<!-- COURSE LIST -->
<h2 style="margin-top:30px;">Course List</h2>

<?php
$sql2 = "SELECT fullname FROM mdl_course";
$result2 = $conn->query($sql2);

while($row = $result2->fetch_assoc()) {
    echo "<div class='course-box'>📘 ".$row["fullname"]."</div>";
}

$conn->close();
?>

</div>