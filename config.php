<?php

$conn = mysqli_connect("localhost", "root", "", "userdb");

if (!$conn)
{
    die("Database Connection Failed: " . mysqli_connect_error());
}

?>