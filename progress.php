<?php
session_start();
echo json_encode(["progress" => $_SESSION['progress'] ?? 0]);
