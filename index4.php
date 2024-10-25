<?php
// Conectar a la base de datos
$conexion = new mysqli("localhost", "root", "root", "sakila");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Definir las fechas de inicio y fin
$fecha_inicio = '2005-01-01'; // Cambia esto según lo necesites
$fecha_fin = '2024-01-31';    // Cambia esto según lo necesites

// Ejecutar la consulta SQL
$query = "
    SELECT 
        f.film_id AS codigo_filme,
        f.title AS nombre_filme,
        COUNT(r.rental_id) AS veces_alquilada,
        SUM(p.amount) AS total_generado
    FROM rental r
    INNER JOIN inventory i ON r.inventory_id = i.inventory_id
    INNER JOIN film f ON i.film_id = f.film_id
    INNER JOIN payment p ON r.rental_id = p.rental_id
    WHERE r.rental_date BETWEEN '$fecha_inicio' AND '$fecha_fin'
    GROUP BY f.film_id, f.title
    ORDER BY veces_alquilada DESC
    LIMIT 10;
";

$result = $conexion->query($query); // Cambié mysqli_query() a $conexion->query()

// Crear un objeto XML
$xml = new DOMDocument('1.0', 'UTF-8');
$xml->formatOutput = true;

// Crear el nodo raíz
$root = $xml->createElement('FilmesSolicitados');
$xml->appendChild($root);

$total_general = 0;

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Crear el nodo para cada filme
        $filme = $xml->createElement('Filme');

        // Crear nodos para los datos del filme
        $codigo = $xml->createElement('Codigo', $row['codigo_filme']);
        $nombre = $xml->createElement('Nombre', $row['nombre_filme']);
        $veces = $xml->createElement('VecesAlquilada', $row['veces_alquilada']);
        $total = $xml->createElement('TotalGenerado', $row['total_generado']);

        // Añadir los nodos al filme
        $filme->appendChild($codigo);
        $filme->appendChild($nombre);
        $filme->appendChild($veces);
        $filme->appendChild($total);

        // Añadir el filme al nodo raíz
        $root->appendChild($filme);

        // Sumar al total general
        $total_general += $row['total_generado'];
    }
}

// Añadir el gran total generado
$gran_total = $xml->createElement('GranTotalGenerado', $total_general);
$root->appendChild($gran_total);

// Añadir la instrucción de procesamiento para XSL
$xml->insertBefore($xml->createProcessingInstruction('xml-stylesheet', 'type="text/xsl" href="filmes_solicitados.xsl"'), $xml->documentElement);

// Guardar el archivo XML
$xml->save('filmes_solicitados.xml');

// Cerrar la conexión a la base de datos
$conexion->close(); // Cambié mysqli_close() a $conexion->close()

// Mostrar mensaje y enlace para visualizar el XML
echo "XML generado exitosamente<br>";
echo '<a href="filmes_solicitados.xml" target="_blank">Ver XML generado</a>';
?>
