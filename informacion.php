<?php
$nombre = $_ POST['nombre']; 
$edad = $_POST[ ' edad ' ];
$direccion = $_POST [' direccion' ]; 
$salario = $_POST [' salario ' ]; 
$correo = $_POST['correo'];
$cargo = $_POST['cargo'];


echo "<h2> informacion recibida</h2>"; 
echo " el empleado $nombre tiene $edad años.<br>"; 
echo "vive en $direccion y su salario es: $salario."; 
echo "Su correo electrónico es: $correo y su cargo es: $cargo.";

?> 
