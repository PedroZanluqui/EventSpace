<?php
$conn = new mysqli("localhost", "root", "", "eventspace");
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
