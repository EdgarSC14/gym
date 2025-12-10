<?php
echo "<h1>Prueba de Endpoints</h1>";

// Probar get_comments.php
$url = "get_comments.php?product_id=1";
$response = file_get_contents($url);
echo "<p>Respuesta de get_comments.php:</p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

// Verificar JSON
$json = json_decode($response, true);
if ($json === null) {
    echo "<p style='color: red;'>Error JSON: " . json_last_error_msg() . "</p>";
} else {
    echo "<p style='color: green;'>JSON válido</p>";
}
?> 