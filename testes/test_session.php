<?php
session_start();
if (!isset($_SESSION['teste'])) {
    $_SESSION['teste'] = uniqid();
}
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Valor salvo: " . $_SESSION['teste'] . "</p>";
?>
